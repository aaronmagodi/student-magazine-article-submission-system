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

// Handle Add or Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    if (empty($name)) {
        $errors[] = 'Faculty name is required.';
    } else {
        if (!empty($_POST['id'])) {
            // Update
            $stmt = $conn->prepare("UPDATE faculties SET name = ? WHERE id = ?");
            $stmt->execute([$name, $_POST['id']]);
            $_SESSION['success'] = 'Faculty updated successfully.';
        } else {
            // Add
            $stmt = $conn->prepare("INSERT INTO faculties (name) VALUES (?)");
            $stmt->execute([$name]);
            $_SESSION['success'] = 'Faculty added successfully.';
        }
        header('Location: manage_faculties.php');
        exit;
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM faculties WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $_SESSION['success'] = 'Faculty deleted successfully.';
    header('Location: manage_faculties.php');
    exit;
}

// Handle Edit
$edit_data = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM faculties WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch all
$faculties = $conn->query("SELECT * FROM faculties ORDER BY name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Faculties</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .container { max-width: 900px; margin: auto; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: center; }
        .form-group { margin-bottom: 15px; }
        .form-control { padding: 8px; width: 100%; }
        .btn { padding: 8px 12px; border: none; background: #3498db; color: white; cursor: pointer; }
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
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <h1>Manage Faculties</h1>
        <a href="manage_closure_dates.php" class="btn btn-primary">Manage Academic Closures</a>

        <?php if (!empty($success)): ?>
            <div class="alert-success"><?= $success ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="alert-danger"><?= implode('<br>', $errors) ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="id" value="<?= $edit_data['id'] ?? '' ?>">
            <label>Faculty Name:</label>
            <input type="text" name="name" class="form-control" value="<?= $edit_data['name'] ?? '' ?>" required>
            <button type="submit" class="btn btn-primary"><?= $edit_data ? 'Update' : 'Add' ?></button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Faculty Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($faculties as $f): ?>
                    <tr>
                        <td><?= $f['id'] ?></td>
                        <td><?= htmlspecialchars($f['name']) ?></td>
                        <td>
                            <a href="?edit=<?= $f['id'] ?>" class="btn btn-warning">Edit</a>
                            <a href="?delete=<?= $f['id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this faculty?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($faculties)): ?>
                    <tr><td colspan="3">No faculties found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
