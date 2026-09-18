<?php
require_once 'db.php';
require_once 'auth.php';

$error = '';

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
    $blood_type = trim($_POST['blood_type']);
    $allergies = trim($_POST['allergies']) ?: 'None reported';
    $medical_conditions = trim($_POST['medical_conditions']) ?: 'None reported';
    $current_medication = trim($_POST['current_medication']) ?: 'None';
    $emergency_contact_name = trim($_POST['emergency_contact_name']);
    $emergency_contact_number = trim($_POST['emergency_contact_number']);

    try {
        $stmt = $pdo->prepare("INSERT INTO students (
            student_id_code, first_name, middle_name, last_name, dob, sex, course, year_level, 
            section, contact_number, status, blood_type, allergies, medical_conditions, 
            current_medication, emergency_contact_name, emergency_contact_number
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
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
            $blood_type,
            $allergies,
            $medical_conditions,
            $current_medication,
            $emergency_contact_name,
            $emergency_contact_number
        ]);

        header("Location: students.php");
        exit;
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $error = "Student ID Code already exists in the system!";
        } else {
            $error = "Failed to add student: " . $e->getMessage();
        }
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Add New Student - Medical System</title>
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
                <h1>Add New Student Record</h1>
                <p>Fill out the form below to register a new student</p>
            </div>
        </header>

        <a href="students.php" class="back-button"> ← Back to Students </a>

        <?php if (!empty($error)): ?>
            <p style="color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <?= htmlspecialchars($error) ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="add-student.php" style="background: #fff; padding: 25px; border-radius: 8px;">
            <h3>Personal Information</h3>
            <br>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <label>Student ID Number *</label>
                    <input type="text" name="student_id_code" required placeholder="e.g. 2021-2287611"
                        style="width: 100%; padding: 8px; margin-top: 5px;">
                </div>
                <div>
                    <label>First Name *</label>
                    <input type="text" name="first_name" required style="width: 100%; padding: 8px; margin-top: 5px;">
                </div>
                <div>
                    <label>Middle Name</label>
                    <input type="text" name="middle_name" style="width: 100%; padding: 8px; margin-top: 5px;">
                </div>
                <div>
                    <label>Last Name *</label>
                    <input type="text" name="last_name" required style="width: 100%; padding: 8px; margin-top: 5px;">
                </div>
                <div>
                    <label>Date of Birth *</label>
                    <input type="date" name="dob" required style="width: 100%; padding: 8px; margin-top: 5px;">
                </div>
                <div>
                    <label>Sex *</label>
                    <select name="sex" required style="width: 100%; padding: 8px; margin-top: 5px;">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div>
                    <label>Course *</label>
                    <input type="text" name="course" required placeholder="e.g. BS Computer Engineering"
                        style="width: 100%; padding: 8px; margin-top: 5px;">
                </div>
                <div>
                    <label>Year Level *</label>
                    <select name="year_level" required style="width: 100%; padding: 8px; margin-top: 5px;">
                        <option value="1st Year">1st Year</option>
                        <option value="2nd Year">2nd Year</option>
                        <option value="3rd Year">3rd Year</option>
                        <option value="4th Year">4th Year</option>
                    </select>
                </div>
                <div>
                    <label>Section *</label>
                    <input type="text" name="section" required placeholder="e.g. A"
                        style="width: 100%; padding: 8px; margin-top: 5px;">
                </div>
                <div>
                    <label>Contact Number *</label>
                    <input type="text" name="contact_number" required
                        style="width: 100%; padding: 8px; margin-top: 5px;">
                </div>
            </div>

            <hr style="margin: 25px 0;">

            <h3>Medical Details</h3>
            <br>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <label>Blood Type</label>
                    <select name="blood_type" style="width: 100%; padding: 8px; margin-top: 5px;">
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="O+" selected>O+</option>
                        <option value="O-">O-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                    </select>
                </div>
                <div>
                    <label>Allergies</label>
                    <input type="text" name="allergies" placeholder="None reported"
                        style="width: 100%; padding: 8px; margin-top: 5px;">
                </div>
                <div>
                    <label>Medical Conditions</label>
                    <input type="text" name="medical_conditions" placeholder="None reported"
                        style="width: 100%; padding: 8px; margin-top: 5px;">
                </div>
                <div>
                    <label>Current Medication</label>
                    <input type="text" name="current_medication" placeholder="None"
                        style="width: 100%; padding: 8px; margin-top: 5px;">
                </div>
            </div>

            <hr style="margin: 25px 0;">

            <h3>Emergency Contact</h3>
            <br>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <label>Contact Name *</label>
                    <input type="text" name="emergency_contact_name" required
                        style="width: 100%; padding: 8px; margin-top: 5px;">
                </div>
                <div>
                    <label>Contact Number *</label>
                    <input type="text" name="emergency_contact_number" required
                        style="width: 100%; padding: 8px; margin-top: 5px;">
                </div>
            </div>

            <br><br>
            <button type="submit"
                style="background: #2563eb; color: #fff; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">Save
                Student Record</button>
        </form>
    </main>
</body>

</html>