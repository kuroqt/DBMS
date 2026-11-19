<?php
require_once 'db.php';
require_once 'auth.php';


date_default_timezone_set('Asia/Manila');


$currentDate = date('l, F d, Y');
$currentTime = date('h:i A');


$error = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $emp_code = trim($_POST['emp_code'] ?? '');
  $title = trim($_POST['title'] ?? '');
  $first_name = trim($_POST['first_name'] ?? '');
  $last_name = trim($_POST['last_name'] ?? '');
  $dob = $_POST['dob'] ?? '';
  $sex = $_POST['sex'] ?? 'Male';
  $position = trim($_POST['position'] ?? '');
  $department = trim($_POST['department'] ?? '');
  $permanent_address = trim($_POST['permanent_address'] ?? '');
  $contact_number = trim($_POST['contact_number'] ?? '');
  $blood_type = trim($_POST['blood_type'] ?? 'A+');
  $allergies = trim($_POST['allergies'] ?? '') ?: 'None reported';
  $medical_conditions = trim($_POST['medical_conditions'] ?? '') ?: 'None reported';
  $current_medication = trim($_POST['current_medication'] ?? '') ?: 'None';
  $emergency_contact_name = trim($_POST['emergency_contact_name'] ?? '');
  $emergency_contact_number = trim($_POST['emergency_contact_number'] ?? '');


  try {
    $stmt = $pdo->prepare("INSERT INTO employees (
        emp_code,
        title,
        first_name,
        last_name,
        dob,
        sex,
        position,
        department,
        contact_number,
        status,
        blood_type,
        allergies,
        medical_conditions,
        current_medication,
        emergency_contact_name,
        emergency_contact_number,
        permanent_address
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active',
        ?, ?, ?, ?, ?, ?, ?
    )");


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
      $emergency_contact_number,
      $permanent_address
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


<body class="bg-gray-100 font-sans text-gray-800 flex flex-col min-h-screen md:h-screen md:overflow-hidden">


  <!-- TOP HEADER (SIMS Navy Theme) -->
  <header
    class="bg-brand-navy text-white h-16 flex items-center justify-between px-4 md:px-6 shadow-md z-30 shrink-0 sticky top-0">
    <!-- Mobile Menu Toggle + Brand / Title -->
    <div class="flex items-center space-x-3">
      <button id="mobile-menu-btn" type="button" aria-label="Toggle Menu"
        class="md:hidden text-white hover:text-brand-accent focus:outline-none p-1.5 rounded">
        <i class="fa-solid fa-bars text-lg"></i>
      </button>


      <div class="bg-white p-1 rounded shrink-0">
        <img src="images/acsat.jpg" alt="ACSAT Logo" class="h-7 md:h-8 w-auto object-contain"
          onerror="this.style.display='none'">
      </div>
      <div>
        <h1 class="text-sm md:text-base font-bold leading-tight">Asian College Computer-Based EMR System</h1>
        <p class="text-[11px] md:text-xs text-blue-200 hidden sm:block">Student & Employee Health Portal</p>
      </div>
    </div>


    <!-- Search, Date/Time & Profile Bar -->
    <div class="flex items-center space-x-3 md:space-x-6">
      <!-- Desktop Search Form -->
      <form class="relative w-64 hidden md:block" action="search.php" method="GET">
        <button type="submit" class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-300">
          <i class="fa-solid fa-magnifying-glass text-xs"></i>
        </button>
        <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
          placeholder="Search student, employee, ID..." required
          class="w-full pl-8 pr-4 py-1.5 text-xs rounded bg-white/10 text-white placeholder-gray-300 focus:outline-none focus:bg-white focus:text-gray-900 border border-white/20 transition-all">
      </form>


      <!-- Date & Time Display -->
      <div class="hidden lg:block text-right border-l border-blue-800 pl-6">
        <strong class="text-xs font-bold text-white block leading-tight"><?= htmlspecialchars($currentDate) ?></strong>
        <span class="text-[11px] text-blue-200 block"><?= htmlspecialchars($currentTime) ?></span>
      </div>


      <!-- User Info -->
      <div class="flex items-center space-x-2 md:space-x-3 md:border-l md:border-blue-800 md:pl-6">
        <div
          class="w-8 h-8 rounded-full bg-blue-100 text-brand-navy font-bold flex items-center justify-center text-xs shadow shrink-0">
          <?= strtoupper(substr($_SESSION['full_name'] ?? 'A', 0, 1)) ?>
        </div>
        <div class="text-right hidden sm:block">
          <p class="text-xs font-bold leading-tight"><?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?></p>
          <span
            class="text-[11px] text-blue-200 block"><?= htmlspecialchars($_SESSION['role'] ?? 'Administrator') ?></span>
        </div>
        <a href="logout.php"
          class="ml-1 md:ml-2 text-xs text-red-300 hover:text-red-100 font-semibold hover:underline">Logout</a>
      </div>
    </div>
  </header>


  <!-- MOBILE SIDEBAR BACKDROP -->
  <div id="sidebar-backdrop" class="fixed inset-0 bg-black/50 z-30 hidden md:hidden transition-opacity"></div>


  <!-- MAIN BODY WRAPPER -->
  <div class="flex flex-1 md:overflow-hidden relative">


    <!-- SIDEBAR (Responsive Drawer on Mobile, Fixed on Desktop) -->
    <aside id="sidebar"
      class="fixed inset-y-0 left-0 z-40 w-64 md:w-60 bg-brand-sidebar text-gray-300 flex flex-col justify-between shrink-0 shadow-xl transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-200 ease-in-out pt-16 md:pt-0">
      <div class="py-4 overflow-y-auto">
        <!-- Mobile User Info Summary inside Drawer -->
        <div class="px-6 py-3 mb-2 border-b border-gray-800 sm:hidden">
          <p class="text-xs font-bold text-white"><?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?></p>
          <p class="text-[11px] text-gray-400"><?= htmlspecialchars($_SESSION['role'] ?? 'Administrator') ?></p>
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


          <a href="students.php"
            class="flex items-center px-6 py-3 text-sm md:text-xs font-medium text-gray-300 hover:bg-white/5 hover:text-white transition-colors">
            <i class="fa-solid fa-user-graduate w-6 text-gray-400"></i>
            <span>Students</span>
          </a>


          <!-- Active Link (Employees) -->
          <a href="employee.php"
            class="flex items-center px-6 py-3 text-sm md:text-xs font-semibold text-white bg-white/10 border-l-4 border-brand-accent">
            <i class="fa-solid fa-id-badge w-6 text-brand-accent"></i>
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
    <main class="flex-1 overflow-y-auto p-4 md:p-6 bg-gray-100 space-y-4 md:space-y-5 w-full">


      <!-- MOBILE SEARCH BAR -->
      <form class="relative w-full md:hidden" action="search.php" method="GET">
        <button type="submit" class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
          <i class="fa-solid fa-magnifying-glass text-xs"></i>
        </button>
        <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
          placeholder="Search student, employee, ID..." required
          class="w-full pl-9 pr-4 py-2.5 text-xs rounded-lg bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-navy border border-gray-200 shadow-sm">
      </form>


      <!-- PAGE HEADING & BREADCRUMB -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div>
          <a href="employee.php"
            class="text-xs font-bold text-brand-navy hover:underline inline-flex items-center gap-1 mb-1">
            <i class="fa-solid fa-arrow-left text-[10px]"></i> Back to Employees
          </a>
          <h2 class="text-lg md:text-xl font-bold text-gray-800">Add New Employee Record</h2>
          <p class="text-xs text-gray-500 mt-0.5">Fill out the form below to register a new employee.</p>
        </div>
        <div class="text-[11px] text-gray-500 lg:hidden">
          <?= htmlspecialchars($currentDate) ?> &bull; <?= htmlspecialchars($currentTime) ?>
        </div>
      </div>


      <!-- ERROR BANNER -->
      <?php if (!empty($error)): ?>
        <div
          class="p-3 bg-red-50 border border-red-200 text-red-700 text-xs font-semibold rounded-lg flex items-center gap-2">
          <i class="fa-solid fa-circle-exclamation text-sm shrink-0"></i>
          <span><?= htmlspecialchars($error) ?></span>
        </div>
      <?php endif; ?>


      <!-- FORM CONTAINER CARD -->
      <form method="POST" action="add-employee.php"
        class="bg-white rounded-lg border border-gray-200 p-4 sm:p-6 shadow-sm space-y-6">


        <!-- SECTION 1: PERSONAL INFORMATION -->
        <div class="space-y-4">
          <h3
            class="text-xs font-bold text-brand-navy uppercase tracking-wider border-b border-gray-200 pb-2 flex items-center gap-2">
            <i class="fa-solid fa-user text-brand-accent"></i> Personal Information
          </h3>


          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1">Employee ID Code *</label>
              <input type="text" name="emp_code" required placeholder="e.g. EMP-0002"
                class="w-full px-3 py-2 md:py-1.5 text-sm md:text-xs rounded border border-gray-300 focus:outline-none focus:border-brand-navy transition-all" />
            </div>


            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1">Title</label>
              <input type="text" name="title" placeholder="e.g. Engr. / Dr. / Mr."
                class="w-full px-3 py-2 md:py-1.5 text-sm md:text-xs rounded border border-gray-300 focus:outline-none focus:border-brand-navy transition-all" />
            </div>


            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1">First Name *</label>
              <input type="text" name="first_name" required
                class="w-full px-3 py-2 md:py-1.5 text-sm md:text-xs rounded border border-gray-300 focus:outline-none focus:border-brand-navy transition-all" />
            </div>


            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1">Last Name *</label>
              <input type="text" name="last_name" required
                class="w-full px-3 py-2 md:py-1.5 text-sm md:text-xs rounded border border-gray-300 focus:outline-none focus:border-brand-navy transition-all" />
            </div>


            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1">Date of Birth *</label>
              <input type="date" name="dob" required
                class="w-full px-3 py-2 md:py-1.5 text-sm md:text-xs rounded border border-gray-300 focus:outline-none focus:border-brand-navy transition-all" />
            </div>


            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1">Sex *</label>
              <select name="sex" required
                class="w-full px-3 py-2 md:py-1.5 text-sm md:text-xs rounded border border-gray-300 focus:outline-none focus:border-brand-navy transition-all bg-white">
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
            </div>


            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1">Position *</label>
              <input type="text" name="position" required placeholder="e.g. Instructor"
                class="w-full px-3 py-2 md:py-1.5 text-sm md:text-xs rounded border border-gray-300 focus:outline-none focus:border-brand-navy transition-all" />
            </div>


            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1">Department *</label>
              <input type="text" name="department" required placeholder="e.g. CSE Department"
                class="w-full px-3 py-2 md:py-1.5 text-sm md:text-xs rounded border border-gray-300 focus:outline-none focus:border-brand-navy transition-all" />
            </div>


            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1">Contact Number *</label>
              <input type="text" name="contact_number" required
                class="w-full px-3 py-2 md:py-1.5 text-sm md:text-xs rounded border border-gray-300 focus:outline-none focus:border-brand-navy transition-all" />
            </div>


            <div class="sm:col-span-2 lg:col-span-3">
              <label class="block text-xs font-semibold text-gray-700 mb-1">Permanent Address *</label>
              <input type="text" name="permanent_address" required
                class="w-full px-3 py-2 md:py-1.5 text-sm md:text-xs rounded border border-gray-300 focus:outline-none focus:border-brand-navy transition-all" />
            </div>
          </div>
        </div>


        <!-- SECTION 2: MEDICAL DETAILS -->
        <div class="space-y-4">
          <h3
            class="text-xs font-bold text-brand-navy uppercase tracking-wider border-b border-gray-200 pb-2 flex items-center gap-2">
            <i class="fa-solid fa-notes-medical text-brand-accent"></i> Medical Details
          </h3>


          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1">Blood Type</label>
              <select name="blood_type"
                class="w-full px-3 py-2 md:py-1.5 text-sm md:text-xs rounded border border-gray-300 focus:outline-none focus:border-brand-navy transition-all bg-white">
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
              <label class="block text-xs font-semibold text-gray-700 mb-1">Allergies</label>
              <input type="text" name="allergies" placeholder="None reported"
                class="w-full px-3 py-2 md:py-1.5 text-sm md:text-xs rounded border border-gray-300 focus:outline-none focus:border-brand-navy transition-all" />
            </div>


            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1">Medical Conditions</label>
              <input type="text" name="medical_conditions" placeholder="None reported"
                class="w-full px-3 py-2 md:py-1.5 text-sm md:text-xs rounded border border-gray-300 focus:outline-none focus:border-brand-navy transition-all" />
            </div>


            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1">Current Medication</label>
              <input type="text" name="current_medication" placeholder="None"
                class="w-full px-3 py-2 md:py-1.5 text-sm md:text-xs rounded border border-gray-300 focus:outline-none focus:border-brand-navy transition-all" />
            </div>
          </div>
        </div>


        <!-- SECTION 3: EMERGENCY CONTACT -->
        <div class="space-y-4">
          <h3
            class="text-xs font-bold text-brand-navy uppercase tracking-wider border-b border-gray-200 pb-2 flex items-center gap-2">
            <i class="fa-solid fa-phone text-brand-accent"></i> Emergency Contact
          </h3>


          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1">Contact Name *</label>
              <input type="text" name="emergency_contact_name" required
                class="w-full px-3 py-2 md:py-1.5 text-sm md:text-xs rounded border border-gray-300 focus:outline-none focus:border-brand-navy transition-all" />
            </div>


            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1">Contact Number *</label>
              <input type="text" name="emergency_contact_number" required
                class="w-full px-3 py-2 md:py-1.5 text-sm md:text-xs rounded border border-gray-300 focus:outline-none focus:border-brand-navy transition-all" />
            </div>
          </div>
        </div>


        <!-- ACTION BUTTONS -->
        <div class="pt-4 border-t border-gray-200 flex flex-col-reverse sm:flex-row items-center justify-end gap-3">
          <a href="employee.php"
            class="w-full sm:w-auto text-center px-4 py-2.5 sm:py-2 border border-gray-300 hover:bg-gray-50 text-gray-700 font-semibold text-xs rounded transition-all">
            Cancel
          </a>
          <button type="submit"
            class="w-full sm:w-auto justify-center px-5 py-2.5 sm:py-2 bg-brand-green hover:bg-emerald-700 text-white font-bold text-xs rounded shadow-sm transition-all flex items-center gap-2 cursor-pointer">
            <i class="fa-solid fa-check text-xs"></i> Save Employee Record
          </button>
        </div>


      </form>


    </main>
  </div>


  <!-- MOBILE SIDEBAR TOGGLE SCRIPT -->
  <script>
    const menuBtn = document.getElementById('mobile-menu-btn');
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');


    function toggleSidebar() {
      sidebar.classList.toggle('-translate-x-full');
      backdrop.classList.toggle('hidden');
    }


    menuBtn.addEventListener('click', toggleSidebar);
    backdrop.addEventListener('click', toggleSidebar);
  </script>


</body>


</html>