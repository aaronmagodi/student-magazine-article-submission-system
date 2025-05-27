<?php
// Enable error reporting
@ini_set('display_errors', 1);
@ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include your database and function files
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    $_SESSION['role'] = 'guest'; // Guest role for visitors
}

// Connect to database
$db = new Database();
$conn = $db->getConnection();

try {
    // Fetch selected contributions
    $stmt = $conn->prepare("
        SELECT c.id, c.title, c.abstract, c.submission_date,
               f.name AS faculty_name,
               u.username AS student_name
        FROM contributions c
        JOIN faculties f ON c.faculty_id = f.id
        JOIN users u ON c.student_id = u.id
        WHERE c.status = 'selected'
        ORDER BY c.submission_date DESC
    ");
    $stmt->execute();
    $contributions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch statistics
    $stats = $conn->query("
        SELECT 
            COUNT(*) AS total_selected,
            COUNT(DISTINCT c.student_id) AS unique_contributors,
            COUNT(DISTINCT c.faculty_id) AS faculties_represented
        FROM contributions c
        WHERE c.status = 'selected'
    ")->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Guest Dashboard - University Magazine</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 1200px; margin: auto; padding: 20px; }
        h1 { color: #333; }
        .stat-boxes { display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap; }
        .stat { background: #fff; border-radius: 10px; padding: 20px; flex: 1; box-shadow: 0 0 10px rgba(0,0,0,0.1); min-width: 200px; }
        .stat h2 { margin: 0 0 10px; font-size: 18px; color: #777; }
        .stat p { font-size: 24px; font-weight: bold; color: #3498db; }
        .contribution { background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 0 8px rgba(0,0,0,0.05); }
        .contribution h3 { margin: 0; }
        .contribution .meta { color: #555; font-size: 14px; margin-top: 5px; }
        .contribution .abstract { margin-top: 10px; }
        .back-link {
                        display: block;
                        margin-top: 15px;
                        text-align: center;
                        font-size: 14px;
                        color: #666;
                        text-decoration: none;
                    }
    </style>
</head>
<body>

<?php //include 'header.php'; ?>
<div class="container">
<a class="back-link" href="../index.php">← Back to Dashboard</a>
    <h1><i class="fas fa-eye"></i> Selected Contributions</h1>
    <p>Explore student articles selected for publication across all faculties.</p>

    <!-- Stats -->
    <div class="stat-boxes">
        <div class="stat">
            <h2>Total Selected</h2>
            <p><?php echo $stats['total_selected']; ?></p>
        </div>
        <div class="stat">
            <h2>Unique Contributors</h2>
            <p><?php echo $stats['unique_contributors']; ?></p>
        </div>
        <div class="stat">
            <h2>Faculties Represented</h2>
            <p><?php echo $stats['faculties_represented']; ?></p>
        </div>
    </div>

    <!-- Contributions -->
    <?php if (!empty($contributions)) : ?>
        <?php foreach ($contributions as $item) : ?>
            <div class="contribution">
                <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                <div class="meta">
                    Submitted by <strong><?php echo htmlspecialchars($item['student_name']); ?></strong>
                    from <strong><?php echo htmlspecialchars($item['faculty_name']); ?></strong>
                    on <?php echo date('F j, Y', strtotime($item['submission_date'])); ?>
                </div>
                <?php if ($item['abstract']) : ?>
                    <div class="abstract"><?php echo nl2br(htmlspecialchars($item['abstract'])); ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else : ?>
        <p>No selected contributions yet.</p>
    <?php endif; ?>
</div>

</body>
</html>
