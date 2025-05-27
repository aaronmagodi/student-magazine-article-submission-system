<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->redirectIfNotAuthorized(['marketing_coordinator']);

$db = new Database();
$conn = $db->getConnection();

$facultyId = $_SESSION['faculty_id'];

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

// --- Pagination Setup ---
$limit = 5;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Get pending contributions for coordinator's faculty with pagination
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
    LIMIT :limit OFFSET :offset
");
$pendingStmt->bindParam(':faculty_id', $facultyId, PDO::PARAM_INT);
$pendingStmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$pendingStmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$pendingStmt->execute();
$pendingContributions = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$totalStmt = $conn->prepare("SELECT COUNT(*) FROM contributions WHERE faculty_id = :faculty_id AND status = 'submitted'");
$totalStmt->bindParam(':faculty_id', $facultyId, PDO::PARAM_INT);
$totalStmt->execute();
$totalContributions = $totalStmt->fetchColumn();
$totalPages = ceil($totalContributions / $limit);

// --- Stats ---
$statsStmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_contributions,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
        SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as pending_count
    FROM contributions
    WHERE faculty_id = :faculty_id
");
$statsStmt->bindParam(':faculty_id', $facultyId, PDO::PARAM_INT);
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordinator Dashboard - University Magazine</title>
    <link rel="stylesheet" href="../assets/css/coordinator.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   
    <style>
        .btn {
    padding: 8px 12px;
    border-radius: 4px;
    text-decoration: none;
    display: inline-block;
    margin-right: 5px;
    font-size: 0.9em;
}

.review-btn {
    display: inline-block;
    padding: 0.5rem 1rem;
    background: #007BFF;
    color: white;
    border: none;
    border-radius: 5px;
    text-decoration: none;
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
            <i class="fas fa-newspaper fa-2x"></i>
            <div><strong><?= $stats['total_contributions'] ?></strong></div>
            <small>Total Contributions</small>
        </div>
        <div class="stat-card">
            <i class="fas fa-check-circle fa-2x" style="color:green;"></i>
            <div><strong><?= $stats['approved_count'] ?></strong></div>
            <small>Approved</small>
        </div>
        <div class="stat-card">
            <i class="fas fa-times-circle fa-2x" style="color:red;"></i>
            <div><strong><?= $stats['rejected_count'] ?></strong></div>
            <small>Rejected</small>
        </div>
        <div class="stat-card">
            <i class="fas fa-hourglass-half fa-2x" style="color:orange;"></i>
            <div><strong><?= $stats['pending_count'] ?></strong></div>
            <small>Pending</small>
        </div>

        
    </div>
        
        <h2>Pending Contributions</h2>
        
        <?php if (count($pendingContributions) > 0): ?>
        <?php foreach ($pendingContributions as $contribution): ?>
            <div class="contribution-card">
                <h3><?= htmlspecialchars($contribution['title']) ?></h3>
                <p><strong>Submitted by:</strong> <?= htmlspecialchars($contribution['student_name']) ?></p>
                <p><strong>Days Pending:</strong> <?= $contribution['days_pending'] ?> day(s)</p>
                <p><strong>Comments:</strong> <?= $contribution['comment_count'] ?></p>
                <a href="review_contribution.php?id=<?= $contribution['id'] ?>"></a>
                <a href="review_contribution.php?id=<?= $contribution['id'] ?>" class="review-btn">Review</a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No pending contributions at the moment.</p>
    <?php endif; ?>

    <!-- Pagination -->
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>" class="<?= ($page == $i) ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>

    
    <?php include '../includes/footer.php'; ?>
</body>
</html>