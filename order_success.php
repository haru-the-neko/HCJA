<?php
session_start();

$is_logged_in = isset($_SESSION['username']);
if (!$is_logged_in) {
    header("Location: login.php");
    exit();
}

$username = htmlspecialchars($_SESSION['username']);
$user_id = $_SESSION['user_id'] ?? null;

$conn = new mysqli("localhost", "root", "", "hc+ja");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_query = "SELECT full_name FROM accounts WHERE username = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("s", $username);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_details = $user_result->fetch_assoc();
$user_stmt->close();
$conn->close();

$order_number = 'HC' . date('Y') . rand(1000, 9999);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Complete - HC + JA</title>
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
        .main-content { padding: 80px 0; min-height: 70vh; display: flex; align-items: center; }
        .success-container { text-align: center; max-width: 600px; margin: 0 auto; }
        .success-icon { width: 80px; height: 80px; background: #1a1a1a; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 40px; }
        .success-icon::after { content: "✓"; color: white; font-size: 36px; font-weight: bold; }
        .success-title { font-size: 48px; font-weight: 300; margin-bottom: 20px; }
        .success-subtitle { font-size: 18px; color: #666; margin-bottom: 30px; }
        .order-details { background: white; padding: 40px; border: 1px solid #f0f0f0; margin: 40px 0; text-align: left; }
        .detail-row { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 14px; }
        .detail-row:last-child { margin-bottom: 0; font-weight: 500; }
        .action-buttons { display: flex; gap: 20px; justify-content: center; margin-top: 40px; }
        .btn-primary { background: #1a1a1a; color: white; border: none; padding: 15px 30px; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; font-weight: 500; text-decoration: none; transition: all 0.3s ease; }
        .btn-primary:hover { background: #333; color: white; }
        .btn-secondary { background: transparent; color: #1a1a1a; border: 2px solid #1a1a1a; padding: 13px 30px; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; font-weight: 500; text-decoration: none; transition: all 0.3s ease; }
        .btn-secondary:hover { background: #1a1a1a; color: white; }
        .footer { background: #1a1a1a; color: #999; text-align: center; padding: 40px 0; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
        @media (max-width: 768px) {
            .success-title { font-size: 36px; }
            .action-buttons { flex-direction: column; align-items: center; }
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
            <div class="success-container">
                <div class="success-icon"></div>
                <h1 class="success-title">Order Complete</h1>
                <p class="success-subtitle">Thank you for your purchase! Your order has been successfully placed.</p>
                
                <div class="order-details">
                    <div class="detail-row">
                        <span>Order Number:</span>
                        <span><?php echo $order_number; ?></span>
                    </div>
                    <div class="detail-row">
                        <span>Order Date:</span>
                        <span><?php echo date('F j, Y'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span>Status:</span>
                        <span>Processing</span>
                    </div>
                    <div class="detail-row">
                        <span>Estimated Delivery:</span>
                        <span><?php echo date('F j, Y', strtotime('+3 days')); ?></span>
                    </div>
                </div>

                <div class="action-buttons">
                    <a href="products.php" class="btn-primary">Continue Shopping</a>
                    
                </div>
            </div>
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