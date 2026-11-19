<?php
require_once 'auth.php';
require_once 'db.php';

date_default_timezone_set('Asia/Manila');

$patient_id = intval($_SESSION['patient_id'] ?? 0);
$patient_type = strtolower(trim($_SESSION['patient_type'] ?? 'student'));

if ($patient_id <= 0 || !in_array($patient_type, ['student', 'employee'])) {
  header("Location: logout.php");
  exit();
}

$record = null;
$consultations = [];
$success_msg = '';
$error_msg = '';

// Fetch Patient Record
try {
  if ($patient_type === 'student') {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ? LIMIT 1");
    $stmt->execute([$patient_id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
  } else {
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ? LIMIT 1");
    $stmt->execute([$patient_id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
  }
} catch (Exception $e) {
  $record = false;
}

if (!$record) {
  die("<h2 style='padding: 40px; font-family: sans-serif; color: #1e293b;'>Record not found. <a href='logout.php' style='color: #2563eb;'>Logout</a></h2>");
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
  $current_password = trim($_POST['current_password'] ?? '');
  $new_password = trim($_POST['new_password'] ?? '');
  $confirm_password = trim($_POST['confirm_password'] ?? '');

  $db_pass = $record['password'] ?? '';
  $default_id_pass = trim($record['student_id_code'] ?? $record['emp_code'] ?? '');
  $current_valid = false;

  if (!empty($db_pass) && password_verify($current_password, $db_pass)) {
    $current_valid = true;
  } elseif (!empty($db_pass) && $db_pass === $current_password) {
    $current_valid = true;
  } elseif (empty($db_pass) && $current_password === $default_id_pass) {
    $current_valid = true;
  }

  if (!$current_valid) {
    $error_msg = "Current password is incorrect.";
  } elseif (strlen($new_password) < 6) {
    $error_msg = "New password must be at least 6 characters long.";
  } elseif ($new_password !== $confirm_password) {
    $error_msg = "New password and confirmation do not match.";
  } else {
    try {
      $table = ($patient_type === 'employee') ? 'employees' : 'students';
      $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
      $upd = $pdo->prepare("UPDATE {$table} SET password = ? WHERE id = ?");
      $upd->execute([$new_hash, $patient_id]);
      $record['password'] = $new_hash;
      $success_msg = "Your password has been updated successfully!";
    } catch (PDOException $e) {
      $error_msg = "Failed to update password. Please try again.";
    }
  }
}

// Fetch Consultation History
try {
  if ($patient_type === 'student') {
    try {
      $c_stmt = $pdo->prepare("SELECT * FROM student_consultations WHERE student_id = ? ORDER BY consultation_date DESC");
      $c_stmt->execute([$patient_id]);
      $consultations = $c_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      $c_stmt = $pdo->prepare("SELECT * FROM consultations WHERE student_id = ? ORDER BY consultation_date DESC");
      $c_stmt->execute([$patient_id]);
      $consultations = $c_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
  } else {
    try {
      $c_stmt = $pdo->prepare("SELECT * FROM employee_consultations WHERE employee_id = ? ORDER BY consultation_date DESC");
      $c_stmt->execute([$patient_id]);
      $consultations = $c_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      $c_stmt = $pdo->prepare("SELECT * FROM consultations WHERE employee_id = ? ORDER BY consultation_date DESC");
      $c_stmt->execute([$patient_id]);
      $consultations = $c_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
  }
} catch (Exception $e) {
  $consultations = [];
}

$title = $record['title'] ?? '';
$firstName = $record['first_name'] ?? '';
$lastName = $record['last_name'] ?? '';
$full_name = trim(($title ? $title . ' ' : '') . $firstName . ' ' . $lastName);
if (empty($full_name)) {
  $full_name = $_SESSION['full_name'] ?? 'My Medical Record';
}

$initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
if (empty($initials)) {
  $initials = 'ME';
}

$code = $record['student_id_code'] ?? $record['emp_code'] ?? 'N/A';
$formatted_dob = (!empty($record['dob']) && $record['dob'] !== '0000-00-00') ? date('F j, Y', strtotime($record['dob'])) : 'N/A';
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Medical Record - ACSAT Medical System</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: {
              navy: '#10386C',
              sidebar: '#1E222D',
              accent: '#00A3E0',
              green: '#16BA52',
            }
          }
        }
      }
    }
  </script>
</head>

<body class="bg-gray-100 font-sans text-gray-800 flex flex-col min-h-[100dvh] md:h-screen md:overflow-hidden">

  <!-- TOP HEADER -->
  <header
    class="bg-brand-navy text-white h-16 flex items-center justify-between px-4 md:px-6 shadow-md z-30 shrink-0 sticky top-0">
    <div class="flex items-center space-x-2.5 sm:space-x-3 min-w-0">
      <!-- Mobile Hamburger Button -->
      <button id="menu-toggle" type="button" aria-label="Open Navigation"
        class="md:hidden text-white hover:text-brand-accent focus:outline-none p-1.5 rounded-lg hover:bg-white/10 transition shrink-0">
        <i class="fa-solid fa-bars text-lg"></i>
      </button>

      <div class="bg-white p-1 rounded shrink-0">
        <img src="images/acsat.jpg" alt="ACSAT Logo" class="h-7 md:h-8 w-auto object-contain"
          onerror="this.style.display='none'">
      </div>
      <div class="min-w-0">
        <h1 class="text-sm md:text-base font-bold leading-tight truncate">Asian College Computer-Based EMR System</h1>
        <p class="hidden sm:block text-xs text-blue-200 truncate">Student & Employee Health Portal</p>
      </div>
    </div>

    <!-- User Information & Logout -->
    <div class="flex items-center space-x-2.5 sm:space-x-3 shrink-0">
      <div
        class="w-8 h-8 rounded-full bg-blue-100 text-brand-navy font-bold flex items-center justify-center text-xs shadow shrink-0">
        <?= htmlspecialchars($initials) ?>
      </div>
      <div class="hidden sm:block text-right">
        <p class="text-xs font-bold leading-tight"><?= htmlspecialchars($full_name) ?></p>
        <span class="text-[11px] text-blue-200 block"><?= ucfirst(htmlspecialchars($patient_type)) ?> Account</span>
      </div>
      <a href="logout.php"
        class="ml-1 sm:ml-2 text-xs text-red-200 hover:text-white font-semibold px-2.5 py-1.5 rounded bg-red-950/40 sm:bg-transparent hover:underline transition">
        Logout
      </a>
    </div>
  </header>

  <!-- MAIN BODY WRAPPER -->
  <div class="flex flex-1 md:overflow-hidden relative">

    <!-- MOBILE SIDEBAR BACKDROP -->
    <div id="sidebar-backdrop" class="fixed inset-0 bg-black/50 z-30 hidden md:hidden transition-opacity"></div>

    <!-- SIDEBAR (Off-Canvas Drawer on Mobile, Static on Desktop) -->
    <aside id="sidebar"
      class="fixed inset-y-0 left-0 z-40 w-64 md:w-60 bg-brand-sidebar text-gray-300 flex flex-col justify-between shrink-0 shadow-xl transform -translate-x-full md:translate-x-0 md:static transition-transform duration-200 ease-in-out">
      <div class="py-4 overflow-y-auto">
        <!-- Mobile Drawer Header -->
        <div class="flex items-center justify-between px-6 pb-3 mb-3 border-b border-gray-800 md:hidden">
          <div class="min-w-0 pr-2">
            <p class="text-xs font-bold text-white truncate"><?= htmlspecialchars($full_name) ?></p>
            <span class="text-[11px] text-gray-400 block"><?= ucfirst(htmlspecialchars($patient_type)) ?> Account</span>
          </div>
          <button id="menu-close" type="button" aria-label="Close Navigation"
            class="text-gray-400 hover:text-white p-1 shrink-0">
            <i class="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>

        <div
          class="px-6 py-2 mb-3 bg-black/20 text-xs md:text-[11px] font-bold text-brand-accent uppercase tracking-wider">
          SY: <?= date('Y') ?>-<?= date('Y', strtotime('+1 year')) ?>-1
        </div>

        <nav class="space-y-1">
          <a href="my-record.php"
            class="flex items-center px-6 py-3 text-sm md:text-xs font-semibold text-white bg-white/10 border-l-4 border-brand-accent">
            <i class="fa-solid fa-notes-medical w-6 text-brand-accent"></i>
            <span>My Record</span>
          </a>

          <a href="export-pdf.php?type=<?= htmlspecialchars($patient_type) ?>&id=<?= $patient_id ?>&autoprint=1"
            target="_blank"
            class="flex items-center px-6 py-3 text-sm md:text-xs font-medium text-gray-300 hover:bg-white/5 hover:text-white transition-colors">
            <i class="fa-solid fa-file-pdf w-6 text-gray-400"></i>
            <span>Export / Print PDF</span>
          </a>

          <button type="button" onclick="openPasswordModal()"
            class="w-full flex items-center px-6 py-3 text-sm md:text-xs font-medium text-gray-300 hover:bg-white/5 hover:text-white transition-colors text-left">
            <i class="fa-solid fa-key w-6 text-gray-400"></i>
            <span>Change Password</span>
          </button>
        </nav>
      </div>

      <div class="p-4 text-[11px] text-gray-500 border-t border-gray-800 text-center">
        ACSAT Medical Portal &copy; <?= date('Y') ?>
      </div>
    </aside>

    <!-- MAIN CONTENT CANVAS -->
    <main class="flex-1 overflow-y-auto overflow-x-hidden p-4 sm:p-6 bg-gray-100">
      <div class="max-w-5xl mx-auto space-y-4 sm:space-y-5">

        <!-- FLASH MESSAGES -->
        <?php if (!empty($success_msg)): ?>
          <div
            class="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-semibold rounded-lg flex items-center gap-2.5">
            <i class="fa-solid fa-circle-check text-sm shrink-0"></i>
            <span><?= htmlspecialchars($success_msg) ?></span>
          </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
          <div
            class="p-3.5 bg-red-50 border border-red-200 text-red-700 text-xs font-semibold rounded-lg flex items-center gap-2.5">
            <i class="fa-solid fa-circle-exclamation text-sm shrink-0"></i>
            <span><?= htmlspecialchars($error_msg) ?></span>
          </div>
        <?php endif; ?>

        <!-- PROFILE HERO HEADER -->
        <section
          class="bg-white rounded-lg border border-gray-200 p-4 sm:p-5 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="flex items-center gap-3.5 sm:gap-4 min-w-0">
            <div
              class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-blue-100 text-brand-navy font-bold flex items-center justify-center text-base sm:text-lg shadow-inner shrink-0">
              <?= htmlspecialchars($initials) ?>
            </div>
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-base sm:text-lg font-bold text-gray-900 break-words"><?= htmlspecialchars($full_name) ?>
                </h2>
                <span
                  class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] font-bold text-white bg-brand-green rounded-full">
                  <i class="fa-solid fa-circle-check text-[9px]"></i>
                  <?= htmlspecialchars($record['status'] ?? 'Active') ?>
                </span>
              </div>
              <p class="text-xs text-gray-500 mt-0.5">
                ID Number: <strong class="text-gray-800"><?= htmlspecialchars($code) ?></strong>
              </p>
              <?php if ($patient_type === 'student' && !empty($record['course'])): ?>
                <p class="text-xs text-gray-500 mt-0.5 break-words">
                  <?= htmlspecialchars($record['course']) ?>
                  <?= !empty($record['year_level']) ? ' &bull; ' . htmlspecialchars($record['year_level']) : '' ?>
                  <?= !empty($record['section']) ? ' - Sec ' . htmlspecialchars($record['section']) : '' ?>
                </p>
              <?php elseif ($patient_type !== 'student' && (!empty($record['position']) || !empty($record['department']))): ?>
                <p class="text-xs text-gray-500 mt-0.5 break-words">
                  <?= htmlspecialchars($record['position'] ?? '') ?>
                  <?= (!empty($record['position']) && !empty($record['department'])) ? ' &bull; ' : '' ?>
                  <?= htmlspecialchars($record['department'] ?? '') ?>
                </p>
              <?php endif; ?>
            </div>
          </div>

          <!-- ACTION BUTTONS (Export PDF & Change Password) -->
          <div
            class="flex flex-wrap items-center gap-2 w-full sm:w-auto pt-3 sm:pt-0 border-t sm:border-t-0 border-gray-100 shrink-0">
            <a href="export-pdf.php?type=<?= htmlspecialchars($patient_type) ?>&id=<?= $patient_id ?>&autoprint=1"
              target="_blank"
              class="flex-1 sm:flex-initial justify-center inline-flex items-center gap-1.5 bg-brand-green hover:bg-emerald-700 text-white font-bold text-xs py-2.5 sm:py-2 px-3.5 rounded-lg sm:rounded shadow-sm transition-all">
              <i class="fa-solid fa-file-pdf"></i> Export PDF
            </a>

            <button type="button" onclick="openPasswordModal()"
              class="flex-1 sm:flex-initial justify-center inline-flex items-center gap-1.5 border border-gray-300 hover:bg-gray-50 text-gray-700 font-semibold text-xs py-2.5 sm:py-2 px-3.5 rounded-lg sm:rounded transition-all">
              <i class="fa-solid fa-key text-gray-500"></i> Change Password
            </button>
          </div>
        </section>

        <!-- RECORD DETAILS GRID -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
          <!-- PERSONAL INFORMATION -->
          <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-5 shadow-sm space-y-3">
            <h3
              class="text-xs font-bold text-brand-navy uppercase tracking-wider border-b border-gray-200 pb-2 flex items-center gap-2">
              <i class="fa-solid fa-user text-brand-accent"></i> Personal Information
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 text-xs">
              <div>
                <span class="text-gray-400 block text-[11px]">First Name</span>
                <strong
                  class="text-gray-800 break-words"><?= htmlspecialchars($record['first_name'] ?? 'N/A') ?></strong>
              </div>
              <div>
                <span class="text-gray-400 block text-[11px]">Last Name</span>
                <strong
                  class="text-gray-800 break-words"><?= htmlspecialchars($record['last_name'] ?? 'N/A') ?></strong>
              </div>
              <div>
                <span class="text-gray-400 block text-[11px]">Date of Birth</span>
                <strong class="text-gray-800"><?= htmlspecialchars($formatted_dob) ?></strong>
              </div>
              <div>
                <span class="text-gray-400 block text-[11px]">Sex</span>
                <strong class="text-gray-800"><?= htmlspecialchars($record['sex'] ?? 'N/A') ?></strong>
              </div>
              <div class="sm:col-span-2">
                <span class="text-gray-400 block text-[11px]">Contact Number</span>
                <strong class="text-gray-800"><?= htmlspecialchars($record['contact_number'] ?? 'N/A') ?></strong>
              </div>
              <div class="sm:col-span-2">
                <span class="text-gray-400 block text-[11px]">Permanent Address</span>
                <strong
                  class="text-gray-800 break-words"><?= htmlspecialchars($record['permanent_address'] ?? $record['address'] ?? 'N/A') ?></strong>
              </div>
            </div>
          </div>

          <!-- MEDICAL DETAILS -->
          <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-5 shadow-sm space-y-3">
            <h3
              class="text-xs font-bold text-brand-navy uppercase tracking-wider border-b border-gray-200 pb-2 flex items-center gap-2">
              <i class="fa-solid fa-notes-medical text-brand-accent"></i> Medical Information
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 text-xs">
              <div>
                <span class="text-gray-400 block text-[11px]">Blood Type</span>
                <span
                  class="inline-block mt-0.5 px-2 py-0.5 rounded bg-red-50 text-red-600 font-bold text-sm border border-red-100">
                  <?= htmlspecialchars($record['blood_type'] ?? 'N/A') ?>
                </span>
              </div>
              <div>
                <span class="text-gray-400 block text-[11px]">Allergies</span>
                <strong
                  class="text-gray-800 break-words"><?= htmlspecialchars($record['allergies'] ?? 'None reported') ?></strong>
              </div>
              <div>
                <span class="text-gray-400 block text-[11px]">Medical Conditions</span>
                <strong
                  class="text-gray-800 break-words"><?= htmlspecialchars($record['medical_conditions'] ?? 'None reported') ?></strong>
              </div>
              <div>
                <span class="text-gray-400 block text-[11px]">Current Medication</span>
                <strong
                  class="text-gray-800 break-words"><?= htmlspecialchars($record['current_medication'] ?? 'None') ?></strong>
              </div>
            </div>
          </div>
        </div>

        <!-- EMERGENCY CONTACT CARD -->
        <section class="bg-white rounded-lg border border-gray-200 p-4 sm:p-5 shadow-sm space-y-3">
          <h3
            class="text-xs font-bold text-brand-navy uppercase tracking-wider border-b border-gray-200 pb-2 flex items-center gap-2">
            <i class="fa-solid fa-phone-volume text-brand-accent"></i> Emergency Contact
          </h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 text-xs">
            <div>
              <span class="text-gray-400 block text-[11px]">Contact Person</span>
              <strong
                class="text-gray-800 break-words"><?= htmlspecialchars($record['emergency_contact_name'] ?? 'N/A') ?></strong>
            </div>
            <div>
              <span class="text-gray-400 block text-[11px]">Contact Number</span>
              <?php if (!empty($record['emergency_contact_number'])): ?>
                <a href="tel:<?= htmlspecialchars($record['emergency_contact_number']) ?>"
                  class="font-bold text-brand-navy hover:underline">
                  <?= htmlspecialchars($record['emergency_contact_number']) ?>
                </a>
              <?php else: ?>
                <strong class="text-gray-800">N/A</strong>
              <?php endif; ?>
            </div>
          </div>
        </section>

        <!-- MEDICAL CONSULTATION HISTORY -->
        <section class="bg-white rounded-lg border border-gray-200 p-4 sm:p-5 shadow-sm space-y-3">
          <h3
            class="text-xs font-bold text-brand-navy uppercase tracking-wider border-b border-gray-200 pb-2 flex items-center justify-between">
            <span class="flex items-center gap-2">
              <i class="fa-solid fa-clock-rotate-left text-brand-accent"></i> Consultation History
            </span>
            <span class="bg-blue-50 text-brand-navy px-2.5 py-0.5 rounded-full text-[11px] font-bold">
              <?= count($consultations) ?>
            </span>
          </h3>

          <?php if (!empty($consultations)): ?>
            <div class="space-y-3">
              <?php foreach ($consultations as $c): ?>
                <div
                  class="p-3.5 sm:p-4 bg-gray-50 rounded-lg border-l-4 border-brand-navy border-t border-r border-b border-gray-200 space-y-2">
                  <div
                    class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 border-b border-gray-200 pb-1.5">
                    <strong class="text-xs font-bold text-brand-navy">
                      <i class="fa-regular fa-calendar mr-1 text-brand-accent"></i>
                      <?= date('F j, Y', strtotime($c['consultation_date'])) ?>
                    </strong>
                    <span class="text-[11px] text-gray-500 font-medium">
                      <?= date('h:i A', strtotime($c['consultation_date'])) ?>
                    </span>
                  </div>

                  <div class="space-y-1.5 text-xs text-gray-700 break-words">
                    <?php if (!empty($c['vital_signs'])): ?>
                      <p><strong>Vital Signs:</strong> <?= htmlspecialchars($c['vital_signs']) ?></p>
                    <?php endif; ?>
                    <p><strong>Chief Complaint:</strong> <?= htmlspecialchars($c['chief_complaint'] ?? 'N/A') ?></p>
                    <p><strong>Diagnosis:</strong>
                      <?= htmlspecialchars(!empty($c['diagnosis']) ? $c['diagnosis'] : 'N/A') ?>
                    </p>
                    <?php if (!empty($c['treatment'])): ?>
                      <p><strong>Treatment / Medicine:</strong> <?= htmlspecialchars($c['treatment']) ?></p>
                    <?php endif; ?>
                    <p><strong>Remarks / Advice:</strong>
                      <?= htmlspecialchars(!empty($c['remarks']) ? $c['remarks'] : 'N/A') ?>
                    </p>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <p class="text-xs text-gray-400 py-6 text-center italic">No consultation history recorded for your account.
            </p>
          <?php endif; ?>
        </section>

      </div>
    </main>
  </div>

  <!-- CHANGE PASSWORD MODAL -->
  <div id="password-modal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-xl border border-gray-200 shadow-xl max-w-md w-full overflow-hidden">
      <div class="bg-brand-navy text-white px-5 py-3.5 flex items-center justify-between">
        <h3 class="text-sm font-bold flex items-center gap-2">
          <i class="fa-solid fa-key text-brand-accent"></i> Change Password
        </h3>
        <button type="button" onclick="closePasswordModal()" class="text-blue-200 hover:text-white">
          <i class="fa-solid fa-xmark text-base"></i>
        </button>
      </div>

      <form method="POST" action="my-record.php" class="p-5 space-y-4">
        <input type="hidden" name="action" value="change_password">

        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1.5">Current Password <span
              class="text-red-500">*</span></label>
          <input type="password" name="current_password" required placeholder="Enter current password (or ID Code)"
            class="w-full px-3 py-2 text-xs rounded-lg border border-gray-300 focus:outline-none focus:border-brand-navy focus:ring-1 focus:ring-brand-navy">
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1.5">New Password <span
              class="text-red-500">*</span></label>
          <input type="password" name="new_password" required minlength="6" placeholder="Minimum 6 characters"
            class="w-full px-3 py-2 text-xs rounded-lg border border-gray-300 focus:outline-none focus:border-brand-navy focus:ring-1 focus:ring-brand-navy">
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1.5">Confirm New Password <span
              class="text-red-500">*</span></label>
          <input type="password" name="confirm_password" required minlength="6" placeholder="Re-enter new password"
            class="w-full px-3 py-2 text-xs rounded-lg border border-gray-300 focus:outline-none focus:border-brand-navy focus:ring-1 focus:ring-brand-navy">
        </div>

        <div class="pt-2 border-t border-gray-100 flex items-center justify-end gap-2">
          <button type="button" onclick="closePasswordModal()"
            class="px-4 py-2 border border-gray-300 hover:bg-gray-50 text-gray-700 font-semibold text-xs rounded-lg transition-all">
            Cancel
          </button>
          <button type="submit"
            class="px-4 py-2 bg-brand-navy hover:bg-blue-900 text-white font-bold text-xs rounded-lg shadow-sm transition-all flex items-center gap-1.5">
            <i class="fa-solid fa-floppy-disk"></i> Save Password
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- MOBILE SIDEBAR & MODAL SCRIPT -->
  <script>
    const menuToggle = document.getElementById('menu-toggle');
    const menuClose = document.getElementById('menu-close');
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    const passwordModal = document.getElementById('password-modal');

    function openSidebar() {
      sidebar.classList.remove('-translate-x-full');
      backdrop.classList.remove('hidden');
      document.body.classList.add('overflow-hidden');
    }

    function closeSidebar() {
      sidebar.classList.add('-translate-x-full');
      backdrop.classList.add('hidden');
      document.body.classList.remove('overflow-hidden');
    }

    function openPasswordModal() {
      closeSidebar();
      passwordModal.classList.remove('hidden');
      passwordModal.classList.add('flex');
    }

    function closePasswordModal() {
      passwordModal.classList.add('hidden');
      passwordModal.classList.remove('flex');
    }

    menuToggle.addEventListener('click', openSidebar);
    menuClose.addEventListener('click', closeSidebar);
    backdrop.addEventListener('click', closeSidebar);
    passwordModal.addEventListener('click', (e) => {
      if (e.target === passwordModal) closePasswordModal();
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        closeSidebar();
        closePasswordModal();
      }
    });
  </script>

</body>

</html>