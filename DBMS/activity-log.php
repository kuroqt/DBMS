<?php
require_once 'db.php';
require_once 'auth.php';

try {
    $stmt = $pdo->query("SELECT * FROM consultations ORDER BY consultation_date DESC LIMIT 20");
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $activities = [];
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - Medical Record System</title>
    <link rel="stylesheet" href="style.css?v=2">
</head>

<body>
    <main class="main-content" style="padding: 30px;">
        <p><a href="index.php" style="text-decoration:none; font-weight:bold;">← Back to Dashboard</a></p>
        <h1 style="margin-top:15px;">System Activity Log</h1>

        <div class="recent-card" style="padding:25px; margin-top:20px;">
            <ul style="line-height:2.2; list-style:none; padding:0;">
                <?php foreach ($activities as $act): ?>
                    <li style="border-bottom:1px solid #eee; padding:8px 0;">
                        <strong>
                            <?= htmlspecialchars(date('M d, Y - h:i A', strtotime($act['consultation_date']))) ?>:
                        </strong>
                        Consultation record processed (Status: <em>
                            <?= htmlspecialchars($act['status'] ?? 'Completed') ?>
                        </em>)
                    </li>
                <?php endforeach; ?>
                <?php if (empty($activities)): ?>
                    <li>No system activity recorded yet.</li>
                <?php endif; ?>
            </ul>
        </div>
    </main>
</body>

</html>