<?php
require_once 'db.php';
require_once 'auth.php';


// Use Philippine local time
date_default_timezone_set('Asia/Manila');


$currentDate = date('l, F d, Y');
$currentTime = date('h:i A');


// Ensure table & columns exist for vital signs & treatment
try {
  $pdo->exec("CREATE TABLE IF NOT EXISTS student_consultations (
      id INT AUTO_INCREMENT PRIMARY KEY,
      student_id INT NOT NULL,
      consultation_date DATETIME NOT NULL,
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


  // Make sure existing consultation_date column can store date AND time
  try {
    $pdo->exec("ALTER TABLE student_consultations MODIFY consultation_date DATETIME NOT NULL");
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
  // Keep the selected visit date and add the current Philippine time
  $selected_date = !empty($_POST['consultation_date']) ? $_POST['consultation_date'] : date('Y-m-d');
  $visit_date = $selected_date . ' ' . date('H:i:s');


  // Separate Vital Signs Inputs
  $bp = trim($_POST['bp'] ?? '');
  $temp = trim($_POST['temp'] ?? '');
  $hr = trim($_POST['hr'] ?? '');


  // Format into a single clean string for database storage
  $vitals_parts = [];
  if (!empty($bp))
    $vitals_parts[] = "BP: " . $bp;
  if (!empty($temp))
    $vitals_parts[] = "Temp: " . $temp;
  if (!empty($hr))
    $vitals_parts[] = "HR: " . $hr;
  $vital_signs = implode(' | ', $vitals_parts);


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


// Check if user is allowed to export PDF (Doctor, Nurse, Admin)
$user_role = strtolower(trim($_SESSION['role'] ?? ''));
$can_export_pdf = in_array($user_role, ['doctor', 'nurse', 'admin', 'administrator']);
?>


<!doctype html>
<html lang="en">


<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Student Medical Record - <?= htmlspecialchars($full_name) ?></title>


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
</head>


<body class="bg-gray-100 font-sans text-gray-800 flex flex-col min-h-[100dvh] md:h-screen md:overflow-hidden">


  <!-- TOP HEADER (SIMS Navy Theme) -->
  <header
    class="bg-brand-navy text-white h-16 flex items-center justify-between px-4 md:px-6 shadow-md z-30 shrink-0 sticky top-0">
    <!-- Mobile Hamburger + Brand / Title -->
    <div class="flex items-center space-x-2.5 sm:space-x-3 min-w-0">
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


    <!-- Search, Date/Time & Profile Bar -->
    <div class="flex items-center space-x-3 md:space-x-6 shrink-0">
      <!-- Desktop Search Form -->
      <form class="relative w-64 hidden lg:block" action="search.php" method="GET">
        <button type="submit" class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-300">
          <i class="fa-solid fa-magnifying-glass text-xs"></i>
        </button>
        <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
          placeholder="Search student, employee, ID..." required
          class="w-full pl-8 pr-4 py-1.5 text-xs rounded bg-white/10 text-white placeholder-gray-300 focus:outline-none focus:bg-white focus:text-gray-900 border border-white/20 transition-all">
      </form>


      <!-- Date & Time Display -->
      <div class="hidden xl:block text-right border-l border-blue-800 pl-6">
        <strong class="text-xs font-bold text-white block leading-tight"><?= htmlspecialchars($currentDate) ?></strong>
        <span class="text-[11px] text-blue-200 block"><?= htmlspecialchars($currentTime) ?></span>
      </div>


      <!-- User Info -->
      <div class="flex items-center space-x-2.5 md:space-x-3 lg:border-l lg:border-blue-800 lg:pl-6">
        <div
          class="w-8 h-8 rounded-full bg-blue-100 text-brand-navy font-bold flex items-center justify-center text-xs shadow shrink-0">
          <?= strtoupper(substr($_SESSION['full_name'] ?? 'A', 0, 1)) ?>
        </div>
        <div class="hidden sm:block text-right">
          <p class="text-xs font-bold leading-tight"><?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?></p>
          <span
            class="text-[11px] text-blue-200 block"><?= htmlspecialchars($_SESSION['role'] ?? 'Administrator') ?></span>
        </div>
        <a href="logout.php"
          class="ml-1 md:ml-2 text-xs text-red-200 hover:text-white font-semibold px-2.5 py-1.5 rounded bg-red-950/40 sm:bg-transparent hover:underline transition">
          Logout
        </a>
      </div>
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
            <p class="text-xs font-bold text-white truncate"><?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?>
            </p>
            <span
              class="text-[11px] text-gray-400 block"><?= htmlspecialchars($_SESSION['role'] ?? 'Administrator') ?></span>
          </div>
          <button id="menu-close" type="button" aria-label="Close Navigation"
            class="text-gray-400 hover:text-white p-1 shrink-0">
            <i class="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>


        <!-- Mobile & Tablet Search Form -->
        <div class="px-4 mb-4 lg:hidden">
          <form class="relative" action="search.php" method="GET">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
              <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </span>
            <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
              placeholder="Search student, employee, ID..." required
              class="w-full pl-8 pr-3 py-2 text-xs rounded bg-white/10 text-white placeholder-gray-400 focus:outline-none focus:bg-white focus:text-gray-900 border border-white/10">
          </form>
        </div>


        <!-- School Year Banner -->
        <div
          class="px-6 py-2 mb-3 bg-black/20 text-xs md:text-[11px] font-bold text-brand-accent uppercase tracking-wider">
          SY: <?= date('Y') ?>-<?= date('Y', strtotime('+1 year')) ?>-1
        </div>


        <!-- Navigation Links -->
        <nav class="space-y-1">
          <a href="index.php"
            class="flex items-center px-6 py-3 text-sm md:text-xs font-medium text-gray-300 hover:bg-white/5 hover:text-white transition-colors">
            <i class="fa-solid fa-house w-6 text-gray-400"></i>
            <span>Dashboard</span>
          </a>


          <!-- Active Link (Students) -->
          <a href="students.php"
            class="flex items-center px-6 py-3 text-sm md:text-xs font-semibold text-white bg-white/10 border-l-4 border-brand-accent">
            <i class="fa-solid fa-user-graduate w-6 text-brand-accent"></i>
            <span>Students</span>
          </a>


          <a href="employee.php"
            class="flex items-center px-6 py-3 text-sm md:text-xs font-medium text-gray-300 hover:bg-white/5 hover:text-white transition-colors">
            <i class="fa-solid fa-id-badge w-6 text-gray-400"></i>
            <span>Employees</span>
          </a>


          <a href="consultations.php"
            class="flex items-center px-6 py-3 text-sm md:text-xs font-medium text-gray-300 hover:bg-white/5 hover:text-white transition-colors">
            <i class="fa-solid fa-clipboard-list w-6 text-gray-400"></i>
            <span>Consultations</span>
          </a>
        </nav>
      </div>


      <!-- Footer Info -->
      <div class="p-4 text-[11px] text-gray-500 border-t border-gray-800 text-center">
        ACSAT Medical Portal &copy; <?= date('Y') ?>
      </div>
    </aside>


    <!-- MAIN CONTENT CANVAS -->
    <main class="flex-1 overflow-y-auto overflow-x-hidden p-4 sm:p-6 bg-gray-100">
      <div class="max-w-6xl mx-auto space-y-4 sm:space-y-5">


        <!-- BREADCRUMB & HEADING -->
        <div>
          <a href="students.php"
            class="text-xs font-bold text-brand-navy hover:underline inline-flex items-center gap-1.5 mb-1">
            <i class="fa-solid fa-arrow-left text-[10px]"></i> Back to Students
          </a>
          <h2 class="text-lg sm:text-xl font-bold text-gray-800">Student Medical Record</h2>
        </div>


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
          class="bg-white rounded-lg border border-gray-200 p-4 sm:p-5 shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
          <div class="flex items-center gap-3.5 sm:gap-4 min-w-0">
            <div
              class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-blue-100 text-brand-navy font-bold flex items-center justify-center text-base sm:text-lg shadow-inner shrink-0">
              <?= htmlspecialchars($initials) ?>
            </div>
            <div class="min-w-0">
              <h2 class="text-base sm:text-lg font-bold text-gray-900 break-words"><?= htmlspecialchars($full_name) ?>
              </h2>
              <p class="text-xs text-gray-500 mt-0.5">Student ID: <strong
                  class="text-gray-800"><?= htmlspecialchars($student['student_id_code'] ?? 'N/A') ?></strong></p>
              <span class="inline-block mt-1.5 px-2.5 py-0.5 text-[10px] font-bold text-white bg-brand-green rounded">
                <?= htmlspecialchars($student['status'] ?? 'Active') ?>
              </span>
            </div>
          </div>


          <div
            class="flex flex-wrap items-center gap-2 w-full sm:w-auto pt-3 sm:pt-0 border-t sm:border-t-0 border-gray-100 shrink-0">
            <?php if ($can_export_pdf): ?>
              <a href="export-pdf.php?type=student&id=<?= $student_db_id ?>&autoprint=1" target="_blank"
                class="flex-1 sm:flex-initial justify-center inline-flex items-center gap-1.5 bg-brand-green hover:bg-emerald-700 text-white font-bold text-xs py-2.5 sm:py-2 px-3.5 rounded-lg sm:rounded shadow-sm transition-all">
                <i class="fa-solid fa-file-pdf"></i> Export PDF
              </a>
            <?php endif; ?>


            <a href="edit-student.php?id=<?= $student_db_id ?>"
              class="flex-1 sm:flex-initial justify-center inline-flex items-center gap-1.5 border border-gray-300 hover:bg-gray-50 text-gray-700 font-semibold text-xs py-2.5 sm:py-2 px-3.5 rounded-lg sm:rounded transition-all">
              <i class="fa-solid fa-pen-to-square text-gray-500"></i> Edit Profile
            </a>
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
                  class="text-gray-800 break-words"><?= htmlspecialchars($student['first_name'] ?? 'N/A') ?></strong>
              </div>
              <div>
                <span class="text-gray-400 block text-[11px]">Middle Name</span>
                <strong
                  class="text-gray-800 break-words"><?= htmlspecialchars($student['middle_name'] ?? 'N/A') ?></strong>
              </div>
              <div>
                <span class="text-gray-400 block text-[11px]">Last Name</span>
                <strong
                  class="text-gray-800 break-words"><?= htmlspecialchars($student['last_name'] ?? 'N/A') ?></strong>
              </div>
              <div>
                <span class="text-gray-400 block text-[11px]">Date of Birth</span>
                <strong class="text-gray-800"><?= htmlspecialchars($formatted_dob) ?></strong>
              </div>
              <div>
                <span class="text-gray-400 block text-[11px]">Sex</span>
                <strong class="text-gray-800"><?= htmlspecialchars($student['sex'] ?? 'N/A') ?></strong>
              </div>
              <div>
                <span class="text-gray-400 block text-[11px]">Course</span>
                <strong class="text-gray-800 break-words"><?= htmlspecialchars($student['course'] ?? 'N/A') ?></strong>
              </div>
              <div>
                <span class="text-gray-400 block text-[11px]">Year Level</span>
                <strong class="text-gray-800"><?= htmlspecialchars($student['year_level'] ?? 'N/A') ?></strong>
              </div>
              <div>
                <span class="text-gray-400 block text-[11px]">Section</span>
                <strong class="text-gray-800"><?= htmlspecialchars($student['section'] ?? 'N/A') ?></strong>
              </div>
              <div class="sm:col-span-2">
                <span class="text-gray-400 block text-[11px]">Contact Number</span>
                <?php if (!empty($student['contact_number'])): ?>
                  <a href="tel:<?= htmlspecialchars($student['contact_number']) ?>"
                    class="font-bold text-brand-navy hover:underline">
                    <?= htmlspecialchars($student['contact_number']) ?>
                  </a>
                <?php else: ?>
                  <strong class="text-gray-800">N/A</strong>
                <?php endif; ?>
              </div>
              <div class="sm:col-span-2">
                <span class="text-gray-400 block text-[11px]">Permanent Address</span>
                <strong
                  class="text-gray-800 break-words"><?= htmlspecialchars($student['permanent_address'] ?? $student['address'] ?? 'N/A') ?></strong>
              </div>
            </div>
          </div>


          <!-- MEDICAL & EMERGENCY INFORMATION -->
          <div class="space-y-4 sm:space-y-5">
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
                    <?= htmlspecialchars($student['blood_type'] ?? 'N/A') ?>
                  </span>
                </div>
                <div>
                  <span class="text-gray-400 block text-[11px]">Allergies</span>
                  <strong
                    class="text-gray-800 break-words"><?= htmlspecialchars($student['allergies'] ?? 'None reported') ?></strong>
                </div>
                <div>
                  <span class="text-gray-400 block text-[11px]">Medical Conditions</span>
                  <strong
                    class="text-gray-800 break-words"><?= htmlspecialchars($student['medical_conditions'] ?? 'None reported') ?></strong>
                </div>
                <div>
                  <span class="text-gray-400 block text-[11px]">Current Medication</span>
                  <strong
                    class="text-gray-800 break-words"><?= htmlspecialchars($student['current_medication'] ?? 'None') ?></strong>
                </div>
              </div>
            </div>


            <!-- EMERGENCY CONTACT -->
            <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-5 shadow-sm space-y-3">
              <h3
                class="text-xs font-bold text-brand-navy uppercase tracking-wider border-b border-gray-200 pb-2 flex items-center gap-2">
                <i class="fa-solid fa-phone text-brand-accent"></i> Emergency Contact
              </h3>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 text-xs">
                <div>
                  <span class="text-gray-400 block text-[11px]">Contact Name</span>
                  <strong
                    class="text-gray-800 break-words"><?= htmlspecialchars($student['emergency_contact_name'] ?? 'N/A') ?></strong>
                </div>
                <div>
                  <span class="text-gray-400 block text-[11px]">Contact Number</span>
                  <?php if (!empty($student['emergency_contact_number'])): ?>
                    <a href="tel:<?= htmlspecialchars($student['emergency_contact_number']) ?>"
                      class="font-bold text-brand-navy hover:underline">
                      <?= htmlspecialchars($student['emergency_contact_number']) ?>
                    </a>
                  <?php else: ?>
                    <strong class="text-gray-800">N/A</strong>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>


        </div>


        <!-- RECORD NEW CLINIC VISIT FORM -->
        <section class="bg-white rounded-lg border border-gray-200 p-4 sm:p-5 shadow-sm space-y-4">
          <h3
            class="text-xs font-bold text-brand-navy uppercase tracking-wider border-b border-gray-200 pb-2 flex items-center gap-2">
            <i class="fa-solid fa-plus-circle text-brand-accent"></i> Record New Clinic Visit
          </h3>


          <form method="POST" action="student-record.php?id=<?= $student_db_id ?>" class="space-y-4">
            <input type="hidden" name="action" value="add_consultation">


            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
              <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Visit Date <span
                    class="text-red-500">*</span></label>
                <input type="date" name="consultation_date" value="<?= date('Y-m-d') ?>" required
                  class="w-full px-3 py-2.5 sm:py-1.5 text-base sm:text-xs rounded-lg sm:rounded border border-gray-300 bg-white focus:outline-none focus:border-brand-navy focus:ring-1 focus:ring-brand-navy transition-all" />
              </div>


              <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">BP</label>
                <input type="text" name="bp" placeholder="e.g. 120/80"
                  class="w-full px-3 py-2.5 sm:py-1.5 text-base sm:text-xs rounded-lg sm:rounded border border-gray-300 focus:outline-none focus:border-brand-navy focus:ring-1 focus:ring-brand-navy transition-all" />
              </div>


              <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Temperature</label>
                <input type="text" name="temp" placeholder="e.g. 36.6°C"
                  class="w-full px-3 py-2.5 sm:py-1.5 text-base sm:text-xs rounded-lg sm:rounded border border-gray-300 focus:outline-none focus:border-brand-navy focus:ring-1 focus:ring-brand-navy transition-all" />
              </div>


              <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Heart Rate</label>
                <input type="text" name="hr" placeholder="e.g. 72 bpm"
                  class="w-full px-3 py-2.5 sm:py-1.5 text-base sm:text-xs rounded-lg sm:rounded border border-gray-300 focus:outline-none focus:border-brand-navy focus:ring-1 focus:ring-brand-navy transition-all" />
              </div>


              <div class="sm:col-span-2 lg:col-span-1">
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Chief Complaint <span
                    class="text-red-500">*</span></label>
                <input type="text" name="chief_complaint" required placeholder="e.g. Fever, Headache"
                  class="w-full px-3 py-2.5 sm:py-1.5 text-base sm:text-xs rounded-lg sm:rounded border border-gray-300 focus:outline-none focus:border-brand-navy focus:ring-1 focus:ring-brand-navy transition-all" />
              </div>
            </div>


            <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
              <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Treatment / Medication</label>
                <input type="text" name="treatment" placeholder="e.g. Paracetamol 500mg"
                  class="w-full px-3 py-2.5 sm:py-1.5 text-base sm:text-xs rounded-lg sm:rounded border border-gray-300 focus:outline-none focus:border-brand-navy focus:ring-1 focus:ring-brand-navy transition-all" />
              </div>


              <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Diagnosis</label>
                <input type="text" name="diagnosis" placeholder="e.g. Mild Hyperthermia"
                  class="w-full px-3 py-2.5 sm:py-1.5 text-base sm:text-xs rounded-lg sm:rounded border border-gray-300 focus:outline-none focus:border-brand-navy focus:ring-1 focus:ring-brand-navy transition-all" />
              </div>


              <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Remarks / Recommendations</label>
                <input type="text" name="remarks" placeholder="e.g. Rested for 30 mins"
                  class="w-full px-3 py-2.5 sm:py-1.5 text-base sm:text-xs rounded-lg sm:rounded border border-gray-300 focus:outline-none focus:border-brand-navy focus:ring-1 focus:ring-brand-navy transition-all" />
              </div>
            </div>


            <button type="submit"
              class="w-full sm:w-auto justify-center bg-brand-navy hover:bg-blue-900 active:scale-[0.99] text-white font-bold text-xs py-2.5 sm:py-2 px-5 rounded-lg sm:rounded shadow-sm transition-all flex items-center gap-2">
              <i class="fa-solid fa-check text-xs"></i> Save Visit Record
            </button>
          </form>
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
                    <strong class="text-xs font-bold text-gray-900">
                      <i class="fa-regular fa-calendar mr-1 text-brand-accent"></i>
                      <?= date('F j, Y', strtotime($c['consultation_date'])) ?>
                    </strong>
                    <span class="text-[11px] text-gray-500 font-medium">
                      <?= date('h:i A', strtotime($c['consultation_date'])) ?>
                    </span>
                  </div>


                  <div class="space-y-1.5 text-xs text-gray-600 break-words">
                    <?php if (!empty($c['vital_signs'])): ?>
                      <p><strong>Vital Signs:</strong> <?= htmlspecialchars($c['vital_signs']) ?></p>
                    <?php endif; ?>


                    <p><strong>Chief Complaint:</strong>
                      <?= htmlspecialchars($c['chief_complaint']) ?>
                    </p>


                    <?php if (!empty($c['treatment'])): ?>
                      <p><strong>Treatment:</strong> <?= htmlspecialchars($c['treatment']) ?></p>
                    <?php endif; ?>


                    <p><strong>Diagnosis:</strong>
                      <?= htmlspecialchars(!empty($c['diagnosis']) ? $c['diagnosis'] : 'N/A') ?>
                    </p>
                    <p><strong>Remarks:</strong> <?= htmlspecialchars(!empty($c['remarks']) ? $c['remarks'] : 'N/A') ?>
                    </p>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <p class="text-xs text-gray-400 py-4 text-center italic">No consultation history found for this student.</p>
          <?php endif; ?>
        </section>


      </div>
    </main>
  </div>


  <!-- MOBILE SIDEBAR TOGGLE SCRIPT -->
  <script>
    const menuToggle = document.getElementById('menu-toggle');
    const menuClose = document.getElementById('menu-close');
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');


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


    menuToggle.addEventListener('click', openSidebar);
    menuClose.addEventListener('click', closeSidebar);
    backdrop.addEventListener('click', closeSidebar);
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeSidebar();
    });
  </script>


</body>


</html>