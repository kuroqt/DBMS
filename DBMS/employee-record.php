<?php
require_once 'db.php';
require_once 'auth.php';

// Get employee ID from URL (e.g. employee-record.php?id=1). Default to 1 if not provided.
$emp_id = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Fetch Employee Record from Database
try {
  $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = :id");
  $stmt->execute(['id' => $emp_id]);
  $employee = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  $employee = false;
}

// If employee not found, display message
if (!$employee) {
  die("<h2 style='padding: 20px; font-family: sans-serif; color: #1e293b;'>Employee record not found. <a href='employee.php' style='color: #2563eb;'>Go back</a></h2>");
}

// Fetch Consultations for this employee
try {
  $consult_stmt = $pdo->prepare("SELECT * FROM consultations WHERE employee_id = :id ORDER BY consultation_date DESC");
  $consult_stmt->execute(['id' => $emp_id]);
  $consultations = $consult_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  $consultations = [];
}

// Helper variables for UI
$firstName = $employee['first_name'] ?? '';
$lastName = $employee['last_name'] ?? '';
$title = $employee['title'] ?? '';

$initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
if (empty($initials)) {
  $initials = 'EMP';
}

$full_name = trim(($title ? $title . ' ' : '') . $firstName . ' ' . $lastName);
if (empty($full_name)) {
  $full_name = 'Employee Record';
}

$formatted_dob = (!empty($employee['dob']) && $employee['dob'] !== '0000-00-00')
  ? date('F j, Y', strtotime($employee['dob']))
  : 'N/A';
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Employee Medical Record - <?= htmlspecialchars($full_name) ?></title>
  <link rel="stylesheet" href="style.css?v=2" />
</head>

<body>

  <!-- SIDEBAR -->
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
      <a href="index.php"> <span class="nav-icon">⌂</span> Dashboard </a>
      <a href="students.php"> <span class="nav-icon">♙</span> Students </a>
      <a href="employee.php" class="active"> <span class="nav-icon">♟</span> Employees </a>
    </nav>
  </aside>

  <!-- MAIN CONTENT -->
  <main class="main-content">

    <!-- TOPBAR WITH LOGOUT BUTTON -->
    <header class="topbar">
      <!-- UPDATED TOPBAR SEARCH FORM -->
      <form class="top-search" action="search.php" method="GET" style="display: flex; align-items: center; gap: 8px;">
        <button type="submit"
          style="background: none; border: none; cursor: pointer; font-size: 16px; color: #64748b; padding: 0;">⌕</button>
        <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
          placeholder="Search student, employee, ID number..." required
          style="border: none; outline: none; background: transparent; width: 100%;">
      </form>

      <div class="user">
        <div class="user-icon">
          <?= strtoupper(substr($_SESSION['full_name'] ?? 'A', 0, 1)) ?>
        </div>

        <div class="user-information">
          <strong><?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?></strong>
          <small><?= htmlspecialchars($_SESSION['role'] ?? 'Administrator') ?></small>
        </div>

        <a href="logout.php"
          style="color: #ef4444; font-size: 13px; font-weight: 600; text-decoration: none; margin-left: 10px;">Logout</a>
      </div>
    </header>

    <!-- PAGE HEADING -->
    <section class="page-heading">
      <h1>Employee Medical Record</h1>
      <p>Employee information and medical history</p>
    </section>

    <!-- BACK BUTTON -->
    <div style="margin-bottom: 20px;">
      <a href="employee.php" style="color: #2563eb; text-decoration: none; font-size: 14px; font-weight: 600;">← Back to
        Employees</a>
    </div>

    <!-- PROFILE HERO HEADER -->
    <section class="profile-header"
      style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap: 20px; flex-wrap: wrap;">
      <div class="profile-info" style="display: flex; align-items: center; gap: 16px;">
        <div class="profile-icon"
          style="width: 56px; height: 56px; background: #dbeafe; color: #1d4ed8; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 20px;">
          <?= htmlspecialchars($initials) ?>
        </div>
        <div class="profile-details">
          <h2 style="margin: 0; font-size: 22px; font-weight: 700; color: #0f172a;"><?= htmlspecialchars($full_name) ?>
          </h2>
          <p style="margin: 4px 0 6px 0; color: #64748b; font-size: 14px;">Employee ID:
            <strong><?= htmlspecialchars($employee['emp_code'] ?? 'N/A') ?></strong>
          </p>
          <span class="status"
            style="background: <?= strtolower($employee['status'] ?? '') == 'active' ? '#dcfce7' : '#f1f5f9' ?>; color: <?= strtolower($employee['status'] ?? '') == 'active' ? '#15803d' : '#475569' ?>; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block;">
            <?= htmlspecialchars($employee['status'] ?? 'Active') ?>
          </span>
        </div>
      </div>

      <div class="profile-actions">
        <a href="edit-employee.php?id=<?= $emp_id ?>"
          style="background: #f1f5f9; color: #334155; text-decoration: none; padding: 8px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; border: 1px solid #cbd5e1; display: inline-block;">✎
          Edit Profile</a>
      </div>
    </section>

    <!-- RECORD GRID -->
    <section class="record-grid"
      style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">

      <!-- PERSONAL INFORMATION -->
      <div class="record-card"
        style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px;">
        <h3
          style="margin-top: 0; margin-bottom: 16px; font-size: 16px; color: #0f172a; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
          Personal Information</h3>
        <div class="info-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 14px;">
          <div>
            <label style="color: #64748b; display: block; font-size: 12px;">First Name</label>
            <p style="margin: 2px 0 0 0; font-weight: 600; color: #1e293b;">
              <?= htmlspecialchars($employee['first_name'] ?? 'N/A') ?>
            </p>
          </div>

          <div>
            <label style="color: #64748b; display: block; font-size: 12px;">Last Name</label>
            <p style="margin: 2px 0 0 0; font-weight: 600; color: #1e293b;">
              <?= htmlspecialchars($employee['last_name'] ?? 'N/A') ?>
            </p>
          </div>

          <div>
            <label style="color: #64748b; display: block; font-size: 12px;">Date of Birth</label>
            <p style="margin: 2px 0 0 0; font-weight: 600; color: #1e293b;"><?= htmlspecialchars($formatted_dob) ?></p>
          </div>

          <div>
            <label style="color: #64748b; display: block; font-size: 12px;">Sex</label>
            <p style="margin: 2px 0 0 0; font-weight: 600; color: #1e293b;">
              <?= htmlspecialchars($employee['sex'] ?? 'N/A') ?>
            </p>
          </div>

          <div>
            <label style="color: #64748b; display: block; font-size: 12px;">Position</label>
            <p style="margin: 2px 0 0 0; font-weight: 600; color: #1e293b;">
              <?= htmlspecialchars($employee['position'] ?? 'N/A') ?>
            </p>
          </div>

          <div>
            <label style="color: #64748b; display: block; font-size: 12px;">Department</label>
            <p style="margin: 2px 0 0 0; font-weight: 600; color: #1e293b;">
              <?= htmlspecialchars($employee['department'] ?? 'N/A') ?>
            </p>
          </div>

          <div style="grid-column: span 2;">
            <label style="color: #64748b; display: block; font-size: 12px;">Contact Number</label>
            <p style="margin: 2px 0 0 0; font-weight: 600; color: #1e293b;">
              <?= htmlspecialchars($employee['contact_number'] ?? 'N/A') ?>
            </p>
          </div>
        </div>
      </div>

      <!-- MEDICAL INFORMATION -->
      <div class="record-card"
        style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px;">
        <h3
          style="margin-top: 0; margin-bottom: 16px; font-size: 16px; color: #0f172a; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
          Medical Information</h3>

        <div class="info-grid" style="display: grid; grid-template-columns: 1fr; gap: 12px; font-size: 14px;">
          <div>
            <label style="color: #64748b; display: block; font-size: 12px;">Blood Type</label>
            <p style="margin: 2px 0 0 0; font-weight: 700; color: #ef4444; font-size: 16px;">
              <?= htmlspecialchars($employee['blood_type'] ?? 'N/A') ?>
            </p>
          </div>

          <div>
            <label style="color: #64748b; display: block; font-size: 12px;">Allergies</label>
            <p style="margin: 2px 0 0 0; font-weight: 600; color: #1e293b;">
              <?= htmlspecialchars($employee['allergies'] ?? 'None reported') ?>
            </p>
          </div>

          <div>
            <label style="color: #64748b; display: block; font-size: 12px;">Medical Conditions</label>
            <p style="margin: 2px 0 0 0; font-weight: 600; color: #1e293b;">
              <?= htmlspecialchars($employee['medical_conditions'] ?? 'None reported') ?>
            </p>
          </div>

          <div>
            <label style="color: #64748b; display: block; font-size: 12px;">Current Medication</label>
            <p style="margin: 2px 0 0 0; font-weight: 600; color: #1e293b;">
              <?= htmlspecialchars($employee['current_medication'] ?? 'None') ?>
            </p>
          </div>
        </div>
      </div>

      <!-- EMERGENCY CONTACT -->
      <div class="record-card"
        style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px; grid-column: 1 / -1;">
        <h3
          style="margin-top: 0; margin-bottom: 16px; font-size: 16px; color: #0f172a; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
          Emergency Contact</h3>

        <div class="info-grid"
          style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; font-size: 14px;">
          <div>
            <label style="color: #64748b; display: block; font-size: 12px;">Name</label>
            <p style="margin: 2px 0 0 0; font-weight: 600; color: #1e293b;">
              <?= htmlspecialchars($employee['emergency_contact_name'] ?? 'N/A') ?>
            </p>
          </div>

          <div>
            <label style="color: #64748b; display: block; font-size: 12px;">Contact Number</label>
            <p style="margin: 2px 0 0 0; font-weight: 600; color: #1e293b;">
              <?= htmlspecialchars($employee['emergency_contact_number'] ?? 'N/A') ?>
            </p>
          </div>
        </div>
      </div>

      <!-- MEDICAL CONSULTATION HISTORY -->
      <div class="record-card"
        style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px; grid-column: 1 / -1;">
        <h3
          style="margin-top: 0; margin-bottom: 16px; font-size: 16px; color: #0f172a; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
          Medical Consultation History</h3>

        <?php if (!empty($consultations)): ?>
          <div style="display: flex; flex-direction: column; gap: 16px;">
            <?php foreach ($consultations as $c): ?>
              <div class="consultation"
                style="background: #f8fafc; border-left: 4px solid #2563eb; border-radius: 6px; padding: 14px 16px;">
                <strong style="color: #0f172a; font-size: 14px; display: block; margin-bottom: 6px;">
                  <?= date('F j, Y', strtotime($c['consultation_date'])) ?>
                </strong>
                <p style="margin: 4px 0; color: #334155; font-size: 13px;"><strong>Chief Complaint:</strong>
                  <?= htmlspecialchars($c['chief_complaint'] ?? 'N/A') ?>
                </p>
                <p style="margin: 4px 0; color: #334155; font-size: 13px;"><strong>Diagnosis:</strong>
                  <?= htmlspecialchars($c['diagnosis'] ?? 'N/A') ?>
                </p>
                <p style="margin: 4px 0; color: #334155; font-size: 13px;"><strong>Remarks:</strong>
                  <?= htmlspecialchars($c['remarks'] ?? 'N/A') ?>
                </p>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p style="color: #64748b; margin: 0; font-size: 14px;">No consultation history found.</p>
        <?php endif; ?>
      </div>

    </section>
  </main>
</body>

</html>