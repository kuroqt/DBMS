<?php
require_once 'db.php';
require_once 'auth.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if (!empty($search)) {

  $stmt = $pdo->prepare("SELECT * FROM employees WHERE emp_code LIKE :s OR first_name LIKE :s OR last_name LIKE :s OR department LIKE :s ORDER BY id DESC");
  $stmt->execute(['s' => "%$search%"]);
} else {

  $stmt = $pdo->query("SELECT * FROM employees ORDER BY id DESC");
}

$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Employee Medical Records</title>
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
      <a href="index.php"> <span>⌂</span> Dashboard </a>
      <a href="students.php"> <span>♙</span> Students </a>
      <a href="employee.php" class="active"> <span>♙</span> Employees </a>
    </nav>
  </aside>

  <main class="main-content">
    <header class="topbar">
      <div>
        <h1>Employee Medical Records</h1>
        <p>Manage medical information and consultation history of employees.</p>
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

    <!-- Search & Action Section -->
    <section class="page-actions">
      <form method="GET" action="employee.php" class="search-box">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
          placeholder="Search employee ID or name..." />
        <button type="submit">Search</button>
        <?php if (!empty($search)): ?>
          <a href="employee.php" style="margin-left: 10px; text-decoration: none; color: #666;">Clear</a>
        <?php endif; ?>
      </form>
      <a href="add-employee.php" class="primary-button" style="text-decoration: none;">+ Add Employee</a>
    </section>

    <!-- Table Container -->
    <section class="record-container">
      <div class="section-title">
        <div>
          <h2>Employee List</h2>
          <p>Registered employee medical records</p>
        </div>
      </div>

      <table>
        <thead>
          <tr>
            <th>Employee ID</th>
            <th>Name</th>
            <th>Position</th>
            <th>Department</th>
            <th>Contact</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>

        <tbody>
          <?php if (!empty($employees)): ?>
            <?php foreach ($employees as $emp): ?>
              <?php
              $full_name = trim($emp['title'] . ' ' . $emp['first_name'] . ' ' . $emp['last_name']);
              $status_class = (strtolower($emp['status']) === 'active' || strtolower($emp['status']) === 'normal') ? 'normal' : 'warning';
              ?>
              <tr>
                <td><?= htmlspecialchars($emp['emp_code']) ?></td>
                <td>
                  <strong><?= htmlspecialchars($full_name) ?></strong>
                </td>
                <td><?= htmlspecialchars($emp['position']) ?></td>
                <td><?= htmlspecialchars($emp['department']) ?></td>
                <td><?= htmlspecialchars($emp['contact_number']) ?></td>
                <td>
                  <span class="status <?= $status_class ?>"> <?= htmlspecialchars($emp['status']) ?> </span>
                </td>
                <td>
                  <a href="employee-record.php?id=<?= $emp['id'] ?>" class="view-button"> View </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" style="text-align: center; padding: 20px;">No employee records found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </section>
  </main>
</body>

</html>