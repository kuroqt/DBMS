<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);

// Public pages that don't require authentication
$public_pages = ['login.php', 'register.php'];

// 1. If not logged in at all, force redirect to login.php
if (!isset($_SESSION['user_id']) && !isset($_SESSION['patient_id'])) {
    if (!in_array($current_page, $public_pages)) {
        header("Location: login.php");
        exit();
    }
}

// 2. If logged in as a Patient (Student/Employee), restrict access strictly to their own record & export
$current_role = strtolower(trim($_SESSION['role'] ?? ''));
$is_student_role = ($current_role === 'student');
$is_employee_role = ($current_role === 'employee');

if (isset($_SESSION['patient_id']) || $is_student_role || $is_employee_role) {
    // Sync patient_id if login.php stored student_id or employee_id instead
    if (!isset($_SESSION['patient_id'])) {
        if (isset($_SESSION['student_id'])) {
            $_SESSION['patient_id'] = $_SESSION['student_id'];
            $_SESSION['patient_type'] = 'student';
        } elseif (isset($_SESSION['employee_id'])) {
            $_SESSION['patient_id'] = $_SESSION['employee_id'];
            $_SESSION['patient_type'] = 'employee';
        }
    }

    $own_id = intval($_SESSION['patient_id'] ?? 0);
    $own_type = strtolower(trim($_SESSION['patient_type'] ?? ($is_employee_role ? 'employee' : 'student')));

    if ($current_page === 'export-pdf.php') {
        $req_type = strtolower(trim($_GET['type'] ?? ''));
        $req_id = intval($_GET['id'] ?? 0);

        // Block if trying to export another person's record
        if ($own_id <= 0 || $req_type !== $own_type || $req_id !== $own_id) {
            header("Location: my-record.php");
            exit();
        }
    } else {
        $allowed_patient_pages = ['my-record.php', 'logout.php'];
        if (!in_array($current_page, $allowed_patient_pages)) {
            header("Location: my-record.php");
            exit();
        }
    }
}
?>