<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

$auth = new Auth();
$auth->redirectIfNotAuthorized(['marketing_manager']);

$db = new Database();
$conn = $db->getConnection();

// Settings
$finalClosureDate = '2025-05-10 23:59:59';
$now = new DateTime();
$pageSize = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Prepare search
$searchClause = '';
$params = [];

if ($search !== '') {
    $searchClause = "WHERE c.title LIKE :search OR u.first_name LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

// Count total for pagination
$countStmt = $conn->prepare("
    SELECT COUNT(*) as total
    FROM selected_contributions sc
    JOIN contributions c ON sc.contribution_id = c.id
    JOIN users u ON c.user_id = u.id
    $searchClause
");
$countStmt->execute($params);
$totalRows = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalRows / $pageSize);
$offset = ($page - 1) * $pageSize;

// Fetch selected contributions
$query = "
    SELECT c.id, c.title, c.description, c.word_file_path, u.first_name AS author, sc.selected_at
    FROM selected_contributions sc
    JOIN contributions c ON sc.contribution_id = c.id
    JOIN users u ON c.user_id = u.id
    $searchClause
    ORDER BY sc.selected_at DESC
    LIMIT $pageSize OFFSET $offset
";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$contributions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle ZIP download
if (isset($_GET['download']) && $now > new DateTime($finalClosureDate)) {
    $zip = new ZipArchive();
    $zipName = 'selected_contributions_' . date('Ymd_His') . '.zip';
    $zipPath = sys_get_temp_dir() . '/' . $zipName;

    if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
        die("Cannot create ZIP file.");
    }

    foreach ($contributions as $c) {
        if (!empty($c['word_file_path']) && file_exists($c['word_file_path'])) {
            $zip->addFile($c['word_file_path'], basename($c['word_file_path']));
        }
    }

    $zip->close();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . basename($zipName) . '"');
    header('Content-Length: ' . filesize($zipPath));
    readfile($zipPath);
    unlink($zipPath);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Selected Contributions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            padding: 30px;
            background-color: #f8f9fa;
        }
        .container {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .btn-download {
            margin-bottom: 20px;
        }
        .search-box {
            margin-bottom: 20px;
        }
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

<div class="container">
    <h2 class="mb-4">Selected Contributions</h2>
    <a class="back-link" href="dashboard.php">← Back to Dashboard</a>
    <p><strong>Total Contributions:</strong> <?= $totalRows ?></p>

    <?php if ($now > new DateTime($finalClosureDate)): ?>
        <form method="get">
            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
            <button type="submit" name="download" class="btn btn-success btn-download">Download All as ZIP</button>
        </form>
    <?php else: ?>
        <button class="btn btn-secondary btn-download" disabled>
            Download available after final closure date (<?= $finalClosureDate ?>)
        </button>
    <?php endif; ?>

    <!-- Search form -->
    <form method="get" class="row search-box g-2">
        <div class="col-md-6">
            <input type="text" name="search" class="form-control" placeholder="Search by title or author..." value="<?= htmlspecialchars($search ?? '') ?>">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="selected_contributions.php" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Selected At</th>
                    <th>File</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($contributions) === 0): ?>
                <tr><td colspan="5" class="text-center">No contributions found.</td></tr>
            <?php else: ?>
                <?php foreach ($contributions as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['title']) ?></td>
                        <td><?= htmlspecialchars($c['author']) ?></td>
                        <td><?= date('Y-m-d H:i', strtotime($c['selected_at'])) ?></td>
                        <td>
                            <?php if (!empty($c['word_file_path']) && file_exists($c['word_file_path'])): ?>
                                <a href="<?= htmlspecialchars($c['word_file_path']) ?>" class="btn btn-outline-primary btn-sm" target="_blank">View File</a>
                            <?php else: ?>
                                <span class="text-muted">No File</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <nav>
        <ul class="pagination">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>">Previous</a>
                </li>
            <?php endif; ?>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?search=<?= urlencode($search) ?>&page=<?= $p ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>">Next</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>

</body>
</html>
