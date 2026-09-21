<?php
require_once 'db.php';
require_once 'auth.php';

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
    <title><?= $page_title ?> - Medical Record System</title>
    <link rel="stylesheet" href="style.css?v=2">
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
            <form class="top-search" action="search.php" method="GET" style="display: flex; align-items: center; gap: 8px;">
                <button type="submit" style="background: none; border: none; cursor: pointer; font-size: 16px; color: #64748b; padding: 0;">⌕</button>
                <input type="text" name="q" placeholder="Search student, employee, ID number..." required style="border: none; outline: none; background: transparent; width: 100%;">
            </form>

            <div class="user">
                <div class="user-icon">
                    <?= strtoupper(substr($_SESSION['full_name'] ?? 'A', 0, 1)) ?>
                </div>

                <div class="user-information">
                    <strong><?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?></strong>
                    <small><?= htmlspecialchars($_SESSION['role'] ?? 'Administrator') ?></small>
                </div>

                <a href="logout.php" style="color: #ef4444; font-size: 13px; font-weight: 600; text-decoration: none; margin-left: 10px;">Logout</a>
            </div>
        </header>

        <section class="page-heading">
            <p><a href="index.php" style="text-decoration:none; font-weight:bold; color: #2563eb;">← Back to Dashboard</a></p>
            <h1 style="margin-top: 10px;"><?= $page_title ?></h1>
        </section>

        <!-- Filter Navigation Tabs -->
        <div style="margin: 20px 0; display: flex; gap: 10px;">
            <a href="consultations.php" style="padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; background: <?= empty($filter) ? '#2563eb' : '#f1f5f9' ?>; color: <?= empty($filter) ? '#fff' : '#374151' ?>;">All Records</a>
            <a href="consultations.php?filter=today" style="padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; background: <?= $filter === 'today' ? '#2563eb' : '#f1f5f9' ?>; color: <?= $filter === 'today' ? '#fff' : '#374151' ?>;">Today Only</a>
            <a href="consultations.php?filter=followup" style="padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; background: <?= $filter === 'followup' ? '#2563eb' : '#f1f5f9' ?>; color: <?= $filter === 'followup' ? '#fff' : '#374151' ?>;">Pending Follow-ups</a>
        </div>

        <div class="recent-card" style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden;">
            <div class="consultation-table">
                <div class="table-head" style="display: grid; grid-template-columns: 2fr 1.5fr 2fr 1fr 1fr; padding: 14px 20px; background: #f8fafc; font-weight: 600; border-bottom: 1px solid #e2e8f0; color: #475569;">
                    <span>Patient Name</span>
                    <span>Date & Time</span>
                    <span>Reason / Complaint</span>
                    <span>Status</span>
                    <span>Action</span>
                </div>

                <?php if (!empty($consultations)): ?>
                    <?php foreach ($consultations as $c): ?>
                        <?php 
                            $patient_name = !empty($c['first_name']) ? ($c['first_name'] . ' ' . $c['last_name']) : ($c['patient_name'] ?? 'N/A');
                            $status_text = $c['follow_up_status'] ?? $c['status'] ?? 'Completed';
                            $reason_text = $c['complaint'] ?? $c['reason'] ?? 'N/A';
                            $student_link = !empty($c['student_id']) ? "student-record.php?id=" . $c['student_id'] : "javascript:void(0)";
                        ?>
                        <div class="table-row" style="display: grid; grid-template-columns: 2fr 1.5fr 2fr 1fr 1fr; padding: 14px 20px; border-bottom: 1px solid #f1f5f9; align-items: center;">
                            <div>
                                <strong style="color: #0f172a; display: block;"><?= htmlspecialchars($patient_name) ?></strong>
                                <?php if (!empty($c['student_id_code'])): ?>
                                    <small style="color: #64748b; font-size: 11px;"><?= htmlspecialchars($c['student_id_code']) ?></small>
                                <?php endif; ?>
                            </div>
                            <span><?= htmlspecialchars(date('M d, Y - h:i A', strtotime($c['consultation_date']))) ?></span>
                            <span><?= htmlspecialchars($reason_text) ?></span>
                            <span>
                                <span style="display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; background: <?= ($status_text === 'Pending' || $status_text === 'Follow-up') ? '#fef3c7' : '#dcfce7' ?>; color: <?= ($status_text === 'Pending' || $status_text === 'Follow-up') ? '#92400e' : '#166534' ?>;">
                                    <?= htmlspecialchars($status_text) ?>
                                </span>
                            </span>
                            <span>
                                <a href="<?= $student_link ?>" style="color: #2563eb; font-weight: 600; text-decoration: none;">View Record →</a>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-consultations" style="padding: 40px; text-align: center; color: #64748b;">
                        <p style="margin: 0;">No consultation records found for this view.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>

</html>