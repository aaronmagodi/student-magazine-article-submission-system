<?php
require_once '../includes/db.php';
$db = new Database();
$conn = $db->getConnection();

$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Count total matching users
$countStmt = $conn->prepare("
    SELECT COUNT(*) FROM users 
    WHERE first_name LIKE :search1 
       OR last_name LIKE :search2 
       OR email LIKE :search3
");
$countStmt->execute([
    ':search1' => "%$search%",
    ':search2' => "%$search%",
    ':search3' => "%$search%",
]);
$totalUsers = $countStmt->fetchColumn();
$totalPages = ceil($totalUsers / $perPage);


// Fetch paginated users
$likeSearch = "%$search%";
$stmt = $conn->prepare("
    SELECT * FROM users 
    WHERE first_name LIKE ? 
       OR last_name LIKE ? 
       OR email LIKE ? 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $likeSearch, PDO::PARAM_STR);
$stmt->bindValue(2, $likeSearch, PDO::PARAM_STR);
$stmt->bindValue(3, $likeSearch, PDO::PARAM_STR);
$stmt->bindValue(4, $perPage, PDO::PARAM_INT);
$stmt->bindValue(5, $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<table class="table table-striped">
    <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Locked</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if (count($users) > 0): ?>
        <?php foreach ($users as $user): ?>
        <tr>
            <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
            <td><?= htmlspecialchars($user['email']) ?></td>
            <td><?= htmlspecialchars($user['role']) ?></td>
            <td><?= $user['is_active'] ? 'Active' : 'Suspended' ?></td>
            <td><?= $user['is_locked'] ? 'Yes' : 'No' ?></td>
            <td>
            <a href="/dashboard/SchoolProjectMS/admin/user_actions.php?action=edit&id=<?= $user['id']; ?>" class="btn btn-primary">Edit</a>

<?php if ($user['is_active']): ?>
    <a href="/dashboard/SchoolProjectMS/admin/user_actions.php?action=suspend&id=<?= $user['id']; ?>" class="btn btn-danger">Suspend</a>
<?php else: ?>
    <a href="/dashboard/SchoolProjectMS/admin/user_actions.php?action=activate&id=<?= $user['id']; ?>" class="btn btn-success">Activate</a>
<?php endif; ?>

<?php if ($user['is_locked']): ?>
    <a href="/dashboard/SchoolProjectMS/admin/user_actions.php?action=unlock&id=<?= $user['id']; ?>" class="btn btn-warning">Unlock</a>
<?php endif; ?>

            </td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="6" class="text-center">No users found.</td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>

<div class="pagination mt-3">
    <?php if ($page > 1): ?>
        <button class="btn btn-secondary btn-sm" onclick="loadUsers(<?= $page - 1 ?>)">« Prev</button>
    <?php endif; ?>
    <span class="mx-2">Page <?= $page ?> of <?= $totalPages ?></span>
    <?php if ($page < $totalPages): ?>
        <button class="btn btn-secondary btn-sm" onclick="loadUsers(<?= $page + 1 ?>)">Next »</button>
    <?php endif; ?>
</div>
