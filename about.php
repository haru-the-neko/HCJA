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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Homura Design</title>
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
            line-height: 1.6;
            color: #1a1a1a;
            background-color: #ffffff;
            overflow-x: hidden;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
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


        .hero-header {
            background: linear-gradient(135deg, #000 0%, #333 100%);
            color: white;
            padding: 140px 0 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" patternUnits="userSpaceOnUse" width="100" height="100"><circle cx="50" cy="50" r="0.5" fill="rgba(255,255,255,0.02)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .hero-header-content {
            position: relative;
            z-index: 2;
        }

        .logo {
            font-size: 3.5rem;
            font-weight: 300;
            letter-spacing: 8px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .tagline {
            font-size: 1.2rem;
            font-weight: 300;
            letter-spacing: 2px;
            opacity: 0.9;
        }

        .main-content {
            padding: 100px 0;
        }

        .section {
            margin-bottom: 80px;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 300;
            text-align: center;
            margin-bottom: 50px;
            letter-spacing: 3px;
            text-transform: uppercase;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 2px;
            background: linear-gradient(90deg, transparent, #000, transparent);
        }

        .company-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: start;
        }

        .info-text {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #555;
        }

        .info-text p {
            margin-bottom: 25px;
        }

        .highlight {
            color: #000;
            font-weight: 500;
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            margin-top: 50px;
        }

        .value-card {
            background: #f8f8f8;
            padding: 40px 30px;
            text-align: center;
            border-radius: 2px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .value-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .value-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            display: block;
        }

        .value-title {
            font-size: 1.3rem;
            font-weight: 500;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .value-desc {
            color: #666;
            line-height: 1.6;
        }

        .contact-section {
            background: linear-gradient(135deg, #f8f8f8 0%, #e8e8e8 100%);
            padding: 80px 0;
            margin-top: 60px;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
        }

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .contact-item {
            background: white;
            padding: 25px;
            border-radius: 2px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .contact-item h3 {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: #000;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .contact-item p {
            color: #666;
            margin-bottom: 5px;
        }

        .contact-item a {
            color: #000;
            text-decoration: none;
            font-weight: 500;
        }

        .contact-item a:hover {
            text-decoration: underline;
        }

        .contact-form {
            background: white;
            padding: 40px;
            border-radius: 2px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.9rem;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 2px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            background: #fafafa;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #000;
            background: white;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .submit-btn {
            background: linear-gradient(135deg, #000 0%, #333 100%);
            color: white;
            padding: 18px 40px;
            border: none;
            border-radius: 2px;
            font-size: 1rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }

        .submit-btn:hover {
            background: linear-gradient(135deg, #333 0%, #000 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
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

        @media (max-width: 768px) {
            .logo {
                font-size: 2.5rem;
                letter-spacing: 4px;
            }

            .company-info,
            .contact-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .values-grid {
                grid-template-columns: 1fr;
            }

            .section-title {
                font-size: 2rem;
            }

            .hero-header {
                padding: 120px 0 60px;
            }

            .main-content {
                padding: 60px 0;
            }

            .navbar-nav .nav-link {
                margin: 10px 0;
            }
        }

        .image-placeholder {
            background: linear-gradient(135deg, #e0e0e0 0%, #f0f0f0 100%);
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-style: italic;
            border-radius: 2px;
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

    <header class="hero-header">
        <div class="container">
            <div class="hero-header-content">
                <h1 class="logo">HC+JA</h1>
                <p class="tagline">Heritage Connects</p>
                
                <p class="tagline">Just Aesthetics</p>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <section class="section">
                <h2 class="section-title">About Us</h2>
                <div class="company-info">
                    <div class="info-text">
                        <p>HC+JA is inspired by <span class="highlight">timeless design aesthetics</span>. Attention to detail and elegance are the foundations of our hand-made technique. Reflecting a sense of refinement and showcasing the sophisticated artistry of individually-crafted creations, each Homura piece will make a valued addition to your collection.</p>
                        
                        <p>With a focus on <span class="highlight">unique designs and high-quality materials</span>, HC+JA has quickly become a favorite among stylish individuals everywhere. From sleek and minimalist pieces to bold and statement-making designs, we have something for everyone.</p>
                        
                        <p>Our commitment to quality and craftsmanship has caught the attention of celebrities and style icons, including David Guison, James Reid, and Baron Geisler, who appreciate unique and fashionable accessories.</p>
                    </div>
                    
                    <div class="image-placeholder" style="width: 100%; height: 100%; overflow: hidden;">
                        <img src="img/workshop.png" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>

                </div>
            </section>

            <section class="section">
                <h2 class="section-title">Our Values</h2>
                <div class="values-grid">
                    <div class="value-card">
                        <span class="value-icon"></span>
                        <h3 class="value-title">Sustainability</h3>
                        <p class="value-desc">We work with non-virgin materials and primarily reclaimed steel to shape our original pieces, reducing our impact across the value chain.</p>
                    </div>
                    
                    <div class="value-card">
                        <span class="value-icon"></span>
                        <h3 class="value-title">Handcrafted</h3>
                        <p class="value-desc">Each item is slow-made by hand, honoring traditional craftsmanship while ensuring world-class quality.</p>
                    </div>
                    
                    <div class="value-card">
                        <span class="value-icon"></span>
                        <h3 class="value-title">Ethical</h3>
                        <p class="value-desc">We stand by the people behind our work. Our skilled team is treated fairly and with deep respect, never exploited.</p>
                    </div>
                </div>
            </section>
        </div>

        <section class="contact-section">
            <div class="container">
                <h2 class="section-title">Get in Touch</h2>
                <div class="contact-grid">
                    <div class="contact-info">
                        <div class="contact-item">
                            <h3>General Inquiries</h3>
                            <p>For general enquiries, product information, or order-related concerns:</p>
                            <p><a href="mailto:info@hc+ja.com">info@hc+ja.com</a></p>
                        </div>
                        
                        <div class="contact-item">
                            <h3>Returns & Exchanges</h3>
                            <p>For returns, exchanges and refunds:</p>
                            <p><a href="mailto:return@hc+ja.com">return@hc+ja.com</a></p>
                        </div>
                        
                        <div class="contact-item">
                            <h3>Media & Collaborations</h3>
                            <p>For media, collaboration proposals and marketing:</p>
                            <p><a href="mailto:jay@hc+ja.com">jay@hc+ja.com</a></p>
                        </div>
                        
                        <div class="contact-item">
                            <h3>Business Partnerships</h3>
                            <p>For business enquiries and investor relations:</p>
                            <p><a href="mailto:humar@hc+ja.com">humar@hc+ja.com</a></p>
                        </div>
                        
                        <div class="contact-item">
                            <h3>Live Chat Support</h3>
                            <p>Available Monday to Saturday</p>
                            <p><strong>9:00 AM - 4:00 PM</strong></p>
                            <p>Our social messaging features AI assistance and live agents.</p>
                        </div>
                    </div>
                    
                    <form class="contact-form" onsubmit="handleSubmit(event)">
                        <h3 style="margin-bottom: 30px; text-align: center; text-transform: uppercase; letter-spacing: 2px;">Contact Form</h3>
                        
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone">
                        </div>
                        
                        <div class="form-group">
                            <label for="inquiry-type">Inquiry Type</label>
                            <select id="inquiry-type" name="inquiry-type" required>
                                <option value="">Select inquiry type...</option>
                                <option value="general">General Inquiry</option>
                                <option value="product">Product Information</option>
                                <option value="order">Order Support</option>
                                <option value="return">Returns & Exchanges</option>
                                <option value="collaboration">Media & Collaboration</option>
                                <option value="business">Business Partnership</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" placeholder="Please describe your inquiry in detail..." required></textarea>
                        </div>
                        
                        <button type="submit" class="submit-btn">Send Message</button>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 HC+JA. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function handleSubmit(event) {
            event.preventDefault();
            
            // Get form data
            const formData = new FormData(event.target);
            const data = Object.fromEntries(formData);
            
            // Simple validation
            if (!data.name || !data.email || !data.message || !data['inquiry-type']) {
                alert('Please fill in all required fields.');
                return;
            }
            
            // Simulate form submission
            const submitBtn = event.target.querySelector('.submit-btn');
            const originalText = submitBtn.textContent;
            
            submitBtn.textContent = 'Sending...';
            submitBtn.disabled = true;
            
            setTimeout(() => {
                alert('Thank you for your message! We will get back to you within 24 hours.');
                event.target.reset();
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }, 2000);
        }

        // Add smooth scrolling for better UX
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Add loading animation on page load
        window.addEventListener('load', function() {
            document.body.style.opacity = '0';
            document.body.style.transition = 'opacity 0.5s ease-in-out';
            setTimeout(() => {
                document.body.style.opacity = '1';
            }, 100);
        });
    </script>
</body>
</html>