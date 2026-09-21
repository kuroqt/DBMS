<?php
require_once 'db.php';
require_once 'auth.php';

// Ensure table & columns exist for vital signs & treatment
try {
  $pdo->exec("CREATE TABLE IF NOT EXISTS student_consultations (
      id INT AUTO_INCREMENT PRIMARY KEY,
      student_id INT NOT NULL,
      consultation_date DATE NOT NULL,
      vital_signs VARCHAR(100) DEFAULT '',
      chief_complaint TEXT,
      treatment TEXT,
      diagnosis TEXT,
      remarks TEXT,
      FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
  )");

  try {
    $pdo->exec("ALTER TABLE student_consultations ADD COLUMN vital_signs VARCHAR(100) DEFAULT ''");
  } catch (PDOException $e) {
  }

  try {
    $pdo->exec("ALTER TABLE student_consultations ADD COLUMN treatment TEXT");
  } catch (PDOException $e) {
  }
} catch (Exception $e) {
  // DB initialization fallback
}

// Get student ID from URL
$student_db_id = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Handle New Consultation Form Submission
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_consultation') {
  $visit_date = !empty($_POST['consultation_date']) ? $_POST['consultation_date'] : date('Y-m-d');
  $vital_signs = trim($_POST['vital_signs'] ?? '');
  $chief_complaint = trim($_POST['chief_complaint'] ?? '');
  $treatment = trim($_POST['treatment'] ?? '');
  $diagnosis = trim($_POST['diagnosis'] ?? '');
  $remarks = trim($_POST['remarks'] ?? '');

  if (!empty($chief_complaint)) {
    try {
      $stmt_add = $pdo->prepare("INSERT INTO student_consultations 
            (student_id, consultation_date, vital_signs, chief_complaint, treatment, diagnosis, remarks) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
      $stmt_add->execute([$student_db_id, $visit_date, $vital_signs, $chief_complaint, $treatment, $diagnosis, $remarks]);
      $success_msg = "Clinic visit recorded successfully!";
    } catch (PDOException $e) {
      $error_msg = "Failed to record visit: " . $e->getMessage();
    }
  } else {
    $error_msg = "Chief Complaint is required.";
  }
}

// Fetch Student Record
try {
  $stmt = $pdo->prepare("SELECT * FROM students WHERE id = :id");
  $stmt->execute(['id' => $student_db_id]);
  $student = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  $student = false;
}

if (!$student) {
  die("<h2 style='padding: 40px; font-family: sans-serif; color: #1e293b;'>Student record not found. <a href='students.php' style='color: #2563eb;'>Back to Student List</a></h2>");
}

// Fetch Consultations
try {
  $consult_stmt = $pdo->prepare("SELECT * FROM student_consultations WHERE student_id = :id ORDER BY consultation_date DESC, id DESC");
  $consult_stmt->execute(['id' => $student_db_id]);
  $consultations = $consult_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  $consultations = [];
}

// UI Helpers
$firstName = $student['first_name'] ?? '';
$middleName = $student['middle_name'] ?? '';
$lastName = $student['last_name'] ?? '';

$initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
if (empty($initials)) {
  $initials = 'STU';
}

$full_name = trim($firstName . ' ' . ($middleName ? $middleName . ' ' : '') . $lastName);
if (empty($full_name)) {
  $full_name = 'Student Record';
}

$formatted_dob = (!empty($student['dob']) && $student['dob'] !== '0000-00-00')
  ? date('F j, Y', strtotime($student['dob']))
  : 'N/A';
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Student Medical Record - <?= htmlspecialchars($full_name) ?></title>
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
      <a href="students.php" class="active"> <span class="nav-icon">♙</span> Students </a>
      <a href="employee.php"> <span class="nav-icon">♟</span> Employees </a>
    </nav>
  </aside>

  <!-- MAIN CONTENT -->
  <main class="main-content">

    <!-- TOPBAR WITH SEARCH & LOGOUT -->
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
      <h1>Student Medical Record</h1>
      <p>Student health details, visit records, and consultation history</p>
    </section>

    <!-- BACK BUTTON -->
    <div style="margin-bottom: 20px;">
      <a href="students.php" style="color: #2563eb; text-decoration: none; font-size: 14px; font-weight: 600;">← Back to
        Students</a>
    </div>

    <!-- FLASH MESSAGES -->
    <?php if (!empty($success_msg)): ?>
      <div
        style="background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; font-size: 14px;">
        ✓ <?= htmlspecialchars($success_msg) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
      <div
        style="background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; font-size: 14px;">
        ⚠ <?= htmlspecialchars($error_msg) ?>
      </div>
    <?php endif; ?>

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
          <p style="margin: 4px 0 6px 0; color: #64748b; font-size: 14px;">Student ID:
            <strong><?= htmlspecialchars($student['student_id_code'] ?? 'N/A') ?></strong>
          </p>
          <span class="status"
            style="background: <?= (strtolower($student['status'] ?? '') == 'active' || strtolower($student['status'] ?? '') == 'normal') ? '#dcfce7' : '#f1f5f9' ?>; color: <?= (strtolower($student['status'] ?? '') == 'active' || strtolower($student['status'] ?? '') == 'normal') ? '#15803d' : '#475569' ?>; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block;">
            <?= htmlspecialchars($student['status'] ?? 'Active') ?>
          </span>
        </div>
      </div>

      <div class="profile-actions">
        <a href="edit-student.php?id=<?= $student_db_id ?>"
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
              <?= htmlspecialchars($student['first_name'] ?? 'N/A') ?>
            </p>
          </div>

          <div>
            <label style="color: #64748b; display: block; font-size: 12px;">Last Name</label>
            <p style="margin: 2px 0 0 0; font-weight: 600; color: #1e293b;">
              <?= htmlspecialchars($student['last_name'] ?? 'N/A') ?>
            </p>
          </div>

          <div>
            <label style="color: #64748b; display: block; font-size: 12px;">Date of Birth</label>
            <p style="margin: 2px 0 0 0; font-weight: 600; color: #1e293b;"><?= htmlspecialchars($formatted_dob) ?></p>
          </div>

          <div>
            <label style="color: #64748b; display: block; font-size: 12px;">Sex</label>
            <p style="margin: 2px 0 0 0; font-weight: 600; color: #1e293b;">
              <?= htmlspecialchars($student['sex'] ?? 'N/A') ?>
            </p>
          </div>

          <div>
            <label style="color: #64748b; display: block; font-size: 12px;">Course</label>
            <p style="margin: 2px 0 0 0; font-weight: 600; color: #1e293b;">
              <?= htmlspecialchars($student['course'] ?? 'N/A') ?>
            </p>
          </div>

          <div>
            <label style="color: #64748b; display: block; font-size: 12px;">Year Level</label>
            <p style="margin: 2px 0 0 0; font-weight: 600; color: #1e293b;">
              <?= htmlspecialchars($student['year_level'] ?? 'N/A') ?>
            </p>
          </div>

          <div>
            <label style="color: #64748b; display: block; font-size: 12px;">Section</label>
            <p style="margin: 2px 0 0 0; font-weight: 600; color: #1e293b;">
              <?= htmlspecialchars($student['section'] ?? 'N/A') ?>
            </p>
          </div>

          <div>
            <label style="color: #64748b; display: block; font-size: 12px;">Contact Number</label>
            <p style="margin: 2px 0 0 0; font-weight: 600; color: #1e293b;">
              <?= htmlspecialchars($student['contact_number'] ?? 'N/A') ?>
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
              <?= htmlspecialchars($student['blood_type'] ?? 'N/A') ?>
            </p>
          </div>

          <div>
            <label style="color: #64748b; display: block; font-size: 12px;">Allergies</label>
            <p style="margin: 2px 0 0 0; font-weight: 600; color: #1e293b;">
              <?= htmlspecialchars($student['allergies'] ?? 'None reported') ?>
            </p>
          </div>

          <div>
            <label style="color: #64748b; display: block; font-size: 12px;">Medical Conditions</label>
            <p style="margin: 2px 0 0 0; font-weight: 600; color: #1e293b;">
              <?= htmlspecialchars($student['medical_conditions'] ?? 'None reported') ?>
            </p>
          </div>

          <div>
            <label style="color: #64748b; display: block; font-size: 12px;">Current Medication</label>
            <p style="margin: 2px 0 0 0; font-weight: 600; color: #1e293b;">
              <?= htmlspecialchars($student['current_medication'] ?? 'None') ?>
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
              <?= htmlspecialchars($student['emergency_contact_name'] ?? 'N/A') ?>
            </p>
          </div>

          <div>
            <label style="color: #64748b; display: block; font-size: 12px;">Contact Number</label>
            <p style="margin: 2px 0 0 0; font-weight: 600; color: #1e293b;">
              <?= htmlspecialchars($student['emergency_contact_number'] ?? 'N/A') ?>
            </p>
          </div>
        </div>
      </div>

      <!-- RECORD NEW CLINIC VISIT FORM -->
      <div class="record-card"
        style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px; grid-column: 1 / -1;">
        <h3
          style="margin-top: 0; margin-bottom: 16px; font-size: 16px; color: #0f172a; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
          + Record New Clinic Visit</h3>

        <form method="POST" action="student-record.php?id=<?= $student_db_id ?>">
          <input type="hidden" name="action" value="add_consultation">

          <div
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 16px;">
            <div>
              <label
                style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; display: block;">Visit
                Date *</label>
              <input type="date" name="consultation_date" value="<?= date('Y-m-d') ?>" required
                style="width: 100%; padding: 9px 12px; margin-top: 6px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
            </div>
            <div>
              <label
                style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; display: block;">Vital
                Signs (BP / Temp / HR)</label>
              <input type="text" name="vital_signs" placeholder="e.g. BP: 120/80, Temp: 36.6°C, HR: 72 bpm"
                style="width: 100%; padding: 9px 12px; margin-top: 6px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
            </div>
            <div>
              <label
                style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; display: block;">Chief
                Complaint *</label>
              <input type="text" name="chief_complaint" required placeholder="e.g. Fever, Headache, Stomachache"
                style="width: 100%; padding: 9px 12px; margin-top: 6px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
            </div>
          </div>

          <div
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 20px;">
            <div>
              <label
                style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; display: block;">Treatment
                / Medication Provided</label>
              <input type="text" name="treatment" placeholder="e.g. Paracetamol 500mg given"
                style="width: 100%; padding: 9px 12px; margin-top: 6px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
            </div>
            <div>
              <label
                style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; display: block;">Diagnosis</label>
              <input type="text" name="diagnosis" placeholder="e.g. Mild Hyperthermia / Fatigue"
                style="width: 100%; padding: 9px 12px; margin-top: 6px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
            </div>
            <div>
              <label
                style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; display: block;">Remarks
                / Advice</label>
              <input type="text" name="remarks" placeholder="e.g. Advised to rest in clinic for 30 mins"
                style="width: 100%; padding: 9px 12px; margin-top: 6px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
            </div>
          </div>

          <button type="submit"
            style="background: #2563eb; color: white; border: none; padding: 10px 22px; border-radius: 6px; font-weight: 600; font-size: 14px; cursor: pointer; display: inline-block;">
            Save Visit Record
          </button>
        </form>
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

                <?php if (!empty($c['vital_signs'])): ?>
                  <p style="margin: 4px 0; color: #334155; font-size: 13px;"><strong>Vital Signs:</strong>
                    <?= htmlspecialchars($c['vital_signs']) ?>
                  </p>
                <?php endif; ?>

                <p style="margin: 4px 0; color: #334155; font-size: 13px;"><strong>Chief Complaint:</strong>
                  <?= htmlspecialchars($c['chief_complaint']) ?>
                </p>

                <?php if (!empty($c['treatment'])): ?>
                  <p style="margin: 4px 0; color: #334155; font-size: 13px;"><strong>Treatment:</strong>
                    <?= htmlspecialchars($c['treatment']) ?>
                  </p>
                <?php endif; ?>

                <p style="margin: 4px 0; color: #334155; font-size: 13px;"><strong>Diagnosis:</strong>
                  <?= htmlspecialchars($c['diagnosis'] ?: 'N/A') ?>
                </p>
                <p style="margin: 4px 0; color: #334155; font-size: 13px;"><strong>Remarks:</strong>
                  <?= htmlspecialchars($c['remarks'] ?: 'N/A') ?>
                </p>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p style="color: #64748b; margin: 0; font-size: 14px;">No consultation history found for this student.</p>
        <?php endif; ?>
      </div>

    </section>
  </main>
</body>

</html>