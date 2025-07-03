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

// Handle Add to Cart
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["product_id"])) {
    $product_id = (int)$_POST["product_id"];
    $qty = max(1, (int)$_POST["qty"]);
    
    $check = $conn->prepare("SELECT cart_id FROM cart WHERE (user_id = ? OR session_id = ?) AND product_id = ?");
    $check->bind_param("isi", $user_id, $session_id, $product_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $update = $conn->prepare("UPDATE cart SET cart_quantity = cart_quantity + ? WHERE (user_id = ? OR session_id = ?) AND product_id = ?");
        $update->bind_param("iisi", $qty, $user_id, $session_id, $product_id);
        $update->execute();
        $update->close();
    } else {
        $insert = $conn->prepare("INSERT INTO cart (user_id, session_id, product_id, cart_quantity) VALUES (?, ?, ?, ?)");
        $insert->bind_param("isii", $user_id, $session_id, $product_id, $qty);
        $insert->execute();
        $insert->close();
    }
    
    $check->close();
    
    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF'] . (isset($_GET['sort']) ? '?sort=' . $_GET['sort'] : ''));
    exit();
}

// Get cart count
// Get cart count
if ($user_id) {
    $stmt = $conn->prepare("SELECT SUM(cart_quantity) AS total FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
} else {
    $stmt = $conn->prepare("SELECT SUM(cart_quantity) AS total FROM cart WHERE session_id = ?");
    $stmt->bind_param("s", $session_id);
}
$stmt->execute();
$cart_count = $stmt->get_result()->fetch_assoc()['total'] ?? 0;




// Handle sorting
$order = "ORDER BY product_price ASC"; 
$sort_param = $_GET['sort'] ?? 'cheapest';
switch ($sort_param) {
    case 'cheapest': 
        $order = "ORDER BY product_price ASC"; 
        break;
    case 'expensive': 
        $order = "ORDER BY product_price DESC"; 
        break;
    case 'recommended': 
        $order = "ORDER BY rating DESC"; 
        break;
    case 'newest': 
        $order = "ORDER BY product_id DESC"; 
        break;
    case 'popular': 
        $order = "ORDER BY RAND()"; 
        break;
    default: 
        $order = "ORDER BY product_price ASC";
}

// Get products from database
$products_query = "SELECT * FROM products $order LIMIT 20";
$products = $conn->query($products_query);
$total_products = $products->num_rows;

// Function to generate star rating HTML
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
        $stars .= '★'; // Half star added
    }
    
    for ($i = 0; $i < $emptyStars; $i++) {
        $stars .= '☆';
    }
    
    return $stars;
}


// Function to generate Homura-themed product descriptions
function generateHomuraDescription($productName) {
    $descriptions = [
        'Elegant crimson essence meets timeless craftsmanship',
        'Forged with passion, tempered by flame and dedication',
        'Where fiery determination meets sophisticated design',
        'Infused with the spirit of unwavering resolve',
        'Crafted from shadows and light, bound by eternal promise',
        'Born from sacrifice, perfected through countless iterations',
        'Embodies the strength found in protecting what matters most',
        'Resonates with the power of time itself',
        'Carries the weight of destiny in every detail',
        'Manifests the beauty of determination and hope'
    ];
    
    return $descriptions[array_rand($descriptions)];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>What We Offer - HC + JA</title>
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

        .filters-section {
            background: white;
            padding: 30px 0;
            border-top: 1px solid #f0f0f0;
            border-bottom: 1px solid #f0f0f0;
            margin-bottom: 60px;
        }

        .filters-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .filter-label {
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #666;
        }

        .filter-select {
            background: transparent;
            border: none;
            border-bottom: 1px solid #e0e0e0;
            padding: 8px 0;
            font-size: 14px;
            color: #1a1a1a;
            min-width: 150px;
            cursor: pointer;
        }

        .filter-select:focus {
            outline: none;
            border-bottom-color: #1a1a1a;
        }

        .products-count {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 40px;
            margin-bottom: 80px;
        }

        .product-card {
            background: white;
            transition: all 0.3s ease;
            cursor: pointer;
            border: 1px solid transparent;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border-color: #f0f0f0;
        }

        .product-image {
            width: 100%;
            height: 300px;
            background: #f8f8f8;
            position: relative;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image img {
            transform: scale(1.05);
        }

        .product-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: #1a1a1a;
            color: white;
            padding: 5px 10px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 500;
        }

        .product-badge.new {
            background: #28a745;
        }

        .product-badge.sale {
            background: #dc3545;
        }

        .product-info {
            padding: 25px;
        }

        .product-name {
            font-size: 16px;
            font-weight: 400;
            color: #1a1a1a;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .product-rating {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }

        .star-rating {
            display: flex;
            gap: 2px;
        }

        .star {
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .star.filled {
            color: #d4004b;
            text-shadow: 0 0 3px rgba(212, 0, 75, 0.3);
        }

        .star.half {
            background: linear-gradient(90deg, #d4004b 50%, #ddd 50%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .star.empty {
            color: #ddd;
        }

        .rating-text {
            font-size: 12px;
            color: #666;
            font-weight: 500;
        }

        .product-price {
            font-size: 18px;
            font-weight: 500;
            color: #1a1a1a;
            margin-bottom: 15px;
        }

        .product-description {
            font-size: 13px;
            color: #666;
            line-height: 1.5;
            margin-bottom: 20px;
            font-style: italic;
            position: relative;
            padding-left: 15px;
        }

        .product-description::before {
            content: '"';
            position: absolute;
            left: 0;
            top: -2px;
            font-size: 18px;
            color: #d4004b;
            font-weight: bold;
        }

        .product-description::after {
            content: '"';
            font-size: 14px;
            color: #d4004b;
            font-weight: bold;
        }

        .product-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .quantity-input {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .quantity-input label {
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #666;
        }

        .quantity-input input {
            width: 60px;
            padding: 8px;
            border: 1px solid #e0e0e0;
            background: transparent;
            text-align: center;
            font-size: 14px;
        }

        .quantity-input input:focus {
            outline: none;
            border-color: #1a1a1a;
        }

        .btn-add-cart {
            background: #1a1a1a;
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-add-cart:hover {
            background: #333;
            color: white;
        }

        .bottom-nav {
            background: white;
            padding: 30px 0;
            border-top: 1px solid #f0f0f0;
            text-align: center;
        }

        .nav-links {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        .nav-links a {
            color: #1a1a1a;
            text-decoration: none;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            color: #666;
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

        .features-section {
            background: white;
            padding: 60px 0;
            border-top: 1px solid #f0f0f0;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
        }

        .feature-item {
            text-align: center;
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: #1a1a1a;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }

        .feature-title {
            font-size: 14px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #1a1a1a;
            margin-bottom: 10px;
        }

        .feature-text {
            font-size: 13px;
            color: #666;
            line-height: 1.5;
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

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                padding-top: 120px;
            }
            
            .filters-container {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group {
                justify-content: space-between;
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 30px;
            }
            
            .product-info {
                padding: 20px;
            }
            
            .navbar-nav .nav-link {
                margin: 10px 0;
            }
            
            .page-title {
                font-size: 28px;
            }

            .nav-links {
                flex-direction: column;
                gap: 15px;
            }
        }

        /* Success message */
        .success-message {
            position: fixed;
            top: 100px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 15px 20px;
            border-radius: 4px;
            font-size: 14px;
            z-index: 1001;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
        }

        .success-message.show {
            opacity: 1;
            transform: translateX(0);
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

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">What We Offer</h1>
                <p class="page-subtitle">Discover our curated collection of premium products, crafted with attention to detail and designed for the modern lifestyle.</p>
            </div>

            <!-- Filters and Sort -->
            <div class="filters-section">
                <div class="container">
                    <div class="filters-container">
                        <div class="d-flex gap-4 flex-wrap">
                            <div class="filter-group">
                                <span class="filter-label">Sort By</span>
                                <select class="filter-select" id="sortFilter" onchange="window.location.href='?sort=' + this.value">
                                    <option value="cheapest" <?php echo $sort_param === 'cheapest' ? 'selected' : ''; ?>>Price: Low to High</option>
                                    <option value="expensive" <?php echo $sort_param === 'expensive' ? 'selected' : ''; ?>>Price: High to Low</option>
                                    <option value="recommended" <?php echo $sort_param === 'recommended' ? 'selected' : ''; ?>>Most Recommended</option>
                                    <option value="newest" <?php echo $sort_param === 'newest' ? 'selected' : ''; ?>>Newest</option>
                                    <option value="popular" <?php echo $sort_param === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                                </select>
                            </div>
                        </div>
                        <div class="products-count">
                            <span><?php echo $total_products; ?> Products</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="products-grid">
                <?php if ($products && $products->num_rows > 0): ?>
                    <?php while ($row = $products->fetch_assoc()): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <?php if (isset($row['is_new']) && $row['is_new']): ?>
                                    <div class="product-badge new">New</div>
                                <?php elseif (isset($row['on_sale']) && $row['on_sale']): ?>
                                    <div class="product-badge sale">Sale</div>
                                <?php endif; ?>
                                
                                <?php if (!empty($row['image'])): ?>
                                    <img src="img/<?php echo htmlspecialchars($row['image']); ?>.jpg" 
                                         alt="<?php echo htmlspecialchars($row['product_name']); ?>" 
                                         onerror="this.style.display='none'; this.parentElement.style.background='linear-gradient(135deg, #667eea 0%, #764ba2 100%)';">
                                <?php else: ?>
                                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 100%; display: flex; align-items: center; justify-content: center; color: white; font-size: 48px;">
                                        <?php echo strtoupper(substr($row['product_name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-info">
                                <h3 class="product-name"><?php echo htmlspecialchars($row['product_name']); ?></h3>
                                
                                <!-- Product Rating -->
                                <?php if (isset($row['rating']) && $row['rating'] > 0): ?>
                                    <div class="product-rating">
                                        <div class="star-rating">
                                            <?php echo generateStarRating($row['rating']); ?>
                                        </div>
                                        <span class="rating-text"><?php echo number_format($row['rating'], 1); ?> / 5</span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="product-price">₱<?php echo number_format($row['product_price'], 2); ?></div>
                                
                                <!-- Homura-themed Description -->
                                <p class="product-description"><?php echo generateHomuraDescription($row['product_name']); ?></p>
                                
                                <form method="post" action="" class="product-form">
                                    <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                                    <div class="quantity-input">
                                        <label for="qty_<?php echo $row['product_id']; ?>">Quantity:</label>
                                        <input type="number" 
                                               id="qty_<?php echo $row['product_id']; ?>" 
                                               name="qty" 
                                               value="1" 
                                               min="1" 
                                               max="20">
                                    </div>
                                    <button type="submit" class="btn-add-cart">Add to Cart</button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="container text-center" style="grid-column: 1 / -1; padding: 60px 0;">
                        <h3>No products found</h3>
                        <p>Please check back later for new products.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Bottom Navigation -->
            <div class="bottom-nav">
                <div class="nav-links">
                    <a href="cart.php">Go to Cart (<?php echo $cart_count; ?>)</a>
                    <a href="account.php">My Account</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="logout.php">Logout</a>
                    <?php else: ?>
                        <a href="login.php">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div class="features-section">
            <div class="container">
                <div class="features-grid">
                    <div class="feature-item">
                        <div class="feature-icon">🚚</div>
                        <h3 class="feature-title">Free Shipping</h3>
                        <p class="feature-text">Free nationwide shipping on orders over ₱2,000</p>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">🔒</div>
                        <h3 class="feature-title">Secure Payment</h3>
                        <p class="feature-text">Your payment information is safe and secure</p>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">↩️</div>
                        <h3 class="feature-title">30-Day Returns</h3>
                        <p class="feature-text">Easy returns and exchanges within 30 days</p>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">⭐</div>
                        <h3 class="feature-title">Quality Guarantee</h3>
                        <p class="feature-text">Premium quality products with lifetime warranty</p>
                    </div>
                </div>
            </div>
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
        // Show success message if item was added
        document.addEventListener('DOMContentLoaded', function() {
            // Check if we were redirected after adding to cart
            if (window.location.search.includes('added=1')) {
                showSuccessMessage('Item added to cart successfully!');
            }
        });

        function showSuccessMessage(message) {
            const successDiv = document.createElement('div');
            successDiv.className = 'success-message';
            successDiv.textContent = message;
            document.body.appendChild(successDiv);
            
            setTimeout(() => {
                successDiv.classList.add('show');
            }, 100);
            
            setTimeout(() => {
                successDiv.classList.remove('show');
                setTimeout(() => {
                    document.body.removeChild(successDiv);
                }, 300);
            }, 3000);
        }

        // Add smooth form submission feedback
        document.addEventListener('submit', function(e) {
            if (e.target.classList.contains('product-form')) {
                const submitBtn = e.target.querySelector('.btn-add-cart');
                const originalText = submitBtn.textContent;
                submitBtn.textContent = 'Adding...';
                submitBtn.disabled = true;
                
                // Re-enable button if form submission fails
                setTimeout(() => {
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                }, 5000);
            }
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>