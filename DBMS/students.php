<?php
require_once 'db.php';
require_once 'auth.php';

$search = trim($_GET['search'] ?? '');

try {
  if (!empty($search)) {
    $stmt = $pdo->prepare("
            SELECT * FROM students 
            WHERE student_id LIKE :search 
               OR name LIKE :search 
               OR course LIKE :search 
            ORDER BY id DESC
        ");
    $stmt->execute(['search' => "%{$search}%"]);
  } else {
    $stmt = $pdo->query("SELECT * FROM students ORDER BY id DESC");
  }
  $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  $students = [];
}
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Records - Medical Record System</title>
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
      <a href="index.php">
        <span class="nav-icon">⌂</span>
        Dashboard
      </a>

      <a href="students.php" class="active">
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
      <h1>Student Medical Records</h1>
      <p>Manage and organize medical records for students.</p>
    </section>

    <!-- TABLE CARD -->
    <section class="table-card"
      style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px; margin-top: 20px;">

      <div
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 12px; flex-wrap: wrap;">
        <form method="GET" action="students.php" style="display: flex; gap: 8px; flex: 1; max-width: 400px;">
          <input type="text" name="search" placeholder="Search student ID or name..."
            value="<?= htmlspecialchars($search) ?>"
            style="flex: 1; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none;" />
          <button type="submit"
            style="padding: 10px 16px; background: #f1f5f9; border: 1px solid #cbd5e1; color: #334155; font-weight: 600; font-size: 14px; border-radius: 8px; cursor: pointer;">Search</button>
          <?php if (!empty($search)): ?>
            <a href="students.php"
              style="padding: 10px 12px; color: #64748b; text-decoration: none; font-size: 13px; align-self: center;">Clear</a>
          <?php endif; ?>
        </form>

        <a href="add-student.php" class="quick-button blue"
          style="text-decoration: none; padding: 10px 18px; display: inline-flex; align-items: center; gap: 6px; border-radius: 8px; font-weight: 600; font-size: 14px;">
          <span>+</span> Add Student
        </a>
      </div>

      <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
          <thead>
            <tr style="background: #f8fafc; color: #475569; font-weight: 600; border-bottom: 1px solid #e2e8f0;">
              <th style="padding: 14px 16px;">Student ID</th>
              <th style="padding: 14px 16px;">Name</th>
              <th style="padding: 14px 16px;">Course</th>
              <th style="padding: 14px 16px;">Year</th>
              <th style="padding: 14px 16px;">Section</th>
              <th style="padding: 14px 16px;">Status</th>
              <th style="padding: 14px 16px; text-align: right;">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($students)): ?>
              <?php foreach ($students as $student): ?>
                <tr style="border-bottom: 1px solid #f1f5f9; color: #334155;">
                  <td style="padding: 14px 16px; font-weight: 600; color: #0f172a;">
                    <?= htmlspecialchars($student['student_id'] ?? '') ?>
                  </td>
                  <td style="padding: 14px 16px; font-weight: 600; color: #1e293b;">
                    <?= htmlspecialchars($student['name'] ?? ($student['first_name'] ?? '' . ' ' . $student['last_name'] ?? '')) ?>
                  </td>
                  <td style="padding: 14px 16px; color: #475569;"><?= htmlspecialchars($student['course'] ?? 'N/A') ?></td>
                  <td style="padding: 14px 16px; color: #475569;"><?= htmlspecialchars($student['year'] ?? 'N/A') ?></td>
                  <td style="padding: 14px 16px; color: #475569;"><?= htmlspecialchars($student['section'] ?? 'N/A') ?></td>
                  <td style="padding: 14px 16px;">
                    <span
                      style="background: #dcfce7; color: #15803d; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; display: inline-block;">
                      <?= htmlspecialchars($student['status'] ?? 'Active') ?>
                    </span>
                  </td>
                  <td style="padding: 14px 16px; text-align: right;">
                    <a href="student-record.php?id=<?= $student['id'] ?>"
                      style="color: #2563eb; font-weight: 600; text-decoration: none; padding: 6px 12px; border-radius: 6px; background: #eff6ff;">
                      View
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" style="padding: 30px; text-align: center; color: #94a3b8;">
                  No student records found.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </section>

  </main>

</body>

</html>