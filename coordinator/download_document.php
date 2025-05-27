<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

$auth = new Auth();
$auth->redirectIfNotAuthorized(['marketing_coordinator']);

if (!isset($_GET['doc_id'])) {
    die('No document ID specified.');
}

$docId = (int) $_GET['doc_id'];

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT * FROM word_documents WHERE id = :id");
$stmt->bindParam(':id', $docId, PDO::PARAM_INT);
$stmt->execute();
$document = $stmt->fetch(PDO::FETCH_ASSOC);

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'download';

if ($mode === 'view') {
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: inline; filename="' . basename($filepath) . '"');
} else {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
}


if (!$document) {
    die('Document not found.');
}

$filepath = '../uploads/word_documents/' . $document['filename']; // Adjust path if needed

if (!file_exists($filepath)) {
    die('File does not exist.');
}

// Tell browser to open in tab
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document'); // MIME type for .docx
header('Content-Disposition: inline; filename="' . basename($filepath) . '"');
header('Content-Length: ' . filesize($filepath));

// Clean (just in case)
ob_clean();
flush();

// Output file
readfile($filepath);
exit;
?>
