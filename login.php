<?php
session_start();

$error_message = "";

$host = 'localhost'; 
$dbname = 'hc+ja'; 
$db_username = 'root'; 
$db_password = ''; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $db_username, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("SELECT user_id, full_name, username, password FROM accounts WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            
            // Redirect to home page
            header("Location: index.php");
            exit();
        } else {
            $error_message = "Invalid username or password.";
        }
    } catch (PDOException $e) {
        $error_message = "Invalid username or password.";
        // Log the actual error for debugging (don't show to user)
        error_log("Database error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login - MyWebsite</title>
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid #f0f0f0;
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

        .navbar-nav .nav-link.active {
            color: #1a1a1a;
            font-weight: 500;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px 0;
            background: #fafafa;
        }

        .login-container {
            background: white;
            padding: 60px;
            border-radius: 0;
            box-shadow: 0 0 40px rgba(0, 0, 0, 0.05);
            max-width: 420px;
            width: 100%;
        }

        .login-title {
            font-size: 28px;
            font-weight: 300;
            text-align: center;
            margin-bottom: 40px;
            letter-spacing: -0.5px;
            color: #1a1a1a;
        }

        .form-group {
            margin-bottom: 30px;
        }

        .form-label {
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #666;
            margin-bottom: 10px;
            display: block;
        }

        .form-control {
            background: transparent;
            border: none;
            border-bottom: 1px solid #e0e0e0;
            border-radius: 0;
            padding: 12px 0;
            font-size: 16px;
            color: #1a1a1a;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: transparent;
            border-color: #1a1a1a;
            box-shadow: none;
            outline: none;
        }

        .form-control::placeholder {
            color: #999;
            font-size: 14px;
        }

        .btn-login {
            background: #1a1a1a;
            color: white;
            border: none;
            padding: 15px 0;
            width: 100%;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .btn-login:hover {
            background: #333;
            color: white;
        }

        .error-message {
            background: #fee;
            color: #c33;
            padding: 15px;
            margin-bottom: 30px;
            font-size: 14px;
            text-align: center;
            border: 1px solid #fcc;
        }

        .login-links {
            text-align: center;
            margin-top: 30px;
        }

        .login-links a {
            color: #666;
            text-decoration: none;
            font-size: 13px;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .login-links a:hover {
            color: #1a1a1a;
        }

        .login-links .divider {
            margin: 15px 0;
            color: #ccc;
            font-size: 12px;
        }

        /* Footer */
        .footer {
            background: #1a1a1a;
            color: #999;
            text-align: center;
            padding: 30px 0;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .login-container {
                padding: 40px 30px;
                margin: 20px;
            }
            
            .navbar-nav .nav-link {
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
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
                        <li class="nav-item"><a class="nav-link active" href="login.php">Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="login-container">
            <h1 class="login-title">Welcome Back</h1>
            
            <?php if ($error_message): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required />
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required />
                </div>
                
                <button type="submit" class="btn-login">Sign In</button>
            </form>

            <div class="login-links">
                <a href="#">Forgot your password?</a>
                <div class="divider">—</div>
                <a href="register.php">Don't have an account? Create one</a>
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
</body>
</html>