<?php
require_once 'db.php';
require_once 'auth.php';

$error = '';

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
  $blood_type = trim($_POST['blood_type']);
  $allergies = trim($_POST['allergies']) ?: 'None reported';
  $medical_conditions = trim($_POST['medical_conditions']) ?: 'None reported';
  $current_medication = trim($_POST['current_medication']) ?: 'None';
  $emergency_contact_name = trim($_POST['emergency_contact_name']);
  $emergency_contact_number = trim($_POST['emergency_contact_number']);

  try {
    $stmt = $pdo->prepare("INSERT INTO employees (
            emp_code, title, first_name, last_name, dob, sex, position, 
            department, contact_number, status, blood_type, allergies, 
            medical_conditions, current_medication, emergency_contact_name, emergency_contact_number
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
      $emp_code,
      $title,
      $first_name,
      $last_name,
      $dob,
      $sex,
      $position,
      $department,
      $contact_number,
      $blood_type,
      $allergies,
      $medical_conditions,
      $current_medication,
      $emergency_contact_name,
      $emergency_contact_number
    ]);

    header("Location: employee.php");
    exit;
  } catch (PDOException $e) {
    if ($e->getCode() == 23000) {
      $error = "Employee ID Code already exists in the system!";
    } else {
      $error = "Failed to add employee: " . $e->getMessage();
    }
  }
}
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Add New Employee - Medical System</title>
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
        <h1>Add New Employee Record</h1>
        <p>Fill out the form below to register a new employee</p>
      </div>
    </header>
    <div style="margin-bottom: 20px;">
      <a href="employee.php" style="color: #2563eb; text-decoration: none; font-size: 14px; font-weight: 600;">← Back to
        Employee</a>
    </div>


    <?php if (!empty($error)): ?>
      <p style="color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
        <?= htmlspecialchars($error) ?>
      </p>
    <?php endif; ?>

    <form method="POST" action="add-employee.php" style="background: #fff; padding: 25px; border-radius: 8px;">
      <h3>Personal Information</h3>
      <br>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        <div>
          <label>Employee ID Code *</label>
          <input type="text" name="emp_code" required placeholder="e.g. EMP-0002"
            style="width: 100%; padding: 8px; margin-top: 5px;">
        </div>
        <div>
          <label>Title</label>
          <input type="text" name="title" placeholder="e.g. Engr. / Dr. / Mr."
            style="width: 100%; padding: 8px; margin-top: 5px;">
        </div>
        <div>
          <label>First Name *</label>
          <input type="text" name="first_name" required style="width: 100%; padding: 8px; margin-top: 5px;">
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
          <label>Position *</label>
          <input type="text" name="position" required placeholder="e.g. Instructor"
            style="width: 100%; padding: 8px; margin-top: 5px;">
        </div>
        <div>
          <label>Department *</label>
          <input type="text" name="department" required placeholder="e.g. CSE Department"
            style="width: 100%; padding: 8px; margin-top: 5px;">
        </div>
        <div>
          <label>Contact Number *</label>
          <input type="text" name="contact_number" required style="width: 100%; padding: 8px; margin-top: 5px;">
        </div>
      </div>

      <hr style="margin: 25px 0;">

      <h3>Medical Details</h3>
      <br>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        <div>
          <label>Blood Type</label>
          <select name="blood_type" style="width: 100%; padding: 8px; margin-top: 5px;">
            <option value="A+" selected>A+</option>
            <option value="A-">A-</option>
            <option value="B+">B+</option>
            <option value="B-">B-</option>
            <option value="O+">O+</option>
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
          <input type="text" name="emergency_contact_name" required style="width: 100%; padding: 8px; margin-top: 5px;">
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
        Employee Record</button>
    </form>
  </main>
</body>

</html>