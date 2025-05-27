<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

if (!isset($_GET['id'])) {
    exit('No ID provided.');
}

$contributionId = (int) $_GET['id'];

$commentsStmt = $conn->prepare("
    SELECT comments.*, users.username, users.role 
    FROM comments 
    JOIN users ON comments.user_id = users.id 
    WHERE contribution_id = :contribution_id 
    ORDER BY created_at ASC
");
$commentsStmt->bindParam(':contribution_id', $contributionId, PDO::PARAM_INT);
$commentsStmt->execute();
$comments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($comments as $comment) {
    $isCoordinator = ($comment['role'] == 'marketing_coordinator') ? 'coordinator' : 'student';
    ?>
    <div class="comment <?= $isCoordinator ?>">
        <strong><?= htmlspecialchars($comment['username']) ?></strong>
        <p><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
        <small><?= date('F j, Y, g:i A', strtotime($comment['created_at'])) ?></small>
    </div>
<?php
}
?>
