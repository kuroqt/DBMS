<?php
require_once 'db.php';
require_once 'auth.php';

/* =========================================================
   DASHBOARD STATISTICS
========================================================= */

// Total Employees
try {
  $total_employees = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
} catch (Exception $e) {
  $total_employees = 0;
}

// Total Students
try {
  $total_students = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
} catch (Exception $e) {
  $total_students = 0;
}

// Today's Consultations
try {
  $today = date('Y-m-d');
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM consultations WHERE DATE(consultation_date) = :today");
  $stmt->execute(['today' => $today]);
  $todays_consultations = $stmt->fetchColumn();
} catch (Exception $e) {
  $todays_consultations = 0;
}

// Pending Follow-ups
try {
  $pending_followups = $pdo->query("SELECT COUNT(*) FROM consultations WHERE status = 'Follow-up'")->fetchColumn();
} catch (Exception $e) {
  $pending_followups = 0;
}

?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Medical Record System</title>
  <link rel="stylesheet" href="style.css?v=2" />
</head>

<body>

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="logo">
      <div class="logo-icon">
        <img src="images/acsat.jpg" alt="ACSAT Logo">
      </div>
      <div>
        <h2>Medical Record</h2>
        <span>ACSAT Medical System</span>
      </div>
    </div>

    <nav>
      <a href="index.php" class="active">
        <span class="nav-icon">⌂</span>
        Dashboard
      </a>

      <a href="students.php">
        <span class="nav-icon">♙</span>
        Students
      </a>

      <a href="employee.php">
        <span class="nav-icon">♟</span>
        Employees
      </a>
    </nav>
  </aside>

  <!-- MAIN CONTENT -->
  <main class="main-content">

    <!-- TOP BAR -->
    <header class="topbar">
      <!-- UPDATED TOPBAR SEARCH FORM -->
      <form class="top-search" action="search.php" method="GET" style="display: flex; align-items: center; gap: 8px;">
        <button type="submit"
          style="background: none; border: none; cursor: pointer; font-size: 16px; color: #64748b; padding: 0;">⌕</button>
        <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
          placeholder="Search student, employee, ID number..." required
          style="border: none; outline: none; background: transparent; width: 100%;">
      </form>

      <!-- USER INFO & LOGOUT -->
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

    <!-- PAGE TITLE -->
    <section class="page-heading">
      <h1>Dashboard</h1>
      <p>Medical Record Management System</p>
    </section>

    <!-- WELCOME -->
    <section class="welcome">
      <div class="welcome-icon">♡</div>
      <div class="welcome-content">
        <h2>Welcome to our Medical Record System</h2>
        <p>Manage and organize medical records for students and employees in one system.</p>
      </div>
      <div class="welcome-decoration">♡</div>
    </section>

    <!-- STATISTICS -->
    <section class="statistics">

      <div class="stat-card">
        <div class="stat-icon student-stat">🎓</div>
        <div class="stat-content">
          <span>Total Students</span>
          <strong><?= $total_students ?></strong>
          <small>Registered students</small>
        </div>
        <a href="students.php">›</a>
      </div>

      <div class="stat-card">
        <div class="stat-icon employee-stat">👤</div>
        <div class="stat-content">
          <span>Total Employees</span>
          <strong><?= $total_employees ?></strong>
          <small>Registered employees</small>
        </div>
        <a href="employee.php">›</a>
      </div>

      <div class="stat-card">
        <div class="stat-icon consultation-stat">♡</div>
        <div class="stat-content">
          <span>Today's Consultations</span>
          <strong><?= $todays_consultations ?></strong>
          <small><?= ($todays_consultations == 0) ? 'No consultations today' : 'Consultations today' ?></small>
        </div>
        <a href="consultations.php">›</a>
      </div>

      <div class="stat-card">
        <div class="stat-icon followup-stat">!</div>
        <div class="stat-content">
          <span>Pending Follow-ups</span>
          <strong><?= $pending_followups ?></strong>
          <small><?= ($pending_followups == 0) ? 'All clear' : 'Needs attention' ?></small>
        </div>
        <a href="consultations.php?filter=followup">›</a>
      </div>

    </section>

    <!-- QUICK ACTIONS -->
    <section class="quick-section">
      <h2><span>⚡</span> Quick Actions</h2>

      <div class="quick-actions">
        <a href="add-student.php" class="quick-button blue">
          <span>+</span> Add Student Record
        </a>

        <a href="add-employee.php" class="quick-button green">
          <span>+</span> Add Employee Record
        </a>

        <a href="students.php" class="quick-button white">
          <span>🎓</span> View Student Records
        </a>

        <a href="employee.php" class="quick-button white">
          <span>👤</span> View Employee Records
        </a>

        <a href="consultations.php" class="quick-button white">
          <span>▣</span> View Consultations
        </a>
      </div>
    </section>

  </main>

</body>

</html>