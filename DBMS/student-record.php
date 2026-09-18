<?php
require_once 'db.php';
require_once 'auth.php';

// Ensure table & columns exist for vital signs & treatment
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

// Safely add vital_signs and treatment columns if missing from earlier table creation
try {
  $pdo->exec("ALTER TABLE student_consultations ADD COLUMN vital_signs VARCHAR(100) DEFAULT ''");
} catch (PDOException $e) {
}
try {
  $pdo->exec("ALTER TABLE student_consultations ADD COLUMN treatment TEXT");
} catch (PDOException $e) {
}

// Get student ID from URL
$student_db_id = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Handle New Consultation Form Submission
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_consultation') {
  $visit_date = $_POST['consultation_date'] ?: date('Y-m-d');
  $vital_signs = trim($_POST['vital_signs']);
  $chief_complaint = trim($_POST['chief_complaint']);
  $treatment = trim($_POST['treatment']);
  $diagnosis = trim($_POST['diagnosis']);
  $remarks = trim($_POST['remarks']);

  if (!empty($chief_complaint)) {
    $stmt_add = $pdo->prepare("INSERT INTO student_consultations 
            (student_id, consultation_date, vital_signs, chief_complaint, treatment, diagnosis, remarks) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt_add->execute([$student_db_id, $visit_date, $vital_signs, $chief_complaint, $treatment, $diagnosis, $remarks]);
    $success_msg = "Clinic visit recorded successfully!";
  } else {
    $error_msg = "Chief Complaint is required.";
  }
}

// Fetch Student Record
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = :id");
$stmt->execute(['id' => $student_db_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
  die("<h2 style='padding: 40px; font-family: sans-serif;'>Student record not found. <a href='students.php'>Back to Student List</a></h2>");
}

// Fetch Consultations
$consult_stmt = $pdo->prepare("SELECT * FROM student_consultations WHERE student_id = :id ORDER BY consultation_date DESC, id DESC");
$consult_stmt->execute(['id' => $student_db_id]);
$consultations = $consult_stmt->fetchAll(PDO::FETCH_ASSOC);

// UI Helpers
$initials = strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1));
$full_name = trim($student['first_name'] . ' ' . ($student['middle_name'] ? $student['middle_name'] . ' ' : '') . $student['last_name']);
$formatted_dob = $student['dob'] ? date('F j, Y', strtotime($student['dob'])) : 'N/A';
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Student Medical Record - <?= htmlspecialchars($full_name) ?></title>
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
        <h1>Student Medical Record</h1>
        <p>Student health details, visit records, and consultation history</p>
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

    <a href="students.php" class="back-button"> ← Back to Students </a>

    <?php if (!empty($success_msg)): ?>
      <div
        style="background: #dcfce7; color: #15803d; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: bold;">
        <?= htmlspecialchars($success_msg) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
      <div
        style="background: #fee2e2; color: #b91c1c; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: bold;">
        <?= htmlspecialchars($error_msg) ?>
      </div>
    <?php endif; ?>

    <!-- Mobile-optimized profile header -->
    <section class="profile-header">
      <div class="profile-info">
        <div class="profile-icon"><?= htmlspecialchars($initials) ?></div>
        <div class="profile-details">
          <h2><?= htmlspecialchars($full_name) ?></h2>
          <p>Student ID: <?= htmlspecialchars($student['student_id_code']) ?></p>
          <span
            class="status <?= (strtolower($student['status']) == 'active' || strtolower($student['status']) == 'normal') ? 'normal' : '' ?>">
            <?= htmlspecialchars($student['status']) ?>
          </span>
        </div>
      </div>

      <div class="profile-actions">
        <a href="edit-student.php?id=<?= $student_db_id ?>" class="primary-button">✎ Edit Profile</a>
      </div>
    </section>

    <section class="record-grid">
      <!-- Personal Information -->
      <div class="record-card">
        <h3>Personal Information</h3>
        <div class="info-grid">
          <div>
            <label>First Name</label>
            <p><?= htmlspecialchars($student['first_name']) ?></p>
          </div>

          <div>
            <label>Last Name</label>
            <p><?= htmlspecialchars($student['last_name']) ?></p>
          </div>

          <div>
            <label>Date of Birth</label>
            <p><?= htmlspecialchars($formatted_dob) ?></p>
          </div>

          <div>
            <label>Sex</label>
            <p><?= htmlspecialchars($student['sex']) ?></p>
          </div>

          <div>
            <label>Course</label>
            <p><?= htmlspecialchars($student['course']) ?></p>
          </div>

          <div>
            <label>Year Level</label>
            <p><?= htmlspecialchars($student['year_level']) ?></p>
          </div>

          <div>
            <label>Section</label>
            <p><?= htmlspecialchars($student['section']) ?></p>
          </div>

          <div>
            <label>Contact Number</label>
            <p><?= htmlspecialchars($student['contact_number']) ?></p>
          </div>
        </div>
      </div>

      <!-- Medical Information -->
      <div class="record-card">
        <h3>Medical Information</h3>
        <div class="info-grid">
          <div>
            <label>Blood Type</label>
            <p><?= htmlspecialchars($student['blood_type']) ?></p>
          </div>

          <div>
            <label>Allergies</label>
            <p><?= htmlspecialchars($student['allergies']) ?></p>
          </div>

          <div>
            <label>Medical Conditions</label>
            <p><?= htmlspecialchars($student['medical_conditions']) ?></p>
          </div>

          <div>
            <label>Current Medication</label>
            <p><?= htmlspecialchars($student['current_medication']) ?></p>
          </div>
        </div>
      </div>

      <!-- Emergency Contact -->
      <div class="record-card">
        <h3>Emergency Contact</h3>
        <div class="info-grid">
          <div>
            <label>Name</label>
            <p><?= htmlspecialchars($student['emergency_contact_name']) ?></p>
          </div>

          <div>
            <label>Contact Number</label>
            <p><?= htmlspecialchars($student['emergency_contact_number']) ?></p>
          </div>
        </div>
      </div>

      <!-- Record New Visit Form -->
      <div class="record-card" style="grid-column: span 2;">
        <h3>+ Record New Clinic Visit</h3>
        <form method="POST" action="student-record.php?id=<?= $student_db_id ?>">
          <input type="hidden" name="action" value="add_consultation">

          <div
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
            <div>
              <label style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Visit Date
                *</label>
              <input type="date" name="consultation_date" value="<?= date('Y-m-d') ?>" required
                style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
            </div>
            <div>
              <label style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Vital Signs
                (BP / Temp / HR)</label>
              <input type="text" name="vital_signs" placeholder="e.g. BP: 120/80, Temp: 36.6°C, HR: 72 bpm"
                style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
            </div>
            <div>
              <label style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Chief
                Complaint *</label>
              <input type="text" name="chief_complaint" required placeholder="e.g. Fever, Headache, Stomachache"
                style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
            </div>
          </div>

          <div
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
            <div>
              <label style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Treatment /
                Medication Provided</label>
              <input type="text" name="treatment" placeholder="e.g. Paracetamol 500mg given"
                style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
            </div>
            <div>
              <label
                style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Diagnosis</label>
              <input type="text" name="diagnosis" placeholder="e.g. Mild Hyperthermia / Fatigue"
                style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
            </div>
            <div>
              <label style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Remarks /
                Advice</label>
              <input type="text" name="remarks" placeholder="e.g. Advised to rest in clinic for 30 mins"
                style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px;">
            </div>
          </div>

          <button type="submit"
            style="background: #2563eb; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer;">Save
            Visit Record</button>
        </form>
      </div>

      <!-- Medical Consultation History -->
      <div class="record-card" style="grid-column: span 2;">
        <h3>Medical Consultation History</h3>

        <?php if (!empty($consultations)): ?>
          <?php foreach ($consultations as $c): ?>
            <div class="consultation" style="margin-bottom: 18px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9;">
              <strong
                style="color: #2563eb; font-size: 14px;"><?= date('F j, Y', strtotime($c['consultation_date'])) ?></strong>

              <?php if (!empty($c['vital_signs'])): ?>
                <p><strong>Vital Signs:</strong> <?= htmlspecialchars($c['vital_signs']) ?></p>
              <?php endif; ?>

              <p><strong>Chief Complaint:</strong> <?= htmlspecialchars($c['chief_complaint']) ?></p>

              <?php if (!empty($c['treatment'])): ?>
                <p><strong>Treatment:</strong> <?= htmlspecialchars($c['treatment']) ?></p>
              <?php endif; ?>

              <p><strong>Diagnosis:</strong> <?= htmlspecialchars($c['diagnosis'] ?: 'N/A') ?></p>
              <p><strong>Remarks:</strong> <?= htmlspecialchars($c['remarks'] ?: 'N/A') ?></p>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p style="color: #64748b;">No consultation history found for this student.</p>
        <?php endif; ?>
      </div>
    </section>
  </main>
</body>

</html>