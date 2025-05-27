<?php
session_start();
if (isset($_SESSION['success_message'])) {
    echo "<div class='alert alert-success'>" . $_SESSION['success_message'] . "</div>";
    unset($_SESSION['success_message']);
}
require '../includes/db.php';
require_once '../includes/db.php';

$db = new Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token.";
        echo $error;
        exit();
    }

    $contributionId = $_POST['contribution_id'] ?? null;
    $title = trim($_POST['title'] ?? '');
    $abstract = trim($_POST['abstract'] ?? '');
   // $academicYear = trim($_POST['academic_year'] ?? '');

    if (!$contributionId || !$title) {
        $error = "All required fields must be filled.";
        echo $error;
        var_dump($_POST); // Debug: check submitted fields
        exit();
    }


    $conn->beginTransaction(); // <-- Start transaction here

    try {

        $updateStmt = $conn->prepare("
            UPDATE contributions
            SET title = ?, abstract = ?, last_updated = NOW()  
            WHERE id = ?
        ");
        $updateStmt->execute([$title, $abstract, $contributionId]);

        echo "Updated rows: " . $updateStmt->rowCount() . "<br>";

        if (!empty($_POST['delete_materials'])) {
            $deleteStmt = $conn->prepare("DELETE FROM contribution_materials WHERE material_id = ?");
            foreach ($_POST['delete_materials'] as $materialId) {
                $deleteStmt->execute([$materialId]);
                echo "Deleted material ID: $materialId<br>";
            }
        }

        // Upload Word file
        if (!empty($_FILES['word_file']['name'])) {
            echo "Word file received: " . $_FILES['word_file']['name'] . "<br>";
            $wordFileName = basename($_FILES['word_file']['name']);
            $targetDir = '../uploads/';
            $targetFile = $targetDir . uniqid() . "_" . $wordFileName;
            $fileType = pathinfo($targetFile, PATHINFO_EXTENSION);

            if (in_array($fileType, ['doc', 'docx'])) {
                if (move_uploaded_file($_FILES['word_file']['tmp_name'], $targetFile)) {
                    echo "Word file uploaded to: $targetFile<br>";
                    $insertWord = $conn->prepare("
                        INSERT INTO word_documents (contribution_id, file_name, file_path)
                        VALUES (?, ?, ?)
                    ");
                    $insertWord->execute([$contributionId, $targetFile, 'word']);
                } else {
                    throw new Exception("Failed to move uploaded Word file.");
                }
            } else {
                throw new Exception("Invalid Word file type: $fileType");
            }
        }

        // Upload images
        if (!empty($_FILES['images']['name'][0])) {
            foreach ($_FILES['images']['name'] as $index => $imageName) {
                $tmpName = $_FILES['images']['tmp_name'][$index];
                $targetImage = $targetDir . uniqid() . "_" . basename($imageName);
                $imageType = pathinfo($targetImage, PATHINFO_EXTENSION);

                if (in_array(strtolower($imageType), ['jpg', 'jpeg', 'png'])) {
                    if (move_uploaded_file($tmpName, $targetImage)) {
                        echo "Image uploaded: $targetImage<br>";
                        $insertImage = $conn->prepare("
                            INSERT INTO images (contribution_id, file_name, file_path)
                            VALUES (?, ?, ?)
                        ");
                        $insertImage->execute([$contributionId, $targetImage, 'image']);
                    } else {
                        echo "Failed to upload image: $imageName<br>";
                    }
                } else {
                    echo "Skipped unsupported image file type: $imageName<br>";
                }
            }
        }

        $conn->commit();
        $_SESSION['success_message'] = "Contribution successfully updated.";
        header("Location: view_contribution.php");
        exit();
        
        
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Error updating contribution: " . $e->getMessage();
        echo $error;
        exit();
    }
}


?>
