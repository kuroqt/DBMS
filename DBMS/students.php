<?php
require_once 'db.php';
require_once 'auth.php';

// Safety net: Auto-create 'students' table if missing
$pdo->exec("CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id_code VARCHAR(50) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50) DEFAULT '',
    last_name VARCHAR(50) NOT NULL,
    dob DATE,
    sex VARCHAR(10),
    course VARCHAR(100),
    year_level VARCHAR(20),
    section VARCHAR(10),
    contact_number VARCHAR(11),
    status VARCHAR(20) DEFAULT 'Active',
    blood_type VARCHAR(5),
    allergies TEXT,
    medical_conditions TEXT,
    current_medication TEXT,
    emergency_contact_name VARCHAR(100),
    emergency_contact_number VARCHAR(11)
)");

// Check if search query was submitted
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if (!empty($search)) {
  // Search by Student ID, First Name, Last Name, or Course
  $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id_code LIKE :s OR first_name LIKE :s OR last_name LIKE :s OR course LIKE :s ORDER BY id DESC");
  $stmt->execute(['s' => "%$search%"]);
} else {
  // Fetch all students
  $stmt = $pdo->query("SELECT * FROM students ORDER BY id DESC");
}

$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Student Medical Records</title>
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
      <a href="students.php" class="active"> <span>♙</span> Students </a>
      <a href="employee.php"> <span>♙</span> Employees </a>
    </nav>
  </aside>

  <main class="main-content">
    <header class="topbar">
      <div>
        <h1>Student Medical Records</h1>
        <p>
          Manage medical information and consultation history of students.
        </p>
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

    <!-- Dynamic Search & Action Section -->
    <section class="page-actions">
      <form method="GET" action="students.php" class="search-box">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
          placeholder="Search student ID or name..." />
        <button type="submit">Search</button>
        <?php if (!empty($search)): ?>
          <a href="students.php" style="margin-left: 10px; text-decoration: none; color: #666;">Clear</a>
        <?php endif; ?>
      </form>
      <a href="add-student.php" class="primary-button" style="text-decoration: none; display: inline-block;">+ Add
        Student</a>
    </section>

    <section class="record-container">
      <div class="section-title">
        <div>
          <h2>Student List</h2>
          <p>Registered student medical records</p>
        </div>
      </div>

      <table>
        <thead>
          <tr>
            <th>Student ID</th>
            <th>Name</th>
            <th>Course</th>
            <th>Year</th>
            <th>Section</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>

        <tbody>
          <?php if (!empty($students)): ?>
            <?php foreach ($students as $st): ?>
              <?php
              $full_name = trim($st['first_name'] . ' ' . ($st['middle_name'] ? $st['middle_name'] . ' ' : '') . $st['last_name']);
              $status_class = (strtolower($st['status']) === 'active' || strtolower($st['status']) === 'normal') ? 'normal' : 'warning';
              ?>
              <tr>
                <td><?= htmlspecialchars($st['student_id_code']) ?></td>
                <td>
                  <strong><?= htmlspecialchars($full_name) ?></strong>
                </td>
                <td><?= htmlspecialchars($st['course']) ?></td>
                <td><?= htmlspecialchars($st['year_level']) ?></td>
                <td><?= htmlspecialchars($st['section']) ?></td>
                <td>
                  <span class="status <?= $status_class ?>"> <?= htmlspecialchars($st['status']) ?> </span>
                </td>
                <td>
                  <!-- Link dynamically passes the student ID to student-record.php -->
                  <a href="student-record.php?id=<?= $st['id'] ?>" class="view-button"> View </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" style="text-align: center; padding: 20px;">No student records found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </section>
  </main>
</body>

</html>