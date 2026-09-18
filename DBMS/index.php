<?php
require_once 'db.php';
require_once 'auth.php';

// 1. Get Total Employees count
try {
  $total_employees = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
} catch (Exception $e) {
  $total_employees = 0;
}

// 2. Get Total Students count (defaults to 0 if students table isn't created yet)
try {
  $total_students = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
} catch (Exception $e) {
  $total_students = 0;
}

// 3. Get Today's Consultations count
try {
  $today = date('Y-m-d');
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM consultations WHERE DATE(consultation_date) = :today");
  $stmt->execute(['today' => $today]);
  $todays_consultations = $stmt->fetchColumn();
} catch (Exception $e) {
  $todays_consultations = 0;
}
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Medical Record System</title>
  <link rel="stylesheet" href="style.css" />
</head>

<body>
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
      <a href="index.php" class="active"> <span>⌂</span> Dashboard </a>
      <a href="students.php"> <span>♙</span> Students </a>
      <a href="employee.php"> <span>♙</span> Employees </a>
    </nav>
  </aside>

  <main class="main-content">
    <header class="topbar">
      <div>
        <h1>Dashboard</h1>
        <p>Medical Record Management System</p>
      </div>

      <div class="user">
        <div class="user-icon"><?= strtoupper(substr($_SESSION['full_name'], 0, 1)) ?></div>
        <div>
          <strong><?= htmlspecialchars($_SESSION['full_name']) ?></strong>
          <small><?= htmlspecialchars($_SESSION['role']) ?></small>
        </div>
        <a href="logout.php"
          style="margin-left: 15px; color: #ef4444; font-weight: bold; font-size: 13px; text-decoration: none;">Logout</a>
      </div>
    </header>

    <section class="welcome">
      <h2>Welcome to our Medical Record</h2>
      <p>
        Manage and organize medical records for students and employees in one
        system.
      </p>
    </section>

    <section class="dashboard-cards">
      <div class="dashboard-card">
        <div class="card-icon student-icon">♙</div>

        <div>
          <h3>Students</h3>
          <p>Manage student medical records.</p>
          <a href="students.php" class="card-button">
            View Student Records →
          </a>
        </div>
      </div>

      <div class="dashboard-card">
        <div class="card-icon employee-icon">♙</div>

        <div>
          <h3>Employees</h3>
          <p>Manage employee medical records.</p>
          <a href="employee.php" class="card-button">
            View Employee Records →
          </a>
        </div>
      </div>
    </section>

    <section class="stats">
      <div class="stat-box">
        <span>Total Students</span>
        <strong><?= $total_students ?></strong>
      </div>

      <div class="stat-box">
        <span>Total Employees</span>
        <strong><?= $total_employees ?></strong>
      </div>

      <div class="stat-box">
        <span>Today's Consultations</span>
        <strong><?= $todays_consultations ?></strong>
      </div>
    </section>
  </main>
</body>

</html>