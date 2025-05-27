<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

$auth = new Auth();
$auth->redirectIfNotAuthorized(['marketing_manager']);

$db = new Database();
$conn = $db->getConnection();

if (!isset($_GET['faculty_id'])) {
    die("Faculty ID is required.");
}

$facultyId = intval($_GET['faculty_id']);

// Fetch files for the given faculty
$query = "
    SELECT c.word_file_path
    FROM selected_contributions sc
    JOIN contributions c ON sc.contribution_id = c.id
    JOIN users u ON c.user_id = u.id
    WHERE u.faculty_id = :faculty_id
";

$stmt = $conn->prepare($query);
$stmt->execute([':faculty_id' => $facultyId]);
$files = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (!$files) {
    die("No files found for this faculty.");
}

$zip = new ZipArchive();
$zipName = 'faculty_contributions_' . $facultyId . '_' . date('Ymd_His') . '.zip';
$zipPath = sys_get_temp_dir() . '/' . $zipName;

if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
    die("Cannot create ZIP file.");
}

foreach ($files as $filePath) {
    if (!empty($filePath) && file_exists($filePath)) {
        $zip->addFile($filePath, basename($filePath));
    }
}

$zip->close();

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . basename($zipName) . '"');
header('Content-Length: ' . filesize($zipPath));
readfile($zipPath);
unlink($zipPath);
exit;
