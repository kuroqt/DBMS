<?php
require_once 'db.php';
require_once 'auth.php';

// Set Philippine local timezone
date_default_timezone_set('Asia/Manila');

$currentDate = date('l, F d, Y');
$currentTime = date('h:i A');

$error = '';
$preselected_student_id = $_GET['student_id'] ?? $_POST['student_id'] ?? '';

// Fetch students for the dropdown selector
try {
    $stmt_students = $pdo->query("SELECT id, student_id_code, first_name, last_name, course, year_level FROM students ORDER BY last_name ASC, first_name ASC");
    $students_list = $stmt_students->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    try {
        $stmt_students = $pdo->query("SELECT id, student_id AS student_id_code, name FROM students ORDER BY name ASC");
        $students_list = $stmt_students->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $students_list = [];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $student_id = !empty($_POST['student_id']) ? (int) $_POST['student_id'] : null;

    // Get form values
    $complaint = trim($_POST['complaint'] ?? '');
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    // Generate exact current Philippine date and time
    $consultation_date = date('Y-m-d H:i:s');

    if (empty($student_id) || empty($complaint)) {
        $error = "Please select a student and enter the chief complaint.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO student_consultations (
                    student_id,
                    consultation_date,
                    vital_signs,
                    chief_complaint,
                    treatment,
                    diagnosis,
                    remarks
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $student_id,
                $consultation_date,
                '',             // vital_signs
                $complaint,     // chief_complaint
                '',             // treatment
                $diagnosis,
                $notes          // remarks
            ]);

            // Return to consultation list
            header("Location: consultations.php?added=1");
            exit;

        } catch (PDOException $e) {
            $error = "Failed to add consultation: " . $e->getMessage();
        }
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Consultation - Medical Record System</title>

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

            <!-- PAGE HEADING & BACK BUTTON -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 max-w-3xl mx-auto">
                <div>
                    <h2 class="text-lg sm:text-xl font-bold text-gray-800">Record Student Consultation</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Log a new clinic visit, complaint, diagnosis, and remarks.
                    </p>
                </div>
                <a href="consultations.php"
                    class="self-start sm:self-auto inline-flex items-center gap-1.5 text-xs font-semibold text-gray-600 hover:text-brand-navy bg-white border border-gray-300 px-3 py-2 sm:py-1.5 rounded shadow-sm transition">
                    <i class="fa-solid fa-arrow-left text-[10px]"></i> Back to List
                </a>
            </div>

            <!-- FORM CONTAINER CARD -->
            <section class="max-w-3xl mx-auto bg-white rounded-lg border border-gray-200 p-4 sm:p-6 shadow-sm">

                <?php if (!empty($error)): ?>
                    <div
                        class="mb-5 p-3.5 bg-red-50 border border-red-200 text-red-700 text-xs font-semibold rounded-lg flex items-center gap-2.5">
                        <i class="fa-solid fa-circle-exclamation text-sm shrink-0"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="space-y-4">

                    <!-- Student Selection & Timestamp Info -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <label for="student_id" class="block text-xs font-bold text-gray-700 mb-1.5">
                                Select Student <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <select id="student_id" name="student_id" required
                                    class="w-full px-3 py-2.5 sm:py-2 text-base sm:text-xs rounded-lg border border-gray-300 bg-white focus:outline-none focus:border-brand-navy focus:ring-1 focus:ring-brand-navy transition-all">
                                    <option value="">-- Choose a Student --</option>
                                    <?php foreach ($students_list as $st): ?>
                                        <?php
                                        $st_code = $st['student_id_code'] ?? 'N/A';
                                        $st_name = !empty($st['first_name'])
                                            ? trim($st['first_name'] . ' ' . ($st['last_name'] ?? ''))
                                            : ($st['name'] ?? 'Unknown');
                                        $st_course = !empty($st['course']) ? ' (' . $st['course'] . ')' : '';
                                        $selected = ((string) $preselected_student_id === (string) $st['id']) ? 'selected' : '';
                                        ?>
                                        <option value="<?= (int) $st['id'] ?>" <?= $selected ?>>
                                            <?= htmlspecialchars($st_code . ' - ' . $st_name . $st_course) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">
                                Date &amp; Time (PH)
                            </label>
                            <div
                                class="px-3 py-2.5 sm:py-2 text-xs font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded-lg flex items-center gap-2">
                                <i class="fa-regular fa-clock text-brand-navy"></i>
                                <span><?= date('M d, Y h:i A') ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Chief Complaint -->
                    <div>
                        <label for="complaint" class="block text-xs font-bold text-gray-700 mb-1.5">
                            Chief Complaint <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="complaint" name="complaint" required
                            value="<?= htmlspecialchars($_POST['complaint'] ?? '') ?>"
                            placeholder="e.g., Headache, Fever, Stomachache, Routine Checkup..."
                            class="w-full px-3 py-2.5 sm:py-2 text-base sm:text-xs rounded-lg border border-gray-300 focus:outline-none focus:border-brand-navy focus:ring-1 focus:ring-brand-navy transition-all">
                    </div>

                    <!-- Diagnosis -->
                    <div>
                        <label for="diagnosis" class="block text-xs font-bold text-gray-700 mb-1.5">
                            Diagnosis / Assessment
                        </label>
                        <input type="text" id="diagnosis" name="diagnosis"
                            value="<?= htmlspecialchars($_POST['diagnosis'] ?? '') ?>"
                            placeholder="e.g., Viral Upper Respiratory Infection, Tension Headache..."
                            class="w-full px-3 py-2.5 sm:py-2 text-base sm:text-xs rounded-lg border border-gray-300 focus:outline-none focus:border-brand-navy focus:ring-1 focus:ring-brand-navy transition-all">
                    </div>

                    <!-- Remarks / Notes -->
                    <div>
                        <label for="notes" class="block text-xs font-bold text-gray-700 mb-1.5">
                            Remarks / Clinical Notes
                        </label>
                        <textarea id="notes" name="notes" rows="4"
                            placeholder="Enter treatment given, prescribed medication, rest instructions, or follow-up notes..."
                            class="w-full px-3 py-2.5 sm:py-2 text-base sm:text-xs rounded-lg border border-gray-300 focus:outline-none focus:border-brand-navy focus:ring-1 focus:ring-brand-navy transition-all"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-3 border-t border-gray-100 flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5">
                        <a href="consultations.php"
                            class="w-full sm:w-auto text-center px-4 py-2.5 sm:py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 font-semibold text-xs transition">
                            Cancel
                        </a>
                        <button type="submit"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-brand-navy hover:bg-blue-900 active:scale-[0.99] text-white font-bold text-xs px-5 py-2.5 sm:py-2 rounded-lg shadow-sm transition-all">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Save Consultation</span>
                        </button>
                    </div>

                </form>

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