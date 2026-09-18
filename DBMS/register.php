<?php
session_start();
require_once 'db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $role = $_POST['role'];

    if (!empty($full_name) && !empty($username) && !empty($password) && !empty($role)) {
        if ($password !== $confirm_password) {
            $error = "Passwords do not match!";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters long.";
        } else {
            try {
                // Check if username already exists
                $check_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $check_stmt->execute([$username]);

                if ($check_stmt->fetch()) {
                    $error = "Username is already taken. Please choose another.";
                } else {
                    // Hash password for security
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    $stmt = $pdo->prepare("INSERT INTO users (full_name, username, password, role) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$full_name, $username, $hashed_password, $role]);

                    $success = "Account created successfully! You can now log in.";
                }
            } catch (PDOException $e) {
                $error = "Failed to register: " . $e->getMessage();
            }
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register Staff Account - Medical System</title>
    <link rel="stylesheet" href="style.css" />
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #f5f7fa;
        }

        .register-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 35px 30px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .register-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .register-header img {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            margin-bottom: 12px;
        }

        .register-header h2 {
            font-size: 20px;
            color: #1f2937;
        }

        .register-header p {
            font-size: 13px;
            color: #64748b;
            margin-top: 4px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 11px;
            font-weight: bold;
            color: #4b5563;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            outline: none;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #2563eb;
        }

        .btn-register {
            width: 100%;
            padding: 12px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 14px;
            cursor: pointer;
            margin-top: 10px;
        }

        .btn-register:hover {
            background: #1d4ed8;
        }

        .alert-box {
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 18px;
            text-align: center;
        }

        .alert-error {
            background: #fee2e2;
            color: #b91c1c;
        }

        .alert-success {
            background: #dcfce7;
            color: #15803d;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: #64748b;
        }

        .login-link a {
            color: #2563eb;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="register-card">
        <div class="register-header">
            <img src="images/acsat.jpg" alt="Logo" />
            <h2>Create Staff Account</h2>
            <p>Register credentials for Doctors and Medical Personnel</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-box alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert-box alert-success">
                <?= htmlspecialchars($success) ?><br>
                <a href="login.php" style="color: #15803d; font-weight: bold; display: inline-block; margin-top: 8px;">Go to
                    Login Page →</a>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" required placeholder="e.g. Dr. Maria Santos / Nurse John Doe">
            </div>

            <div class="form-group">
                <label>Medical Role *</label>
                <select name="role" required>
                    <option value="Doctor">Doctor</option>
                    <option value="Nurse">Nurse</option>
                    <option value="Medical Staff">Medical Staff</option>
                    <option value="Administrator">Administrator</option>
                </select>
            </div>

            <div class="form-group">
                <label>Username *</label>
                <input type="text" name="username" required placeholder="Choose a username">
            </div>

            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" required placeholder="At least 6 characters">
            </div>

            <div class="form-group">
                <label>Confirm Password *</label>
                <input type="password" name="confirm_password" required placeholder="Re-enter password">
            </div>

            <button type="submit" class="btn-register">Create Account</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Sign In here</a>
        </div>
    </div>
</body>

</html>