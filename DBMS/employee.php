<?php
require_once 'db.php';
require_once 'auth.php';

date_default_timezone_set('Asia/Manila');

$currentDate = date('l, F d, Y');
$currentTime = date('h:i A');

$search = trim($_GET['search'] ?? '');

try {
  if (!empty($search)) {
    $stmt = $pdo->prepare("
      SELECT * FROM employees
      WHERE emp_code LIKE :search
         OR employee_id_code LIKE :search
         OR first_name LIKE :search
         OR last_name LIKE :search
         OR CONCAT(first_name, ' ', last_name) LIKE :search
         OR department LIKE :search
         OR position LIKE :search
      ORDER BY id DESC
    ");
    $stmt->execute(['search' => "%{$search}%"]);
  } else {
    $stmt = $pdo->query("SELECT * FROM employees ORDER BY id DESC");
  }

  $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
  try {
    if (!empty($search)) {
      $stmt = $pdo->prepare("
        SELECT * FROM employees
        WHERE emp_code LIKE :search
           OR first_name LIKE :search
           OR last_name LIKE :search
        ORDER BY id DESC
      ");
      $stmt->execute(['search' => "%{$search}%"]);
    } else {
      $stmt = $pdo->query("SELECT * FROM employees ORDER BY id DESC");
    }

    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

  } catch (Exception $ex) {
    $employees = [];
  }
}
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employee Records - Medical Record System</title>

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
    <!-- Left: Hamburger Menu (Mobile) + Brand / Title -->
    <div class="flex items-center space-x-3">
      <button id="menu-toggle" type="button" aria-label="Toggle Menu"
        class="md:hidden text-white hover:text-brand-accent focus:outline-none p-1.5 rounded-lg hover:bg-white/10 transition">
        <i class="fa-solid fa-bars text-lg"></i>
      </button>

      <div class="bg-white p-1 rounded shrink-0">
        <img src="images/acsat.jpg" alt="ACSAT Logo" class="h-7 md:h-8 w-auto object-contain"
          onerror="this.style.display='none'">
      </div>
      <div>
        <h1 class="text-sm md:text-base font-bold leading-tight truncate max-w-[180px] sm:max-w-none">
          Asian College Computer-Based EMR System
        </h1>
        <p class="hidden sm:block text-xs text-blue-200">Student & Employee Health Portal</p>
      </div>
    </div>

    <!-- Search, Date/Time & Profile Bar -->
    <div class="flex items-center space-x-3 md:space-x-6">
      <!-- Desktop Global Search Form -->
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
          class="ml-1 md:ml-2 text-xs text-red-300 hover:text-red-100 font-semibold hover:underline px-2 py-1 rounded bg-red-950/30 sm:bg-transparent">
          Logout
        </a>
      </div>
    </div>
  </header>

  <!-- MAIN BODY WRAPPER -->
  <div class="flex flex-1 md:overflow-hidden relative">

    <!-- MOBILE SIDEBAR BACKDROP -->
    <div id="sidebar-backdrop" class="fixed inset-0 bg-black/50 z-30 hidden md:hidden transition-opacity"></div>

    <!-- SIDEBAR (SIMS Dark Theme) -->
    <aside id="sidebar"
      class="fixed inset-y-0 left-0 z-40 w-64 md:w-60 bg-brand-sidebar text-gray-300 flex flex-col justify-between shrink-0 shadow-xl transform -translate-x-full md:translate-x-0 md:static transition-transform duration-200 ease-in-out">
      <div class="py-4 overflow-y-auto">
        <!-- Mobile Drawer Header -->
        <div class="flex items-center justify-between px-6 pb-3 mb-3 border-b border-gray-800 md:hidden">
          <span class="text-sm font-bold text-white">Navigation</span>
          <button id="menu-close" type="button" class="text-gray-400 hover:text-white p-1">
            <i class="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>

        <!-- Mobile Global Search Bar -->
        <div class="px-4 mb-4 lg:hidden">
          <form class="relative" action="search.php" method="GET">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
              <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </span>
            <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
              placeholder="Search student, ID..." required
              class="w-full pl-8 pr-3 py-2 text-xs rounded bg-white/10 text-white placeholder-gray-400 focus:outline-none focus:bg-white focus:text-gray-900 border border-white/10">
          </form>
        </div>

        <!-- School Year Banner -->
        <div class="px-6 py-2 mb-3 bg-black/20 text-sm font-bold text-brand-accent uppercase tracking-wider">
          SY: <?= date('Y') ?>-<?= date('Y', strtotime('+1 year')) ?>-1
        </div>

        <!-- Navigation Links -->
        <nav class="space-y-1">
          <a href="index.php"
            class="flex items-center px-6 py-3 text-sm font-medium text-gray-300 hover:bg-white/5 hover:text-white transition-colors">
            <i class="fa-solid fa-house w-6 text-gray-400"></i>
            <span>Dashboard</span>
          </a>

          <a href="students.php"
            class="flex items-center px-6 py-3 text-sm font-medium text-gray-300 hover:bg-white/5 hover:text-white transition-colors">
            <i class="fa-solid fa-user-graduate w-6 text-gray-400"></i>
            <span>Students</span>
          </a>

          <!-- Active Link (Employees) -->
          <a href="employee.php"
            class="flex items-center px-6 py-3 text-sm font-semibold text-white bg-white/10 border-l-4 border-brand-accent">
            <i class="fa-solid fa-id-badge w-6 text-brand-accent"></i>
            <span>Employees</span>
          </a>

          <a href="consultations.php"
            class="flex items-center px-6 py-3 text-sm font-medium text-gray-300 hover:bg-white/5 hover:text-white transition-colors">
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
    <main class="flex-1 overflow-y-auto p-4 sm:p-6 bg-gray-100 space-y-5">

      <!-- PAGE HEADING -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div>
          <h2 class="text-lg sm:text-xl font-bold text-gray-800">Employee Medical Records</h2>
          <p class="text-xs text-gray-500 mt-0.5">Manage and organize medical records for faculty and staff.</p>
        </div>
        <div class="text-xs text-gray-500 font-medium">
          Showing <span class="font-bold text-gray-800"><?= count($employees) ?></span> record(s)
        </div>
      </div>

      <!-- TABLE CONTAINER CARD -->
      <section class="bg-white rounded-lg border border-gray-200 p-4 sm:p-5 shadow-sm space-y-4">

        <!-- FILTER & ACTION TOOLBAR -->
        <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3">

          <!-- Search Employees Form -->
          <form method="GET" action="employee.php" class="flex items-center gap-2 flex-1 max-w-md">
            <div class="relative flex-1">
              <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
              </span>
              <input type="text" name="search" placeholder="Search employee ID or name..."
                value="<?= htmlspecialchars($search) ?>"
                class="w-full pl-8 pr-3 py-2 sm:py-1.5 text-base sm:text-xs rounded border border-gray-300 focus:outline-none focus:border-brand-navy transition-all" />
            </div>

            <button type="submit"
              class="px-3.5 py-2 sm:py-1.5 bg-gray-100 hover:bg-gray-200 border border-gray-300 text-gray-700 font-semibold text-xs rounded transition-all shrink-0">
              Search
            </button>

            <?php if (!empty($search)): ?>
              <a href="employee.php" class="text-xs text-gray-500 hover:text-gray-800 underline px-1 shrink-0">Clear</a>
            <?php endif; ?>
          </form>

          <!-- Add Employee Button -->
          <a href="add-employee.php"
            class="inline-flex items-center justify-center gap-2 bg-brand-navy hover:bg-blue-900 text-white font-medium text-xs py-2.5 sm:py-2 px-4 rounded shadow-sm transition-all shrink-0">
            <i class="fa-solid fa-plus text-[10px]"></i> Add Employee
          </a>

        </div>

        <?php if (!empty($employees)): ?>

          <!-- MOBILE CARD VIEW (< 768px) -->
          <div class="grid grid-cols-1 gap-3 md:hidden">
            <?php foreach ($employees as $employee): ?>
              <?php
              $emp_code = $employee['emp_code'] ?? $employee['employee_id_code'] ?? 'N/A';

              if (!empty($employee['first_name']) || !empty($employee['last_name'])) {
                $title = !empty($employee['title']) ? $employee['title'] . ' ' : '';
                $full_name = trim($title . ($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''));
              } else {
                $full_name = $employee['name'] ?? 'N/A';
              }

              $position = $employee['position'] ?? 'N/A';
              $department = $employee['department'] ?? 'N/A';
              $contact = $employee['contact_number'] ?? 'N/A';
              $status = $employee['status'] ?? 'Active';
              ?>

              <div class="p-3.5 rounded-lg border border-gray-200 bg-gray-50/70 space-y-2.5">
                <div class="flex items-start justify-between gap-2">
                  <div>
                    <span class="text-[11px] font-bold text-brand-navy uppercase tracking-wide block">
                      <?= htmlspecialchars($emp_code) ?>
                    </span>
                    <h3 class="text-sm font-bold text-gray-900 leading-snug">
                      <?= htmlspecialchars($full_name) ?>
                    </h3>
                  </div>
                  <span class="inline-block px-2 py-0.5 text-[10px] font-bold text-white bg-brand-green rounded shrink-0">
                    <?= htmlspecialchars($status) ?>
                  </span>
                </div>

                <div class="grid grid-cols-2 gap-2 pt-2 border-t border-gray-200/80 text-[11px]">
                  <div>
                    <span class="text-gray-400 block">Position</span>
                    <span class="font-semibold text-gray-700"><?= htmlspecialchars($position) ?></span>
                  </div>
                  <div>
                    <span class="text-gray-400 block">Department</span>
                    <span class="font-semibold text-gray-700"><?= htmlspecialchars($department) ?></span>
                  </div>
                  <div class="col-span-2">
                    <span class="text-gray-400 block">Contact Number</span>
                    <span class="font-semibold text-gray-700"><?= htmlspecialchars($contact) ?></span>
                  </div>
                </div>

                <div class="pt-1">
                  <a href="employee-record.php?id=<?= (int) $employee['id'] ?>"
                    class="w-full inline-flex items-center justify-center gap-1.5 bg-blue-50 hover:bg-blue-100 text-brand-navy font-bold py-2 px-3 rounded text-xs transition-colors">
                    <i class="fa-solid fa-file-medical text-[11px]"></i> View Medical Record
                  </a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- DESKTOP & TABLET TABLE VIEW (>= 768px) -->
          <div class="hidden md:block overflow-x-auto rounded border border-gray-200">
            <table class="w-full text-left border-collapse text-xs">

              <!-- SIMS Table Header Theme -->
              <thead>
                <tr
                  class="bg-gray-100 text-gray-700 font-bold border-b border-gray-200 uppercase tracking-wider text-[11px]">
                  <th class="py-3 px-4 whitespace-nowrap">Employee ID</th>
                  <th class="py-3 px-4">Name</th>
                  <th class="py-3 px-4">Position</th>
                  <th class="py-3 px-4">Department</th>
                  <th class="py-3 px-4 whitespace-nowrap">Contact</th>
                  <th class="py-3 px-4 whitespace-nowrap">Status</th>
                  <th class="py-3 px-4 text-right whitespace-nowrap">Action</th>
                </tr>
              </thead>

              <tbody class="divide-y divide-gray-200 text-gray-700">
                <?php foreach ($employees as $employee): ?>
                  <?php
                  $emp_code = $employee['emp_code'] ?? $employee['employee_id_code'] ?? 'N/A';

                  if (!empty($employee['first_name']) || !empty($employee['last_name'])) {
                    $title = !empty($employee['title']) ? $employee['title'] . ' ' : '';
                    $full_name = trim($title . ($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''));
                  } else {
                    $full_name = $employee['name'] ?? 'N/A';
                  }
                  ?>

                  <tr class="hover:bg-gray-50/80 transition-colors">

                    <td class="py-3 px-4 font-bold text-gray-900 whitespace-nowrap">
                      <?= htmlspecialchars($emp_code) ?>
                    </td>

                    <td class="py-3 px-4 font-bold text-gray-800">
                      <?= htmlspecialchars($full_name) ?>
                    </td>

                    <td class="py-3 px-4 text-gray-600">
                      <?= htmlspecialchars($employee['position'] ?? 'N/A') ?>
                    </td>

                    <td class="py-3 px-4 text-gray-600">
                      <?= htmlspecialchars($employee['department'] ?? 'N/A') ?>
                    </td>

                    <td class="py-3 px-4 text-gray-600 whitespace-nowrap">
                      <?= htmlspecialchars($employee['contact_number'] ?? 'N/A') ?>
                    </td>

                    <td class="py-3 px-4 whitespace-nowrap">
                      <span class="inline-block px-2.5 py-0.5 text-[10px] font-bold text-white bg-brand-green rounded">
                        <?= htmlspecialchars($employee['status'] ?? 'Active') ?>
                      </span>
                    </td>

                    <td class="py-3 px-4 text-right whitespace-nowrap">
                      <a href="employee-record.php?id=<?= (int) $employee['id'] ?>"
                        class="inline-block bg-blue-50 hover:bg-blue-100 text-brand-navy font-bold px-3 py-1 rounded text-xs transition-colors">
                        View
                      </a>
                    </td>

                  </tr>
                <?php endforeach; ?>
              </tbody>

            </table>
          </div>

        <?php else: ?>

          <!-- EMPTY STATE -->
          <div class="py-12 text-center text-gray-400 border border-dashed border-gray-200 rounded-lg">
            <i class="fa-solid fa-folder-open text-2xl block mb-2"></i>
            <p class="text-xs">No employee records found.</p>
          </div>

        <?php endif; ?>

      </section>

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
  </script>

</body>

</html>