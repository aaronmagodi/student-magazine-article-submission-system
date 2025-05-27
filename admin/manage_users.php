<?php
session_start();

require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->redirectIfNotAuthorized(['admin']);

$db = new Database();
$conn = $db->getConnection();

// Handle approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $stmt = $conn->prepare("UPDATE users SET status = 'approved' WHERE id = :id AND role = 'marketing_manager'");
    $stmt->bindParam(':id', $_POST['user_id']);
    $stmt->execute();
    header('Location: manage_users.php?approved=1');
    exit;
}

// Fetch all marketing managers pending approval
$pendingStmt = $conn->query("SELECT * FROM users WHERE role = 'marketing_manager' AND status != 'approved'");
$pendingManagers = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - University Magazine</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>../assets/css/styles.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>../assets/css/admin.css">
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
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .stats-table th, .stats-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .stats-table th {
            background-color: #f2f2f2;
        }
    </style>

</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="dashboard-container">
    <a href="dashboard.php" class="btn btn-primary" style="margin-bottom: 20px; display: inline-block;">&larr; Back to Dashboard</a>

        <h2>Marketing Managers - Pending Approval</h2>
        
        <?php if (isset($_GET['approved'])): ?>
            <div style="color: green; margin-bottom: 10px;">User approved successfully!</div>
        <?php endif; ?>

        <?php if (count($pendingManagers) === 0): ?>
            <p>No pending marketing managers.</p>
        <?php else: ?>
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Submitted</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($pendingManagers as $manager): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($manager['email']); ?></td>
                        <td><?php echo date('F j, Y', strtotime($manager['created_at'])); ?></td>
                        <td>
                            <form method="POST" action="manage_users.php" style="display:inline;">
                                <input type="hidden" name="user_id" value="<?php echo $manager['id']; ?>">
                                <button class="btn btn-primary" type="submit">Approve</button>
                                <button class="btn btn-primary" type="submit">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
