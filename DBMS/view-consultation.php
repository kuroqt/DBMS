<?php
require_once 'db.php';
require_once 'auth.php';

$id = $_GET['id'] ?? null;
$consultation = null;

if ($id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM consultations WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $consultation = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $consultation = null;
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Consultation - Medical Record System</title>
    <link rel="stylesheet" href="style.css?v=2">
</head>

<body>
    <main class="main-content" style="padding: 30px;">
        <p><a href="consultations.php" style="text-decoration:none; font-weight:bold;">← Back to Consultations</a></p>
        <h1 style="margin-top:15px;">Consultation Details</h1>

        <?php if ($consultation): ?>
            <div class="recent-card" style="padding:25px; margin-top:20px; line-height:1.8;">
                <p><strong>Consultation ID:</strong> #
                    <?= htmlspecialchars($consultation['id']) ?>
                </p>
                <p><strong>Date & Time:</strong>
                    <?= htmlspecialchars(date('M d, Y - h:i A', strtotime($consultation['consultation_date']))) ?>
                </p>
                <p><strong>Reason:</strong>
                    <?= htmlspecialchars($consultation['reason'] ?? 'N/A') ?>
                </p>
                <p><strong>Status:</strong>
                    <?= htmlspecialchars($consultation['status'] ?? 'Completed') ?>
                </p>
                <p><strong>Diagnosis / Notes:</strong>
                    <?= htmlspecialchars($consultation['notes'] ?? $consultation['diagnosis'] ?? 'No notes recorded.') ?>
                </p>
            </div>
        <?php else: ?>
            <div class="recent-card" style="padding:25px; margin-top:20px;">
                <p>Consultation record not found or invalid ID specified.</p>
            </div>
        <?php endif; ?>
    </main>
</body>

</html>