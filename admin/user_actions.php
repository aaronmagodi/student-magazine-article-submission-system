<?php
require_once '../includes/db.php';
$db = new Database();
$conn = $db->getConnection();

$action = $_GET['action'] ?? null;
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$action || !$id) {
    die("Invalid request.");
}

switch ($action) {
    case 'edit':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $first_name = $_POST['first_name'] ?? '';
            $last_name = $_POST['last_name'] ?? '';
            $email = $_POST['email'] ?? '';
            $role = $_POST['role'] ?? '';

            $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, role = ? WHERE id = ?");
            $stmt->execute([$first_name, $last_name, $email, $role, $id]);

            header("Location: manage_users.php?message=User updated successfully");
            exit;
        } else {
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                die("User not found.");
            }

            ?>
            <!DOCTYPE html>
            <html>
            <head>
                <title>Edit User</title>
                <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
                <style>
                    body {
                        font-family: 'Inter', sans-serif;
                        background-color: #f4f6f9;
                        margin: 0;
                        padding: 0;
                    }

                    .container {
                        max-width: 500px;
                        background: #fff;
                        padding: 30px;
                        margin: 60px auto;
                        border-radius: 8px;
                        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    }

                    h2 {
                        margin-bottom: 20px;
                        color: #333;
                        text-align: center;
                    }

                    label {
                        display: block;
                        margin-bottom: 5px;
                        color: #555;
                        font-weight: 600;
                    }

                    input[type="text"],
                    input[type="email"] {
                        width: 100%;
                        padding: 10px;
                        margin-bottom: 15px;
                        border: 1px solid #ccc;
                        border-radius: 4px;
                        font-size: 14px;
                    }

                    button {
                        background-color: #3498db;
                        color: white;
                        padding: 10px 20px;
                        font-size: 16px;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                        width: 100%;
                    }

                    button:hover {
                        background-color: #2980b9;
                    }

                    .back-link {
                        display: block;
                        margin-top: 15px;
                        text-align: center;
                        font-size: 14px;
                        color: #666;
                        text-decoration: none;
                    }

                    .back-link:hover {
                        text-decoration: underline;
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <h2>Edit User</h2>
                    <form method="POST" action="user_actions.php?action=edit&id=<?= $user['id'] ?>">
                        <label>First Name:</label>
                        <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>

                        <label>Last Name:</label>
                        <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>

                        <label>Email:</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

                        <label>Role:</label>
                        <input type="text" name="role" value="<?= htmlspecialchars($user['role']) ?>" required>

                        <button type="submit">Update User</button>
                    </form>

                    <a class="back-link" href="manage_users.php">← Back to user management</a>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
        break;

    case 'suspend':
        $stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: manage_users.php?message=User suspended");
        break;

    case 'activate':
        $stmt = $conn->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: manage_users.php?message=User activated");
        break;

    case 'unlock':
        $stmt = $conn->prepare("UPDATE users SET is_locked = 0 WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: manage_users.php?message=User unlocked");
        break;

    default:
        die("Unknown action: $action");
}
?>
