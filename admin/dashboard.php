<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->redirectIfNotAuthorized(['admin']);

$db = new Database();
$conn = $db->getConnection();

// Get admin information
$adminStmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
$adminStmt->bindParam(':user_id', $_SESSION['user_id']);
$adminStmt->execute();
$admin = $adminStmt->fetch(PDO::FETCH_ASSOC);

// Get system statistics
$statsStmt = $conn->query("
    SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) as student_count,
        SUM(CASE WHEN role = 'coordinator' THEN 1 ELSE 0 END) as coordinator_count,
        SUM(CASE WHEN role = 'manager' THEN 1 ELSE 0 END) as manager_count,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_count
    FROM users
");
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Get recent activities
$activitiesStmt = $conn->query("
    SELECT * FROM system_logs
    ORDER BY created_at DESC
    LIMIT 10
");
$activities = $activitiesStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - University Magazine</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
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
        .activity-log {
            background: white;
            border-radius: 5px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .activity-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .activity-item:last-child {
            border-bottom: none;
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
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="dashboard-container">
        <div class="welcome-message">
            <h1>Welcome, <?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></h1>
            <p>System Administration Dashboard</p>
            <?php if ($admin['last_login']): ?>
                <p>Last login: <?php echo date('F j, Y \a\t g:i a', strtotime($admin['last_login'])); ?></p>
            <?php endif; ?>
        </div>
        
        <h2>System Statistics</h2>
        <div class="stat-cards">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                <div>Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['student_count']; ?></div>
                <div>Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['coordinator_count']; ?></div>
                <div>Coordinators</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['manager_count']; ?></div>
                <div>Managers</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['admin_count']; ?></div>
                <div>Admins</div>
            </div>
        </div>
        
        <div class="admin-actions" style="margin-bottom: 20px;">
            <a href="manage_users.php" class="btn btn-primary">
                <i class="fas fa-users-cog"></i> Manage Users
            </a>
            <a href="system_settings.php" class="btn btn-primary">
                <i class="fas fa-cog"></i> System Settings
            </a>
            <a href="manage_faculties.php" class="btn btn-primary">
                <i class="fas fa-university"></i> Manage Faculties
            </a>
        </div>
        
        <h2>Recent Activities</h2>
        <div class="activity-log">
            <?php if (empty($activities)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No recent activities found.
                </div>
            <?php else: ?>
                <?php foreach ($activities as $activity): ?>
                    <div class="activity-item">
                        <p>
                            <strong><?php echo htmlspecialchars($activity['action']); ?></strong>
                            <span style="color: #666;">- <?php echo date('M j, Y g:i a', strtotime($activity['created_at'])); ?></span>
                        </p>
                        <p style="color: #666;"><?php echo htmlspecialchars($activity['details']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>