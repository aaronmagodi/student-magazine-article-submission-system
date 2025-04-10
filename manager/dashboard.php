<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->redirectIfNotAuthorized(['manager']);

$db = new Database();
$conn = $db->getConnection();

// Get manager information
$managerStmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
$managerStmt->bindParam(':user_id', $_SESSION['user_id']);
$managerStmt->execute();
$manager = $managerStmt->fetch(PDO::FETCH_ASSOC);

// Get all published contributions
$publishedStmt = $conn->query("
    SELECT c.*, f.name as faculty_name, u.username as student_name
    FROM contributions c
    JOIN faculties f ON c.faculty_id = f.id
    JOIN users u ON c.student_id = u.id
    WHERE c.status = 'published'
    ORDER BY c.submission_date DESC
");
$publishedContributions = $publishedStmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics by faculty
$statsStmt = $conn->query("
    SELECT 
        f.name as faculty_name,
        COUNT(c.id) as total_contributions,
        SUM(CASE WHEN c.status = 'published' THEN 1 ELSE 0 END) as published_count
    FROM faculties f
    LEFT JOIN contributions c ON f.id = c.faculty_id
    GROUP BY f.id
");
$facultyStats = $statsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard - University Magazine</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/manager.css">
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
        .contribution-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            background: white;
        }
        .faculty-badge {
            display: inline-block;
            padding: 3px 8px;
            background: #e7f5ff;
            color: #1864ab;
            border-radius: 4px;
            font-size: 0.8rem;
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
        .btn-success {
            background: #2ecc71;
            color: white;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="dashboard-container">
        <!-- Welcome Message -->
        <div class="welcome-message">
            <h1>Welcome, <?php echo htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name']); ?></h1>
            <p>Marketing Manager Dashboard</p>
            <?php if ($manager['last_login']): ?>
                <p>Last login: <?php echo date('F j, Y \a\t g:i a', strtotime($manager['last_login'])); ?></p>
            <?php endif; ?>
        </div>
        
        <!-- Faculty Statistics -->
        <h2>Faculty Statistics</h2>
        <table class="stats-table">
            <thead>
                <tr>
                    <th>Faculty</th>
                    <th>Total Contributions</th>
                    <th>Published</th>
                    <th>Publication Rate</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($facultyStats as $stat): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($stat['faculty_name']); ?></td>
                        <td><?php echo $stat['total_contributions']; ?></td>
                        <td><?php echo $stat['published_count']; ?></td>
                        <td>
                            <?php 
                            $rate = $stat['total_contributions'] > 0 
                                ? round(($stat['published_count'] / $stat['total_contributions']) * 100, 2) 
                                : 0;
                            echo $rate . '%';
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Published Contributions -->
        <h2>Published Contributions</h2>
        <?php if (empty($publishedContributions)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No published contributions yet.
            </div>
        <?php else: ?>
            <?php foreach ($publishedContributions as $contribution): ?>
                <div class="contribution-card">
                    <h3><?php echo htmlspecialchars($contribution['title']); ?></h3>
                    <p>
                        <span class="faculty-badge"><?php echo htmlspecialchars($contribution['faculty_name']); ?></span>
                        <span>By <?php echo htmlspecialchars($contribution['student_name']); ?></span>
                    </p>
                    <p><?php echo nl2br(htmlspecialchars($contribution['abstract'])); ?></p>
                    
                    <div class="contribution-meta">
                        <span><i class="fas fa-calendar"></i> Published on <?php echo date('F j, Y', strtotime($contribution['submission_date'])); ?></span>
                    </div>
                    
                    <div class="contribution-actions">
                        <a href="view_contribution.php?id=<?php echo $contribution['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <a href="<?php echo BASE_URL; ?>download_zip.php?faculty_id=<?php echo $contribution['faculty_id']; ?>" class="btn btn-success">
                            <i class="fas fa-file-archive"></i> Download Faculty ZIP
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>
