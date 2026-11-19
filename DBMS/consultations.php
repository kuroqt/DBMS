<?php
require_once 'db.php';
require_once 'auth.php';

// Set exact local timezone
date_default_timezone_set('Asia/Manila');

$currentDate = date('l, F d, Y');
$currentTime = date('h:i A');

$filter = $_GET['filter'] ?? '';
$page_title = 'All Consultations';

try {
    if ($filter === 'today') {
        $page_title = "Today's Consultations";
        $stmt = $pdo->query("SELECT sc.*, s.first_name, s.last_name, s.student_id_code
                             FROM student_consultations sc
                             LEFT JOIN students s ON sc.student_id = s.id
                             WHERE DATE(sc.consultation_date) = CURDATE()
                             ORDER BY sc.consultation_date DESC");
    } elseif ($filter === 'followup') {
        $page_title = "Pending Follow-ups";
        $stmt = $pdo->query("SELECT sc.*, s.first_name, s.last_name, s.student_id_code
                             FROM student_consultations sc
                             LEFT JOIN students s ON sc.student_id = s.id
                             WHERE sc.follow_up_status = 'Pending' OR sc.status = 'Follow-up'
                             ORDER BY sc.consultation_date DESC");
    } else {
        $stmt = $pdo->query("SELECT sc.*, s.first_name, s.last_name, s.student_id_code
                             FROM student_consultations sc
                             LEFT JOIN students s ON sc.student_id = s.id
                             ORDER BY sc.consultation_date DESC");
    }
    $consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback attempt if table name is 'consultations'
    try {
        if ($filter === 'today') {
            $stmt = $pdo->query("SELECT * FROM consultations WHERE DATE(consultation_date) = CURDATE() ORDER BY consultation_date DESC");
        } elseif ($filter === 'followup') {
            $stmt = $pdo->query("SELECT * FROM consultations WHERE status = 'Follow-up' OR status = 'Pending' ORDER BY consultation_date DESC");
        } else {
            $stmt = $pdo->query("SELECT * FROM consultations ORDER BY consultation_date DESC");
        }
        $consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $consultations = [];
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Asian College Computer-Based EMR System</title>

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

    <!-- TOP HEADER (Exact Match with employee.php) -->
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
                <strong
                    class="text-xs font-bold text-white block leading-tight"><?= htmlspecialchars($currentDate) ?></strong>
                <span class="text-[11px] text-blue-200 block"><?= htmlspecialchars($currentTime) ?></span>
            </div>

            <!-- User Info -->
            <div class="flex items-center space-x-2.5 md:space-x-3 lg:border-l lg:border-blue-800 lg:pl-6">
                <div
                    class="w-8 h-8 rounded-full bg-blue-100 text-brand-navy font-bold flex items-center justify-center text-xs shadow shrink-0">
                    <?= strtoupper(substr($_SESSION['full_name'] ?? 'A', 0, 1)) ?>
                </div>
                <div class="hidden sm:block text-right">
                    <p class="text-xs font-bold leading-tight"><?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?>
                    </p>
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

        <!-- SIDEBAR (Exact Match with employee.php) -->
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

                    <a href="employee.php"
                        class="flex items-center px-6 py-3 text-sm font-medium text-gray-300 hover:bg-white/5 hover:text-white transition-colors">
                        <i class="fa-solid fa-id-badge w-6 text-gray-400"></i>
                        <span>Employees</span>
                    </a>

                    <!-- Active Link (Consultations) -->
                    <a href="consultations.php"
                        class="flex items-center px-6 py-3 text-sm font-semibold text-white bg-white/10 border-l-4 border-brand-accent">
                        <i class="fa-solid fa-clipboard-list w-6 text-brand-accent"></i>
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

            <!-- PAGE HEADING & BREADCRUMB -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <a href="index.php"
                        class="text-xs font-bold text-brand-navy hover:underline inline-flex items-center gap-1 mb-1">
                        <i class="fa-solid fa-arrow-left text-[10px]"></i> Back to Dashboard
                    </a>
                    <h2 class="text-lg sm:text-xl font-bold text-gray-800"><?= htmlspecialchars($page_title) ?></h2>
                </div>
                <div class="text-xs text-gray-500 font-medium">
                    Showing <span class="font-bold text-gray-800"><?= count($consultations) ?></span> record(s)
                </div>
            </div>

            <!-- FILTER NAVIGATION TABS -->
            <div class="flex items-center gap-2 border-b border-gray-200 pb-3 overflow-x-auto">
                <a href="consultations.php"
                    class="px-4 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all <?= empty($filter) ? 'bg-brand-navy text-white shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-200 border border-gray-200' ?>">
                    All Records
                </a>
                <a href="consultations.php?filter=today"
                    class="px-4 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all <?= $filter === 'today' ? 'bg-brand-navy text-white shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-200 border border-gray-200' ?>">
                    Today Only
                </a>
                <a href="consultations.php?filter=followup"
                    class="px-4 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all <?= $filter === 'followup' ? 'bg-brand-navy text-white shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-200 border border-gray-200' ?>">
                    Pending Follow-ups
                </a>
            </div>

            <!-- MOBILE CARD LIST VIEW (< 768px) -->
            <section class="md:hidden space-y-3">
                <?php if (!empty($consultations)): ?>
                    <?php foreach ($consultations as $c): ?>
                        <?php
                        $patient_name = !empty($c['first_name']) ? ($c['first_name'] . ' ' . $c['last_name']) : ($c['patient_name'] ?? 'N/A');
                        $status_text = $c['follow_up_status'] ?? $c['status'] ?? 'Completed';
                        $reason_text = $c['chief_complaint'] ?? $c['complaint'] ?? $c['reason'] ?? 'N/A';
                        $student_link = !empty($c['student_id']) ? "student-record.php?id=" . $c['student_id'] : "javascript:void(0)";

                        $formatted_date = 'N/A';
                        if (!empty($c['consultation_date'])) {
                            try {
                                $dt = new DateTime($c['consultation_date']);
                                $formatted_date = $dt->format('M d, Y - h:i A');
                            } catch (Exception $e) {
                                $formatted_date = $c['consultation_date'];
                            }
                        }
                        ?>
                        <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm space-y-2.5">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h3 class="text-sm font-bold text-gray-900 leading-snug">
                                        <?= htmlspecialchars($patient_name) ?>
                                    </h3>
                                    <?php if (!empty($c['student_id_code'])): ?>
                                        <span
                                            class="text-[11px] text-gray-500 font-medium"><?= htmlspecialchars($c['student_id_code']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($status_text === 'Pending' || $status_text === 'Follow-up'): ?>
                                    <span class="px-2.5 py-0.5 text-[10px] font-bold text-amber-800 bg-amber-100 rounded shrink-0">
                                        <?= htmlspecialchars($status_text) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="px-2.5 py-0.5 text-[10px] font-bold text-white bg-brand-green rounded shrink-0">
                                        <?= htmlspecialchars($status_text) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="text-xs text-gray-600 space-y-1 border-t border-gray-100 pt-2">
                                <p class="flex items-center gap-2">
                                    <i class="fa-regular fa-clock text-gray-400 w-3.5"></i>
                                    <span><?= htmlspecialchars($formatted_date) ?></span>
                                </p>
                                <p class="flex items-start gap-2">
                                    <i class="fa-solid fa-notes-medical text-gray-400 w-3.5 mt-0.5"></i>
                                    <span><?= htmlspecialchars($reason_text) ?></span>
                                </p>
                            </div>

                            <div class="pt-1">
                                <a href="<?= $student_link ?>"
                                    class="block w-full text-center bg-blue-50 hover:bg-blue-100 text-brand-navy font-bold py-2 rounded text-xs transition-colors">
                                    View Record &rarr;
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="bg-white rounded-lg border border-gray-200 p-8 text-center text-gray-400 text-xs">
                        <i class="fa-solid fa-clipboard text-2xl block mb-2"></i>
                        No consultation records found for this view.
                    </div>
                <?php endif; ?>
            </section>

            <!-- DESKTOP DATA TABLE CARD (>= 768px) -->
            <section class="hidden md:block bg-white rounded-lg border border-gray-200 p-5 shadow-sm">
                <div class="overflow-x-auto rounded border border-gray-200">
                    <table class="w-full text-left border-collapse text-xs">

                        <!-- SIMS Table Header Theme -->
                        <thead>
                            <tr
                                class="bg-gray-100 text-gray-700 font-bold border-b border-gray-200 uppercase tracking-wider text-[11px]">
                                <th class="py-3 px-4">Patient Name</th>
                                <th class="py-3 px-4">Date & Time</th>
                                <th class="py-3 px-4">Reason / Complaint</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4 text-right">Action</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 text-gray-700">
                            <?php if (!empty($consultations)): ?>
                                <?php foreach ($consultations as $c): ?>
                                    <?php
                                    $patient_name = !empty($c['first_name']) ? ($c['first_name'] . ' ' . $c['last_name']) : ($c['patient_name'] ?? 'N/A');
                                    $status_text = $c['follow_up_status'] ?? $c['status'] ?? 'Completed';
                                    $reason_text = $c['chief_complaint'] ?? $c['complaint'] ?? $c['reason'] ?? 'N/A';
                                    $student_link = !empty($c['student_id']) ? "student-record.php?id=" . $c['student_id'] : "javascript:void(0)";

                                    $formatted_date = 'N/A';
                                    if (!empty($c['consultation_date'])) {
                                        try {
                                            $dt = new DateTime($c['consultation_date']);
                                            $formatted_date = $dt->format('M d, Y - h:i A');
                                        } catch (Exception $e) {
                                            $formatted_date = $c['consultation_date'];
                                        }
                                    }
                                    ?>
                                    <tr class="hover:bg-gray-50/80 transition-colors">
                                        <td class="py-3 px-4">
                                            <strong
                                                class="text-gray-900 block font-bold"><?= htmlspecialchars($patient_name) ?></strong>
                                            <?php if (!empty($c['student_id_code'])): ?>
                                                <span
                                                    class="text-[10px] text-gray-400 font-medium"><?= htmlspecialchars($c['student_id_code']) ?></span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="py-3 px-4 text-gray-600 font-medium whitespace-nowrap">
                                            <?= htmlspecialchars($formatted_date) ?>
                                        </td>

                                        <td class="py-3 px-4 text-gray-600">
                                            <?= htmlspecialchars($reason_text) ?>
                                        </td>

                                        <td class="py-3 px-4 whitespace-nowrap">
                                            <?php if ($status_text === 'Pending' || $status_text === 'Follow-up'): ?>
                                                <span
                                                    class="inline-block px-2.5 py-0.5 text-[10px] font-bold text-amber-800 bg-amber-100 rounded">
                                                    <?= htmlspecialchars($status_text) ?>
                                                </span>
                                            <?php else: ?>
                                                <span
                                                    class="inline-block px-2.5 py-0.5 text-[10px] font-bold text-white bg-brand-green rounded">
                                                    <?= htmlspecialchars($status_text) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="py-3 px-4 text-right whitespace-nowrap">
                                            <a href="<?= $student_link ?>"
                                                class="inline-block bg-blue-50 hover:bg-blue-100 text-brand-navy font-bold px-3 py-1 rounded text-xs transition-colors">
                                                View Record &rarr;
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="py-10 text-center text-gray-400">
                                        <i class="fa-solid fa-clipboard text-2xl block mb-2"></i>
                                        No consultation records found for this view.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>

                    </table>
                </div>
            </section>

        </main>
    </div>

    <!-- MOBILE SIDEBAR TOGGLE SCRIPT (Exact Match with employee.php) -->
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