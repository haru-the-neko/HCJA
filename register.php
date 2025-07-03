<?php
session_start();

$errors = [];
$old_input = [];
$message = "";

// Database connection
$host = 'localhost';
$dbname = 'hc+ja';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $street = trim($_POST['street'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $province = trim($_POST['state'] ?? '');
    $zip = trim($_POST['zip'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $old_input = [
        'full_name' => $full_name,
        'gender' => $gender,
        'dob' => $dob,
        'phone' => $phone,
        'email' => $email,
        'street' => $street,
        'city' => $city,
        'state' => $province,
        'zip' => $zip,
        'country' => $country,
        'username' => $username
    ];

    // Validation
    if (!preg_match("/^[a-zA-Z\s]{2,50}$/", $full_name)) {
        $errors['full_name'] = "Full name must contain only letters and spaces, between 2 to 50 characters.";
    }

    if (empty($gender)) {
        $errors['gender'] = "Gender is required.";
    }

    if (!empty($dob)) {
        try {
            $birth_date = new DateTime($dob);
            $current_date = new DateTime();
            $age = $current_date->diff($birth_date)->y;
            if ($age < 18) {
                $errors['dob'] = "You must be at least 18 years old to register.";
            }
        } catch (Exception $e) {
            $errors['dob'] = "Invalid Date of Birth format.";
        }
    } else {
        $errors['dob'] = "Date of Birth is required.";
    }

    if (!preg_match("/^09\d{9}$/", $phone)) {
        $errors['phone'] = "Phone number must be 11 digits, start with '09', and contain only digits.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format.";
    }

    if (!preg_match("/^[a-zA-Z0-9\s.,'#\-\/]{5,100}$/", $street)) {
        $errors['street'] = "Street must be between 5 to 100 characters.";
    }

    if (!preg_match("/^[a-zA-Z\s]{2,50}$/", $city)) {
        $errors['city'] = "City must contain only letters and spaces, between 2 to 50 characters.";
    }

    if (!preg_match("/^[a-zA-Z\s]{2,50}$/", $province)) {
        $errors['state'] = "Province/State must contain only letters and spaces, between 2 to 50 characters.";
    }

    if (!preg_match("/^\d{4}$/", $zip)) {
        $errors['zip'] = "Zip code must be exactly 4 digits.";
    }

    if (!preg_match("/^[a-zA-Z\s]{2,}$/", $country)) {
        $errors['country'] = "Country must contain only letters and spaces.";
    }

    if (!preg_match("/^[a-zA-Z0-9_]{5,20}$/", $username)) {
        $errors['username'] = "Username must contain only letters, numbers, and underscores, between 5 to 20 characters.";
    } else {
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT username FROM accounts WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            $errors['username'] = "Username already taken. Please choose a different one.";
        }
    }

    // Check if email already exists
    if (!isset($errors['email'])) {
        $stmt = $pdo->prepare("SELECT email FROM accounts WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $errors['email'] = "Email already registered. Please use a different email.";
        }
    }

    if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()\-_=+{};:,<.>]).{8,}$/", $password)) {
        $errors['password'] = "Password must be at least 8 characters with uppercase, lowercase, digit, and special character.";
    }

    if ($password !== $confirm_password) {
        $errors['confirm_password'] = "Passwords do not match.";
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO accounts (full_name, gender, dob, phone, email, street, city, state, zip, country, username, password, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$full_name, $gender, $dob, $phone, $email, $street, $city, $province, $zip, $country, $username, $hashed_password]);
            
            $_SESSION['registration_success'] = "Registration successful! You can now log in.";
            header('Location: login.php');
            exit();
        } catch(PDOException $e) {
            $message = "<div class='error-message'>Registration failed. Please try again.</div>";
        }
    } else {
        $message = "<div class='error-message'>Please correct the errors below and try again.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - HC+JA</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; color: #1a1a1a; background-color: #ffffff; line-height: 1.6; min-height: 100vh; display: flex; flex-direction: column; }
        .header { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border-bottom: 1px solid #f0f0f0; }
        .navbar { padding: 20px 0; }
        .navbar-brand { font-weight: 700; font-size: 24px; color: #1a1a1a !important; text-decoration: none; letter-spacing: -0.5px; }
        .logo-placeholder { width: 32px; height: 32px; background: #1a1a1a; margin-right: 12px; display: inline-block; vertical-align: middle; }
        .navbar-nav .nav-link { color: #1a1a1a; font-weight: 400; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; margin: 0 20px; transition: all 0.3s ease; }
        .navbar-nav .nav-link:hover { color: #666; }
        .navbar-nav .nav-link.active { color: #1a1a1a; font-weight: 500; }
        .main-content { flex: 1; display: flex; align-items: center; justify-content: center; padding: 60px 0; background: #fafafa; }
        .register-container { background: white; padding: 60px; border-radius: 0; box-shadow: 0 0 40px rgba(0, 0, 0, 0.05); max-width: 600px; width: 100%; }
        .register-title { font-size: 28px; font-weight: 300; text-align: center; margin-bottom: 40px; letter-spacing: -0.5px; color: #1a1a1a; }
        .form-row { display: flex; gap: 20px; margin-bottom: 30px; }
        .form-group { margin-bottom: 30px; flex: 1; }
        .form-label { font-size: 12px; font-weight: 500; text-transform: uppercase; letter-spacing: 1px; color: #666; margin-bottom: 10px; display: block; }
        .form-control { background: transparent; border: none; border-bottom: 1px solid #e0e0e0; border-radius: 0; padding: 12px 0; font-size: 16px; color: #1a1a1a; transition: all 0.3s ease; width: 100%; }
        .form-control:focus { background: transparent; border-color: #1a1a1a; box-shadow: none; outline: none; }
        .form-control::placeholder { color: #999; font-size: 14px; }
        .form-select { background: transparent; border: none; border-bottom: 1px solid #e0e0e0; border-radius: 0; padding: 12px 0; font-size: 16px; color: #1a1a1a; transition: all 0.3s ease; }
        .form-select:focus { background: transparent; border-color: #1a1a1a; box-shadow: none; outline: none; }
        .btn-register { background: #1a1a1a; color: white; border: none; padding: 15px 0; width: 100%; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; font-weight: 500; transition: all 0.3s ease; margin-top: 20px; }
        .btn-register:hover { background: #333; color: white; }
        .error-message { background: #fee; color: #c33; padding: 15px; margin-bottom: 30px; font-size: 14px; text-align: center; border: 1px solid #fcc; }
        .field-error { color: #c33; font-size: 12px; margin-top: 5px; letter-spacing: 0.5px; }
        .register-links { text-align: center; margin-top: 30px; }
        .register-links a { color: #666; text-decoration: none; font-size: 13px; letter-spacing: 0.5px; transition: all 0.3s ease; }
        .register-links a:hover { color: #1a1a1a; }
        .footer { background: #1a1a1a; color: #999; text-align: center; padding: 30px 0; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
        @media (max-width: 768px) {
            .register-container { padding: 40px 30px; margin: 20px; }
            .form-row { flex-direction: column; gap: 0; }
            .navbar-nav .nav-link { margin: 10px 0; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <a href="#" class="navbar-brand">
                        <span class="logo-placeholder"></span>
                        HC + JA
                    </a>
                    <div class="navbar-nav d-flex flex-row">
                        <a class="nav-link" href="index.php">Home</a>
                        <a class="nav-link" href="login.php">Login</a>
                        <a class="nav-link active" href="register.php">Register</a>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="register-container">
            <h2 class="register-title">Create Account</h2>
            
            <?php echo $message; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($old_input['full_name'] ?? ''); ?>" placeholder="Enter your full name">
                    <?php if (isset($errors['full_name'])): ?>
                        <div class="field-error"><?php echo $errors['full_name']; ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="gender" class="form-label">Gender</label>
                        <select class="form-select" id="gender" name="gender">
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo ($old_input['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($old_input['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($old_input['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                        <?php if (isset($errors['gender'])): ?>
                            <div class="field-error"><?php echo $errors['gender']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="dob" class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" id="dob" name="dob" value="<?php echo htmlspecialchars($old_input['dob'] ?? ''); ?>">
                        <?php if (isset($errors['dob'])): ?>
                            <div class="field-error"><?php echo $errors['dob']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($old_input['phone'] ?? ''); ?>" placeholder="09XXXXXXXXX">
                        <?php if (isset($errors['phone'])): ?>
                            <div class="field-error"><?php echo $errors['phone']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($old_input['email'] ?? ''); ?>" placeholder="example@domain.com">
                        <?php if (isset($errors['email'])): ?>
                            <div class="field-error"><?php echo $errors['email']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="street" class="form-label">Street Address</label>
                    <input type="text" class="form-control" id="street" name="street" value="<?php echo htmlspecialchars($old_input['street'] ?? ''); ?>" placeholder="Enter your street address">
                    <?php if (isset($errors['street'])): ?>
                        <div class="field-error"><?php echo $errors['street']; ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="city" class="form-label">City</label>
                        <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($old_input['city'] ?? ''); ?>" placeholder="Enter your city">
                        <?php if (isset($errors['city'])): ?>
                            <div class="field-error"><?php echo $errors['city']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="state" class="form-label">Province/State</label>
                        <input type="text" class="form-control" id="state" name="state" value="<?php echo htmlspecialchars($old_input['state'] ?? ''); ?>" placeholder="Enter your province/state">
                        <?php if (isset($errors['state'])): ?>
                            <div class="field-error"><?php echo $errors['state']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="zip" class="form-label">Zip Code</label>
                        <input type="text" class="form-control" id="zip" name="zip" value="<?php echo htmlspecialchars($old_input['zip'] ?? ''); ?>" placeholder="1234">
                        <?php if (isset($errors['zip'])): ?>
                            <div class="field-error"><?php echo $errors['zip']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="country" class="form-label">Country</label>
                        <input type="text" class="form-control" id="country" name="country" value="<?php echo htmlspecialchars($old_input['country'] ?? ''); ?>" placeholder="Enter your country">
                        <?php if (isset($errors['country'])): ?>
                            <div class="field-error"><?php echo $errors['country']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($old_input['username'] ?? ''); ?>" placeholder="Choose a username">
                    <?php if (isset($errors['username'])): ?>
                        <div class="field-error"><?php echo $errors['username']; ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password">
                        <?php if (isset($errors['password'])): ?>
                            <div class="field-error"><?php echo $errors['password']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm your password">
                        <?php if (isset($errors['confirm_password'])): ?>
                            <div class="field-error"><?php echo $errors['confirm_password']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <button type="submit" class="btn-register">Create Account</button>
            </form>

            <div class="register-links">
                <a href="login.php">Already have an account? Sign in</a>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 HC+JA. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>