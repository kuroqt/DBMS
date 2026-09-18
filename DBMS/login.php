<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// Auto-repair: Ensure password columns exist in students and employees tables
try {
    $pdo->exec("ALTER TABLE students ADD COLUMN password VARCHAR(255) DEFAULT NULL");
} catch (PDOException $e) {
}
try {
    $pdo->exec("ALTER TABLE employees ADD COLUMN password VARCHAR(255) DEFAULT NULL");
} catch (PDOException $e) {
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
} elseif (isset($_SESSION['patient_id'])) {
    header("Location: my-record.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['login_input']);
    $password = trim($_POST['password']);

    if (!empty($login_input) && !empty($password)) {

        // 1. Staff / Doctor Check (users table)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$login_input]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($staff && password_verify($password, $staff['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $staff['id'];
            $_SESSION['username'] = $staff['username'];
            $_SESSION['full_name'] = $staff['full_name'];
            $_SESSION['role'] = $staff['role'];
            header("Location: index.php");
            exit;
        }

        // 2. Student Check (students table)
        $stmt_stud = $pdo->prepare("SELECT * FROM students WHERE student_id_code = ?");
        $stmt_stud->execute([$login_input]);
        $student = $stmt_stud->fetch(PDO::FETCH_ASSOC);

        if ($student) {
            $db_pass = $student['password'] ?? '';
            $pass_valid = false;

            if (!empty($db_pass) && password_verify($password, $db_pass)) {
                $pass_valid = true;
            } elseif ($db_pass === $password) {
                // Plain text match -> upgrade to hash
                $pass_valid = true;
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE students SET password = ? WHERE id = ?");
                $upd->execute([$new_hash, $student['id']]);
            } elseif (empty($db_pass) && $password === 'password123') {
                // Default initial password 'password123'
                $pass_valid = true;
                $new_hash = password_hash('password123', PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE students SET password = ? WHERE id = ?");
                $upd->execute([$new_hash, $student['id']]);
            }

            if ($pass_valid) {
                session_regenerate_id(true);
                $_SESSION['patient_id'] = $student['id'];
                $_SESSION['patient_type'] = 'student';
                $_SESSION['patient_name'] = $student['first_name'] . ' ' . $student['last_name'];
                header("Location: my-record.php");
                exit;
            }
        }

        // 3. Employee Check (employees table)
        $stmt_emp = $pdo->prepare("SELECT * FROM employees WHERE emp_code = ?");
        $stmt_emp->execute([$login_input]);
        $employee = $stmt_emp->fetch(PDO::FETCH_ASSOC);

        if ($employee) {
            $db_pass = $employee['password'] ?? '';
            $pass_valid = false;

            if (!empty($db_pass) && password_verify($password, $db_pass)) {
                $pass_valid = true;
            } elseif ($db_pass === $password) {
                $pass_valid = true;
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE employees SET password = ? WHERE id = ?");
                $upd->execute([$new_hash, $employee['id']]);
            } elseif (empty($db_pass) && $password === 'password123') {
                $pass_valid = true;
                $new_hash = password_hash('password123', PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE employees SET password = ? WHERE id = ?");
                $upd->execute([$new_hash, $employee['id']]);
            }

            if ($pass_valid) {
                session_regenerate_id(true);
                $_SESSION['patient_id'] = $employee['id'];
                $_SESSION['patient_type'] = 'employee';
                $_SESSION['patient_name'] = $employee['first_name'] . ' ' . $employee['last_name'];
                header("Location: my-record.php");
                exit;
            }
        }

        $error = "Invalid Username / Student ID / Employee ID or Password!";
    } else {
        $error = "Please fill in all fields.";
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - ACSAT Medical System</title>
    <link rel="stylesheet" href="style.css" />
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(rgba(15, 23, 42, 0.65), rgba(15, 23, 42, 0.65)),
                url('images/bg.jpg') no-repeat center center fixed;
            background-size: cover;
            font-family: Arial, Helvetica, sans-serif;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 16px;
            padding: 40px 35px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
        }

        .login-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .login-header img {
            width: 65px;
            height: 65px;
            border-radius: 10px;
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .login-header h2 {
            font-size: 22px;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .login-header p {
            font-size: 13px;
            color: #64748b;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 11px;
            font-weight: bold;
            color: #4b5563;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            outline: none;
            font-size: 14px;
            box-sizing: border-box;
        }

        .form-group input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 15px;
            cursor: pointer;
            margin-top: 10px;
        }

        .btn-login:hover {
            background: #1d4ed8;
        }

        .error-alert {
            background: #fee2e2;
            color: #b91c1c;
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 18px;
            text-align: center;
            font-weight: bold;
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: #64748b;
        }

        .register-link a {
            color: #2563eb;
            font-weight: bold;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="login-header">
            <img src="images/acsat.jpg" alt="Logo" />
            <h2>Medical Record System</h2>
            <p>Sign in with your Staff Username, Student ID, or Employee ID</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label>Username / Student ID / Employee ID *</label>
                <input type="text" name="login_input" required autofocus placeholder="Enter Username or ID Code">
            </div>

            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" required placeholder="Enter your password">
            </div>

            <button type="submit" class="btn-login">Sign In</button>
        </form>

        <div class="register-link">
            New Doctor / Nurse? <a href="register.php">Register Staff Account</a>
        </div>
    </div>
</body>

</html>