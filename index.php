<?php
session_start();

$is_logged_in = isset($_SESSION['username']);
$username = $is_logged_in ? htmlspecialchars($_SESSION['username']) : '';
$user_details = [];

if ($is_logged_in) {
    $users_file = 'users.txt';
    if (file_exists($users_file)) {
        $users = file($users_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($users as $user_data_line) {
            $data = explode("|", $user_data_line);
            
            if (count($data) >= 11 && $data[10] === $username) {
                $user_details = [
                    'full_name' => $data[0],
                    'gender' => $data[1],
                    'dob' => $data[2],
                    'phone' => $data[3],
                    'email' => $data[4],
                    'street' => $data[5],
                    'city' => $data[6],
                    'state' => $data[7],
                    'zip' => $data[8],
                    'country' => $data[9],
                    'username' => $data[10]
                ];
                break; 
            }
        }
    }
}

$conn = new mysqli("localhost", "root", "", "hc+ja");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'] ?? null;
$session_id = session_id();

if ($user_id) {
    $stmt = $conn->prepare("SELECT SUM(cart_quantity) AS total FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
} else {
    $stmt = $conn->prepare("SELECT SUM(cart_quantity) AS total FROM cart WHERE session_id = ?");
    $stmt->bind_param("s", $session_id);
}
$stmt->execute();
$cart_count = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>MyWebsite<?php echo $is_logged_in ? ' - Welcome ' . ($user_details['full_name'] ?? $username) : ''; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
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
            overflow-x: hidden;
        }

        /* Header */
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

        /* Hero Section */
        .hero {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('asap.jpg');
            background-size: cover;
            background-position: center;
            color: white;
        }

        .hero-content h1 {
            font-size: 4rem;
            font-weight: 300;
            margin-bottom: 1rem;
            letter-spacing: -2px;
        }

        .hero-content p {
            font-size: 1.2rem;
            font-weight: 300;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .hero-personalized {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-primary {
            background: transparent;
            border: 1px solid white;
            color: white;
            padding: 12px 30px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: white;
            color: #1a1a1a;
            border-color: white;
        }

        /* Promotion Banner */
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

        /* Collection Sections */
        .section {
            padding: 100px 0;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 300;
            text-align: center;
            margin-bottom: 60px;
            letter-spacing: -1px;
        }

        .collection-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2px;
            margin-bottom: 60px;
        }

        .collection-item {
            position: relative;
            overflow: hidden;
            aspect-ratio: 1;
            background: #f5f5f5;
            cursor: pointer;
            transition: all 0.5s ease;
        }

        .collection-item:hover {
            transform: scale(1.02);
        }

        .collection-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all 0.5s ease;
        }

        .collection-item:hover img {
            transform: scale(1.1);
        }

        .collection-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            color: white;
            padding: 40px 30px 30px;
            transform: translateY(100%);
            transition: all 0.5s ease;
        }

        .collection-item:hover .collection-overlay {
            transform: translateY(0);
        }

        .image-placeholder {
            width: 100%;
            height: 100%;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Featured Products */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
        }

        .product-card {
            text-align: center;
            transition: all 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-image {
            aspect-ratio: 1;
            background: #f5f5f5;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .product-title {
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 10px;
            color: #1a1a1a;
        }

        .product-price {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }

        .product-btn {
            background: transparent;
            border: 1px solid #1a1a1a;
            color: #1a1a1a;
            padding: 8px 20px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }

        .product-btn:hover {
            background: #1a1a1a;
            color: white;
        }

        /* Brand Story */
        .brand-story {
            background: #f8f8f8;
            text-align: center;
        }

        .brand-story h2 {
            font-size: 2rem;
            font-weight: 300;
            margin-bottom: 30px;
            letter-spacing: -1px;
        }

        .brand-story p {
            font-size: 16px;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.8;
        }

        /* Footer */
        .footer {
            background: #1a1a1a;
            color: #999;
            text-align: center;
            padding: 40px 0;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2.5rem;
            }
            
            .navbar-nav .nav-link {
                margin: 10px 0;
            }
            
            .collection-grid {
                grid-template-columns: 1fr;
            }
            
            .product-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 30px;
            }
            
            .section {
                padding: 60px 0;
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
                                <span class="cart-count"><?php echo $cart_count; ?></span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">          
            <h1>Elevated Experiences</h1>
            <p>Discover premium quality and exceptional design</p>
            <a href="products.php" class="btn btn-primary">Explore Collection</a>
        </div>
    </section>

    <!-- New Arrivals -->
    <section class="section">
        <div class="container">
            <h2 class="section-title">NEW ARRIVALS</h2>
            <div class="collection-grid">
                <div class="collection-item">
                    <div class="image-placeholder"><img src="img/rocky.jpg"></div>
                    <div class="collection-overlay">
                        <h3>Premium Collection</h3>
                        <p>Discover our latest premium items crafted with attention to detail</p>
                        <a href="products.php" class="btn btn-primary mt-3">Shop Now</a>
                    </div>
                </div>
                <div class="collection-item">
                    <div class="image-placeholder"><img src="img/jadee.jpg"></div>
                    <div class="collection-overlay">
                        <h3>Limited Edition</h3>
                        <p>Exclusive pieces available for a limited time only</p>
                        <a href="products.php" class="btn btn-primary mt-3">View Details</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
<section class="section">
    <div class="container">
        <h2 class="section-title">FEATURED PRODUCTS</h2>
        <div class="product-grid">
            <div class="product-card">
                <div class="product-image" style="width: 100%; height: 100%; overflow: hidden;">
                    <div class="image-placeholder">
                        <img src="img/SHERPA.jpg" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                </div>
                <h3 class="product-title">Sherpa Shawl Collar Jacket</h3>
                <p class="product-price"></p>
                <a href="products.php" class="product-btn" style="text-decoration: none;">Shop Now</a>
            </div>
            <div class="product-card">
                <div class="product-image" style="width: 100%; height: 100%; overflow: hidden;">
                    <div class="image-placeholder">
                        <img src="img/CEN.jpg" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                </div>
                <h3 class="product-title">Crocodile Embossed Leather Jacket</h3>
                <p class="product-price"></p>
                <a href="products.php" class="product-btn" style="text-decoration: none;">Shop Now</a>
            </div>
            <div class="product-card">
                <div class="product-image" style="width: 100%; height: 100%; overflow: hidden;">
                    <div class="image-placeholder">
                        <img src="img/espresso.jpg" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                </div>
                <h3 class="product-title">Leather Rider Jacket</h3>
                <p class="product-price"></p>
                <a href="products.php" class="product-btn" style="text-decoration: none;">Shop Now</a>
            </div>
        </div>
    </div>
</section>

    <!-- Brand Story -->
    <section class="section brand-story">
        <div class="container">
            <h2>ELEVATED ARTISTRY</h2>
            <p>Sustainable, ethical production drives everything we do. We work with premium materials to shape our original pieces. Each item is crafted by hand, honoring traditional craftsmanship while ensuring world-class quality. These practices not only elevate our artistry—they also reduce our impact across the value chain.</p>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 HC+JA. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>