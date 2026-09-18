<?php
require_once 'db.php';
require_once 'auth.php';

// Get employee ID from URL (e.g. employee-record.php?id=1). Default to 1 if not provided.
$emp_id = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Fetch Employee Record from Database
$stmt = $pdo->prepare("SELECT * FROM employees WHERE id = :id");
$stmt->execute(['id' => $emp_id]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

// If employee not found, display message
if (!$employee) {
  die("<h2>Employee record not found.</h2>");
}

// Fetch Consultations for this employee
$consult_stmt = $pdo->prepare("SELECT * FROM consultations WHERE employee_id = :id ORDER BY consultation_date DESC");
$consult_stmt->execute(['id' => $emp_id]);
$consultations = $consult_stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper variables for UI
$initials = strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1));
$full_name = trim($employee['title'] . ' ' . $employee['first_name'] . ' ' . $employee['last_name']);
$formatted_dob = $employee['dob'] ? date('F j, Y', strtotime($employee['dob'])) : 'N/A';
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Employee Medical Record - <?= htmlspecialchars($full_name) ?></title>
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
        <h1>Employee Medical Record</h1>
        <p>Employee information and medical history</p>
      </div>
    </header>

    <a href="employee.php" class="back-button"> ← Back to Employees </a>

    <section class="profile-header">
      <div class="profile-info">
        <div class="profile-icon"><?= htmlspecialchars($initials) ?></div>
        <div class="profile-details">
          <h2><?= htmlspecialchars($full_name) ?></h2>
          <p>Employee ID: <?= htmlspecialchars($employee['emp_code']) ?></p>
          <span class="status <?= strtolower($employee['status']) == 'active' ? 'normal' : '' ?>">
            <?= htmlspecialchars($employee['status']) ?>
          </span>
        </div>
      </div>

      <div class="profile-actions">
        <a href="edit-employee.php?id=<?= $emp_id ?>" class="primary-button">✎ Edit Profile</a>
      </div>
    </section>

    <section class="record-grid">
      <div class="record-card">
        <h3>Personal Information</h3>
        <div class="info-grid">
          <div>
            <label>First Name</label>
            <p><?= htmlspecialchars($employee['first_name']) ?></p>
          </div>

          <div>
            <label>Last Name</label>
            <p><?= htmlspecialchars($employee['last_name']) ?></p>
          </div>

          <div>
            <label>Date of Birth</label>
            <p><?= htmlspecialchars($formatted_dob) ?></p>
          </div>

          <div>
            <label>Sex</label>
            <p><?= htmlspecialchars($employee['sex']) ?></p>
          </div>

          <div>
            <label>Position</label>
            <p><?= htmlspecialchars($employee['position']) ?></p>
          </div>

          <div>
            <label>Department</label>
            <p><?= htmlspecialchars($employee['department']) ?></p>
          </div>

          <div>
            <label>Contact Number</label>
            <p><?= htmlspecialchars($employee['contact_number']) ?></p>
          </div>
        </div>
      </div>

      <div class="record-card">
        <h3>Medical Information</h3>

        <div class="info-grid">
          <div>
            <label>Blood Type</label>
            <p><?= htmlspecialchars($employee['blood_type']) ?></p>
          </div>

          <div>
            <label>Allergies</label>
            <p><?= htmlspecialchars($employee['allergies']) ?></p>
          </div>

          <div>
            <label>Medical Conditions</label>
            <p><?= htmlspecialchars($employee['medical_conditions']) ?></p>
          </div>

          <div>
            <label>Current Medication</label>
            <p><?= htmlspecialchars($employee['current_medication']) ?></p>
          </div>
        </div>
      </div>

      <div class="record-card">
        <h3>Emergency Contact</h3>

        <div class="info-grid">
          <div>
            <label>Name</label>
            <p><?= htmlspecialchars($employee['emergency_contact_name']) ?></p>
          </div>

          <div>
            <label>Contact Number</label>
            <p><?= htmlspecialchars($employee['emergency_contact_number']) ?></p>
          </div>
        </div>
      </div>

      <div class="record-card">
        <h3>Medical Consultation History</h3>

        <?php if (!empty($consultations)): ?>
          <?php foreach ($consultations as $c): ?>
            <div class="consultation">
              <strong><?= date('F j, Y', strtotime($c['consultation_date'])) ?></strong>
              <p>Chief Complaint: <?= htmlspecialchars($c['chief_complaint']) ?></p>
              <p>Diagnosis: <?= htmlspecialchars($c['diagnosis']) ?></p>
              <p>Remarks: <?= htmlspecialchars($c['remarks']) ?></p>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p>No consultation history found.</p>
        <?php endif; ?>
      </div>
    </section>
  </main>
</body>

</html>