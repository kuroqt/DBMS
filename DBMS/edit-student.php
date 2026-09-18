<?php
require_once 'db.php';
require_once 'auth.php';

$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';

if ($student_id <= 0) {
    header("Location: students.php");
    exit;
}

// Fetch existing student details
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("<h2 style='padding: 40px; font-family: sans-serif;'>Student record not found. <a href='students.php'>Back to Student List</a></h2>");
}

// Handle Form Submission (Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id_code = trim($_POST['student_id_code']);
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $dob = $_POST['dob'];
    $sex = $_POST['sex'];
    $course = trim($_POST['course']);
    $year_level = trim($_POST['year_level']);
    $section = trim($_POST['section']);
    $contact_number = trim($_POST['contact_number']);
    $status = trim($_POST['status']);
    $blood_type = trim($_POST['blood_type']);
    $allergies = trim($_POST['allergies']) ?: 'None reported';
    $medical_conditions = trim($_POST['medical_conditions']) ?: 'None reported';
    $current_medication = trim($_POST['current_medication']) ?: 'None';
    $emergency_contact_name = trim($_POST['emergency_contact_name']);
    $emergency_contact_number = trim($_POST['emergency_contact_number']);

    try {
        $update_stmt = $pdo->prepare("UPDATE students SET 
            student_id_code = ?, first_name = ?, middle_name = ?, last_name = ?, 
            dob = ?, sex = ?, course = ?, year_level = ?, section = ?, 
            contact_number = ?, status = ?, blood_type = ?, allergies = ?, 
            medical_conditions = ?, current_medication = ?, 
            emergency_contact_name = ?, emergency_contact_number = ? 
            WHERE id = ?");

        $update_stmt->execute([
            $student_id_code,
            $first_name,
            $middle_name,
            $last_name,
            $dob,
            $sex,
            $course,
            $year_level,
            $section,
            $contact_number,
            $status,
            $blood_type,
            $allergies,
            $medical_conditions,
            $current_medication,
            $emergency_contact_name,
            $emergency_contact_number,
            $student_id
        ]);

        header("Location: student-record.php?id=" . $student_id . "&updated=1");
        exit;
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $error = "Student ID Code already exists for another record!";
        } else {
            $error = "Failed to update record: " . $e->getMessage();
        }
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Student - <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></title>
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
            <a href="students.php" class="active"> <span>♙</span> Students </a>
            <a href="employee.php"> <span>♙</span> Employees </a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1>Edit Student Record</h1>
                <p>Update student health details and personal information</p>
            </div>

            <div class="user">
                <div class="user-icon">
                    <?= strtoupper(substr($_SESSION['full_name'], 0, 1)) ?>
                </div>
                <div>
                    <strong>
                        <?= htmlspecialchars($_SESSION['full_name']) ?>
                    </strong>
                    <small>
                        <?= htmlspecialchars($_SESSION['role']) ?>
                    </small>
                </div>
                <a href="logout.php"
                    style="margin-left: 15px; color: #ef4444; font-weight: bold; font-size: 13px; text-decoration: none;">Logout</a>
            </div>
        </header>

        <a href="student-record.php?id=<?= $student_id ?>" class="back-button"> ← Back to Student Profile </a>

        <?php if (!empty($error)): ?>
            <p
                style="color: red; background: #ffe6e6; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: bold;">
                <?= htmlspecialchars($error) ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="edit-student.php?id=<?= $student_id ?>"
            style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e5e7eb;">
            <h3>Personal Information</h3>
            <br>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <label
                        style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Student
                        ID Number *</label>
                    <input type="text" name="student_id_code"
                        value="<?= htmlspecialchars($student['student_id_code']) ?>" required
                        style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div>
                    <label style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">First
                        Name *</label>
                    <input type="text" name="first_name" value="<?= htmlspecialchars($student['first_name']) ?>"
                        required
                        style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div>
                    <label style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Middle
                        Name</label>
                    <input type="text" name="middle_name" value="<?= htmlspecialchars($student['middle_name']) ?>"
                        style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div>
                    <label style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Last
                        Name *</label>
                    <input type="text" name="last_name" value="<?= htmlspecialchars($student['last_name']) ?>" required
                        style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div>
                    <label style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Date
                        of Birth *</label>
                    <input type="date" name="dob" value="<?= htmlspecialchars($student['dob']) ?>" required
                        style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div>
                    <label style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Sex
                        *</label>
                    <select name="sex" required
                        style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
                        <option value="Male" <?= $student['sex'] === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= $student['sex'] === 'Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>
                <div>
                    <label style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Course
                        *</label>
                    <input type="text" name="course" value="<?= htmlspecialchars($student['course']) ?>" required
                        style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div>
                    <label style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Year
                        Level *</label>
                    <select name="year_level" required
                        style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
                        <option value="1st Year" <?= $student['year_level'] === '1st Year' ? 'selected' : '' ?>>1st Year
                        </option>
                        <option value="2nd Year" <?= $student['year_level'] === '2nd Year' ? 'selected' : '' ?>>2nd Year
                        </option>
                        <option value="3rd Year" <?= $student['year_level'] === '3rd Year' ? 'selected' : '' ?>>3rd Year
                        </option>
                        <option value="4th Year" <?= $student['year_level'] === '4th Year' ? 'selected' : '' ?>>4th Year
                        </option>
                    </select>
                </div>
                <div>
                    <label
                        style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Section
                        *</label>
                    <input type="text" name="section" value="<?= htmlspecialchars($student['section']) ?>" required
                        style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div>
                    <label
                        style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Contact
                        Number *</label>
                    <input type="text" name="contact_number" value="<?= htmlspecialchars($student['contact_number']) ?>"
                        required
                        style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div>
                    <label
                        style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Account
                        Status *</label>
                    <select name="status" required
                        style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
                        <option value="Active" <?= strtolower($student['status']) === 'active' || strtolower($student['status']) === 'normal' ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= strtolower($student['status']) === 'inactive' ? 'selected' : '' ?>>
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
                            <option value="<?= $bt ?>" <?= $student['blood_type'] === $bt ? 'selected' : '' ?>><?= $bt ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label
                        style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Allergies</label>
                    <input type="text" name="allergies" value="<?= htmlspecialchars($student['allergies']) ?>"
                        style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div>
                    <label
                        style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Medical
                        Conditions</label>
                    <input type="text" name="medical_conditions"
                        value="<?= htmlspecialchars($student['medical_conditions']) ?>"
                        style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div>
                    <label
                        style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Current
                        Medication</label>
                    <input type="text" name="current_medication"
                        value="<?= htmlspecialchars($student['current_medication']) ?>"
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
                        value="<?= htmlspecialchars($student['emergency_contact_name']) ?>" required
                        style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div>
                    <label
                        style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Contact
                        Number *</label>
                    <input type="text" name="emergency_contact_number"
                        value="<?= htmlspecialchars($student['emergency_contact_number']) ?>" required
                        style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
            </div>

            <br><br>
            <button type="submit"
                style="background: #2563eb; color: #fff; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">Update
                Student Record</button>
        </form>
    </main>
</body>

</html>