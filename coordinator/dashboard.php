<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->redirectIfNotAuthorized(['coordinator']);

$db = new Database();
$conn = $db->getConnection();

// Get coordinator information
$coordinatorStmt = $conn->prepare("
    SELECT u.*, f.name as faculty_name 
    FROM users u
    JOIN faculties f ON u.faculty_id = f.id
    WHERE u.id = :user_id
");
$coordinatorStmt->bindParam(':user_id', $_SESSION['user_id']);
$coordinatorStmt->execute();
$coordinator = $coordinatorStmt->fetch(PDO::FETCH_ASSOC);

// Get pending contributions for coordinator's faculty
$pendingStmt = $conn->prepare("
    SELECT c.*, u.username as student_name, 
           COUNT(cm.id) as comment_count,
           DATEDIFF(NOW(), c.submission_date) as days_pending
    FROM contributions c
    JOIN users u ON c.student_id = u.id
    LEFT JOIN comments cm ON c.id = cm.contribution_id
    WHERE c.faculty_id = :faculty_id AND c.status = 'submitted'
    GROUP BY c.id
    ORDER BY c.submission_date DESC
");
$pendingStmt->bindParam(':faculty_id', $_SESSION['faculty_id']);
$pendingStmt->execute();
$pendingContributions = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$statsStmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_contributions,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
        SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as pending_count
    FROM contributions
    WHERE faculty_id = :faculty_id
");
$statsStmt->bindParam(':faculty_id', $_SESSION['faculty_id']);
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordinator Dashboard - University Magazine</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/coordinator.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .welcome-message {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .stat-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #3498db;
        }
        .contribution-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            background: white;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .btn {
            padding: 8px 12px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            margin-right: 5px;
            font-size: 0.9em;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="dashboard-container">
        <div class="welcome-message">
            <h1>Welcome, <?php echo htmlspecialchars($coordinator['first_name'] . ' ' . $coordinator['last_name']); ?></h1>
            <p>Faculty: <?php echo htmlspecialchars($coordinator['faculty_name']); ?></p>
            <?php if ($coordinator['last_login']): ?>
                <p>Last login: <?php echo date('F j, Y \a\t g:i a', strtotime($coordinator['last_login'])); ?></p>
            <?php endif; ?>
        </div>
        
        <h2>Faculty Statistics</h2>
        <div class="stat-cards">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_contributions']; ?></div>
                <div>Total Contributions</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['approved_count']; ?></div>
                <div>Approved</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['rejected_count']; ?></div>
                <div>Rejected</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['pending_count']; ?></div>
                <div>Pending Review</div>
            </div>
        </div>
        
        <h2>Pending Contributions</h2>
        
        <?php if (empty($pendingContributions)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No pending contributions for review.
            </div>
        <?php else: ?>
            <?php foreach ($pendingContributions as $contribution): ?>
                <div class="contribution-card">
                    <h3><?php echo htmlspecialchars($contribution['title']); ?></h3>
                    <p>By <?php echo htmlspecialchars($contribution['student_name']); ?></p>
                    <p><?php echo nl2br(htmlspecialchars($contribution['abstract'])); ?></p>
                    
                    <div class="contribution-meta">
                        <span><i class="fas fa-calendar"></i> Submitted on <?php echo date('F j, Y', strtotime($contribution['submission_date'])); ?></span>
                        <span><i class="fas fa-clock"></i> Pending for <?php echo $contribution['days_pending']; ?> days</span>
                        <span><i class="fas fa-comment"></i> <?php echo $contribution['comment_count']; ?> comments</span>
                    </div>
                    
                    <?php if ($contribution['days_pending'] > 14): ?>
                        <div class="alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> This contribution is overdue for review!
                        </div>
                    <?php endif; ?>
                    
                    <div class="contribution-actions">
                        <a href="review_contribution.php?id=<?php echo $contribution['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Review
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>