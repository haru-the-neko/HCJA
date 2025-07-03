<?php
session_start();

$is_logged_in = isset($_SESSION['username']);
$username = $is_logged_in ? htmlspecialchars($_SESSION['username']) : '';
$user_details = [];

$conn = new mysqli("localhost", "root", "", "hc+ja");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($is_logged_in) {
    $user_query = "SELECT * FROM accounts WHERE username = ?";
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bind_param("s", $username);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    
    if ($user_result->num_rows > 0) {
        $user_row = $user_result->fetch_assoc();
        $user_details = [
            'full_name' => $user_row['full_name'],
            'gender' => $user_row['gender'],
            'dob' => $user_row['dob'],
            'phone' => $user_row['phone'],
            'email' => $user_row['email'],
            'street' => $user_row['street'],
            'city' => $user_row['city'],
            'state' => $user_row['state'],
            'zip' => $user_row['zip'],
            'country' => $user_row['country'],
            'username' => $user_row['username'],
            'user_id' => $user_row['user_id']
        ];
        
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['user_id'] = $user_row['user_id'];
        }
    }
    $user_stmt->close();
}

$user_id = $_SESSION['user_id'] ?? null;
$session_id = session_id();

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    if ($_POST["action"] === "update_quantity") {
        $cart_id = (int)$_POST["cart_id"];
        $new_quantity = max(1, (int)$_POST["quantity"]);
        
        if ($user_id) {
            $update = $conn->prepare("UPDATE cart SET cart_quantity = ? WHERE cart_id = ? AND user_id = ?");
            $update->bind_param("iii", $new_quantity, $cart_id, $user_id);
        } else {
            $update = $conn->prepare("UPDATE cart SET cart_quantity = ? WHERE cart_id = ? AND session_id = ?");
            $update->bind_param("iis", $new_quantity, $cart_id, $session_id);
        }
        $update->execute();
        $update->close();
        
        if (isset($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit();
        }
    }
    
    if ($_POST["action"] === "remove_item") {
        $cart_id = (int)$_POST["cart_id"];
        
        if ($user_id) {
            $delete = $conn->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
            $delete->bind_param("ii", $cart_id, $user_id);
        } else {
            $delete = $conn->prepare("DELETE FROM cart WHERE cart_id = ? AND session_id = ?");
            $delete->bind_param("is", $cart_id, $session_id);
        }
        $delete->execute();
        $delete->close();
        
        if (isset($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit();
        }
    }

    header("Location: cart.php");
    exit();
}

$cart_query = "
    SELECT 
        c.cart_id as cart_id,
        c.cart_quantity,
        p.product_id,
        p.product_name,
        p.product_price,
        p.image,
        p.rating
    FROM cart c 
    JOIN products p ON c.product_id = p.product_id 
    WHERE " . ($user_id ? "c.user_id = ?" : "c.session_id = ?") . "
    ORDER BY c.cart_id DESC
";

$stmt = $conn->prepare($cart_query);
if ($user_id) {
    $stmt->bind_param("i", $user_id);
} else {
    $stmt->bind_param("s", $session_id);
}
$stmt->execute();
$cart_items = $stmt->get_result();

$subtotal = 0;
$total_items = 0;

if ($cart_items->num_rows > 0) {
    $items_array = [];
    while ($row = $cart_items->fetch_assoc()) {
        $items_array[] = $row;
        $subtotal += $row['product_price'] * $row['cart_quantity'];
        $total_items += $row['cart_quantity'];
    }
    $cart_items->data_seek(0);
}

$tax = $subtotal * 0.08;
$total = $subtotal + $tax;

function generateStarRating($rating) {
    $stars = '';
    $ratingOutOf5 = $rating / 20;
    $fullStars = floor($ratingOutOf5);
    $halfStar = ($ratingOutOf5 - $fullStars) >= 0.5;
    $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
    
    for ($i = 0; $i < $fullStars; $i++) {
        $stars .= '★';
    }
    
    if ($halfStar) {
        $stars .= '★';
    }
    
    for ($i = 0; $i < $emptyStars; $i++) {
        $stars .= '☆';
    }
    
    return $stars;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - HC + JA</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: #1a1a1a;
            background-color: #ffffff;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        .promo-banner {
            background: #1a1a1a;
            color: white;
            text-align: center;
            padding: 15px 0;
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .promo-highlight {
            color: #f0f0f0;
            font-weight: 700;
        }

        .header {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .navbar {
            padding: 20px 0;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 24px;
            color: #1a1a1a !important;
            text-decoration: none;
            letter-spacing: -0.5px;
        }

        .logo-placeholder {
            width: 32px;
            height: 32px;
            background: #1a1a1a;
            margin-right: 12px;
            display: inline-block;
            vertical-align: middle;
        }

        .navbar-nav .nav-link {
            color: #1a1a1a;
            font-weight: 400;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0 20px;
            transition: all 0.3s ease;
            position: relative;
        }

        .navbar-nav .nav-link:hover {
            color: #666;
        }

        .cart-icon {
            position: relative;
            text-decoration: none;
            color: #1a1a1a;
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #1a1a1a;
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 50%;
            min-width: 18px;
            text-align: center;
        }

        .user-welcome {
            background: linear-gradient(135deg, #1a1a1a, #333);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            text-transform: none;
            letter-spacing: 0;
            margin: 0 10px;
            display: inline-block;
        }

        .main-content {
            flex: 1;
            padding-top: 140px;
            padding-bottom: 80px;
            background: #fafafa;
        }

        .page-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .page-title {
            font-size: 36px;
            font-weight: 300;
            letter-spacing: -1px;
            color: #1a1a1a;
            margin-bottom: 20px;
        }

        .page-subtitle {
            font-size: 16px;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
            letter-spacing: 0.5px;
        }

        .cart-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 40px;
            margin-bottom: 60px;
        }

        .cart-items {
            background: white;
            border: 1px solid #f0f0f0;
        }

        .cart-header {
            padding: 30px;
            border-bottom: 1px solid #f0f0f0;
            background: #fafafa;
        }

        .cart-header h3 {
            font-size: 18px;
            font-weight: 500;
            color: #1a1a1a;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }

        .cart-item {
            padding: 30px;
            border-bottom: 1px solid #f0f0f0;
            display: grid;
            grid-template-columns: 120px 1fr auto;
            gap: 25px;
            align-items: center;
            transition: all 0.3s ease;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-item:hover {
            background: #fafafa;
        }

        .item-image {
            width: 120px;
            height: 120px;
            background: #f8f8f8;
            overflow: hidden;
            position: relative;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-details {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .item-name {
            font-size: 16px;
            font-weight: 400;
            color: #1a1a1a;
            letter-spacing: 0.5px;
            margin: 0;
        }

        .item-price {
            font-size: 14px;
            color: #666;
            margin: 0;
        }

        .item-rating {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .star-rating {
            display: flex;
            gap: 2px;
            font-size: 12px;
        }

        .star.filled {
            color: #d4004b;
        }

        .star.empty {
            color: #ddd;
        }

        .rating-text {
            font-size: 11px;
            color: #666;
            font-weight: 500;
        }

        .item-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 15px;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            border: 1px solid #e0e0e0;
            background: white;
        }

        .qty-btn {
            background: transparent;
            border: none;
            padding: 8px 12px;
            font-size: 14px;
            color: #1a1a1a;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qty-btn:hover {
            background: #f8f8f8;
        }

        .qty-input {
            border: none;
            padding: 8px 12px;
            width: 50px;
            text-align: center;
            font-size: 14px;
            color: #1a1a1a;
            background: transparent;
        }

        .qty-input:focus {
            outline: none;
        }

        .item-total {
            font-size: 16px;
            font-weight: 500;
            color: #1a1a1a;
        }

        .remove-btn {
            background: transparent;
            border: none;
            color: #999;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 5px 0;
        }

        .remove-btn:hover {
            color: #dc3545;
        }

        .cart-summary {
            background: white;
            border: 1px solid #f0f0f0;
            height: fit-content;
        }

        .summary-header {
            padding: 30px;
            border-bottom: 1px solid #f0f0f0;
            background: #fafafa;
        }

        .summary-header h3 {
            font-size: 18px;
            font-weight: 500;
            color: #1a1a1a;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }

        .summary-content {
            padding: 30px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .summary-row:last-of-type {
            margin-bottom: 25px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
            font-size: 16px;
            font-weight: 500;
        }

        .summary-label {
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .summary-value {
            color: #1a1a1a;
            font-weight: 500;
        }

        .checkout-btn {
            width: 100%;
            background: #1a1a1a;
            color: white;
            border: none;
            padding: 15px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }

        .checkout-btn:hover {
            background: #333;
        }

        .continue-shopping {
            width: 100%;
            background: transparent;
            color: #1a1a1a;
            border: 1px solid #e0e0e0;
            padding: 15px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
            text-align: center;
        }

        .continue-shopping:hover {
            border-color: #1a1a1a;
            color: #1a1a1a;
            text-decoration: none;
        }

        .empty-cart {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border: 1px solid #f0f0f0;
            grid-column: 1 / -1;
        }

        .empty-cart-icon {
            font-size: 48px;
            color: #e0e0e0;
            margin-bottom: 20px;
        }

        .empty-cart h3 {
            font-size: 24px;
            font-weight: 300;
            color: #1a1a1a;
            margin-bottom: 15px;
            letter-spacing: -0.5px;
        }

        .empty-cart p {
            color: #666;
            margin-bottom: 30px;
        }

        .footer {
            background: #1a1a1a;
            color: #999;
            text-align: center;
            padding: 40px 0;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .loading {
            opacity: 0.5;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                padding-top: 120px;
            }

            .cart-container {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .cart-item {
                grid-template-columns: 80px 1fr;
                gap: 15px;
            }

            .item-image {
                width: 80px;
                height: 80px;
            }

            .item-actions {
                grid-column: 1 / -1;
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                margin-top: 15px;
            }

            .page-title {
                font-size: 28px;
            }

            .navbar-nav .nav-link {
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
    <!-- Promotion Banner -->
    <div class="promo-banner">
        Mid-year Discount on all orders <span class="promo-highlight">20% off</span> - Limited-time Offer. Discount Applied at Checkout.
    </div>

    <!-- Header -->
    <header class="header">
        <nav class="navbar navbar-expand-lg">
            <div class="container">
                <a class="navbar-brand" href="index.php">
                    <div class="logo-placeholder"></div>
                    HC+JA
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="about.php">About Us</a></li>
                        <li class="nav-item"><a class="nav-link" href="products.php">What We Offer</a></li>
                        <?php if ($is_logged_in): ?>
                            <li class="nav-item"><a class="nav-link" href="account.php">Account</a></li>
                            <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                            <li class="nav-item">
                                <span class="user-welcome">Welcome, <?php echo htmlspecialchars($user_details['full_name'] ?? $username); ?></span>
                            </li>
                        <?php else: ?>
                            <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                            <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="cart-icon nav-link" href="cart.php">
                                Cart
                                <span class="cart-count"><?php echo $total_items; ?></span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">Shopping Cart</h1>
                <p class="page-subtitle">Review your selected items and proceed to checkout</p>
            </div>

            <?php if ($cart_items->num_rows > 0): ?>
                <!-- Cart Content -->
                <div class="cart-container">
                    <!-- Cart Items -->
                    <div class="cart-items">
                        <div class="cart-header">
                            <h3>Your Items (<?php echo $total_items; ?>)</h3>
                        </div>
                        
                        <?php while ($item = $cart_items->fetch_assoc()): ?>
                            <div class="cart-item" data-cart-id="<?php echo $item['cart_id']; ?>">
                                <div class="item-image">
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="img/<?php echo htmlspecialchars($item['image']); ?>.jpg" 
                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                             onerror="this.style.display='none'; this.parentElement.style.background='linear-gradient(135deg, #667eea 0%, #764ba2 100%)';">
                                    <?php else: ?>
                                        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 100%; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px;">
                                            <?php echo strtoupper(substr($item['product_name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="item-details">
                                    <h4 class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                    <p class="item-price">₱<?php echo number_format($item['product_price'], 2); ?></p>
                                    
                                    <?php if (isset($item['rating']) && $item['rating'] > 0): ?>
                                        <div class="item-rating">
                                            <div class="star-rating">
                                                <?php echo generateStarRating($item['rating']); ?>
                                            </div>
                                            <span class="rating-text"><?php echo number_format($item['rating'], 1); ?>/5</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="item-actions">
                                    <div class="quantity-control">
                                        <button class="qty-btn" onclick="updateQuantity(<?php echo $item['cart_id']; ?>, -1, <?php echo $item['cart_quantity']; ?>)">-</button>
                                        <input type="number" class="qty-input" value="<?php echo $item['cart_quantity']; ?>" min="1" 
                                               onchange="updateQuantity(<?php echo $item['cart_id']; ?>, 0, this.value)">
                                        <button class="qty-btn" onclick="updateQuantity(<?php echo $item['cart_id']; ?>, 1, <?php echo $item['cart_quantity']; ?>)">+</button>
                                    </div>
                                    <div class="item-total">₱<?php echo number_format($item['product_price'] * $item['cart_quantity'], 2); ?></div>
                                    <button class="remove-btn" onclick="removeItem(<?php echo $item['cart_id']; ?>)">Remove</button>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <!-- Cart Summary -->
                    <div class="cart-summary">
                        <div class="summary-header">
                            <h3>Order Summary</h3>
                        </div>
                        <div class="summary-content">
                            <div class="summary-row">
                                <span class="summary-label">Subtotal</span>
                                <span class="summary-value" id="subtotal">₱<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <div class="summary-row">
                                <span class="summary-label">Shipping</span>
                                <span class="summary-value"><?php echo $subtotal >= 2000 ? 'Free' : '₱150.00'; ?></span>
                            </div>
                            <div class="summary-row">
                                <span class="summary-label">Tax</span>
                                <span class="summary-value" id="tax">₱<?php echo number_format($tax, 2); ?></span>
                            </div>
                            <div class="summary-row">
                                <span class="summary-label">Total</span>
                                <span class="summary-value" id="total">₱<?php echo number_format($total + ($subtotal >= 2000 ? 0 : 150), 2); ?></span>
                            </div>
                           <form action="checkout.php" method="POST">
                                <button type="submit" class="checkout-btn">
                                    Proceed to Checkout
                                </button>
                            </form>
                            <a href="products.php" class="continue-shopping">Continue Shopping</a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Empty Cart -->
                <div class="empty-cart">
                    <div class="empty-cart-icon">
                        
                    </div>
                    <h3>Your cart is empty</h3>
                    <p>Add some items to your cart to get started</p>
                    <a href="products.php" class="checkout-btn" style="display: inline-block; text-decoration: none; max-width: 200px; margin-top: 20px;">
                        Start Shopping
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 HC+JA. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateQuantity(cartId, change, currentQty, newValue = null) {
            let quantity;
            
            if (newValue !== null) {
                quantity = parseInt(newValue) || 1;
            } else {
                quantity = parseInt(currentQty) + change;
                if (quantity < 1) quantity = 1;
            }

            const cartItem = document.querySelector(`[data-cart-id="${cartId}"]`);
            cartItem.classList.add('loading');

            const formData = new FormData();
            formData.append('action', 'update_quantity');
            formData.append('cart_id', cartId);
            formData.append('quantity', quantity);
            formData.append('ajax', '1');

            fetch('cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    cartItem.classList.remove('loading');
                    alert('Error updating quantity. Please try again.');
                }
            })
            .catch(error => {
                cartItem.classList.remove('loading');
                console.error('Error:', error);
                alert('Network error. Please try again.');
            });
        }

        function removeItem(cartId) {
            if (!confirm('Are you sure you want to remove this item from your cart?')) {
                return;
            }

            const cartItem = document.querySelector(`[data-cart-id="${cartId}"]`);
            cartItem.classList.add('loading');

            const formData = new FormData();
            formData.append('action', 'remove_item');
            formData.append('cart_id', cartId);
            formData.append('ajax', '1');

            fetch('cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    cartItem.classList.remove('loading');
                    alert('Error removing item. Please try again.');
                }
            })
            .catch(error => {
                cartItem.classList.remove('loading');
                console.error('Error:', error);
                alert('Network error. Please try again.');
            });
        }

        function proceedToCheckout() {
            <?php if (!$is_logged_in): ?>
                if (confirm('You need to be logged in to checkout. Would you like to login now?')) {
                window.location.href = 'login.php?redirect=cart.php';
            }
            <?php else: ?>
            window.location.href = 'checkout.php';
            <?php endif; ?>
        }

        let quantityTimeout;
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('qty-input')) {
                const cartId = e.target.closest('[data-cart-id]').dataset.cartId;
                clearTimeout(quantityTimeout);
                quantityTimeout = setTimeout(() => {
                    updateQuantity(parseInt(cartId), 0, 0, e.target.value);
                }, 500); 
            }
        });

        window.addEventListener('scroll', function() {
            const header = document.querySelector('.header');
            if (window.scrollY > 100) {
                header.style.background = 'rgba(255, 255, 255, 0.98)';
                header.style.boxShadow = '0 2px 20px rgba(0,0,0,0.1)';
            } else {
                header.style.background = 'rgba(255, 255, 255, 0.95)';
                header.style.boxShadow = 'none';
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const cartItems = document.querySelectorAll('.cart-item');
            cartItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    item.style.transition = 'all 0.6s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });

        document.querySelectorAll('.qty-btn').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                this.style.background = '#f8f8f8';
                this.style.transform = 'scale(1.1)';
            });
            
            btn.addEventListener('mouseleave', function() {
                this.style.background = 'transparent';
                this.style.transform = 'scale(1)';
            });
        });

        document.querySelectorAll('.remove-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const cartId = this.closest('[data-cart-id]').dataset.cartId;
                
                const item = this.closest('.cart-item');
                item.style.animation = 'shake 0.5s ease-in-out';
                
                setTimeout(() => {
                    removeItem(parseInt(cartId));
                }, 250);
            });
        });

        const style = document.createElement('style');
        style.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
            
            @keyframes fadeOut {
                from { opacity: 1; transform: scale(1); }
                to { opacity: 0; transform: scale(0.8); }
            }
            
            .removing {
                animation: fadeOut 0.3s ease-out forwards;
            }
        `;
        document.head.appendChild(style);

        function previewTotalUpdate(cartId, newQuantity) {
            const cartItem = document.querySelector(`[data-cart-id="${cartId}"]`);
            const priceElement = cartItem.querySelector('.item-price');
            const totalElement = cartItem.querySelector('.item-total');
            
            if (priceElement && totalElement) {
                const price = parseFloat(priceElement.textContent.replace('₱', '').replace(',', ''));
                const newTotal = price * newQuantity;
                totalElement.textContent = `₱${newTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            }
        }
    </script>
</body>
</html>