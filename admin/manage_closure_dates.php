<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->redirectIfNotAuthorized(['admin']);

$db = new Database();
$conn = $db->getConnection();

$errors = [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $year = $_POST['year'];
    $closure_date = $_POST['closure_date'];
    $submissionDeadline=$_POST['submission_date'];

    if (empty($year) || empty($closure_date)) {
        $errors[] = 'Both fields are required.';
    } else {
        if (!empty($_POST['id'])) {
            // Edit
            $stmt = $conn->prepare("UPDATE academic_years SET year = ?, final_closure_date = ? WHERE id = ?");
            $stmt->execute([$year, $closure_date, $_POST['id']]);
            $_SESSION['success'] = 'Academic year updated successfully.';
        } else {
            // Add
            $stmt = $conn->prepare("INSERT INTO academic_years (year, final_closure_date, submission_deadline) VALUES (?, ?, ?)");
            $stmt->execute([$year, $closure_date, $submissionDeadline]);
            $_SESSION['success'] = 'Academic year added successfully.';
        }
        header('Location: manage_closure_dates.php');
        exit;
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM academic_years WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $_SESSION['success'] = 'Academic year deleted successfully.';
    header('Location: manage_closure_dates.php');
    exit;
}

// Handle edit
$edit_data = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM academic_years WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Pagination logic
$perPage = 5;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$start = ($page - 1) * $perPage;

$totalStmt = $conn->query("SELECT COUNT(*) FROM academic_years");
$totalRows = $totalStmt->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

$stmt = $conn->prepare("SELECT * FROM academic_years ORDER BY year DESC LIMIT :start, :perPage");
$stmt->bindValue(':start', $start, PDO::PARAM_INT);
$stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
$stmt->execute();
$closures = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Closure Dates</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .container { max-width: 800px; margin: auto; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: center; }
        .form-group { margin-bottom: 15px; }
        .form-control { padding: 8px; width: 100%; }
        .btn { padding: 8px 12px; border: none; background: #3498db; color: white; cursor: pointer; text-decoration: none; }
        .btn-danger { background: #e74c3c; }
        .btn-warning { background: #f39c12; }
        .btn-primary { background: #2980b9; }
        .alert-success {
            padding: 10px;
            background-color: #d4edda;
            color: #155724;
            margin-bottom: 15px;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
        }
        .alert-danger {
            padding: 10px;
            background-color: #f8d7da;
            color: #721c24;
            margin-bottom: 15px;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
        }
        .pagination {
            margin-top: 20px;
            text-align: center;
        }
        .pagination .btn {
            margin: 0 2px;
            background: #ddd;
            color: #333;
        }
        .pagination .btn-primary {
            background: #2980b9;
            color: white;
        }
        .pagination .btn:hover {
            background: #bbb;
        }
    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container">
    <h1>Manage Academic Closures</h1>
    <a href="manage_faculties.php" class="btn btn-primary">Go to Manage Faculties</a>

    <?php if (!empty($success)): ?>
        <div class="alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert-danger"><?= implode('<br>', $errors) ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="id" value="<?= $edit_data['id'] ?? '' ?>">
        <label>Academic Year:</label>
        <input type="text" name="year" value="<?= $edit_data['year'] ?? '' ?>" required>
        <label>Closure Date:</label>
        <input type="date" name="submission_date" value="<?= $edit_data['submission_date'] ?? '' ?>" required>
        <label>Submission Date:</label>
        <input type="date" name="closure_date" value="<?= $edit_data['final_closure_date'] ?? '' ?>" required>
        <button type="submit" class="btn btn-primary"><?= $edit_data ? 'Update' : 'Add' ?></button>
    </form>

    <table>
        <thead>
        <tr><th>ID</th><th>Year</th><th>Closure Date</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($closures as $row): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['year']) ?></td>
                <td><?= $row['final_closure_date'] ?></td>
                <td>
                    <a href="?edit=<?= $row['id'] ?>" class="btn btn-warning">Edit</a>
                    <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this academic year?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($closures)): ?>
            <tr><td colspan="4">No academic years found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a class="btn" href="?page=<?= $page - 1 ?>">« Prev</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a class="btn <?= ($i === $page) ? 'btn-primary' : '' ?>" href="?page=<?= $i ?>"><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a class="btn" href="?page=<?= $page + 1 ?>">Next »</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>
