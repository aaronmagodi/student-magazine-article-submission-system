<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->redirectIfNotAuthorized(['admin']);

$db = new Database();
$conn = $db->getConnection();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .pagination a {
            margin: 0 5px;
            padding: 5px 10px;
            background: #eee;
            text-decoration: none;
            border-radius: 3px;
        }
        .pagination span {
            margin: 0 5px;
        }
        .table th, .table td {
            padding: 10px;
        }
    </style>
</head>
<body>

<?php include '../includes/header.php'; ?>

< class="dashboard-container">
    <h2>Manage Users</h2>

    <form id="searchForm">
        <input type="text" name="search" id="search" placeholder="Search by name or email">
        <button type="submit" class="btn btn-primary">Search</button>
        <a href="#" id="exportBtn" class="btn btn-success">Export to CSV</a>
    </form>

    <br><a href="../admin-registatar.php" class="btn btn-primary"> Craete Admin Account</a></br>

    <div id="usersTableContainer">
        <!-- AJAX loaded user table -->
    </div>
</>

<?php include '../includes/footer.php'; ?>

<script>
function loadUsers(page = 1) {
    const search = $('#search').val();
    $.get('load_users_ajax.php', { page, search }, function (data) {
        $('#usersTableContainer').html(data);
    });
}

$('#searchForm').on('submit', function (e) {
    e.preventDefault();
    loadUsers(1);
});

$('#exportBtn').on('click', function (e) {
    e.preventDefault();
    const search = $('#search').val();
    window.location.href = `export_users.php?search=${encodeURIComponent(search)}`;
});

// Initial load
loadUsers();
</script>

</body>
</html>
