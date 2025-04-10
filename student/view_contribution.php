<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->redirectIfNotAuthorized(['student']);

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Get contribution details
$contributionStmt = $conn->prepare("
    SELECT c.*, f.name as faculty_name
    FROM contributions c
    JOIN faculties f ON c.faculty_id = f.id
    WHERE c.id = :id AND c.student_id = :student_id
");
$contributionStmt->bindParam(':id', $_GET['id']);
$contributionStmt->bindParam(':student_id', $_SESSION['user_id']);
$contributionStmt->execute();
$contribution = $contributionStmt->fetch(PDO::FETCH_ASSOC);

if (!$contribution) {
    header("Location: dashboard.php");
    exit;
}

// Get documents
$documentsStmt = $conn->prepare("SELECT * FROM documents WHERE contribution_id = :id");
$documentsStmt->bindParam(':id', $_GET['id']);
$documentsStmt->execute();
$documents = $documentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get images
$imagesStmt = $conn->prepare("SELECT * FROM images WHERE contribution_id = :id");
$imagesStmt->bindParam(':id', $_GET['id']);
$imagesStmt->execute();
$images = $imagesStmt->fetchAll(PDO::FETCH_ASSOC);

// Get comments
$commentsStmt = $conn->prepare("
    SELECT cm.*, u.username as coordinator_name
    FROM comments cm
    JOIN users u ON cm.coordinator_id = u.id
    WHERE cm.contribution_id = :id
    ORDER BY cm.created_at DESC
");
$commentsStmt->bindParam(':id', $_GET['id']);
$commentsStmt->execute();
$comments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container">
    <h1><?php echo htmlspecialchars($contribution['title']); ?></h1>
    
    <div class="contribution-meta">
        <span class="status-badge status-<?php echo strtolower($contribution['status']); ?>">
            <?php echo ucfirst($contribution['status']); ?>
        </span>
        <span>Submitted on: <?php echo date('F j, Y \a\t g:i a', strtotime($contribution['submission_date'])); ?></span>
        <span>Faculty: <?php echo htmlspecialchars($contribution['faculty_name']); ?></span>
    </div>
    
    <div class="contribution-content">
        <h2>Abstract</h2>
        <p><?php echo nl2br(htmlspecialchars($contribution['abstract'])); ?></p>
        
        <h2>Documents</h2>
        <?php if (empty($documents)): ?>
            <p>No documents uploaded</p>
        <?php else: ?>
            <ul class="document-list">
                <?php foreach ($documents as $doc): ?>
                    <li>
                        <a href="<?php echo BASE_URL . 'download.php?type=doc&id=' . $doc['id']; ?>" target="_blank">
                            <i class="fas fa-file-word"></i> Download Document
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        
        <h2>Images</h2>
        <?php if (empty($images)): ?>
            <p>No images uploaded</p>
        <?php else: ?>
            <div class="image-gallery">
                <?php foreach ($images as $img): ?>
                    <div class="image-thumbnail">
                        <a href="<?php echo BASE_URL . 'uploads/images/' . basename($img['file_path']); ?>" target="_blank">
                            <img src="<?php echo BASE_URL . 'uploads/images/' . basename($img['file_path']); ?>" alt="Contribution image">
                        </a>
                        <?php if (!empty($img['caption'])): ?>
                            <p><?php echo htmlspecialchars($img['caption']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <h2>Comments from Coordinator</h2>
    <?php if (empty($comments)): ?>
        <p>No comments yet</p>
    <?php else: ?>
        <div class="comments-section">
            <?php foreach ($comments as $comment): ?>
                <div class="comment">
                    <div class="comment-header">
                        <strong><?php echo htmlspecialchars($comment['coordinator_name']); ?></strong>
                        <span><?php echo date('F j, Y \a\t g:i a', strtotime($comment['created_at'])); ?></span>
                    </div>
                    <div class="comment-body">
                        <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="action-buttons">
        <a href="dashboard.php" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>