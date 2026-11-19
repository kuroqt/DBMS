<?php
require_once 'db.php';
require_once 'auth.php';

date_default_timezone_set('Asia/Manila');

// Get logged-in user context from session
$user_role = strtolower(trim($_SESSION['role'] ?? ''));
$is_patient = in_array($user_role, ['student', 'employee']);

// Determine logged-in patient's own ID and type
$own_patient_id = intval($_SESSION['patient_id'] ?? ($user_role === 'employee' ? ($_SESSION['employee_id'] ?? 0) : ($_SESSION['student_id'] ?? 0)));
$own_patient_type = strtolower(trim($_SESSION['patient_type'] ?? $user_role));

$type = strtolower(trim($_GET['type'] ?? 'student')); // 'student' or 'employee'
$record_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($record_id <= 0 || !in_array($type, ['student', 'employee'])) {
    die("Invalid record requested.");
}

// ==========================================
// ROLE-BASED ACCESS CONTROL (RBAC) SECURITY
// ==========================================
$allowed_staff_roles = ['admin', 'staff', 'doctor', 'dr.', 'nurse', 'dr', 'administrator'];

if ($is_patient) {
    // SECURITY CHECK: Students and Employees can ONLY export their own record
    if ($own_patient_id <= 0 || $type !== $own_patient_type || $record_id !== $own_patient_id) {
        http_response_code(403);
        die("<h2 style='color:#ef4444; font-family:sans-serif; text-align:center; margin-top:50px;'>403 Unauthorized Access</h2><p style='text-align:center;'>You are only permitted to view and export your own medical record.</p>");
    }
} elseif (!in_array($user_role, $allowed_staff_roles)) {
    http_response_code(403);
    die("<h2 style='color:#ef4444; font-family:sans-serif; text-align:center; margin-top:50px;'>403 Access Denied</h2><p style='text-align:center;'>Your role does not have permission to export medical records.</p>");
}

// Fetch record details
$record = null;
$consultations = [];

if ($type === 'student') {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$record_id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($record) {
        try {
            $c_stmt = $pdo->prepare("SELECT * FROM student_consultations WHERE student_id = ? ORDER BY consultation_date DESC");
            $c_stmt->execute([$record_id]);
            $consultations = $c_stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $c_stmt = $pdo->prepare("SELECT * FROM consultations WHERE student_id = ? ORDER BY consultation_date DESC");
            $c_stmt->execute([$record_id]);
            $consultations = $c_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} else { // employee
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$record_id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($record) {
        try {
            $c_stmt = $pdo->prepare("SELECT * FROM employee_consultations WHERE employee_id = ? ORDER BY consultation_date DESC");
            $c_stmt->execute([$record_id]);
            $consultations = $c_stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $c_stmt = $pdo->prepare("SELECT * FROM consultations WHERE employee_id = ? ORDER BY consultation_date DESC");
            $c_stmt->execute([$record_id]);
            $consultations = $c_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}

if (!$record) {
    die("Medical record not found.");
}

$raw_name = trim(($record['title'] ?? '') . ' ' . ($record['first_name'] ?? '') . ' ' . ($record['middle_name'] ?? '') . ' ' . ($record['last_name'] ?? ''));
$full_name = htmlspecialchars(preg_replace('/\s+/', ' ', $raw_name));
$code_id = htmlspecialchars($record['student_id_code'] ?? $record['emp_code'] ?? $record['employee_id_code'] ?? 'N/A');
$permanent_address = htmlspecialchars($record['permanent_address'] ?? $record['address'] ?? 'N/A');
$ref_code = "ACSAT-MED-" . strtoupper(substr($type, 0, 1)) . "-" . str_pad($record['id'], 5, '0', STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $ref_code ?> - Official Medical Record Summary</title>

    <!-- Tailwind CSS & Font Awesome CDNs -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom Color Extensions for SIMS Branding -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            navy: '#10386C',     /* SIMS Header Navy */
                            sidebar: '#1E222D',  /* SIMS Sidebar Dark Gray/Navy */
                            accent: '#00A3E0',   /* SIMS Active Blue Accent */
                            green: '#16BA52',    /* SIMS Success Green */
                        }
                    }
                }
            }
        }
    </script>

    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background-color: #ffffff !important;
                padding: 0 !important;
            }

            .document-card {
                box-shadow: none !important;
                border: none !important;
                max-width: 100% !important;
                padding: 0 !important;
            }
        }
    </style>
</head>

<body class="bg-gray-100 font-sans text-gray-800 p-4 sm:p-6 min-h-screen">

    <!-- PRINT & NAVIGATION ACTION TOOLBAR -->
    <div class="no-print max-w-4xl mx-auto mb-5 flex items-center justify-between gap-3">
        <!-- Back Button (Role-Aware) -->
        <a href="<?= $is_patient ? 'my-record.php' : 'index.php' ?>"
            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold text-xs rounded transition-all flex items-center gap-2">
            <i class="fa-solid <?= $is_patient ? 'fa-arrow-left' : 'fa-house' ?>"></i>
            <?= $is_patient ? 'Back to My Record' : 'Dashboard' ?>
        </a>

        <!-- Action Controls -->
        <div class="flex items-center space-x-3">
            <button onclick="window.print()"
                class="px-5 py-2 bg-brand-navy hover:bg-blue-900 text-white font-bold text-xs rounded shadow-sm transition-all flex items-center gap-2">
                <i class="fa-solid fa-print"></i> Print / Save as PDF
            </button>
        </div>
    </div>

    <!-- DOCUMENT CANVAS (SIMS Official Sheet Styling) -->
    <div
        class="document-card max-w-4xl mx-auto bg-white border border-gray-300 rounded-lg shadow-md p-6 sm:p-8 space-y-6">

        <!-- HEADER -->
        <div class="flex items-center justify-between border-b-2 border-brand-navy pb-4">
            <div class="flex items-center space-x-4">
                <img src="images/acsat.jpg" alt="ACSAT Logo" class="h-14 sm:h-16 w-auto object-contain"
                    onerror="this.style.display='none'">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-brand-navy uppercase tracking-tight">Asian College
                        Medical
                        Services</h1>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Official
                        <?= strtoupper($type) ?> Health & Medical Summary
                    </p>
                </div>
            </div>
            <div class="text-right">
                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block">Ref Code</span>
                <strong class="text-xs text-brand-navy font-bold font-mono"><?= $ref_code ?></strong>
            </div>
        </div>

        <!-- SECTION 1: PERSONAL INFORMATION -->
        <div class="space-y-3">
            <h3
                class="text-xs font-bold text-brand-navy uppercase tracking-wider bg-gray-100 py-1.5 px-3 rounded border-l-4 border-brand-accent">
                I. Personal Information (<?= ucfirst($type) ?>)
            </h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs px-1">
                <div>
                    <span class="text-gray-400 block">Full Name</span>
                    <strong class="text-gray-900 font-bold"><?= $full_name ?></strong>
                </div>
                <div>
                    <span class="text-gray-400 block">ID Number</span>
                    <strong class="text-gray-900 font-bold"><?= $code_id ?></strong>
                </div>
                <div>
                    <span class="text-gray-400 block">Sex</span>
                    <strong class="text-gray-900"><?= htmlspecialchars($record['sex'] ?? 'N/A') ?></strong>
                </div>
                <div>
                    <span class="text-gray-400 block">Date of Birth</span>
                    <strong class="text-gray-900"><?= htmlspecialchars($record['dob'] ?? 'N/A') ?></strong>
                </div>

                <?php if ($type === 'student'): ?>
                    <div>
                        <span class="text-gray-400 block">Course & Year</span>
                        <strong
                            class="text-gray-900"><?= htmlspecialchars(($record['course'] ?? '') . ' - ' . ($record['year_level'] ?? '')) ?></strong>
                    </div>
                    <div>
                        <span class="text-gray-400 block">Section</span>
                        <strong class="text-gray-900"><?= htmlspecialchars($record['section'] ?? 'N/A') ?></strong>
                    </div>
                <?php else: ?>
                    <div>
                        <span class="text-gray-400 block">Department</span>
                        <strong class="text-gray-900"><?= htmlspecialchars($record['department'] ?? 'N/A') ?></strong>
                    </div>
                    <div>
                        <span class="text-gray-400 block">Position</span>
                        <strong class="text-gray-900"><?= htmlspecialchars($record['position'] ?? 'N/A') ?></strong>
                    </div>
                <?php endif; ?>

                <div>
                    <span class="text-gray-400 block">Contact Number</span>
                    <strong class="text-gray-900"><?= htmlspecialchars($record['contact_number'] ?? 'N/A') ?></strong>
                </div>
                <div>
                    <span class="text-gray-400 block">Account Status</span>
                    <strong
                        class="text-brand-green font-bold"><?= htmlspecialchars($record['status'] ?? 'Active') ?></strong>
                </div>

                <!-- PERMANENT ADDRESS FIELD -->
                <div class="col-span-2 sm:col-span-3 border-t border-gray-100 pt-2 mt-1">
                    <span class="text-gray-400 block">Permanent Address</span>
                    <strong class="text-gray-900"><?= $permanent_address ?></strong>
                </div>
            </div>
        </div>

        <!-- SECTION 2: MEDICAL DETAILS -->
        <div class="space-y-3">
            <h3
                class="text-xs font-bold text-brand-navy uppercase tracking-wider bg-gray-100 py-1.5 px-3 rounded border-l-4 border-brand-accent">
                II. Medical Overview
            </h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs px-1">
                <div>
                    <span class="text-gray-400 block">Blood Type</span>
                    <strong
                        class="text-red-600 font-bold text-sm"><?= htmlspecialchars($record['blood_type'] ?? 'N/A') ?></strong>
                </div>
                <div>
                    <span class="text-gray-400 block">Known Allergies</span>
                    <strong
                        class="text-gray-900"><?= htmlspecialchars($record['allergies'] ?? 'None reported') ?></strong>
                </div>
                <div>
                    <span class="text-gray-400 block">Medical Conditions</span>
                    <strong
                        class="text-gray-900"><?= htmlspecialchars($record['medical_conditions'] ?? 'None reported') ?></strong>
                </div>
                <div>
                    <span class="text-gray-400 block">Current Medication</span>
                    <strong
                        class="text-gray-900"><?= htmlspecialchars($record['current_medication'] ?? 'None') ?></strong>
                </div>
            </div>
        </div>

        <!-- SECTION 3: EMERGENCY CONTACT -->
        <div class="space-y-3">
            <h3
                class="text-xs font-bold text-brand-navy uppercase tracking-wider bg-gray-100 py-1.5 px-3 rounded border-l-4 border-brand-accent">
                III. Emergency Contact Information
            </h3>
            <div class="grid grid-cols-2 gap-3 text-xs px-1">
                <div>
                    <span class="text-gray-400 block">Contact Name</span>
                    <strong
                        class="text-gray-900"><?= htmlspecialchars($record['emergency_contact_name'] ?? 'N/A') ?></strong>
                </div>
                <div>
                    <span class="text-gray-400 block">Contact Number</span>
                    <strong
                        class="text-gray-900"><?= htmlspecialchars($record['emergency_contact_number'] ?? 'N/A') ?></strong>
                </div>
            </div>
        </div>

        <!-- SECTION 4: CONSULTATION HISTORY -->
        <div class="space-y-3">
            <h3
                class="text-xs font-bold text-brand-navy uppercase tracking-wider bg-gray-100 py-1.5 px-3 rounded border-l-4 border-brand-accent">
                IV. Consultation & Visit History
            </h3>

            <?php if (!empty($consultations)): ?>
                <div class="overflow-x-auto rounded border border-gray-300">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-brand-navy text-white uppercase text-[10px] tracking-wider">
                                <th class="p-2 border-r border-blue-900 w-1/4">Date & Time</th>
                                <th class="p-2 border-r border-blue-900 w-1/3">Chief Complaint</th>
                                <th class="p-2 border-r border-blue-900 w-1/3">Diagnosis / Remarks</th>
                                <th class="p-2 text-center w-1/6">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($consultations as $c): ?>
                                <?php
                                $dt = !empty($c['consultation_date']) ? (new DateTime($c['consultation_date']))->format('M d, Y - h:i A') : 'N/A';
                                $complaint = $c['chief_complaint'] ?? $c['complaint'] ?? $c['reason'] ?? 'N/A';
                                $notes = trim(($c['diagnosis'] ?? '') . ' ' . ($c['remarks'] ?? $c['notes'] ?? ''));
                                $status_text = $c['follow_up_status'] ?? $c['status'] ?? 'Completed';
                                ?>
                                <tr class="hover:bg-gray-50 text-[11px]">
                                    <td class="p-2 font-semibold text-gray-800 border-r border-gray-200">
                                        <?= htmlspecialchars($dt) ?>
                                    </td>
                                    <td class="p-2 text-gray-700 border-r border-gray-200"><?= htmlspecialchars($complaint) ?>
                                    </td>
                                    <td class="p-2 text-gray-700 border-r border-gray-200">
                                        <?= htmlspecialchars(!empty($notes) ? $notes : 'None') ?>
                                    </td>
                                    <td class="p-2 text-center font-bold text-gray-700"><?= htmlspecialchars($status_text) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-xs text-gray-400 italic py-2">No clinical consultations recorded on file.</p>
            <?php endif; ?>
        </div>

        <!-- ATTESTATION & SIGNATURE FOOTER -->
        <div
            class="pt-8 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-6 text-xs text-gray-500">
            <div class="space-y-1">
                <p><strong>Generated By:</strong> <?= htmlspecialchars($_SESSION['full_name'] ?? 'System User') ?>
                    (<?= htmlspecialchars($_SESSION['role'] ?? 'Staff') ?>)</p>
                <p><strong>Date Exported:</strong> <?= date('F j, Y - h:i A') ?></p>
            </div>

            <div class="text-center w-48">
                <div class="border-t border-gray-900 pt-1 font-bold text-gray-900">
                    <?= $is_patient ? 'ACSAT Medical Services' : htmlspecialchars($_SESSION['full_name'] ?? 'ACSAT Medical Services') ?>
                </div>
                <span class="text-[10px] text-gray-500">Medical Services Officer</span>
            </div>
        </div>

    </div>

    <!-- AUTO-PRINT TRIGGER SCRIPT -->
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('autoprint') === '1') {
                window.print();
            }
        });
    </script>
</body>

</html>