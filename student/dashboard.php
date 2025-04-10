<?php
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
            c.created_at,
            c.status,
            COUNT(cm.id) AS comment_count
        FROM contributions c
        LEFT JOIN comments cm ON c.id = cm.contribution_id
        WHERE c.user_id = :user_id
        GROUP BY c.id
        ORDER BY c.created_at DESC
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
        
        while ($comment = $commentsStmt->fetch(PDO::FETCH_ASSOC)) {
            if (!isset($comments[$comment['contribution_id']])) {
                $comments[$comment['contribution_id']] = $comment;
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
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/student.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --info-color: #1abc9c;
            --light-gray: #f8f9fa;
            --border-color: #ddd;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 20px;
        }

        .welcome-card {
            background: var(--light-gray);
            border-left: 4px solid var(--primary-color);
        }

        .deadline-card {
            background: #e7f5ff;
            border-left: 4px solid var(--primary-color);
        }

        .contribution-card {
            border: 1px solid var(--border-color);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .contribution-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-draft { background: var(--warning-color); color: #000; }
        .status-submitted { background: var(--primary-color); color: #fff; }
        .status-accepted { background: var(--success-color); color: #fff; }
        .status-rejected { background: var(--danger-color); color: #fff; }
        .status-under_review { background: #9b59b6; color: #fff; }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #666;
            font-size: 0.9em;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9em;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-warning {
            background: var(--warning-color);
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .comment-card {
            background: #f8f9fa;
            border-left: 3px solid var(--primary-color);
            padding: 15px;
            margin-top: 15px;
        }

        .comment-meta {
            display: flex;
            justify-content: space-between;
            color: #666;
            font-size: 0.85em;
            margin-bottom: 5px;
        }

        .alert {
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffeeba;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #bee5eb;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 10px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
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
                    
                    <!-- Display latest comment if exists -->
                    <?php if (isset($comments[$contribution['contribution_id']])): ?>
                        <div class="comment-card">
                            <div class="comment-meta">
                                <span>
                                    <i class="fas fa-user-tie"></i> 
                                    <?php echo htmlspecialchars($comments[$contribution['contribution_id']]['first_name'] . ' ' . $comments[$contribution['contribution_id']]['last_name']); ?>
                                </span>
                                <span>
                                    <i class="fas fa-clock"></i> 
                                    <?php echo date('M j, Y g:i a', strtotime($comments[$contribution['contribution_id']]['created_at'])); ?>
                                </span>
                            </div>
                            <p><?php echo nl2br(htmlspecialchars($comments[$contribution['contribution_id']]['comment'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="action-buttons">
                        <a href="view_contribution.php?id=<?php echo $contribution['contribution_id']; ?>" class="btn btn-primary">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                        
                        <?php if ($contribution['status'] === 'draft' || 
                                 ($contribution['status'] === 'rejected' && (!$deadline || time() < strtotime($deadline['final_closure_date'])))): ?>
                            <a href="edit_contribution.php?id=<?php echo $contribution['contribution_id']; ?>" class="btn btn-success">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($contribution['status'] === 'draft' && (!$deadline || time() < strtotime($deadline['submission_deadline']))): ?>
                            <form method="post" action="submit_contribution.php" style="display: inline;">
                                <input type="hidden" name="contribution_id" value="<?php echo $contribution['contribution_id']; ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Submit for Review
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>