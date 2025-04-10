<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Initialize guest session if not already set
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    $_SESSION['role'] = 'guest';
}

$db = new Database();
$conn = $db->getConnection();

// Get published contributions
$contributionsStmt = $conn->query("
    SELECT c.id, c.title, c.abstract, c.submission_date, 
           f.name as faculty_name, u.username as student_name
    FROM contributions c
    JOIN faculties f ON c.faculty_id = f.id
    JOIN users u ON c.student_id = u.id
    WHERE c.status = 'published'
    ORDER BY c.submission_date DESC
");
$contributions = $contributionsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$statsStmt = $conn->query("
    SELECT 
        COUNT(*) as total_published,
        COUNT(DISTINCT c.student_id) as unique_contributors,
        COUNT(DISTINCT c.faculty_id) as faculties_represented
    FROM contributions c
    WHERE c.status = 'published'
");
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest View - University Magazine</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/guest.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .guest-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .stat-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #3498db;
        }

        .contribution-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
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

        @media (max-width: 768px) {
            .stat-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <div class="guest-container">
        <h1><i class="fas fa-eye"></i> Magazine Contributions</h1>
        <p>Viewing selected published contributions from all faculties</p>

        <div class="stat-cards">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_published']; ?></div>
                <div class="stat-label">Published Articles</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['unique_contributors']; ?></div>
                <div class="stat-label">Student Contributors</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['faculties_represented']; ?></div>
                <div class="stat-label">Faculties Represented</div>
            </div>
        </div>

        <div class="contributions-list">
            <?php if (empty($contributions)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No published contributions yet.
                </div>
            <?php else: ?>
                <?php foreach ($contributions as $contribution): ?>
                    <div class="contribution-card">
                        <h2><?php echo htmlspecialchars($contribution['title']); ?></h2>
                        <div class="contribution-meta">
                            <span class="faculty-badge">
                                <?php echo htmlspecialchars($contribution['faculty_name']); ?>
                            </span>
                            <span>By <?php echo htmlspecialchars($contribution['student_name']); ?></span>
                            <span>Published on <?php echo date('F j, Y', strtotime($contribution['submission_date'])); ?></span>
                        </div>
                        <div class="contribution-abstract">
                            <p><?php echo nl2br(htmlspecialchars($contribution['abstract'])); ?></p>
                        </div>
                        <a href="view_contribution.php?id=<?php echo $contribution['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-book-open"></i> Read Full Article
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>

</html>
