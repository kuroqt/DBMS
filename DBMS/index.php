<?php
require_once 'db.php';
require_once 'auth.php';

// Philippine timezone
date_default_timezone_set('Asia/Manila');

$currentDate = date('l, F d, Y');
$currentTime = date('h:i A');

// Make sure consultation_date can store both date and time
try {
  $pdo->exec("
    ALTER TABLE student_consultations 
    MODIFY consultation_date DATETIME NOT NULL
  ");
} catch (Exception $e) {
  // Ignore if already DATETIME or if the database does not allow the change
}

// Fetch quick counts
$total_students = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$total_employees = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();

// Fetch Today's Consultations Count
try {
  $todays_consultations = $pdo->query("
    SELECT COUNT(*) 
    FROM student_consultations 
    WHERE DATE(consultation_date) = CURDATE()
  ")->fetchColumn();
} catch (Exception $e) {
  $todays_consultations = 0;
}

// Fetch Pending Follow-ups Count
try {
  $pending_followups = $pdo->query("
    SELECT COUNT(*) 
    FROM student_consultations 
    WHERE follow_up_status = 'Pending' 
       OR status = 'Follow-up'
  ")->fetchColumn();
} catch (Exception $e) {
  $pending_followups = 0;
}

// Fetch Recent 5 Consultations for Dashboard Feed
try {
  $stmt = $pdo->query("
    SELECT 
      sc.*, 
      s.first_name, 
      s.last_name, 
      s.student_id_code 
    FROM student_consultations sc 
    LEFT JOIN students s ON sc.student_id = s.id 
    ORDER BY sc.consultation_date DESC 
    LIMIT 5
  ");

  $recent_consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
  $recent_consultations = [];
}
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Asian College Computer-Based EMR System</title>

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

  <!-- Chart.js for Visual Analytics -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-gray-100 font-sans text-gray-800 flex flex-col min-h-screen md:h-screen md:overflow-hidden">

  <!-- TOP HEADER (Exact Match with employee.php & consultations.php) -->
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

      <!-- User Information -->
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

    <!-- SIDEBAR (SIMS Dark Theme - Slide-out Drawer on Mobile, Fixed on Desktop) -->
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

        <!-- Mobile Search Bar -->
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
            class="flex items-center px-6 py-3 text-sm font-semibold text-white bg-white/10 border-l-4 border-brand-accent">
            <i class="fa-solid fa-house w-6 text-brand-accent"></i>
            <span>Dashboard</span>
          </a>

          <a href="students.php"
            class="flex items-center px-6 py-3 text-sm font-medium text-gray-300 hover:bg-white/5 hover:text-white transition-colors">
            <i class="fa-solid fa-user-graduate w-6 text-gray-400"></i>
            <span>Students</span>
          </a>

          <a href="employee.php"
            class="flex items-center px-6 py-3 text-sm font-medium text-gray-300 hover:bg-white/5 hover:text-white transition-colors">
            <i class="fa-solid fa-id-badge w-6 text-gray-400"></i>
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

    <!-- CONTENT CANVAS -->
    <main class="flex-1 overflow-y-auto p-4 sm:p-6 bg-gray-100 space-y-5">

      <!-- WELCOME BANNER -->
      <div
        class="bg-white rounded-lg p-4 sm:p-5 border border-gray-200 shadow-sm flex items-center justify-between gap-4">
        <div>
          <h1 class="text-lg sm:text-2xl font-bold text-gray-900">Welcome to Asian College Computer-Based EMR System
          </h1>
          <p class="text-xs sm:text-sm text-gray-500 mt-1">Manage, retrieve, and organize electronic medical records for
            students and employees in one centralized system.</p>
        </div>
        <div
          class="hidden sm:flex w-10 h-10 shrink-0 rounded-full bg-blue-50 text-brand-navy items-center justify-center text-lg shadow-inner">
          <i class="fa-solid fa-heart-pulse"></i>
        </div>
      </div>

      <!-- STAT CARDS -->
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">

        <!-- Total Students -->
        <a href="students.php"
          class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm flex items-center justify-between hover:border-brand-navy/40 transition">
          <div class="flex items-center space-x-3.5">
            <div
              class="w-10 h-10 shrink-0 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
              <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <div>
              <p class="text-xs font-bold uppercase text-gray-400">Total Students</p>
              <h3 class="text-xl font-bold text-gray-800"><?= $total_students ?></h3>
              <p class="text-xs text-gray-500">Registered students</p>
            </div>
          </div>
          <i class="fa-solid fa-chevron-right text-xs text-gray-300"></i>
        </a>

        <!-- Total Employees -->
        <a href="employee.php"
          class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm flex items-center justify-between hover:border-brand-navy/40 transition">
          <div class="flex items-center space-x-3.5">
            <div
              class="w-10 h-10 shrink-0 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
              <i class="fa-solid fa-user-group"></i>
            </div>
            <div>
              <p class="text-xs font-bold uppercase text-gray-400">Total Employees</p>
              <h3 class="text-xl font-bold text-gray-800"><?= $total_employees ?></h3>
              <p class="text-xs text-gray-500">Registered employees</p>
            </div>
          </div>
          <i class="fa-solid fa-chevron-right text-xs text-gray-300"></i>
        </a>

        <!-- Today's Consultations -->
        <a href="consultations.php?filter=today"
          class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm flex items-center justify-between hover:border-brand-navy/40 transition">
          <div class="flex items-center space-x-3.5">
            <div
              class="w-10 h-10 shrink-0 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-lg">
              <i class="fa-solid fa-stethoscope"></i>
            </div>
            <div>
              <p class="text-xs font-bold uppercase text-gray-400">Today's Consultations</p>
              <h3 class="text-xl font-bold text-gray-800"><?= $todays_consultations ?></h3>
              <p class="text-xs text-gray-500">
                <?= ($todays_consultations == 0) ? 'No consultations today' : 'Recorded today' ?>
              </p>
            </div>
          </div>
          <i class="fa-solid fa-chevron-right text-xs text-gray-300"></i>
        </a>

        <!-- Pending Follow-ups -->
        <a href="consultations.php?filter=followup"
          class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm flex items-center justify-between hover:border-brand-navy/40 transition">
          <div class="flex items-center space-x-3.5">
            <div
              class="w-10 h-10 shrink-0 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
              <i class="fa-solid fa-circle-exclamation"></i>
            </div>
            <div>
              <p class="text-xs font-bold uppercase text-gray-400">Pending Follow-ups</p>
              <h3 class="text-xl font-bold text-gray-800"><?= $pending_followups ?></h3>
              <?php if ($pending_followups == 0): ?>
                <span class="inline-block mt-0.5 px-2 py-0.5 text-[10px] font-bold text-white bg-brand-green rounded">All
                  clear</span>
              <?php else: ?>
                <span class="inline-block mt-0.5 px-2 py-0.5 text-[10px] font-bold text-white bg-amber-500 rounded">Needs
                  attention</span>
              <?php endif; ?>
            </div>
          </div>
          <i class="fa-solid fa-chevron-right text-xs text-gray-300"></i>
        </a>

      </div>

      <!-- QUICK ACTIONS TOOLBAR -->
      <section class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm space-y-3">
        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2">
          <i class="fa-solid fa-bolt text-amber-500"></i> Quick Actions
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5 sm:gap-3">
          <a href="add-student.php"
            class="flex items-center justify-center gap-2 bg-brand-navy hover:bg-blue-900 text-white font-medium text-xs sm:text-sm py-2.5 px-3 rounded shadow-sm transition-all">
            <i class="fa-solid fa-plus text-[10px]"></i> Add Student Record
          </a>
          <a href="add-employee.php"
            class="flex items-center justify-center gap-2 bg-brand-green hover:bg-emerald-700 text-white font-medium text-xs sm:text-sm py-2.5 px-3 rounded shadow-sm transition-all">
            <i class="fa-solid fa-plus text-[10px]"></i> Add Employee Record
          </a>
          <a href="students.php"
            class="flex items-center justify-center gap-2 border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium text-xs sm:text-sm py-2.5 px-3 rounded transition-all">
            <i class="fa-solid fa-graduation-cap text-gray-400 text-xs"></i> View Students
          </a>
          <a href="employee.php"
            class="flex items-center justify-center gap-2 border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium text-xs sm:text-sm py-2.5 px-3 rounded transition-all">
            <i class="fa-solid fa-user-group text-gray-400 text-xs"></i> View Employees
          </a>
          <a href="consultations.php"
            class="sm:col-span-2 lg:col-span-1 flex items-center justify-center gap-2 border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium text-xs sm:text-sm py-2.5 px-3 rounded transition-all">
            <i class="fa-solid fa-clipboard-list text-gray-400 text-xs"></i> Consultations
          </a>
        </div>
      </section>

      <!-- BOTTOM DASHBOARD SECTION -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        <!-- CLINIC OVERVIEW CHART -->
        <div
          class="lg:col-span-1 bg-white rounded-lg border border-gray-200 p-4 shadow-sm flex flex-col justify-between">
          <div class="border-b pb-2 mb-3">
            <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2">
              <i class="fa-solid fa-chart-pie text-brand-navy"></i> Clinic Overview
            </h3>
          </div>
          <div class="relative h-56 sm:h-48 w-full flex items-center justify-center">
            <canvas id="overviewChart"></canvas>
          </div>
        </div>

        <!-- RECENT CONSULTATIONS FEED -->
        <div
          class="lg:col-span-2 bg-white rounded-lg border border-gray-200 p-4 shadow-sm flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between border-b pb-2 mb-3">
              <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-brand-navy"></i> Recent Consultations
              </h3>
              <a href="consultations.php" class="text-xs font-semibold text-brand-navy hover:underline">View All
                &rarr;</a>
            </div>

            <?php if (!empty($recent_consultations)): ?>
              <div class="space-y-2">
                <?php foreach ($recent_consultations as $row): ?>
                  <?php
                  $p_name = !empty($row['first_name'])
                    ? ($row['first_name'] . ' ' . $row['last_name'])
                    : ($row['patient_name'] ?? 'N/A');

                  $p_reason = $row['chief_complaint']
                    ?? $row['complaint']
                    ?? $row['reason']
                    ?? 'Routine Visit';

                  $p_date = date('M d, Y - h:i A', strtotime($row['consultation_date']));
                  ?>

                  <div
                    class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-2.5 bg-gray-50 rounded border border-gray-200 hover:bg-gray-100/80 transition-all">
                    <div>
                      <strong class="text-xs font-bold text-gray-900 block"><?= htmlspecialchars($p_name) ?></strong>
                      <span class="text-[11px] text-gray-500"><?= htmlspecialchars($p_reason) ?></span>
                    </div>
                    <span
                      class="self-start sm:self-auto text-[11px] font-medium text-gray-500 bg-white px-2 py-1 rounded border border-gray-200 shrink-0">
                      <?= htmlspecialchars($p_date) ?>
                    </span>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="text-center py-8 text-gray-400 space-y-1">
                <i class="fa-solid fa-clipboard text-2xl"></i>
                <p class="text-xs">No consultations recorded yet.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>

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
  </script>

  <!-- CHART.JS INITIALIZATION -->
  <script>
    const ctx = document.getElementById('overviewChart').getContext('2d');
    new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ['Students', 'Employees', 'Today Visited'],
        datasets: [{
          data: [
            <?= (int) $total_students ?>,
            <?= (int) $total_employees ?>,
            <?= (int) $todays_consultations ?>
          ],
          backgroundColor: [
            '#10386C', /* SIMS Navy */
            '#16BA52', /* SIMS Green */
            '#f59e0b'  /* Amber */
          ],
          borderWidth: 2,
          borderColor: '#ffffff'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              boxWidth: 10,
              padding: 10,
              font: {
                size: 11
              }
            }
          }
        }
      }
    });
  </script>

</body>

</html>