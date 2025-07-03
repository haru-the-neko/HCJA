<?php
session_start();

$is_logged_in = isset($_SESSION['username']);
if (!$is_logged_in) {
    header("Location: login.php?redirect=checkout.php");
    exit();
}

$username = htmlspecialchars($_SESSION['username']);
$user_id = $_SESSION['user_id'] ?? null;

$conn = new mysqli("localhost", "root", "", "hc+ja");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_query = "SELECT * FROM accounts WHERE username = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("s", $username);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_details = $user_result->fetch_assoc();
$user_stmt->close();

$cart_query = "SELECT c.cart_id, c.cart_quantity, p.product_id, p.product_name, p.product_price, p.image 
               FROM cart c JOIN products p ON c.product_id = p.product_id WHERE c.user_id = ?";
$cart_stmt = $conn->prepare($cart_query);
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_items = $cart_stmt->get_result();

$subtotal = 0;
$total_items = 0;
$items_array = [];

while ($item = $cart_items->fetch_assoc()) {
    $items_array[] = $item;
    $subtotal += $item['product_price'] * $item['cart_quantity'];
    $total_items += $item['cart_quantity'];
}

if (empty($items_array)) {
    header("Location: cart.php");
    exit();
}

$shipping = $subtotal >= 2000 ? 0 : 150;
$tax = $subtotal * 0.08;
$total = $subtotal + $shipping + $tax;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["place_order"])) {
    $payment_method = $_POST['payment_method'];
    $shipping_address = $_POST['shipping_address'];
    $billing_address = $_POST['billing_address'];
    
    $conn->begin_transaction();
    
    try {
        $order_query = "INSERT INTO orders (user_id, total_amount, payment_method, shipping_address, billing_address, order_date, status) 
                        VALUES (?, ?, ?, ?, ?, NOW(), 'pending')";
        $order_stmt = $conn->prepare($order_query);
        $order_stmt->bind_param("idsss", $user_id, $total, $payment_method, $shipping_address, $billing_address);
        $order_stmt->execute();
        $order_id = $conn->insert_id;
        $order_stmt->close();
        
        foreach ($items_array as $item) {
            $order_items_query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            $order_items_stmt = $conn->prepare($order_items_query);
            $order_items_stmt->bind_param("iiid", $order_id, $item['product_id'], $item['cart_quantity'], $item['product_price']);
            $order_items_stmt->execute();
            $order_items_stmt->close();
        }
        
        $clear_cart = "DELETE FROM cart WHERE user_id = ?";
        $clear_stmt = $conn->prepare($clear_cart);
        $clear_stmt->bind_param("i", $user_id);
        $clear_stmt->execute();
        $clear_stmt->close();
        
        $conn->commit();
        $_SESSION['order_id'] = $order_id;
        header("Location: order_success.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Order failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - HC + JA</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; color: #1a1a1a; background: #fafafa; line-height: 1.6; }
        .promo-banner { background: #1a1a1a; color: white; text-align: center; padding: 15px 0; font-size: 14px; font-weight: 500; }
        .promo-highlight { color: #f0f0f0; font-weight: 700; }
        .header { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); padding: 20px 0; border-bottom: 1px solid #f0f0f0; }
        .navbar-brand { font-weight: 700; font-size: 24px; color: #1a1a1a !important; text-decoration: none; }
        .logo-placeholder { width: 32px; height: 32px; background: #1a1a1a; margin-right: 12px; display: inline-block; vertical-align: middle; }
        .navbar-nav .nav-link { color: #1a1a1a; font-weight: 400; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; margin: 0 20px; }
        .user-welcome { background: linear-gradient(135deg, #1a1a1a, #333); color: white; padding: 8px 16px; border-radius: 20px; font-size: 12px; }
        .main-content { padding: 60px 0; }
        .page-title { font-size: 36px; font-weight: 300; text-align: center; margin-bottom: 50px; }
        .checkout-container { display: grid; grid-template-columns: 1fr 400px; gap: 40px; }
        .checkout-form { background: white; padding: 40px; border: 1px solid #f0f0f0; }
        .form-section { margin-bottom: 40px; }
        .section-title { font-size: 18px; font-weight: 500; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px; color: #1a1a1a; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 500; color: #1a1a1a; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-control { width: 100%; padding: 12px 16px; border: 1px solid #e0e0e0; background: #fafafa; font-size: 14px; color: #1a1a1a; transition: all 0.3s ease; }
        .form-control:focus { outline: none; border-color: #1a1a1a; background: white; }
        .payment-methods { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
        .payment-option { position: relative; }
        .payment-option input { position: absolute; opacity: 0; }
        .payment-option label { display: block; padding: 20px; border: 2px solid #e0e0e0; text-align: center; cursor: pointer; transition: all 0.3s ease; font-weight: 500; text-transform: uppercase; letter-spacing: 1px; font-size: 12px; }
        .payment-option input:checked + label { border-color: #1a1a1a; background: #1a1a1a; color: white; }
        .payment-option label:hover { border-color: #1a1a1a; }
        .order-summary { background: white; padding: 40px; border: 1px solid #f0f0f0; height: fit-content; }
        .summary-title { font-size: 18px; font-weight: 500; margin-bottom: 30px; text-transform: uppercase; letter-spacing: 1px; }
        .order-item { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #f0f0f0; }
        .item-image { width: 60px; height: 60px; background: #f8f8f8; overflow: hidden; }
        .item-image img { width: 100%; height: 100%; object-fit: cover; }
        .item-details h5 { font-size: 14px; font-weight: 400; margin-bottom: 5px; }
        .item-details p { font-size: 12px; color: #666; margin: 0; }
        .item-price { font-weight: 500; color: #1a1a1a; margin-left: auto; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 14px; }
        .summary-row:last-of-type { margin-bottom: 30px; padding-top: 15px; border-top: 1px solid #f0f0f0; font-size: 16px; font-weight: 500; }
        .place-order-btn { width: 100%; background: #1a1a1a; color: white; border: none; padding: 15px; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; font-weight: 500; cursor: pointer; transition: all 0.3s ease; }
        .place-order-btn:hover { background: #333; }
        .footer { background: #1a1a1a; color: #999; text-align: center; padding: 40px 0; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
        .error { color: #dc3545; margin-bottom: 20px; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; }
        @media (max-width: 768px) {
            .checkout-container { grid-template-columns: 1fr; }
            .payment-methods { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="promo-banner">
        Mid-year Discount on all orders <span class="promo-highlight">20% off</span> - Limited-time Offer. Discount Applied at Checkout.
    </div>

    <header class="header">
        <nav class="navbar navbar-expand-lg">
            <div class="container">
                <a class="navbar-brand" href="index.php">
                    <div class="logo-placeholder"></div>
                    HC+JA
                </a>
                <div class="collapse navbar-collapse">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="about.php">About Us</a></li>
                        <li class="nav-item"><a class="nav-link" href="products.php">What We Offer</a></li>
                        <li class="nav-item"><a class="nav-link" href="account.php">Account</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                        <li class="nav-item">
                            <span class="user-welcome">Welcome, <?php echo htmlspecialchars($user_details['full_name'] ?? $username); ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main class="main-content">
        <div class="container">
            <h1 class="page-title">Checkout</h1>
            
            <?php if (isset($error_message)): ?>
                <div class="error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form method="POST" class="checkout-container">
                <div class="checkout-form">
                    <div class="form-section">
                        <h3 class="section-title">Shipping Information</h3>
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_details['full_name']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($user_details['email']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" value="<?php echo htmlspecialchars($user_details['phone']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Shipping Address</label>
                            <textarea name="shipping_address" class="form-control" rows="3" required><?php echo htmlspecialchars($user_details['street'] . ', ' . $user_details['city'] . ', ' . $user_details['state'] . ' ' . $user_details['zip'] . ', ' . $user_details['country']); ?></textarea>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title">Billing Information</h3>
                        <div class="form-group">
                            <label class="form-label">Billing Address</label>
                            <textarea name="billing_address" class="form-control" rows="3" required><?php echo htmlspecialchars($user_details['street'] . ', ' . $user_details['city'] . ', ' . $user_details['state'] . ' ' . $user_details['zip'] . ', ' . $user_details['country']); ?></textarea>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title">Payment Method</h3>
                        <div class="payment-methods">
                            <div class="payment-option">
                                <input type="radio" name="payment_method" id="cod" value="cod" required>
                                <label for="cod">Cash on Delivery</label>
                            </div>
                            <div class="payment-option">
                                <input type="radio" name="payment_method" id="gcash" value="gcash" required>
                                <label for="gcash">GCash</label>
                            </div>
                            <div class="payment-option">
                                <input type="radio" name="payment_method" id="card" value="card" required>
                                <label for="card">Credit/Debit Card</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="order-summary">
                    <h3 class="summary-title">Order Summary</h3>
                    
                    <?php foreach ($items_array as $item): ?>
                    <div class="order-item">
                        <div class="item-image">
                            <?php if (!empty($item['image'])): ?>
                                <img src="img/<?php echo htmlspecialchars($item['image']); ?>.jpg" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                            <?php else: ?>
                                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 100%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                    <?php echo strtoupper(substr($item['product_name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="item-details">
                            <h5><?php echo htmlspecialchars($item['product_name']); ?></h5>
                            <p>Qty: <?php echo $item['cart_quantity']; ?></p>
                        </div>
                        <div class="item-price">₱<?php echo number_format($item['product_price'] * $item['cart_quantity'], 2); ?></div>
                    </div>
                    <?php endforeach; ?>

                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>₱<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span><?php echo $shipping > 0 ? '₱' . number_format($shipping, 2) : 'Free'; ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Tax</span>
                        <span>₱<?php echo number_format($tax, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Total</span>
                        <span>₱<?php echo number_format($total, 2); ?></span>
                    </div>
                    
                    <button type="submit" name="place_order" class="place-order-btn">
                        Place Order
                    </button>
                </div>
            </form>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 HC+JA. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>