<?php
require_once 'db.php';
require_once 'auth.php';


date_default_timezone_set('Asia/Manila');


$currentDate = date('l, F d, Y');
$currentTime = date('h:i A');


$error = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id_code = trim($_POST['student_id_code'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $dob = $_POST['dob'] ?? '';
    $sex = $_POST['sex'] ?? 'Male';
    $course = trim($_POST['course'] ?? '');
    $year_level = trim($_POST['year_level'] ?? '1st Year');
    $section = trim($_POST['section'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $blood_type = trim($_POST['blood_type'] ?? 'O+');
    $allergies = trim($_POST['allergies'] ?? '') ?: 'None reported';
    $medical_conditions = trim($_POST['medical_conditions'] ?? '') ?: 'None reported';
    $current_medication = trim($_POST['current_medication'] ?? '') ?: 'None';
    $emergency_contact_name = trim($_POST['emergency_contact_name'] ?? '');
    $emergency_contact_number = trim($_POST['emergency_contact_number'] ?? '');


    try {
        $pdo->beginTransaction();


        // 1. Insert into students table
        $stmt = $pdo->prepare("INSERT INTO students (
            student_id_code, first_name, middle_name, last_name, dob, sex, course, year_level, 
            section, contact_number, status, blood_type, allergies, medical_conditions, 
            current_medication, emergency_contact_name, emergency_contact_number
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, ?, ?, ?, ?, ?)");


        $stmt->execute([
            $student_id_code,
            $first_name,
            $middle_name,
            $last_name,
            $dob,
            $sex,
            $course,
            $year_level,
            $section,
            $contact_number,
            $blood_type,
            $allergies,
            $medical_conditions,
            $current_medication,
            $emergency_contact_name,
            $emergency_contact_number
        ]);


        // 2. Create student login account (Username & Default Password = Student ID)
        $new_student_id = $pdo->lastInsertId();
        $student_full_name = trim($first_name . ' ' . $last_name);
        $default_password = password_hash($student_id_code, PASSWORD_DEFAULT);


        $user_stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role, student_id) VALUES (?, ?, ?, 'Student', ?)");
        $user_stmt->execute([
            $student_id_code,
            $default_password,
            $student_full_name,
            $new_student_id
        ]);


        $pdo->commit();


        header("Location: students.php");
        exit;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($e->getCode() == 23000) {
            $error = "Student ID Code already exists in the system!";
        } else {
            $error = "Failed to add student: " . $e->getMessage();
        }
    }
}
?>


<!doctype html>
<html lang="en">


<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Add New Student - Medical System</title>


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


                    <!-- Active Link (Students) -->
                    <a href="students.php"
                        class="flex items-center px-6 py-3 text-sm font-semibold text-white bg-white/10 border-l-4 border-brand-accent">
                        <i class="fa-solid fa-user-graduate w-6 text-brand-accent"></i>
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


        <!-- MAIN CONTENT CANVAS -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 bg-gray-100 space-y-5">


            <!-- PAGE HEADING & BREADCRUMB -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 max-w-5xl mx-auto">
                <div>
                    <a href="students.php"
                        class="text-xs font-bold text-brand-navy hover:underline inline-flex items-center gap-1.5 mb-1">
                        <i class="fa-solid fa-arrow-left text-[10px]"></i> Back to Students
                    </a>
                    <h2 class="text-lg sm:text-xl font-bold text-gray-800">Add New Student Record</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Fill out the form below to register a new student.</p>
                </div>
            </div>


            <!-- ERROR BANNER -->
            <?php if (!empty($error)): ?>
                <div
                    class="max-w-5xl mx-auto p-3.5 bg-red-50 border border-red-200 text-red-700 text-xs font-semibold rounded-lg flex items-center gap-2.5">
                    <i class="fa-solid fa-circle-exclamation text-sm shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>


            <!-- FORM CONTAINER CARD -->
            <form method="POST" action="add-student.php"
                class="max-w-5xl mx-auto bg-white rounded-lg border border-gray-200 p-4 sm:p-6 shadow-sm space-y-6">


                <!-- SECTION 1: PERSONAL & ACADEMIC INFORMATION -->
                <div class="space-y-4">
                    <h3
                        class="text-xs font-bold text-brand-navy uppercase tracking-wider border-b border-gray-200 pb-2 flex items-center gap-2">
                        <i class="fa-solid fa-graduation-cap text-brand-accent"></i> Personal &amp; Academic Information
                    </h3>


                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Student ID Number <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="student_id_code" required placeholder="e.g. 2021-2287611"
                                value="<?= htmlspecialchars($_POST['student_id_code'] ?? '') ?>"
                                class="w-full px-3 py-2.5 sm:py-1.5 text-base sm:text-xs rounded-lg sm:rounded border border-gray-300 focus:outline-none focus:border-brand-navy focus:ring-1 focus:ring-brand-navy transition-all" />
                        </div>


                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">First Name <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="first_name" required
                                value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>"
                                class="w-full px-3 py-2.5 sm:py-1.5 text-base sm:text-xs rounded-lg sm:rounded border border-gray-300 focus:outline-none focus:border-brand-navy focus:ring-1 focus:ring-brand-navy transition-all" />
                        </div>


                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Middle Name</label>
                            <input type="text" name="middle_name"
                                value="<?= htmlspecialchars($_POST['middle_name'] ?? '') ?>"
                                class="w-full px-3 py-2.5 sm:py-1.5 text-base sm:text-xs rounded-lg sm:rounded border border-gray-300 focus:outline-none focus:border-brand-navy focus:ring-1 focus:ring-brand-navy transition-all" />
                        </div>


                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Last Name <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="last_name" required
                                value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>"
                                class="w-full px-3 py-2.5 sm:py-1.5 text-base sm:text-xs rounded-lg sm:rounded border border-gray-300 focus:outline-none focus:border-brand-navy focus:ring-1 focus:ring-brand-navy transition-all" />
                        </div>


                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Date of Birth <span
                                    class="text-red-500">*</span></label>
                            <input type="date" name="dob" required value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>"
                                class="w-full px-3 py-2.5 sm:py-1.5 text-base sm:text-xs rounded-lg sm:rounded border border-gray-300 bg-white focus:outline-none focus:border-brand-navy focus:ring-1 focus:ring-brand-navy transition-all" />
                        </div>


                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Sex <span
                                    class="text-red-500">*</span></label>
                            <select name="sex" required
                                class="w-full px-3 py-2.5 sm:py-1.5 text-base sm:text-xs rounded-lg sm:rounded border border-gray-300 bg-white focus:outline-none focus:border-brand-navy focus:ring-1 focus:ring-brand-navy transition-all">
                                <option value="Male" <?= (($_POST['sex'] ?? '') === 'Male') ? 'selected' : '' ?>>Male
                                </option>
                                <option value="Female" <?= (($_POST['sex'] ?? '') === 'Female') ? 'selected' : '' ?>>Female
                                </option>
                            </select>
                        </div>


                        <div class="sm:col-span-2 lg:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Course <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="course" required placeholder="e.g. BS Computer Engineering"
                                value="<?= htmlspecialchars($_POST['course'] ?? '') ?>"
                                class="w-full px-3 py-2.5 sm:py-1.5 text-base sm:text-xs rounded-lg sm:rounded border border-gray-300 focus:outline-none focus:border-brand-navy focus:ring-1 focus:ring-brand-navy transition-all" />
                        </div>


                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Year Level <span
                                    class="text-red-500">*</span></label>
                            <select name="year_level" required
                                class="w-full px-3 py-2.5 sm:py-1.5 text-base sm:text-xs rounded-lg sm:rounded border border-gray-300 bg-white focus:outline-none focus:border-brand-navy focus:ring-1 focus:ring-brand-navy transition-all">
                                <?php foreach (['1st Year', '2nd Year', '3rd Year', '4th Year'] as $yl): ?>
                                    <option value="<?= $yl ?>" <?= (($_POST['year_level'] ?? '') === $yl) ? 'selected' : '' ?>>
                                    
                                            <?= $yl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>


                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Section <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="section" required placeholder="e.g. A"
                                value="<?= htmlspecialchars($_POST['section'] ?? '') ?>"
                                class="w-full px-3 py-2.5 sm:py-1.5 text-base sm:text-xs rounded-lg sm:rounded border border-gray-300 focus:outline-none focus:border-brand-navy focus:ring-1 focus:ring-brand-navy transition-all" />
                        </div>


                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Contact Number <span
                                    class="text-red-500">*</span></label>
                            <input type="tel" name="contact_number" required placeholder="e.g. 0912 345 6789"
                                value="<?= htmlspecialchars($_POST['contact_number'] ?? '') ?>"
                                class="w-full px-3 py-2.5 sm:py-1.5 text-base sm:text-xs rounded-lg sm:rounded border border-gray-300 focus:outline-none focus:border-brand-navy focus:ring-1 focus:ring-brand-navy transition-all" />
                        </div>
                    </div>
                </div>


                <!-- SECTION 2: MEDICAL DETAILS -->
                <div class="space-y-4">
                    <h3
                        class="text-xs font-bold text-brand-navy uppercase tracking-wider border-b border-gray-200 pb-2 flex items-center gap-2">
                        <i class="fa-solid fa-notes-medical text-brand-accent"></i> Medical Details
                    </h3>


                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Blood Type</label>
                            <select name="blood_type"
                                class="w-full px-3 py-2.5 sm:py-1.5 text-base sm:text-xs rounded-lg sm:rounded border border-gray-300 bg-white focus:outline-none focus:border-brand-navy focus:ring-1 focus:ring-brand-navy transition-all">
                                <?php
                                $selected_bt = $_POST['blood_type'] ?? 'O+';
                                foreach (['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bt):
                                    ?>
                                    <option value="<?= $bt ?>" <?= ($selected_bt === $bt) ? 'selected' : '' ?>><?= $bt ?>
                                        </option>
                                <?php endforeach; ?>
                            </select>
                        </div>


                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Allergies</label>
                            <input type="text" name="allergies" placeholder="None reported"
                                value="<?= htmlspecialchars($_POST['allergies'] ?? '') ?>"
                                class="w-full px-3 py-2.5 sm:py-1.5 text-base sm:text-xs rounded-lg sm:rounded border border-gray-300 focus:outline-none focus:border-brand-navy focus:ring-1 focus:ring-brand-navy transition-all" />
                        </div>


                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Medical Conditions</label>
                            <input type="text" name="medical_conditions" placeholder="None reported"
                                value="<?= htmlspecialchars($_POST['medical_conditions'] ?? '') ?>"
                                class="w-full px-3 py-2.5 sm:py-1.5 text-base sm:text-xs rounded-lg sm:rounded border border-gray-300 focus:outline-none focus:border-brand-navy focus:ring-1 focus:ring-brand-navy transition-all" />
                        </div>


                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Current Medication</label>
                            <input type="text" name="current_medication" placeholder="None"
                                value="<?= htmlspecialchars($_POST['current_medication'] ?? '') ?>"
                                class="w-full px-3 py-2.5 sm:py-1.5 text-base sm:text-xs rounded-lg sm:rounded border border-gray-300 focus:outline-none focus:border-brand-navy focus:ring-1 focus:ring-brand-navy transition-all" />
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
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Contact Name <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="emergency_contact_name" required
                                value="<?= htmlspecialchars($_POST['emergency_contact_name'] ?? '') ?>"
                                class="w-full px-3 py-2.5 sm:py-1.5 text-base sm:text-xs rounded-lg sm:rounded border border-gray-300 focus:outline-none focus:border-brand-navy focus:ring-1 focus:ring-brand-navy transition-all" />
                        </div>


                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Contact Number <span
                                    class="text-red-500">*</span></label>
                            <input type="tel" name="emergency_contact_number" required placeholder="e.g. 0912 345 6789"
                                value="<?= htmlspecialchars($_POST['emergency_contact_number'] ?? '') ?>"
                                class="w-full px-3 py-2.5 sm:py-1.5 text-base sm:text-xs rounded-lg sm:rounded border border-gray-300 focus:outline-none focus:border-brand-navy focus:ring-1 focus:ring-brand-navy transition-all" />
                        </div>
                    </div>
                </div>


                <!-- ACTION BUTTONS -->
                <div
                    class="pt-4 border-t border-gray-200 flex flex-col-reverse sm:flex-row items-center justify-end gap-2.5 sm:gap-3">
                    <a href="students.php"
                        class="w-full sm:w-auto text-center px-4 py-2.5 sm:py-2 border border-gray-300 hover:bg-gray-50 text-gray-700 font-semibold text-xs rounded-lg sm:rounded transition-all">
                        Cancel
                    </a>
                    <button type="submit"
                        class="w-full sm:w-auto justify-center px-5 py-2.5 sm:py-2 bg-brand-navy hover:bg-blue-900 active:scale-[0.99] text-white font-bold text-xs rounded-lg sm:rounded shadow-sm transition-all flex items-center gap-2">
                        <i class="fa-solid fa-check text-xs"></i> Save Student Record
                    </button>
                </div>


            </form>


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