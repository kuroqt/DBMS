<?php
require_once 'db.php';
require_once 'auth.php';

$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

$student_results = [];
$employee_results = [];

if (!empty($search_query)) {
    $term = "%{$search_query}%";

    // 1. Search Students Table (includes full name concatenation search)
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

    // 2. Search Employees Table (includes full name concatenation search)
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
        $employee_results = [];
    }
}

$total_results = count($student_results) + count($employee_results);
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Search Results for "<?= htmlspecialchars($search_query) ?>"</title>
    <link rel="stylesheet" href="style.css?v=2" />
</head>

<body>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo">
            <div class="logo-icon">
                <img src="images/acsat.jpg" alt="Logo" />
            </div>
            <div>
                <h2>Medical Record</h2>
                <span>ACSAT Medical System</span>
            </div>
        </div>
        <nav>
            <a href="index.php"> <span class="nav-icon">⌂</span> Dashboard </a>
            <a href="students.php"> <span class="nav-icon">♙</span> Students </a>
            <a href="employee.php"> <span class="nav-icon">♟</span> Employees </a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <!-- TOPBAR -->
        <header class="topbar">
            <form class="top-search" action="search.php" method="GET"
                style="display: flex; align-items: center; gap: 8px;">
                <button type="submit"
                    style="background: none; border: none; cursor: pointer; font-size: 16px; color: #64748b; padding: 0;">⌕</button>
                <input type="text" name="q" value="<?= htmlspecialchars($search_query) ?>"
                    placeholder="Search student, employee, ID number..." required
                    style="border: none; outline: none; background: transparent; width: 100%;">
            </form>

            <div class="user">
                <div class="user-icon">
                    <?= strtoupper(substr($_SESSION['full_name'] ?? 'A', 0, 1)) ?>
                </div>
                <div class="user-information">
                    <strong><?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?></strong>
                    <small><?= htmlspecialchars($_SESSION['role'] ?? 'Administrator') ?></small>
                </div>
                <a href="logout.php"
                    style="color: #ef4444; font-size: 13px; font-weight: 600; text-decoration: none; margin-left: 10px;">Logout</a>
            </div>
        </header>

        <section class="page-heading">
            <h1>Search Results</h1>
            <p><?= $total_results ?> result(s) found for "<strong><?= htmlspecialchars($search_query) ?></strong>"</p>
        </section>

        <!-- RESULTS SECTION -->
        <div style="display: flex; flex-direction: column; gap: 24px;">

            <!-- STUDENTS RESULTS -->
            <div style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px;">
                <h3
                    style="margin-top: 0; margin-bottom: 16px; font-size: 16px; color: #0f172a; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
                    Student Records (<?= count($student_results) ?>)
                </h3>

                <?php if (!empty($student_results)): ?>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <?php foreach ($student_results as $row): ?>
                            <a href="student-record.php?id=<?= $row['id'] ?>"
                                style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: #f8fafc; border-radius: 8px; text-decoration: none; border: 1px solid #e2e8f0; transition: background 0.2s;"
                                onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#f8fafc'">
                                <div>
                                    <strong
                                        style="color: #0f172a; font-size: 15px;"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></strong>
                                    <span style="display: block; font-size: 12px; color: #64748b;">ID Code:
                                        <?= htmlspecialchars($row['code'] ?? 'N/A') ?> | Course:
                                        <?= htmlspecialchars($row['course'] ?? 'N/A') ?></span>
                                </div>
                                <span style="color: #2563eb; font-weight: 600; font-size: 13px;">View Record →</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: #64748b; margin: 0; font-size: 14px;">No student records matched your search query.</p>
                <?php endif; ?>
            </div>

            <!-- EMPLOYEES RESULTS -->
            <div style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px;">
                <h3
                    style="margin-top: 0; margin-bottom: 16px; font-size: 16px; color: #0f172a; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
                    Employee Records (<?= count($employee_results) ?>)
                </h3>

                <?php if (!empty($employee_results)): ?>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <?php foreach ($employee_results as $row): ?>
                            <a href="employee-record.php?id=<?= $row['id'] ?>"
                                style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: #f8fafc; border-radius: 8px; text-decoration: none; border: 1px solid #e2e8f0; transition: background 0.2s;"
                                onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#f8fafc'">
                                <div>
                                    <strong
                                        style="color: #0f172a; font-size: 15px;"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></strong>
                                    <span style="display: block; font-size: 12px; color: #64748b;">Employee Code:
                                        <?= htmlspecialchars($row['code'] ?? 'N/A') ?> | Dept:
                                        <?= htmlspecialchars($row['course'] ?? 'N/A') ?></span>
                                </div>
                                <span style="color: #2563eb; font-weight: 600; font-size: 13px;">View Record →</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: #64748b; margin: 0; font-size: 14px;">No employee records matched your search query.
                    </p>
                <?php endif; ?>
            </div>

        </div>
    </main>
</body>

</html>