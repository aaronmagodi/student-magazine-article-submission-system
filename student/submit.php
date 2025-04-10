<?php
declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Initialize secure session
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/email.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = new Auth();

// Check authentication and role
if (!$auth->isLoggedIn() || $auth->getUserRole() !== 'student') {
    header('Location: ../login.php?redirect=student/submit.php');
    exit;
}

// Check if user has accepted terms
$db = (new Database())->getConnection();
$termsStmt = $db->prepare("SELECT 1 FROM user_terms WHERE user_id = ?");
$termsStmt->execute([$_SESSION['user_id']]);
if ($termsStmt->rowCount() === 0) {
    header('Location: ../terms.php?redirect=student/submit.php');
    exit;
}

// Check submission deadlines
$currentDate = new DateTime();
$submissionDeadline = new DateTime(SUBMISSION_DEADLINE);
$finalDeadline = new DateTime(FINAL_DEADLINE);

// Temporarily allow all submissions and updates for testing
$allowNewSubmissions = true;
$allowUpdates = true;

// Get student's existing contributions
$contributionsStmt = $db->prepare("
    SELECT c.contribution_id, c.title, c.status, c.submission_date 
    FROM contributions c
    WHERE c.student_id = ?
    ORDER BY c.submission_date DESC
");
$contributionsStmt->execute([$_SESSION['user_id']]);
$existingContributions = $contributionsStmt->fetchAll();

// Process form submission
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    }
    // Check submission period
    elseif (!$allowNewSubmissions && empty($_POST['contribution_id'])) {
        $error = 'The submission period for new entries has closed';
    }
    // Check final deadline
    elseif (!$allowUpdates) {
        $error = 'The final submission deadline has passed';
    } else {
        // Process form data
        $title = sanitizeInput($_POST['title'] ?? '');
        $abstract = sanitizeInput($_POST['abstract'] ?? '');
        $contribution_id = $_POST['contribution_id'] ?? null;
        $academic_year_id = $_POST['academic_year_id'] ?? null;  // Added academic_year_id

        // Validate input
        if (empty($title)) {
            $error = 'Title is required';
        } elseif (strlen($title) > 255) {
            $error = 'Title must be less than 255 characters';
        } elseif (empty($academic_year_id)) {
            // Temporarily bypass the academic year validation for testing
            $academic_year_id = 1; // Default academic year ID, can change as needed for testing
            // $error = 'Academic Year is required'; // Temporarily bypass this validation
        } elseif (empty($_FILES['word_file']['name']) && empty($contribution_id)) {
            $error = 'Word document is required for new submissions';
        } else {
            try {
                $db->beginTransaction();

                // Validate academic_year_id existence (skip for testing)
                if ($academic_year_id) {
                    $academicYearStmt = $db->prepare("SELECT 1 FROM academic_years WHERE id = ?");
                    $academicYearStmt->execute([$academic_year_id]);
                    if ($academicYearStmt->rowCount() === 0) {
                        throw new Exception("The provided academic year ID does not exist.");
                    }
                }

                // Handle file uploads
                $wordFilePath = null;
                $imagePaths = [];

                // Process Word document
                if (!empty($_FILES['word_file']['name'])) {
                    $wordFile = $_FILES['word_file'];
                    
                    if ($wordFile['error'] !== UPLOAD_ERR_OK) {
                        throw new Exception("Error uploading Word document: " . getUploadError($wordFile['error']));
                    }
                    
                    $fileExt = strtolower(pathinfo($wordFile['name'], PATHINFO_EXTENSION));
                    if (!in_array($fileExt, ['doc', 'docx'])) {
                        throw new Exception("Only Word documents (.doc, .docx) are allowed");
                    }
                    
                    $wordFileName = 'contribution_' . ($contribution_id ?? 'new') . '_' . time() . '.' . $fileExt;
                    $wordFilePath = '../uploads/documents/' . $wordFileName;
                    
                    if (!is_dir('../uploads/documents')) {
                        mkdir('../uploads/documents', 0755, true);
                    }
                    
                    if (!move_uploaded_file($wordFile['tmp_name'], $wordFilePath)) {
                        throw new Exception("Failed to save Word document");
                    }
                }

                // Process images
                if (!empty($_FILES['images']['name'][0])) {
                    if (!is_dir('../uploads/images')) {
                        mkdir('../uploads/images', 0755, true);
                    }
                    
                    foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
                        if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                            $imageFile = [
                                'name' => $_FILES['images']['name'][$key],
                                'tmp_name' => $tmpName,
                                'size' => $_FILES['images']['size'][$key]
                            ];
                            
                            $imageExt = strtolower(pathinfo($imageFile['name'], PATHINFO_EXTENSION));
                            if (!in_array($imageExt, ['jpg', 'jpeg', 'png', 'gif'])) {
                                continue; // Skip invalid image types
                            }
                            
                            $imageFileName = 'image_' . ($contribution_id ?? 'new') . '_' . time() . '_' . $key . '.' . $imageExt;
                            $imageFilePath = '../uploads/images/' . $imageFileName;
                            
                            if (move_uploaded_file($imageFile['tmp_name'], $imageFilePath)) {
                                $imagePaths[] = [
                                    'path' => $imageFilePath,
                                    'name' => $imageFile['name']
                                ];
                            }
                        }
                    }
                }

                // Insert or update contribution
                if ($contribution_id) {
                    // Update existing contribution
                    $stmt = $db->prepare("
                        UPDATE contributions 
                        SET title = ?, abstract = ?, academic_year_id = ?, updated_at = NOW()
                        WHERE contribution_id = ? AND student_id = ?
                    ");
                    $stmt->execute([
                        $title, 
                        $abstract, 
                        $academic_year_id, 
                        $contribution_id, 
                        $_SESSION['user_id'] // Using user_id as student_id
                    ]);
                } else {
                    // Create new contribution
                    $stmt = $db->prepare("
                        INSERT INTO contributions 
                        (student_id, faculty_id, academic_year_id, title, abstract, submission_date, status)
                        VALUES (?, ?, ?, ?, ?, NOW(), 'submitted')
                    ");
                    $stmt->execute([
                        $_SESSION['user_id'], // student_id
                        $_SESSION['faculty_id'],
                        $academic_year_id,  // Insert academic_year_id
                        $title,
                        $abstract
                    ]);
                    $contribution_id = $db->lastInsertId();
                }

                // Add Word document to materials
                if ($wordFilePath) {
                    $stmt = $db->prepare("
                        INSERT INTO contribution_materials
                        (contribution_id, file_path, file_name, file_type)
                        VALUES (?, ?, ?, 'word')
                    ");
                    $stmt->execute([
                        $contribution_id,
                        $wordFilePath,
                        $_FILES['word_file']['name']
                    ]);
                }

                // Add images to materials
                foreach ($imagePaths as $image) {
                    $stmt = $db->prepare("
                        INSERT INTO contribution_materials
                        (contribution_id, file_path, file_name, file_type)
                        VALUES (?, ?, ?, 'image')
                    ");
                    $stmt->execute([
                        $contribution_id,
                        $image['path'],
                        $image['name']
                    ]);
                }

                $db->commit();

                // Send notification to coordinator
                $coordinatorStmt = $db->prepare("
                    SELECT email FROM users 
                    WHERE faculty_id = ? AND role = 'marketing_coordinator'
                    LIMIT 1
                ");
                $coordinatorStmt->execute([$_SESSION['faculty_id']]);
                $coordinator = $coordinatorStmt->fetch();

                if ($coordinator) {
                    $email = new EmailNotifier();
                    $email->send(
                        $coordinator['email'],
                        'New Contribution Submission',
                        "A new contribution has been submitted:\n\n" . 
                        "Title: $title\n" . 
                        "Student: {$_SESSION['user_name']}\n" . 
                        "Faculty: {$_SESSION['faculty_name']}\n\n" . 
                        "Please review within 14 days."
                    );
                }

                $success = $contribution_id ? 'Contribution updated successfully!' : 'Contribution submitted successfully!';
                header("Location: submit.php?success=" . urlencode($success));
                exit;

            } catch (Exception $e) {
                $db->rollBack();

                // Clean up uploaded files if transaction failed
                if ($wordFilePath && file_exists($wordFilePath)) {
                    unlink($wordFilePath);
                }
                foreach ($imagePaths as $image) {
                    if (file_exists($image['path'])) {
                        unlink($image['path']);
                    }
                }

                $error = 'Submission failed: ' . $e->getMessage();
                error_log("Contribution submission error: " . $e->getMessage());
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $allowNewSubmissions ? 'Submit Contribution' : 'Update Contribution' ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 6px;
        }
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #ef9a9a;
        }
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #a5d6a7;
        }
        .alert-warning {
            background: #fff8e1;
            color: #e65100;
            border-left: 4px solid #ffcc80;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        input[type="text"], textarea, select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        textarea {
            min-height: 150px;
        }
        .file-upload {
            border: 2px dashed #ddd;
            padding: 2rem;
            text-align: center;
            margin: 1rem 0;
            border-radius: 6px;
        }
        .file-upload.drag-over {
            border-color: #3498db;
            background: #f8fafc;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #f1f1f1;
            color: #333;
        }
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        .file-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1rem;
        }
        .file-item {
            width: 120px;
            text-align: center;
        }
        .file-item img {
            max-width: 100%;
            max-height: 80px;
        }
        .file-name {
            font-size: 0.8rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .existing-files {
            margin: 1rem 0;
        }
        .file-row {
            display: flex;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        .file-row:last-child {
            border-bottom: none;
        }
        .terms-check {
            margin: 1.5rem 0;
        }
        @media (max-width: 768px) {
            .container {
                padding: 0;
            }
            .card {
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="card">
            <h1><?= $allowNewSubmissions ? 'Submit New Contribution' : 'Update Contribution' ?></h1>
            
            <?php if (!$allowUpdates): ?>
                <div class="alert alert-error">
                    The final submission deadline has passed. No further updates are allowed.
                </div>
            <?php elseif (!$allowNewSubmissions): ?>
                <div class="alert alert-warning">
                    The submission period for new entries has closed. You can only update existing contributions.
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
                <p>Your faculty coordinator will review your submission within 14 days.</p>
            <?php endif; ?>
            
            <?php if ($allowUpdates): ?>
                <form method="post" enctype="multipart/form-data" id="submission-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    
                    <?php if (!empty($existingContributions) && !$allowNewSubmissions): ?>
                        <div class="form-group">
                            <label for="contribution_id">Select Contribution to Update</label>
                            <select id="contribution_id" name="contribution_id" class="form-control" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($existingContributions as $contribution): ?>
                                    <option value="<?= $contribution['contribution_id'] ?>">
                                        <?= htmlspecialchars($contribution['title']) ?> 
                                        (<?= ucfirst($contribution['status']) ?> - <?= date('M j, Y', strtotime($contribution['submission_date'])) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <?php 
                        // Load materials for selected contribution if editing
                        $existingMaterials = [];
                        if (!empty($_POST['contribution_id'])) {
                            $materialsStmt = $db->prepare("
                                SELECT material_id, file_name, file_type 
                                FROM contribution_materials 
                                WHERE contribution_id = ?
                            ");
                            $materialsStmt->execute([$_POST['contribution_id']]);
                            $existingMaterials = $materialsStmt->fetchAll();
                        }
                        ?>
                        
                        <?php if (!empty($existingMaterials)): ?>
                            <div class="form-group existing-files">
                                <label>Current Files</label>
                                <small>Check files you want to remove</small>
                                <?php foreach ($existingMaterials as $material): ?>
                                    <div class="file-row">
                                        <input type="checkbox" name="delete_materials[]" value="<?= $material['material_id'] ?>" id="material_<?= $material['material_id'] ?>">
                                        <label for="material_<?= $material['material_id'] ?>" style="margin: 0 0 0 0.5rem;">
                                            <?= htmlspecialchars($material['file_name']) ?> 
                                            <small>(<?= $material['file_type'] ?>)</small>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="title">Title*</label>
                        <input type="text" id="title" name="title" required 
                               value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>"
                               maxlength="255" placeholder="Enter your contribution title">
                    </div>
                    
                    <div class="form-group">
                        <label for="abstract">Abstract</label>
                        <textarea id="abstract" name="abstract" placeholder="Enter a brief description of your contribution"><?= isset($_POST['abstract']) ? htmlspecialchars($_POST['abstract']) : '' ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Word Document*</label>
                        <div class="file-upload" id="word-upload">
                            <input type="file" name="word_file" id="word_file" accept=".doc,.docx" style="display: none;">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('word_file').click()">
                                <i class="fas fa-file-word"></i> Select Word File
                            </button>
                            <div id="word-file-name" style="margin-top: 0.5rem;"></div>
                        </div>
                        <small>Only .doc or .docx files are accepted</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Images (Optional)</label>
                        <div class="file-upload" id="image-upload">
                            <input type="file" name="images[]" id="images" accept="image/*" multiple style="display: none;">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('images').click()">
                                <i class="fas fa-images"></i> Select Images
                            </button>
                            <div id="image-preview" class="file-preview"></div>
                        </div>
                        <small>Upload high-quality images (JPEG, PNG)</small>
                    </div>
                    
                    <div class="terms-check">
                        <input type="checkbox" id="terms_agreed" name="terms_agreed" required checked>
                        <label for="terms_agreed">I agree to the <a href="../terms.php" target="_blank">Terms and Conditions</a></label>
                    </div>
                    
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> <?= $allowNewSubmissions ? 'Submit Contribution' : 'Update Contribution' ?>
                        </button>
                        <button type="reset" class="btn btn-secondary">Reset</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($existingContributions)): ?>
            <div class="card">
                <h2>Your Existing Contributions</h2>
                <ul style="list-style: none; padding: 0;">
                    <?php foreach ($existingContributions as $contribution): ?>
                        <li style="padding: 0.5rem 0; border-bottom: 1px solid #eee;">
                            <strong><?= htmlspecialchars($contribution['title']) ?></strong>
                            <div>
                                <span class="status" style="background: #e3f2fd; padding: 0.2rem 0.5rem; border-radius: 20px; font-size: 0.8rem;">
                                    <?= ucfirst($contribution['status']) ?>
                                </span>
                                <small style="color: #777;">Submitted on <?= date('M j, Y', strtotime($contribution['submission_date'])) ?></small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Word file upload
        const wordFileInput = document.getElementById('word_file');
        const wordFileName = document.getElementById('word-file-name');
        
        wordFileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                wordFileName.textContent = this.files[0].name;
            } else {
                wordFileName.textContent = '';
            }
        });
        
        // Image upload and preview
        const imageInput = document.getElementById('images');
        const imagePreview = document.getElementById('image-preview');
        
        imageInput.addEventListener('change', function() {
            imagePreview.innerHTML = '';
            
            if (this.files.length > 0) {
                Array.from(this.files).forEach(file => {
                    if (!file.type.startsWith('image/')) return;
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const previewItem = document.createElement('div');
                        previewItem.className = 'file-item';
                        
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.style.maxHeight = '80px';
                        
                        const fileName = document.createElement('div');
                        fileName.className = 'file-name';
                        fileName.textContent = file.name;
                        
                        previewItem.appendChild(img);
                        previewItem.appendChild(fileName);
                        imagePreview.appendChild(previewItem);
                    };
                    reader.readAsDataURL(file);
                });
            }
        });
        
        // Drag and drop for word file
        const wordUpload = document.getElementById('word-upload');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            wordUpload.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            wordUpload.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            wordUpload.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            wordUpload.classList.add('drag-over');
        }
        
        function unhighlight() {
            wordUpload.classList.remove('drag-over');
        }
        
        wordUpload.addEventListener('drop', function(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                // Only accept Word files
                const fileExt = files[0].name.split('.').pop().toLowerCase();
                if (['doc', 'docx'].includes(fileExt)) {
                    wordFileInput.files = files;
                    wordFileName.textContent = files[0].name;
                }
            }
        });
        
        // Form validation
        const form = document.getElementById('submission-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const title = document.getElementById('title').value.trim();
                const contributionId = document.getElementById('contribution_id')?.value;
                const wordFile = wordFileInput.files.length;
                
                if (!title) {
                    e.preventDefault();
                    alert('Title is required');
                    return;
                }
                
                if (!contributionId && !wordFile) {
                    e.preventDefault();
                    alert('Word document is required for new submissions');
                    return;
                }
                
                if (!document.getElementById('terms_agreed').checked) {
                    e.preventDefault();
                    alert('You must agree to the Terms and Conditions');
                    return;
                }
            });
        }
        
        // Load contribution details when selected
        const contributionSelect = document.getElementById('contribution_id');
        if (contributionSelect) {
            contributionSelect.addEventListener('change', async function() {
                if (this.value) {
                    try {
                        const response = await fetch(`../api/contribution.php?id=${this.value}`);
                        if (response.ok) {
                            const data = await response.json();
                            document.getElementById('title').value = data.title || '';
                            document.getElementById('abstract').value = data.abstract || '';
                            
                            // You could also load existing files here if needed
                        }
                    } catch (error) {
                        console.error('Error loading contribution:', error);
                    }
                } else {
                    document.getElementById('title').value = '';
                    document.getElementById('abstract').value = '';
                }
            });
        }
    });
    </script>
<!-- Buttons for Logout and Back to Dashboard -->
<div class="buttons">
    <!-- Back to Dashboard Button -->
    <a href="dashboard.php">
        <button type="button">Back to Dashboard</button>
    </a>

    <!-- Logout Button -->
    <form action="logout.php" method="POST">
        <button type="submit">Logout</button>
    </form>
</div>


</body>
</html>