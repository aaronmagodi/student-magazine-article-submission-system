<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->redirectIfNotAuthorized(['marketing_coordinator']);

$db = new Database();
$conn = $db->getConnection();

if (!isset($_GET['id'])) {
  die('No contribution ID provided.');
}

$contributionId = (int) $_GET['id'];

//if (!isset($_GET['id'])) {
  //  header("Location: dashboard.php");
    //exit;
//}

// Get the contribution details
$stmt = $conn->prepare("
    SELECT c.*, u.username AS student_name, f.name AS faculty_name
    FROM contributions c
    JOIN users u ON c.student_id = u.id
    JOIN faculties f ON c.faculty_id = f.id
    WHERE c.id = :id
");
$stmt->bindParam(':id', $contributionId, PDO::PARAM_INT);
$stmt->execute();
$contribution = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$contribution) {
    die('Contribution not found.');
}
// Allowed file types for Word documents and images
$allowedWordTypes = ['doc', 'docx'];
$allowedImageTypes = ['jpg', 'jpeg', 'png', 'gif'];

if (!empty($documents)) {
    echo "<h3>Attached Word Documents</h3>";
    echo "<ul>";
    foreach ($documents as $doc) {
        $fileExtension = pathinfo($doc['file_name'], PATHINFO_EXTENSION);
        if (in_array(strtolower($fileExtension), $allowedWordTypes)) {
            // Display valid Word document
            echo '<li>';
            echo '<a href="../uploads/contributions/' . htmlspecialchars($doc['file_name']) . '" target="_blank">';
            echo 'View ' . htmlspecialchars($doc['file_name']);
            echo '</a> | ';
            echo '<a href="../uploads/contributions/' . htmlspecialchars($doc['file_name']) . '" download>';
            echo 'Download';
            echo '</a>';
            echo '</li>';
        }
    }
    echo "</ul>";
}

// Display Images if valid
if (!empty($images)) {
    echo "<h3>Attached Images</h3>";
    echo "<div style='display: flex; flex-wrap: wrap; gap: 15px;'>";
    foreach ($images as $img) {
        $fileExtension = pathinfo($img['file_name'], PATHINFO_EXTENSION);
        if (in_array(strtolower($fileExtension), $allowedImageTypes)) {
            // Display valid Image
            echo '<div>';
            echo '<a href="/../uploads/contribution_images/' . htmlspecialchars($img['file_name']) . '" download>';
            echo '<img src="../uploads/contribution_images/' . htmlspecialchars($img['file_name']) . '" alt="Image" style="max-width: 200px; border: 1px solid #ccc; border-radius: 5px;">';
            echo '</a>';
            echo '<br>';
            echo '<a href="../uploads/contribution_images/' . htmlspecialchars($img['file_name']) . '" download>Download</a>';
            echo '</div>';
        }
    }
    echo "</div>";
}

if (isset($_POST['submit_comment'])) {
    $commentText = trim($_POST['comment']);
    if (!empty($commentText)) {
        $insertComment = $conn->prepare("
            INSERT INTO comments (contribution_id, user_id, comment) 
            VALUES (:contribution_id, :user_id, :comment)
        ");
        $insertComment->execute([
            ':contribution_id' => $contributionId,
            ':user_id' => $_SESSION['user_id'],  // assuming you store logged-in user ID in session
            ':comment' => $commentText
        ]);

        // Optional: Redirect to prevent resubmission
        header("Location: review_contribution.php?id=" . $contributionId);
        exit();
    }
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



// Handle approve/reject actions
// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if (in_array($action, ['approved', 'rejected'])) {
        $conn->beginTransaction(); // Start transaction

        try {
            // 1. Update the contribution status
            $updateStmt = $conn->prepare("UPDATE contributions SET status = :status WHERE id = :id");
            $updateStmt->bindParam(':status', $action);
            $updateStmt->bindParam(':id', $contributionId);
            $updateStmt->execute();

            // 2. Insert into selected_contributions or rejected_contributions
            $userId = $_SESSION['user_id']; // logged-in coordinator

            if ($action === 'approved') {
                $insertSelected = $conn->prepare("
                    INSERT INTO selected_contributions (contribution_id, selected_by)
                    VALUES (:contribution_id, :selected_by)
                ");
                $insertSelected->execute([
                    ':contribution_id' => $contributionId,
                    ':selected_by' => $userId
                ]);
            } elseif ($action === 'rejected') {
                $insertRejected = $conn->prepare("
                    INSERT INTO rejected_contributions (contribution_id, rejected_by)
                    VALUES (:contribution_id, :rejected_by)
                ");
                $insertRejected->execute([
                    ':contribution_id' => $contributionId,
                    ':rejected_by' => $userId
                ]);
            }

            // 3. Insert comment if provided
            if (!empty($commentText)) {
                $commentStmt = $conn->prepare("
                    INSERT INTO comments (contribution_id, user_id, comment)
                    VALUES (:contribution_id, :user_id, :comment)
                ");
                $commentStmt->bindParam(':contribution_id', $contributionId, PDO::PARAM_INT);
                $commentStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $commentStmt->bindParam(':comment', $commentText, PDO::PARAM_STR);
                $commentStmt->execute();
            }

            $conn->commit(); // Commit transaction

            echo "<div style='color: green; font-weight: bold; margin-top:20px;'>
            Contribution has been successfully <u>$action</u>. Redirecting...
          </div>";

    // After 3 seconds, redirect to coordinator_dashboard.php
    header("refresh:3;url=../coordinator/coordinator_dashboard.php");

} catch (Exception $e) {
    $conn->rollBack(); // Rollback if error occurs
    echo "<div style='color: red; font-weight: bold; margin-top:20px;'>
            Error processing the contribution: " . htmlspecialchars($e->getMessage()) . "
          </div>";
}
} else {
echo "<div style='color: red; margin-top:20px;'>Invalid action specified.</div>";
}
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Review Contribution</title>
    <link rel="stylesheet" href="../assets/css/coordinator.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
    .comments-section {
        margin-top: 30px;
        padding: 20px;
        background: #f9f9f9;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .comments-section h3 {
        margin-bottom: 15px;
        font-size: 1.5rem;
        border-bottom: 2px solid #ddd;
        padding-bottom: 5px;
    }

    .comment {
        max-width: 70%;
        padding: 15px;
        margin: 10px 0;
        border-radius: 15px;
        position: relative;
        clear: both;
    }

    .comment.coordinator {
        background-color: #d1f8d1;
        float: right;
        text-align: right;
        border-top-right-radius: 0;
    }

    .comment.student {
        background-color: #e4e4e4;
        float: left;
        text-align: left;
        border-top-left-radius: 0;
    }

    .comment strong {
        font-size: 1rem;
        display: block;
        margin-bottom: 5px;
        color: #34495e;
    }

    .comment p {
        margin: 0 0 5px;
        font-size: 1rem;
    }

    .comment small {
        font-size: 0.75rem;
        color: #777;
    }

    .form-group {
        margin-top: 20px;
    }

    textarea.form-control {
        width: 100%;
        border: 1px solid #ccc;
        border-radius: 10px;
        padding: 10px;
        font-size: 1rem;
        resize: vertical;
    }

    button.btn-submit-comment {
        margin-top: 10px;
        padding: 10px 20px;
        font-size: 1rem;
        background-color: #3498db;
        border: none;
        color: white;
        border-radius: 10px;
        cursor: pointer;
    }

    button.btn-submit-comment:hover {
        opacity: 0.9;
    }

    .clearfix::after {
        content: "";
        display: table;
        clear: both;
    }
</style>

</head>
<body>

<?php include '../includes/header.php'; ?>

<div class="container">
    <h2>Review Contribution</h2>
    <div class="contribution-meta">
        <p><strong>Title:</strong> <?= htmlspecialchars($contribution['title']) ?></p>
        <p><strong>Submitted by:</strong> <?= htmlspecialchars($contribution['student_name']) ?></p>
        <p><strong>Faculty:</strong> <?= htmlspecialchars($contribution['faculty_name']) ?></p>
        <p><strong>Date Submitted:</strong> <?= date('F j, Y', strtotime($contribution['submission_date'])) ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars($contribution['status']) ?></p>
        <p><strong>Word Documents:</strong></p>
    </div>

  
   
    <?php if (!empty($documents)): ?>
    <h3>Attached Word Documents</h3>
    <ul>
        <?php foreach ($documents as $doc): ?>
            <li>
                <a href="../uploads/word_documents/<?= htmlspecialchars($doc['file_name']) ?>" target="_blank">
                    View <?= htmlspecialchars($doc['file_name']) ?>
                </a>
                | 
                <a href="../uploads/word_documents/<?= htmlspecialchars($doc['file_name']) ?>" download>
                    Download
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>


<?php if (!empty($images)): ?>
    <h3>Attached Images</h3>
    <div style="display: flex; flex-wrap: wrap; gap: 15px;">
        <?php foreach ($images as $img): ?>
            <div>
                <a href="../uploads/images/<?= htmlspecialchars($img['file_name']) ?>" download>
                    <img src="../uploads/images/<?= htmlspecialchars($img['file_name']) ?>" alt="Image" style="max-width: 200px; border: 1px solid #ccc; border-radius: 5px;">
                </a>
                <br>
                <a href="../uploads/images/<?= htmlspecialchars($img['file_name']) ?>" download>Download</a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>


    <div class="comments-section">
    <h3>Comments</h3>

    <div id="comments-container" class="comments-list clearfix">
        <?php
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
        <?php } ?>
    </div>

    <!-- Comment form -->
    <form method="post">
        <div class="form-group">
            <textarea name="comment" class="form-control" rows="3" placeholder="Write a comment..." required></textarea>
        </div>
        <button type="submit" name="submit_comment" class="btn-submit-comment">💬 Post Comment</button>
    </form>
</div>

    <form method="post" class="action-buttons">
        <button type="submit" name="action" value="approved" class="btn btn-approve">✅ Approve</button>
        <button type="submit" name="action" value="rejected" class="btn btn-reject">❌ Reject</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>

</body>
<script>
    window.onload = function() {
        var commentsList = document.querySelector('.comments-list');
        if (commentsList) {
            commentsList.scrollTop = commentsList.scrollHeight;
        }
    };
</script>

<script>
    function loadComments() {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', 'load_comments.php?id=<?= $contributionId ?>', true);
        xhr.onload = function() {
            if (this.status === 200) {
                document.getElementById('comments-container').innerHTML = this.responseText;
                var commentsList = document.getElementById('comments-container');
                commentsList.scrollTop = commentsList.scrollHeight;
            }
        };
        xhr.send();
    }

    setInterval(loadComments, 5000); // Refresh every 5 seconds
</script>

<style>
.success-message, .error-message {
    margin-top: 50px;
    text-align: center;
    font-size: 1.5rem;
}
</style>


</html>
