<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once 'db.php';

// Check authorization: Medical Staff OR Patient session
$is_staff = isset($_SESSION['user_id']);
$is_patient = isset($_SESSION['patient_id']);

if (!$is_staff && !$is_patient) {
  header("Location: login.php");
  exit;
}

$type = isset($_GET['type']) ? strtolower($_GET['type']) : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Security check: Patients can ONLY extract their own record
if ($is_patient && !$is_staff) {
  if ($_SESSION['patient_type'] !== $type || $_SESSION['patient_id'] != $id) {
    die("<h2 style='font-family: sans-serif; padding: 30px;'>Unauthorized access. You can only extract your own medical record.</h2>");
  }
}

if ($id <= 0 || ($type !== 'student' && $type !== 'employee')) {
  die("<h2 style='font-family: sans-serif; padding: 30px;'>Invalid extraction parameters. <a href='my-record.php'>Back to record</a></h2>");
}

// Query Details based on account type
if ($type === 'student') {
  $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
  $stmt->execute([$id]);
  $patient = $stmt->fetch(PDO::FETCH_ASSOC);

  $consult_stmt = $pdo->prepare("SELECT * FROM student_consultations WHERE student_id = ? ORDER BY consultation_date DESC");
  $consult_stmt->execute([$id]);
  $consultations = $consult_stmt->fetchAll(PDO::FETCH_ASSOC);

  $doc_type_title = "STUDENT OFFICIAL MEDICAL HEALTH SUMMARY";
  $id_title = "Student ID";
  $id_val = $patient['student_id_code'] ?? 'N/A';
  $classification = ($patient['course'] ?? '') . ' - ' . ($patient['year_level'] ?? '') . ' (Section ' . ($patient['section'] ?? '') . ')';
} else {
  $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
  $stmt->execute([$id]);
  $patient = $stmt->fetch(PDO::FETCH_ASSOC);

  $consult_stmt = $pdo->prepare("SELECT * FROM consultations WHERE employee_id = ? ORDER BY consultation_date DESC");
  $consult_stmt->execute([$id]);
  $consultations = $consult_stmt->fetchAll(PDO::FETCH_ASSOC);

  $doc_type_title = "EMPLOYEE OFFICIAL MEDICAL HEALTH SUMMARY";
  $id_title = "Employee ID";
  $id_val = $patient['emp_code'] ?? 'N/A';
  $classification = ($patient['position'] ?? '') . ' - ' . ($patient['department'] ?? '');
}

if (!$patient) {
  die("<h2 style='font-family: sans-serif; padding: 30px;'>Medical record not found.</h2>");
}

$full_name = trim(($patient['title'] ?? '') . ' ' . $patient['first_name'] . ' ' . ($patient['middle_name'] ?? '') . ' ' . $patient['last_name']);
$formatted_dob = !empty($patient['dob']) ? date('F j, Y', strtotime($patient['dob'])) : 'N/A';
$extraction_time = date('F j, Y - h:i A');
$ref_code = "ACSAT-MED-" . strtoupper(substr($type, 0, 1)) . "-" . str_pad($patient['id'], 5, '0', STR_PAD_LEFT);
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $ref_code ?> - Official Medical Record Extract</title>
  <!-- Library for direct PDF saving -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: Arial, Helvetica, sans-serif;
      background: #f1f5f9;
      color: #0f172a;
      padding: 20px;
    }

    .actions-bar {
      max-width: 800px;
      margin: 0 auto 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .btn {
      padding: 10px 18px;
      border-radius: 6px;
      font-weight: bold;
      text-decoration: none;
      cursor: pointer;
      border: none;
      font-size: 13px;
    }

    .btn-save {
      background: #2563eb;
      color: white;
    }

    .btn-back {
      background: #64748b;
      color: white;
    }

    /* Printable A4 Sheet */
    .document-card {
      background: #ffffff;
      max-width: 800px;
      margin: 0 auto;
      padding: 45px;
      border-radius: 8px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
      border: 1px solid #cbd5e1;
    }

    .doc-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 2px solid #2563eb;
      padding-bottom: 18px;
      margin-bottom: 20px;
    }

    .doc-header img {
      width: 70px;
      height: 70px;
    }

    .header-text {
      text-align: center;
      flex-grow: 1;
    }

    .header-text h1 {
      font-size: 20px;
      color: #1e3a8a;
      text-transform: uppercase;
    }

    .header-text h2 {
      font-size: 13px;
      color: #475569;
      font-weight: normal;
      margin-top: 3px;
    }

    .doc-ref-bar {
      display: flex;
      justify-content: space-between;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      padding: 10px 15px;
      font-size: 11px;
      border-radius: 6px;
      margin-bottom: 20px;
    }

    .section-heading {
      font-size: 13px;
      font-weight: bold;
      color: #2563eb;
      text-transform: uppercase;
      border-bottom: 1px solid #e2e8f0;
      padding-bottom: 4px;
      margin: 18px 0 10px;
    }

    .data-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 12px;
    }

    .data-table th,
    .data-table td {
      border: 1px solid #cbd5e1;
      padding: 8px 10px;
      font-size: 12px;
      text-align: left;
    }

    .data-table th {
      background: #f1f5f9;
      color: #334155;
      width: 25%;
      font-weight: bold;
    }

    .history-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 8px;
    }

    .history-table th,
    .history-table td {
      border: 1px solid #cbd5e1;
      padding: 8px;
      font-size: 11px;
      text-align: left;
    }

    .history-table th {
      background: #1e293b;
      color: #ffffff;
      text-transform: uppercase;
    }

    .attestation {
      margin-top: 35px;
      padding-top: 20px;
      border-top: 1px solid #cbd5e1;
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
    }

    .attestation-text {
      font-size: 10px;
      color: #64748b;
      max-width: 440px;
      line-height: 1.4;
    }

    .signature-box {
      text-align: center;
      width: 200px;
    }

    .signature-line {
      border-top: 1px solid #0f172a;
      margin-top: 40px;
      padding-top: 4px;
      font-size: 12px;
      font-weight: bold;
    }
  </style>
</head>

<body>

  <!-- Top Action Toolbar -->
  <div class="actions-bar">
    <?php if ($is_staff): ?>
      <a href="<?= $type ?>-record.php?id=<?= $id ?>" class="btn btn-back">← Back to Staff View</a>
    <?php else: ?>
      <a href="my-record.php" class="btn btn-back">← Back to My Record</a>
    <?php endif; ?>

    <!-- Direct Download Button -->
    <button onclick="downloadPDF()" class="btn btn-save" id="save-btn">💾 Save PDF to Device</button>
  </div>

  <!-- Printable Official Sheet Container -->
  <div class="document-card" id="pdf-content">

    <!-- Header -->
    <div class="doc-header">
      <img src="images/acsat.jpg" alt="Logo" />
      <div class="header-text">
        <h1>Asian College Medical Services</h1>
        <h2><?= $doc_type_title ?></h2>
      </div>
      <div style="width: 70px;"></div>
    </div>

    <!-- Ref Bar -->
    <div class="doc-ref-bar">
      <div><strong>Reference Code:</strong> <?= $ref_code ?></div>
      <div><strong>Generated Date:</strong> <?= $extraction_time ?></div>
    </div>

    <!-- Section 1 -->
    <div class="section-heading">I. Patient Identification</div>
    <table class="data-table">
      <tr>
        <th>Full Name</th>
        <td><strong><?= htmlspecialchars($full_name) ?></strong></td>
        <th><?= $id_title ?></th>
        <td><?= htmlspecialchars($id_val) ?></td>
      </tr>
      <tr>
        <th>Classification</th>
        <td><?= htmlspecialchars($classification) ?></td>
        <th>Sex</th>
        <td><?= htmlspecialchars($patient['sex'] ?? 'N/A') ?></td>
      </tr>
      <tr>
        <th>Date of Birth</th>
        <td><?= htmlspecialchars($formatted_dob) ?></td>
        <th>Contact Number</th>
        <td><?= htmlspecialchars($patient['contact_number'] ?? 'N/A') ?></td>
      </tr>
    </table>

    <!-- Section 2 -->
    <div class="section-heading">II. Clinical Overview</div>
    <table class="data-table">
      <tr>
        <th>Blood Type</th>
        <td><strong><?= htmlspecialchars($patient['blood_type'] ?? 'N/A') ?></strong></td>
        <th>Known Allergies</th>
        <td><?= htmlspecialchars($patient['allergies'] ?? 'None reported') ?></td>
      </tr>
      <tr>
        <th>Medical Conditions</th>
        <td><?= htmlspecialchars($patient['medical_conditions'] ?? 'None reported') ?></td>
        <th>Current Medication</th>
        <td><?= htmlspecialchars($patient['current_medication'] ?? 'None') ?></td>
      </tr>
      <tr>
        <th>Emergency Contact</th>
        <td colspan="3">
          <?= htmlspecialchars($patient['emergency_contact_name'] ?? 'N/A') ?>
          (<?= htmlspecialchars($patient['emergency_contact_number'] ?? 'N/A') ?>)
        </td>
      </tr>
    </table>

    <!-- Section 3 -->
    <div class="section-heading">III. Logged Consultation & Visit History</div>
    <?php if (!empty($consultations)): ?>
      <table class="history-table">
        <thead>
          <tr>
            <th style="width: 15%;">Date</th>
            <th style="width: 20%;">Vital Signs</th>
            <th style="width: 25%;">Chief Complaint</th>
            <th style="width: 20%;">Treatment Given</th>
            <th style="width: 20%;">Diagnosis / Remarks</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($consultations as $c): ?>
            <tr>
              <td><strong><?= date('M j, Y', strtotime($c['consultation_date'])) ?></strong></td>
              <td><?= htmlspecialchars($c['vital_signs'] ?? 'N/A') ?></td>
              <td><?= htmlspecialchars($c['chief_complaint'] ?? 'N/A') ?></td>
              <td><?= htmlspecialchars($c['treatment'] ?? 'N/A') ?></td>
              <td><?= htmlspecialchars(($c['diagnosis'] ?? 'N/A') . ' ' . ($c['remarks'] ? '(' . $c['remarks'] . ')' : '')) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p style="font-size: 11px; color: #64748b; font-style: italic; padding: 8px 0;">No clinic consultations recorded on
        file.</p>
    <?php endif; ?>

    <!-- Section 4: Attestation -->
    <div class="attestation">
      <div class="attestation-text">
        <p><strong>Official Attestation:</strong></p>
        <p>This medical document is extracted from the official ACSAT Medical Record Management System for personal
          record-keeping, school clearance, or employment documentation purposes.</p>
      </div>

      <div class="signature-box">
        <div class="signature-line">
          <?= $is_staff ? htmlspecialchars($_SESSION['full_name']) : 'ACSAT Medical Services' ?>
        </div>
        <span style="font-size: 10px; color: #64748b;">Medical Services Officer</span>
      </div>
    </div>

  </div>

  <!-- Direct Download Script -->
  <script>
    function downloadPDF() {
      const btn = document.getElementById('save-btn');
      btn.innerText = "⏳ Generate PDF...";
      btn.disabled = true;

      const element = document.getElementById('pdf-content');
      const opt = {
        margin: 0.3,
        filename: '<?= $ref_code ?>_Medical_Record.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
      };

      html2pdf().set(opt).from(element).save().then(() => {
        btn.innerText = "💾 Save PDF to Device";
        btn.disabled = false;
      });
    }
  </script>
</body>

</html>