<?php
require_once 'db.php';
require_once 'auth.php';


date_default_timezone_set('Asia/Manila');


$currentDate = date('l, F d, Y');
$currentTime = date('h:i A');


$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';


$student_results = [];
$employee_results = [];


if (!empty($search_query)) {
    $term = "%{$search_query}%";


    // 1. Search Students Table
    try {
        $stmt = $pdo->prepare("SELECT id, student_id_code AS code, first_name, last_name, course, 'student' AS type 
                               FROM students 
                               WHERE first_name LIKE :term 
                                  OR last_name LIKE :term 
                                  OR CONCAT(first_name, ' ', last_name) LIKE :term
                                  OR student_id_code LIKE :term 
                                  OR course LIKE :term 
                               ORDER BY last_name ASC");
        $stmt->execute(['term' => $term]);
        $student_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $student_results = [];
    }


    // 2. Search Employees Table
    try {
        $stmt = $pdo->prepare("SELECT id, emp_code AS code, first_name, last_name, department AS course, 'employee' AS type 
                               FROM employees 
                               WHERE first_name LIKE :term 
                                  OR last_name LIKE :term 
                                  OR CONCAT(first_name, ' ', last_name) LIKE :term
                                  OR emp_code LIKE :term 
                                  OR department LIKE :term 
                               ORDER BY last_name ASC");
        $stmt->execute(['term' => $term]);
        $employee_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        try {
            $stmt = $pdo->prepare("SELECT id, employee_id AS code, name, department AS course, 'employee' AS type 
                                   FROM employees 
                                   WHERE name LIKE :term 
                                      OR employee_id LIKE :term 
                                      OR department LIKE :term 
                                   ORDER BY name ASC");
            $stmt->execute(['term' => $term]);
            $employee_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $ex) {
            $employee_results = [];
        }
    }
}


$total_results = count($student_results) + count($employee_results);
?>


<!doctype html>
<html lang="en">


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results for "<?= htmlspecialchars($search_query) ?>" - Medical System</title>


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


    <!-- TOP HEADER (Exact SIMS Navy Theme) -->
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
                <input type="text" name="q" value="<?= htmlspecialchars($search_query) ?>"
                    placeholder="Search student, employee, ID..." required
                    class="w-full pl-8 pr-4 py-1.5 text-xs rounded bg-white/10 text-white placeholder-gray-300 focus:outline-none focus:bg-white focus:text-gray-900 border border-white/20 transition-all">
            </form>


            <!-- Date & Time Display -->
            <div class="hidden lg:block text-right border-l border-blue-800 pl-6">
                <strong
                    class="text-xs font-bold text-white block leading-tight"><?= htmlspecialchars($currentDate) ?></strong>
                <span class="text-[11px] text-blue-200 block"><?= htmlspecialchars($currentTime) ?></span>
            </div>


            <!-- User Information -->
            <div class="flex items-center space-x-2 md:space-x-3 md:border-l md:border-blue-800 md:pl-6">
                <div
                    class="w-8 h-8 rounded-full bg-blue-100 text-brand-navy font-bold flex items-center justify-center text-xs shadow shrink-0">
                    <?= strtoupper(substr($_SESSION['full_name'] ?? 'A', 0, 1)) ?>
                </div>
                <div class="text-right hidden sm:block">
                    <p class="text-xs font-bold leading-tight"><?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?>
                    </p>
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
                    <p class="text-xs font-bold text-white"><?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?>
                    </p>
                    <p class="text-[11px] text-gray-400"><?= htmlspecialchars($_SESSION['role'] ?? 'Administrator') ?>
                    </p>
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
        <main class="flex-1 overflow-y-auto p-4 md:p-6 bg-gray-100 space-y-4 md:space-y-5 w-full">


            <!-- MOBILE SEARCH BAR -->
            <form class="relative w-full md:hidden" action="search.php" method="GET">
                <button type="submit" class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </button>
                <input type="text" name="q" value="<?= htmlspecialchars($search_query) ?>"
                    placeholder="Search student, employee, ID..." required
                    class="w-full pl-9 pr-4 py-2.5 text-sm rounded-lg bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-navy border border-gray-200 shadow-sm">
            </form>


            <!-- PAGE HEADING -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h2 class="text-lg md:text-xl font-bold text-gray-800">Search Results</h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Found <strong class="text-brand-navy"><?= $total_results ?></strong> matching record(s) for
                        "<strong><?= htmlspecialchars($search_query) ?></strong>"
                    </p>
                </div>
                <div class="text-[11px] text-gray-500 lg:hidden">
                    <?= htmlspecialchars($currentDate) ?> &bull; <?= htmlspecialchars($currentTime) ?>
                </div>
            </div>


            <!-- RESULTS SECTION -->
            <div class="space-y-5 md:space-y-6">


                <!-- STUDENTS RESULTS CARD -->
                <section class="bg-white rounded-lg border border-gray-200 p-4 md:p-5 shadow-sm space-y-3">
                    <h3
                        class="text-xs font-bold text-brand-navy uppercase tracking-wider border-b border-gray-200 pb-2 flex items-center justify-between">
                        <span class="flex items-center gap-2">
                            <i class="fa-solid fa-user-graduate text-brand-accent"></i> Student Records
                        </span>
                        <span class="bg-blue-50 text-brand-navy px-2.5 py-0.5 rounded-full text-[11px] font-bold">
                            <?= count($student_results) ?>
                        </span>
                    </h3>


                    <?php if (!empty($student_results)): ?>
                        <div class="divide-y divide-gray-100">
                            <?php foreach ($student_results as $row): ?>
                                <a href="student-record.php?id=<?= $row['id'] ?>"
                                    class="py-3 px-2.5 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 hover:bg-gray-50 rounded transition-all group">
                                    <div>
                                        <strong class="text-sm font-bold text-gray-900 group-hover:text-brand-navy block">
                                            <?= htmlspecialchars(trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))) ?>
                                        </strong>
                                        <span class="text-xs text-gray-500 block mt-0.5">
                                            ID Code: <strong
                                                class="text-gray-700"><?= htmlspecialchars($row['code'] ?? 'N/A') ?></strong>
                                            &bull; Course: <?= htmlspecialchars($row['course'] ?? 'N/A') ?>
                                        </span>
                                    </div>
                                    <span
                                        class="text-xs font-bold text-brand-navy group-hover:underline inline-flex items-center gap-1 self-start sm:self-center bg-blue-50 sm:bg-transparent px-2.5 py-1 sm:p-0 rounded">
                                        View Record <i class="fa-solid fa-chevron-right text-[10px]"></i>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-xs text-gray-400 py-4 italic text-center">No student records matched your search
                            query.</p>
                    <?php endif; ?>
                </section>


                <!-- EMPLOYEES RESULTS CARD -->
                <section class="bg-white rounded-lg border border-gray-200 p-4 md:p-5 shadow-sm space-y-3">
                    <h3
                        class="text-xs font-bold text-brand-navy uppercase tracking-wider border-b border-gray-200 pb-2 flex items-center justify-between">
                        <span class="flex items-center gap-2">
                            <i class="fa-solid fa-id-badge text-brand-accent"></i> Employee Records
                        </span>
                        <span class="bg-blue-50 text-brand-navy px-2.5 py-0.5 rounded-full text-[11px] font-bold">
                            <?= count($employee_results) ?>
                        </span>
                    </h3>


                    <?php if (!empty($employee_results)): ?>
                        <div class="divide-y divide-gray-100">
                            <?php foreach ($employee_results as $row): ?>
                                <?php
                                $emp_display_name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                                if (empty($emp_display_name)) {
                                    $emp_display_name = $row['name'] ?? 'N/A';
                                }
                                ?>
                                <a href="employee-record.php?id=<?= $row['id'] ?>"
                                    class="py-3 px-2.5 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 hover:bg-gray-50 rounded transition-all group">
                                    <div>
                                        <strong class="text-sm font-bold text-gray-900 group-hover:text-brand-navy block">
                                            <?= htmlspecialchars($emp_display_name) ?>
                                        </strong>
                                        <span class="text-xs text-gray-500 block mt-0.5">
                                            Employee Code: <strong
                                                class="text-gray-700"><?= htmlspecialchars($row['code'] ?? 'N/A') ?></strong>
                                            &bull; Dept: <?= htmlspecialchars($row['course'] ?? 'N/A') ?>
                                        </span>
                                    </div>
                                    <span
                                        class="text-xs font-bold text-brand-navy group-hover:underline inline-flex items-center gap-1 self-start sm:self-center bg-blue-50 sm:bg-transparent px-2.5 py-1 sm:p-0 rounded">
                                        View Record <i class="fa-solid fa-chevron-right text-[10px]"></i>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-xs text-gray-400 py-4 italic text-center">No employee records matched your search
                            query.</p>
                    <?php endif; ?>
                </section>


            </div>


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