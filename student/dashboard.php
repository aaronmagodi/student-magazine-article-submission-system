<?php
session_start();  // Always start the session at the top of the page

require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->redirectIfNotAuthorized(['student']);

$db = new Database();
$conn = $db->getConnection();

// Get student information
try {
    $studentStmt = $conn->prepare("
        SELECT u.*, f.name AS faculty_name 
        FROM users u
        LEFT JOIN faculties f ON u.faculty_id = f.id
        WHERE u.id = :user_id
    ");
    $studentStmt->bindParam(':user_id', $_SESSION['user_id']);
    $studentStmt->execute();
    $student = $studentStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        throw new Exception("Student not found in database");
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
} catch (Exception $e) {
    die($e->getMessage());
}

// Get student's contributions
try {
    $contributionsStmt = $conn->prepare("
        SELECT 
            c.id,
            c.title,
            c.abstract,
            c.submission_date,
            c.status,
            COUNT(cm.id) AS comment_count
        FROM contributions c
        LEFT JOIN comments cm ON c.id = cm.contribution_id
        WHERE c.user_id = :user_id
        GROUP BY c.id
        ORDER BY c.submission_date DESC
    ");
    $contributionsStmt->bindParam(':user_id', $_SESSION['user_id']);
    $contributionsStmt->execute();
    $contributions = $contributionsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get images for each contribution
    if (!empty($contributions)) {
        $contributionIds = array_column($contributions, 'id');
        $placeholders = implode(',', array_fill(0, count($contributionIds), '?'));
        
        $imagesStmt = $conn->prepare("
            SELECT contribution_id, file_path 
            FROM images 
            WHERE contribution_id IN ($placeholders)
        ");
        $imagesStmt->execute($contributionIds);
        $allImages = $imagesStmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_COLUMN);
        
        // Add images to each contribution
        foreach ($contributions as &$contribution) {
            $contribution['images'] = $allImages[$contribution['id']] ?? [];
        }
    }
} catch (PDOException $e) {
    error_log("Contributions query failed: " . $e->getMessage());
    $contributions = [];
}

// Hardcoded deadlines (replace with database values if available)
$deadline = [
    'submission_deadline' => '2025-06-30',
    'final_closure_date' => '2025-11-30'
];

// Get latest comment for each contribution
// Get latest comment for each contribution
$comments = [];

// Get latest comment for each contribution
$comments = [];

if (!empty($contributions)) {
    try {
        $contributionIds = array_column($contributions, 'id');
        $placeholders = implode(',', array_fill(0, count($contributionIds), '?'));

        $commentsStmt = $conn->prepare("
            SELECT 
                c.contribution_id,
                c.comment,
                c.created_at,
                u.first_name,
                u.last_name
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.contribution_id IN ($placeholders)
            ORDER BY c.created_at DESC
        ");

        $commentsStmt->execute($contributionIds);

        // Fetch comments and store them in the $comments array only if contribution_id exists
        while (($comment = $commentsStmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            // Only add to $comments array if 'contribution_id' is set
            if (isset($comment['contribution_id'])) {
                $comments[$comment['contribution_id']][] = $comment;
            }
        }
    } catch (PDOException $e) {
        error_log("Comments query failed: " . $e->getMessage());
    }
}



defined('BASE_URL') or define('BASE_URL', '/magazine-system/');
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
  
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="dashboard-container">
        <!-- Welcome Card -->
        <div class="card welcome-card">
            <h1>Welcome, <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h1>
            <div class="meta-items" style="display: flex; gap: 15px; margin-top: 10px;">
                <div class="meta-item">
                    <i class="fas fa-university"></i>
                    <span><?php echo htmlspecialchars($student['faculty_name'] ?? 'Not assigned'); ?></span>
                </div>
                <?php if ($student['last_login']): ?>
                <div class="meta-item">
                    <i class="fas fa-clock"></i>
                    <span>Last login: <?php echo date('F j, Y \a\t g:i a', strtotime($student['last_login'])); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Deadline Card -->
        <?php if ($deadline): ?>
        <div class="card deadline-card">
            <h3><i class="fas fa-calendar-alt"></i> Important Dates</h3>
            <div class="meta-items" style="display: flex; flex-wrap: wrap; gap: 15px; margin-top: 10px;">
                <div class="meta-item">
                    <i class="fas fa-paper-plane"></i>
                    <span>Submission Deadline: <?php echo date('F j, Y', strtotime($deadline['submission_deadline'])); ?></span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-lock"></i>
                    <span>Final Closure: <?php echo date('F j, Y', strtotime($deadline['final_closure_date'])); ?></span>
                </div>
            </div>
            <?php if (time() > strtotime($deadline['submission_deadline'])): ?>
                <div class="alert alert-warning" style="margin-top: 15px;">
                    <i class="fas fa-exclamation-triangle"></i> The submission deadline has passed.
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Actions -->
        <div style="margin: 20px 0;">
            <?php if (!$deadline || time() < strtotime($deadline['submission_deadline'])): ?>
                <a href="submit.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Submit New Contribution
                </a>
            <?php endif; ?>
        </div>
        
        <!-- Contributions Section -->
        <h2 style="margin-bottom: 15px;">Your Contributions</h2>
        
        <?php if (empty($contributions)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> You haven't submitted any contributions yet.
            </div>
        <?php else: ?>
            <?php foreach ($contributions as $contribution): ?>
    <div class="card contribution-card">
        <h3><?php echo htmlspecialchars($contribution['title']); ?></h3>
        
        <?php if (!empty($contribution['abstract'])): ?>
            <p style="margin: 10px 0;"><?php echo nl2br(htmlspecialchars($contribution['abstract'])); ?></p>
        <?php endif; ?>
        
        <div style="display: flex; flex-wrap: wrap; gap: 15px; margin: 10px 0;">
            <span class="status-badge status-<?php echo strtolower($contribution['status']); ?>">
                <?php echo ucfirst(str_replace('_', ' ', $contribution['status'])); ?>
            </span>
            <div class="meta-item">
                <i class="fas fa-calendar"></i>
                <span><?php echo date('F j, Y', strtotime($contribution['submission_date'])); ?></span>
            </div>
            <div class="meta-item">
                <i class="fas fa-comment"></i>
                <span><?php echo $contribution['comment_count']; ?> feedback comments</span>
            </div>
        </div>
        
        <?php if (isset($comments[$contribution['id']]) && !empty($comments[$contribution['id']])): ?>
    <div class="comment-card">
        <div class="comment-meta">
            <span>
                <i class="fas fa-user-tie"></i> 
                <?php echo htmlspecialchars($comments[$contribution['id']][0]['first_name'] . ' ' . $comments[$contribution['id']][0]['last_name']); ?>
            </span>
            <span>
                <i class="fas fa-clock"></i> 
                <?php echo date('M j, Y g:i a', strtotime($comments[$contribution['id']][0]['created_at'])); ?>
            </span>
        </div>
        <p><?php echo nl2br(htmlspecialchars($comments[$contribution['id']][0]['comment'])); ?></p>
    </div>
<?php else: ?>
    <p>No comments yet.</p>
<?php endif; ?>


        <!-- Action buttons -->
        <div class="action-buttons">
    <!-- View Details Button -->
    <a href="view_contribution.php?id=<?php echo $contribution['id']; ?>" class="btn btn-primary">
        <i class="fas fa-eye"></i> View Details
    </a>

    <?php if ($contribution['status'] === 'draft' || 
             ($contribution['status'] === 'rejected' && (!$deadline || time() < strtotime($deadline['final_closure_date'])))): ?>
        <!-- Edit Contribution Button if status is draft or rejected and within deadline -->
        <a href="edit_contribution.php?id=<?php echo $contribution['id']; ?>" class="btn btn-warning">
            <i class="fas fa-edit"></i> Edit Contribution
        </a>
    <?php endif; ?>
</div>

    </div>
<?php endforeach; ?>

        <?php endif; ?>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>