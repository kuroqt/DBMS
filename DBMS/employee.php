<?php
require_once 'db.php';
require_once 'auth.php';

$search = trim($_GET['search'] ?? '');

try {
  if (!empty($search)) {
    $stmt = $pdo->prepare("
            SELECT * FROM employees 
            WHERE employee_id LIKE :search 
               OR name LIKE :search 
               OR department LIKE :search 
            ORDER BY id DESC
        ");
    $stmt->execute(['search' => "%{$search}%"]);
  } else {
    $stmt = $pdo->query("SELECT * FROM employees ORDER BY id DESC");
  }
  $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  $employees = [];
}
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employee Records - Medical Record System</title>
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

      <a href="students.php">
        <span class="nav-icon">♙</span>
        Students
      </a>

      <a href="employee.php" class="active">
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
      <h1>Employee Medical Records</h1>
      <p>Manage and organize medical records for employees.</p>
    </section>

    <!-- TABLE CARD -->
    <section class="table-card"
      style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px; margin-top: 20px;">

      <div
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 12px; flex-wrap: wrap;">
        <form method="GET" action="employee.php" style="display: flex; gap: 8px; flex: 1; max-width: 400px;">
          <input type="text" name="search" placeholder="Search employee ID or name..."
            value="<?= htmlspecialchars($search) ?>"
            style="flex: 1; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none;" />
          <button type="submit"
            style="padding: 10px 16px; background: #f1f5f9; border: 1px solid #cbd5e1; color: #334155; font-weight: 600; font-size: 14px; border-radius: 8px; cursor: pointer;">Search</button>
          <?php if (!empty($search)): ?>
            <a href="employee.php"
              style="padding: 10px 12px; color: #64748b; text-decoration: none; font-size: 13px; align-self: center;">Clear</a>
          <?php endif; ?>
        </form>

        <a href="add-employee.php" class="quick-button green"
          style="text-decoration: none; padding: 10px 18px; display: inline-flex; align-items: center; gap: 6px; border-radius: 8px; font-weight: 600; font-size: 14px;">
          <span>+</span> Add Employee
        </a>
      </div>

      <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
          <thead>
            <tr style="background: #f8fafc; color: #475569; font-weight: 600; border-bottom: 1px solid #e2e8f0;">
              <th style="padding: 14px 16px;">Employee ID</th>
              <th style="padding: 14px 16px;">Name</th>
              <th style="padding: 14px 16px;">Department</th>
              <th style="padding: 14px 16px;">Position</th>
              <th style="padding: 14px 16px;">Status</th>
              <th style="padding: 14px 16px; text-align: right;">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($employees)): ?>
              <?php foreach ($employees as $emp): ?>
                <tr style="border-bottom: 1px solid #f1f5f9; color: #334155;">
                  <td style="padding: 14px 16px; font-weight: 600; color: #0f172a;">
                    <?= htmlspecialchars($emp['employee_id'] ?? '') ?>
                  </td>
                  <td style="padding: 14px 16px; font-weight: 600; color: #1e293b;">
                    <?= htmlspecialchars($emp['name'] ?? ($emp['first_name'] ?? '' . ' ' . $emp['last_name'] ?? '')) ?>
                  </td>
                  <td style="padding: 14px 16px; color: #475569;"><?= htmlspecialchars($emp['department'] ?? 'N/A') ?></td>
                  <td style="padding: 14px 16px; color: #475569;"><?= htmlspecialchars($emp['position'] ?? 'N/A') ?></td>
                  <td style="padding: 14px 16px;">
                    <span
                      style="background: #dcfce7; color: #15803d; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; display: inline-block;">
                      <?= htmlspecialchars($emp['status'] ?? 'Active') ?>
                    </span>
                  </td>
                  <td style="padding: 14px 16px; text-align: right;">
                    <a href="employee-record.php?id=<?= $emp['id'] ?>"
                      style="color: #2563eb; font-weight: 600; text-decoration: none; padding: 6px 12px; border-radius: 6px; background: #eff6ff;">
                      View
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" style="padding: 30px; text-align: center; color: #94a3b8;">
                  No employee records found.
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