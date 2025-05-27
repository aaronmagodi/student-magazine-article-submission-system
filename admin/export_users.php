<?php
require_once '../includes/db.php';
$db = new Database();
$conn = $db->getConnection();

$search = $_GET['search'] ?? '';

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="users_export.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['First Name', 'Last Name', 'Email', 'Role', 'Status', 'Locked', 'Created At']);

$stmt = $conn->prepare("SELECT * FROM users WHERE first_name LIKE :s1 OR last_name LIKE :s2 OR email LIKE :s3 ORDER BY created_at DESC");
$stmt->execute([
    ':s1' => "%$search%",
    ':s2' => "%$search%",
    ':s3' => "%$search%"
]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['first_name'],
        $row['last_name'],
        $row['email'],
        $row['role'],
        $row['is_active'] ? 'Active' : 'Suspended',
        $row['is_locked'] ? 'Yes' : 'No',
        $row['created_at']
    ]);
}

fclose($output);
exit;
?>
