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
    exit();
} elseif (isset($_SESSION['patient_id'])) {
    header("Location: my-record.php");
    exit();
}


$error = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['login_input'] ?? '');
    $password = trim($_POST['password'] ?? '');


    if (!empty($login_input) && !empty($password)) {


        // 1. Staff / Doctor Check (users table)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$login_input]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);


        if ($staff && (password_verify($password, $staff['password']) || $password === $staff['password'])) {
            session_regenerate_id(true);


            // If a student account exists in users table, restrict to patient session
            if (strtolower(trim($staff['role'] ?? '')) === 'student' && !empty($staff['student_id'])) {
                $_SESSION['patient_id'] = $staff['student_id'];
                $_SESSION['patient_type'] = 'student';
                $_SESSION['full_name'] = $staff['full_name'] ?? $staff['username'];
                $_SESSION['role'] = 'Student';
                header("Location: my-record.php");
                exit();
            }


            // If an employee account exists in users table, restrict to patient session
            if (strtolower(trim($staff['role'] ?? '')) === 'employee' && !empty($staff['employee_id'])) {
                $_SESSION['patient_id'] = $staff['employee_id'];
                $_SESSION['patient_type'] = 'employee';
                $_SESSION['full_name'] = $staff['full_name'] ?? $staff['username'];
                $_SESSION['role'] = 'Employee';
                header("Location: my-record.php");
                exit();
            }


            $_SESSION['user_id'] = $staff['id'];
            $_SESSION['username'] = $staff['username'];
            $_SESSION['full_name'] = $staff['full_name'] ?? $staff['name'] ?? $staff['username'];
            $_SESSION['role'] = $staff['role'] ?? 'Administrator';
            header("Location: index.php");
            exit();
        }


        // 2. Student Check (students table - Default password is Student ID)
        $stmt_stud = $pdo->prepare("SELECT * FROM students WHERE student_id_code = ?");
        $stmt_stud->execute([$login_input]);
        $student = $stmt_stud->fetch(PDO::FETCH_ASSOC);


        if ($student) {
            $db_pass = $student['password'] ?? '';
            $pass_valid = false;


            if (!empty($db_pass) && password_verify($password, $db_pass)) {
                $pass_valid = true;
            } elseif (!empty($db_pass) && $db_pass === $password) {
                // Plain text match -> upgrade to hash
                $pass_valid = true;
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE students SET password = ? WHERE id = ?");
                $upd->execute([$new_hash, $student['id']]);
            } elseif (empty($db_pass) && $password === trim($student['student_id_code'])) {
                // Default initial password is the Student's ID Code
                $pass_valid = true;
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE students SET password = ? WHERE id = ?");
                $upd->execute([$new_hash, $student['id']]);
            }


            if ($pass_valid) {
                session_regenerate_id(true);
                $_SESSION['patient_id'] = $student['id'];
                $_SESSION['patient_type'] = 'student';
                $_SESSION['full_name'] = trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
                $_SESSION['role'] = 'Student';
                header("Location: my-record.php");
                exit();
            }
        }


        // 3. Employee Check (employees table - Default password is Employee ID)
        $stmt_emp = $pdo->prepare("SELECT * FROM employees WHERE emp_code = ?");
        $stmt_emp->execute([$login_input]);
        $employee = $stmt_emp->fetch(PDO::FETCH_ASSOC);


        if ($employee) {
            $db_pass = $employee['password'] ?? '';
            $pass_valid = false;


            if (!empty($db_pass) && password_verify($password, $db_pass)) {
                $pass_valid = true;
            } elseif (!empty($db_pass) && $db_pass === $password) {
                // Plain text match -> upgrade to hash
                $pass_valid = true;
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE employees SET password = ? WHERE id = ?");
                $upd->execute([$new_hash, $employee['id']]);
            } elseif (empty($db_pass) && $password === trim($employee['emp_code'])) {
                // Default initial password is the Employee's ID Code
                $pass_valid = true;
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE employees SET password = ? WHERE id = ?");
                $upd->execute([$new_hash, $employee['id']]);
            }


            if ($pass_valid) {
                session_regenerate_id(true);
                $_SESSION['patient_id'] = $employee['id'];
                $_SESSION['patient_type'] = 'employee';
                $_SESSION['full_name'] = trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''));
                $_SESSION['role'] = 'Employee';
                header("Location: my-record.php");
                exit();
            }
        }


        $error = "Account not found or invalid credentials.";
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
    <title>Login - Asian College Medical System</title>


    <!-- Tailwind CSS & Font Awesome CDNs -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


    <!-- Custom Color Extensions for SIMS Branding -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            navy: '#10386C',     /* SIMS Header Navy */
                            sidebar: '#1E222D',  /* SIMS Sidebar Dark Gray/Navy */
                            accent: '#00A3E0',   /* SIMS Active Blue Accent */
                            green: '#16BA52',    /* SIMS Success Green */
                        }
                    }
                }
            }
        }
    </script>
</head>


<body
    class="font-sans min-h-[100dvh] flex items-center justify-center p-4 sm:p-6 bg-cover bg-center bg-fixed bg-no-repeat relative"
    style="background-image: linear-gradient(to bottom, rgba(6, 8, 12, 0.10), rgba(16, 36, 58, 0.75)), url('images/bg.jpg');">


    <div
        class="max-w-md w-full bg-white/95 backdrop-blur-md rounded-2xl border border-white/40 shadow-2xl overflow-hidden my-auto">


        <!-- HEADER BANNER (SIMS Navy Theme) -->
        <div class="bg-brand-navy px-5 py-6 sm:p-6 text-center text-white relative">
            <div
                class="bg-white p-1.5 rounded-xl w-14 h-14 sm:w-16 sm:h-16 mx-auto mb-3 flex items-center justify-center shadow-md">
                <img src="images/acsat.jpg" alt="ACSAT Logo" class="h-10 sm:h-12 w-auto object-contain"
                    onerror="this.style.display='none'">
            </div>
            <h2 class="text-base sm:text-lg font-bold leading-snug">Asian College Computer-Based EMR System</h2>
            <p class="text-xs text-blue-200 mt-0.5">Student & Employee Health Portal</p>
        </div>


        <!-- LOGIN FORM -->
        <form method="POST" action="login.php" class="p-5 sm:p-6 space-y-4">


            <?php if (!empty($error)): ?>
                <div
                    class="p-3 bg-red-50 border border-red-200 text-red-600 text-xs font-semibold rounded-lg flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-sm shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>


            <div>
                <label for="login_input" class="block text-xs font-semibold text-gray-700 mb-1.5">
                    Username / Student ID / Employee ID *
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400 pointer-events-none">
                        <i class="fa-solid fa-user text-xs"></i>
                    </span>
                    <input type="text" id="login_input" name="login_input" required autofocus autocomplete="username"
                        autocapitalize="none" spellcheck="false"
                        value="<?= htmlspecialchars($_POST['login_input'] ?? '') ?>"
                        placeholder="Enter Username or ID Code"
                        class="w-full pl-9 pr-3 py-2.5 sm:py-2 text-base sm:text-sm rounded-lg border border-gray-300 focus:outline-none focus:border-brand-navy focus:ring-1 focus:ring-brand-navy transition-all bg-white">
                </div>
            </div>


            <div>
                <label for="password" class="block text-xs font-semibold text-gray-700 mb-1.5">
                    Password *
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400 pointer-events-none">
                        <i class="fa-solid fa-lock text-xs"></i>
                    </span>
                    <input type="password" id="password" name="password" required autocomplete="current-password"
                        placeholder="Enter your password"
                        class="w-full pl-9 pr-10 py-2.5 sm:py-2 text-base sm:text-sm rounded-lg border border-gray-300 focus:outline-none focus:border-brand-navy focus:ring-1 focus:ring-brand-navy transition-all bg-white">
                    <button type="button" id="toggle-password" aria-label="Show password"
                        class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-gray-600 focus:outline-none">
                        <i id="toggle-icon" class="fa-solid fa-eye text-xs"></i>
                    </button>
                </div>
            </div>


            <button type="submit"
                class="w-full bg-brand-navy hover:bg-blue-900 active:scale-[0.99] text-white font-bold text-sm sm:text-xs py-3 sm:py-2.5 px-4 rounded-lg shadow-md transition-all flex items-center justify-center gap-2">
                <i class="fa-solid fa-right-to-bracket text-xs"></i>
                <span>Sign In</span>
            </button>
        </form>


        <!-- FOOTER LINKS -->
        <div class="p-4 bg-gray-50/80 border-t border-gray-100 text-center text-xs text-gray-500">
            New Staff / Doctor? <a href="register.php" class="text-brand-navy font-bold hover:underline">Register Staff
                Account</a>
        </div>


    </div>


    <!-- PASSWORD VISIBILITY TOGGLE -->
    <script>
        const toggleBtn = document.getElementById('toggle-password');
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggle-icon');


        toggleBtn.addEventListener('click', () => {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            toggleIcon.classList.toggle('fa-eye', !isPassword);
            toggleIcon.classList.toggle('fa-eye-slash', isPassword);
        });
    </script>


</body>


</html>