<?php
session_start();

// Include config and database connection
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agree'])) {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("INSERT INTO user_terms (user_id, agreed_at) VALUES (?, NOW())");
    $stmt->execute([$_SESSION['user_id']]);

    $redirect = $_GET['redirect'] ?? 'student/dashboard.php';
    header("Location: " . $redirect);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Terms and Conditions</title>
</head>
<body>
    <h2>Terms and Conditions</h2>
    <p>
        By submitting your article or image to the university magazine, you confirm that:
        <ul>
            <li>Your submission is your original work.</li>
            <li>You grant permission to publish the material in print and online.</li>
            <li>You understand submissions are final once the final deadline passes.</li>
        </ul>
    </p>
    <form method="POST">
        <label>
            <input type="checkbox" name="agree" required>
            I have read and agree to the Terms and Conditions
        </label><br><br>
        <button type="submit">Agree and Continue</button>
    </form>
</body>
</html>
