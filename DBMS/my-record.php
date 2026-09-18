<?php
session_start();
require_once 'db.php';

// Security Check: Must be logged in as a Student or Employee
if (!isset($_SESSION['patient_id']) || !isset($_SESSION['patient_type'])) {
    header("Location: login.php");
    exit;
}

$patient_id = $_SESSION['patient_id'];
$patient_type = $_SESSION['patient_type']; // 'student' or 'employee'

if ($patient_type === 'student') {
    // Fetch Student Details
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch Student Consultations
    $consult_stmt = $pdo->prepare("SELECT * FROM student_consultations WHERE student_id = ? ORDER BY consultation_date DESC, id DESC");
    $consult_stmt->execute([$patient_id]);
    $consultations = $consult_stmt->fetchAll(PDO::FETCH_ASSOC);

    $id_label = "Student ID Number";
    $id_val = $patient['student_id_code'] ?? 'N/A';
    $sub_info = ($patient['course'] ?? '') . ' - ' . ($patient['year_level'] ?? '') . ' (' . ($patient['section'] ?? '') . ')';
} else {
    // Fetch Employee Details
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch Employee Consultations
    $consult_stmt = $pdo->prepare("SELECT * FROM consultations WHERE employee_id = ? ORDER BY consultation_date DESC, id DESC");
    $consult_stmt->execute([$patient_id]);
    $consultations = $consult_stmt->fetchAll(PDO::FETCH_ASSOC);

    $id_label = "Employee ID Code";
    $id_val = $patient['emp_code'] ?? 'N/A';
    $sub_info = ($patient['position'] ?? '') . ' - ' . ($patient['department'] ?? '');
}

if (!$patient) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$initials = strtoupper(substr($patient['first_name'], 0, 1) . substr($patient['last_name'], 0, 1));
$full_name = trim(($patient['title'] ?? '') . ' ' . $patient['first_name'] . ' ' . ($patient['middle_name'] ?? '') . ' ' . $patient['last_name']);
$formatted_dob = $patient['dob'] ? date('F j, Y', strtotime($patient['dob'])) : 'N/A';
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Personal Medical Record - <?= htmlspecialchars($full_name) ?></title>
    <link rel="stylesheet" href="style.css" />
    <style>
        body {
            background: #f8fafc;
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
        }

        .portal-topbar {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .portal-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .portal-logo img {
            width: 42px;
            height: 42px;
            border-radius: 8px;
        }

        .portal-logo h3 {
            font-size: 16px;
            margin: 0;
            color: #1e293b;
        }

        .portal-logo span {
            font-size: 12px;
            color: #64748b;
        }

        .portal-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .extract-btn {
            background: #2563eb;
            color: #ffffff;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 13px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
        }

        .extract-btn:hover {
            background: #1d4ed8;
        }

        .logout-btn {
            background: #fee2e2;
            color: #b91c1c;
            padding: 10px 14px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 13px;
            text-decoration: none;
        }

        .portal-container {
            max-width: 1000px;
            margin: 25px auto;
            padding: 0 18px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            background: #dcfce7;
            color: #15803d;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-top: 6px;
        }
    </style>
</head>

<body>
    <!-- Navigation Bar -->
    <header class="portal-topbar">
        <div class="portal-logo">
            <img src="images/acsat.jpg" alt="ACSAT Logo" />
            <div>
                <h3>ACSAT Medical Portal</h3>
                <span>Personal Health Record Access</span>
            </div>
        </div>

        <div class="portal-actions">
            <!-- Extract PDF Button -->
            <a href="extract-record.php?type=<?= $patient_type ?>&id=<?= $patient_id ?>" target="_blank"
                class="extract-btn">
                📄 Extract Official PDF
            </a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="portal-container">

        <!-- Profile Header Card -->
        <section class="profile-header" style="justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 18px; flex-wrap: wrap;">
                <div class="profile-icon"><?= htmlspecialchars($initials) ?></div>
                <div>
                    <h2><?= htmlspecialchars($full_name) ?></h2>
                    <p><strong><?= $id_label ?>:</strong> <?= htmlspecialchars($id_val) ?> |
                        <?= htmlspecialchars($sub_info) ?>
                    </p>
                    <span class="status-badge">✓ Active Patient Record</span>
                </div>
            </div>
        </section>

        <!-- Details Grid -->
        <section class="record-grid" style="margin-top: 20px;">

            <!-- Personal Details -->
            <div class="record-card">
                <h3>Personal Details</h3>
                <div class="info-grid">
                    <div>
                        <label>Full Name</label>
                        <p><?= htmlspecialchars($full_name) ?></p>
                    </div>
                    <div>
                        <label>Date of Birth</label>
                        <p><?= htmlspecialchars($formatted_dob) ?></p>
                    </div>
                    <div>
                        <label>Sex</label>
                        <p><?= htmlspecialchars($patient['sex'] ?? 'N/A') ?></p>
                    </div>
                    <div>
                        <label>Contact Number</label>
                        <p><?= htmlspecialchars($patient['contact_number'] ?? 'N/A') ?></p>
                    </div>
                </div>
            </div>

            <!-- Medical Details -->
            <div class="record-card">
                <h3>Medical Overview</h3>
                <div class="info-grid">
                    <div>
                        <label>Blood Type</label>
                        <p><?= htmlspecialchars($patient['blood_type'] ?? 'N/A') ?></p>
                    </div>
                    <div>
                        <label>Allergies</label>
                        <p><?= htmlspecialchars($patient['allergies'] ?? 'None reported') ?></p>
                    </div>
                    <div>
                        <label>Medical Conditions</label>
                        <p><?= htmlspecialchars($patient['medical_conditions'] ?? 'None reported') ?></p>
                    </div>
                    <div>
                        <label>Current Medication</label>
                        <p><?= htmlspecialchars($patient['current_medication'] ?? 'None') ?></p>
                    </div>
                </div>
            </div>

            <!-- Emergency Contact -->
            <div class="record-card" style="grid-column: span 2;">
                <h3>Emergency Contact Information</h3>
                <div class="info-grid">
                    <div>
                        <label>Contact Name</label>
                        <p><?= htmlspecialchars($patient['emergency_contact_name'] ?? 'N/A') ?></p>
                    </div>
                    <div>
                        <label>Contact Number</label>
                        <p><?= htmlspecialchars($patient['emergency_contact_number'] ?? 'N/A') ?></p>
                    </div>
                </div>
            </div>

            <!-- Consultation & Clinic History -->
            <div class="record-card" style="grid-column: span 2;">
                <h3>Clinic Visit & Consultation History</h3>

                <?php if (!empty($consultations)): ?>
                    <?php foreach ($consultations as $c): ?>
                        <div class="consultation"
                            style="margin-bottom: 18px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9;">
                            <strong
                                style="color: #2563eb; font-size: 14px;"><?= date('F j, Y', strtotime($c['consultation_date'])) ?></strong>

                            <?php if (!empty($c['vital_signs'])): ?>
                                <p><strong>Vital Signs:</strong> <?= htmlspecialchars($c['vital_signs']) ?></p>
                            <?php endif; ?>

                            <p><strong>Chief Complaint:</strong> <?= htmlspecialchars($c['chief_complaint']) ?></p>

                            <?php if (!empty($c['treatment'])): ?>
                                <p><strong>Treatment Provided:</strong> <?= htmlspecialchars($c['treatment']) ?></p>
                            <?php endif; ?>

                            <p><strong>Diagnosis:</strong> <?= htmlspecialchars($c['diagnosis'] ?: 'N/A') ?></p>
                            <p><strong>Remarks / Advice:</strong> <?= htmlspecialchars($c['remarks'] ?: 'N/A') ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #64748b; padding: 10px 0;">No clinic visit logs recorded for your account yet.</p>
                <?php endif; ?>
            </div>

        </section>
    </main>
</body>

</html>