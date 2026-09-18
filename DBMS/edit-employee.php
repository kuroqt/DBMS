<?php
require_once 'auth.php';
require_once 'db.php';

$emp_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';

if ($emp_id <= 0) {
    header("Location: employee.php");
    exit;
}

// Fetch existing employee details
$stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->execute([$emp_id]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    die("<h2 style='padding: 40px; font-family: sans-serif;'>Employee record not found. <a href='employee.php'>Back to Employee List</a></h2>");
}

// Handle Form Submission (Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emp_code = trim($_POST['emp_code']);
    $title = trim($_POST['title']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $dob = $_POST['dob'];
    $sex = $_POST['sex'];
    $position = trim($_POST['position']);
    $department = trim($_POST['department']);
    $contact_number = trim($_POST['contact_number']);
    $status = trim($_POST['status']);
    $blood_type = trim($_POST['blood_type']);
    $allergies = trim($_POST['allergies']) ?: 'None reported';
    $medical_conditions = trim($_POST['medical_conditions']) ?: 'None reported';
    $current_medication = trim($_POST['current_medication']) ?: 'None';
    $emergency_contact_name = trim($_POST['emergency_contact_name']);
    $emergency_contact_number = trim($_POST['emergency_contact_number']);

    try {
        $update_stmt = $pdo->prepare("UPDATE employees SET 
            emp_code = ?, title = ?, first_name = ?, last_name = ?, 
            dob = ?, sex = ?, position = ?, department = ?, 
            contact_number = ?, status = ?, blood_type = ?, allergies = ?, 
            medical_conditions = ?, current_medication = ?, 
            emergency_contact_name = ?, emergency_contact_number = ? 
            WHERE id = ?");

        $update_stmt->execute([
            $emp_code,
            $title,
            $first_name,
            $last_name,
            $dob,
            $sex,
            $position,
            $department,
            $contact_number,
            $status,
            $blood_type,
            $allergies,
            $medical_conditions,
            $current_medication,
            $emergency_contact_name,
            $emergency_contact_number,
            $emp_id
        ]);

        header("Location: employee-record.php?id=" . $emp_id . "&updated=1");
        exit;
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $error = "Employee ID Code already exists for another record!";
        } else {
            $error = "Failed to update record: " . $e->getMessage();
        }
    }
}

$full_name = trim($employee['title'] . ' ' . $employee['first_name'] . ' ' . $employee['last_name']);
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Employee - <?= htmlspecialchars($full_name) ?></title>
    <link rel="stylesheet" href="style.css" />
</head>

<body>
    <aside class="sidebar">
        <div class="logo">
            <div class="logo-icon">
                <img src="images/acsat.jpg" alt="Logo" />
            </div>
            <div>
                <h2>Medical Record</h2>
                <span>ACSAT Medical System</span>
            </div>
        </div>

        <nav>
            <a href="index.php"> <span>⌂</span> Dashboard </a>
            <a href="students.php"> <span>♙</span> Students </a>
            <a href="employee.php" class="active"> <span>♙</span> Employees </a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1>Edit Employee Record</h1>
                <p>Update employee health details and personal information</p>
            </div>

            <div class="user">
                <div class="user-icon"><?= strtoupper(substr($_SESSION['full_name'], 0, 1)) ?></div>
                <div>
                    <strong><?= htmlspecialchars($_SESSION['full_name']) ?></strong>
                    <small><?= htmlspecialchars($_SESSION['role']) ?></small>
                </div>
                <a href="logout.php"
                    style="margin-left: 15px; color: #ef4444; font-weight: bold; font-size: 13px; text-decoration: none;">Logout</a>
            </div>
        </header>

        <a href="employee-record.php?id=<?= $emp_id ?>" class="back-button"> ← Back to Employee Profile </a>

        <?php if (!empty($error)): ?>
            <p
                style="color: red; background: #ffe6e6; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: bold;">
                <?= htmlspecialchars($error) ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="edit-employee.php?id=<?= $emp_id ?>"
            style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e5e7eb;">
            <h3>Personal Information</h3>
            <br>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <label
                        style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Employee
                        ID Code *</label>
                    <input type="text" name="emp_code" value="<?= htmlspecialchars($employee['emp_code']) ?>" required
                        style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div>
                    <label
                        style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Title</label>
                    <input type="text" name="title" value="<?= htmlspecialchars($employee['title']) ?>"
                        placeholder="e.g. Engr. / Dr. / Mr."
                        style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div>
                    <label style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">First
                        Name *</label>
                    <input type="text" name="first_name" value="<?= htmlspecialchars($employee['first_name']) ?>"
                        required
                        style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div>
                    <label style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Last
                        Name *</label>
                    <input type="text" name="last_name" value="<?= htmlspecialchars($employee['last_name']) ?>" required
                        style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div>
                    <label style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Date
                        of Birth *</label>
                    <input type="date" name="dob" value="<?= htmlspecialchars($employee['dob']) ?>" required
                        style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div>
                    <label style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Sex
                        *</label>
                    <select name="sex" required
                        style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
                        <option value="Male" <?= $employee['sex'] === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= $employee['sex'] === 'Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>
                <div>
                    <label
                        style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Position
                        *</label>
                    <input type="text" name="position" value="<?= htmlspecialchars($employee['position']) ?>" required
                        style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div>
                    <label
                        style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Department
                        *</label>
                    <input type="text" name="department" value="<?= htmlspecialchars($employee['department']) ?>"
                        required
                        style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div>
                    <label
                        style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Contact
                        Number *</label>
                    <input type="text" name="contact_number"
                        value="<?= htmlspecialchars($employee['contact_number']) ?>" required
                        style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div>
                    <label
                        style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Account
                        Status *</label>
                    <select name="status" required
                        style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
                        <option value="Active" <?= strtolower($employee['status']) === 'active' || strtolower($employee['status']) === 'normal' ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= strtolower($employee['status']) === 'inactive' ? 'selected' : '' ?>>
                            Inactive</option>
                    </select>
                </div>
            </div>

            <hr style="margin: 25px 0; border: 0; border-top: 1px solid #e5e7eb;">

            <h3>Medical Details</h3>
            <br>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <label style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Blood
                        Type</label>
                    <select name="blood_type"
                        style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
                        <?php foreach (['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bt): ?>
                            <option value="<?= $bt ?>" <?= $employee['blood_type'] === $bt ? 'selected' : '' ?>><?= $bt ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label
                        style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Allergies</label>
                    <input type="text" name="allergies" value="<?= htmlspecialchars($employee['allergies']) ?>"
                        style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div>
                    <label
                        style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Medical
                        Conditions</label>
                    <input stype="text" name="medical_conditions"
                        value="<?= htmlspecialchars($employee['medical_conditions']) ?>"
                        style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div>
                    <label
                        style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Current
                        Medication</label>
                    <input type="text" name="current_medication"
                        value="<?= htmlspecialchars($employee['current_medication']) ?>"
                        style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
            </div>

            <hr style="margin: 25px 0; border: 0; border-top: 1px solid #e5e7eb;">

            <h3>Emergency Contact</h3>
            <br>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <label
                        style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Contact
                        Name *</label>
                    <input type="text" name="emergency_contact_name"
                        value="<?= htmlspecialchars($employee['emergency_contact_name']) ?>" required
                        style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div>
                    <label
                        style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Contact
                        Number *</label>
                    <input type="text" name="emergency_contact_number"
                        value="<?= htmlspecialchars($employee['emergency_contact_number']) ?>" required
                        style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
            </div>

            <br><br>
            <button type="submit"
                style="background: #2563eb; color: #fff; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">Update
                Employee Record</button>
        </form>
    </main>
</body>

</html>