<?php

session_start();  // Always start the session at the top of the page
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
$documentsStmt = $conn->prepare("SELECT * FROM word_documents WHERE contribution_id = :id");
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
    JOIN users u ON cm.user_id = u.id
    WHERE cm.contribution_id = :id
    ORDER BY cm.created_at DESC
");
$commentsStmt->bindParam(':id', $_GET['id']);
$commentsStmt->execute();
$comments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);


if (isset($success)) {
    $_SESSION['success'] = $success;
    header("Location: edit_contribution.php?id=" . $contributionId);
    exit();
}
if (isset($error)) {
    $_SESSION['error'] = $error;
    header("Location: edit_contribution.php?id=" . $contributionId);
    exit();
}



?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - University Magazine</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/student.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">


    <style>
.modal {
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.6);
}

.modal-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 20px;
    width: 50%;
    border-radius: 10px;
    position: relative;
}

.close {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 22px;
    cursor: pointer;
}
</style>
  
</head>



<body>
<?php include '../includes/header.php'; ?>


<div class="container">

<div class="contribution-meta">
        <span class="status-badge status-<?php echo strtolower($contribution['status']); ?>">
            <?php echo ucfirst($contribution['status']); ?>
        </span>
        <span>Submitted on: <?php echo date('F j, Y \a\t g:i a', strtotime($contribution['submission_date'])); ?></span>
        <span>Faculty: <?php echo htmlspecialchars($contribution['faculty_name']); ?></span>
    </div>
    
    <h2>Title</h2>
   <?php echo htmlspecialchars($contribution['title']); ?>
    
    
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
    
    <!-- Edit Button -->
     <!-- Trigger Button -->
<div class="action-buttons">
    <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#editContributionModal">
        <i class="fas fa-edit"></i> Edit Contribution
    </button>
</div>

<!-- Edit Contribution Modal -->
<div class="modal fade" id="editContributionModal" tabindex="-1" aria-labelledby="editContributionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editContributionModalLabel">Edit Contribution</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form action="edit_contribution.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="contribution_id" value="<?= htmlspecialchars($_GET['id']) ?>">

      

            
            <div class="mb-3">
                <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="title" id="title" value="<?= htmlspecialchars($contribution['title']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="abstract" class="form-label">Abstract</label>
                <textarea class="form-control" name="abstract" id="abstract" rows="3"><?= htmlspecialchars($contribution['abstract']) ?></textarea>
            </div>


            <div class="mb-3">
                <label class="form-label">Upload New Word File</label>
                <input type="file" class="form-control" name="word_file" accept=".doc,.docx">
            </div>

            <div class="mb-3">
                <label class="form-label">Upload Images</label>
                <input type="file" class="form-control" name="images[]" multiple accept="image/*">
            </div>

            <div class="modal-footer">
              <button type="submit" class="btn btn-primary">Update</button>
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>


</div>

<?php include '../includes/footer.php'; ?>


</body>
</html>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


<script>
function showEditModal() {
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}
</script>
<script>
function showEditModal() {
    var editModal = new bootstrap.Modal(document.getElementById('editContributionModal'));
    editModal.show();
}
</script>

