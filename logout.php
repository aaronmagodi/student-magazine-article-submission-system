<?php
// logout.php
session_start();

// Destroy the session
session_unset();
session_destroy();

// Redirect to the login page in the root directory
header('Location:login.php');  // Navigate up one directory to the root
exit;
?>
