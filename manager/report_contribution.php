<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

$auth = new Auth();
$auth->redirectIfNotAuthorized(['marketing_manager']);

$db = new Database();
$conn = $db->getConnection();

// === STATISTICS REPORT === //
$statisticsStmt = $conn->query("
    SELECT 
        f.name AS faculty_name,
        YEAR(c.submission_date) AS academic_year,
        COUNT(c.id) AS total_contributions,
        COUNT(DISTINCT c.student_id) AS total_contributors
    FROM contributions c
    JOIN faculties f ON c.faculty_id = f.id
    GROUP BY f.name, academic_year
    ORDER BY academic_year DESC, f.name
");
$statistics = $statisticsStmt->fetchAll(PDO::FETCH_ASSOC);

// === PERCENTAGE CONTRIBUTION === //
$percentagesStmt = $conn->query("
    SELECT 
        f.name AS faculty_name,
        YEAR(c.submission_date) AS academic_year,
        COUNT(c.id) AS faculty_contributions,
        (SELECT COUNT(*) FROM contributions WHERE YEAR(submission_date) = YEAR(c.submission_date)) AS total_contributions
    FROM contributions c
    JOIN faculties f ON c.faculty_id = f.id
    GROUP BY f.name, academic_year
");
$percentages = $percentagesStmt->fetchAll(PDO::FETCH_ASSOC);

// === EXCEPTION: No Comment at All === //
$noCommentStmt = $conn->query("
    SELECT c.*, f.name as faculty_name, u.username as student_name
    FROM contributions c
    JOIN faculties f ON c.faculty_id = f.id
    JOIN users u ON c.student_id = u.id
    LEFT JOIN comments com ON com.contribution_id = c.id
    WHERE com.id IS NULL
    ORDER BY c.submission_date DESC
");
$noComment = $noCommentStmt->fetchAll(PDO::FETCH_ASSOC);

// === EXCEPTION: No Comment After 14 Days === //
$noLateCommentStmt = $conn->query("
    SELECT c.*, f.name as faculty_name, u.username as student_name
    FROM contributions c
    JOIN faculties f ON c.faculty_id = f.id
    JOIN users u ON c.student_id = u.id
    LEFT JOIN comments com ON com.contribution_id = c.id
    WHERE com.id IS NULL
      AND DATEDIFF(NOW(), c.submission_date) > 14
    ORDER BY c.submission_date DESC
");
$noLateComment = $noLateCommentStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports - University Magazine</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        body {
            padding: 30px;
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f9;
            color: #333;
        }

        h1 {
            margin-bottom: 30px;
            color: #2c3e50;
            text-align: center;
            font-size: 2em;
        }

        h2 {
            background-color: #3498db;
            color: white;
            padding: 10px;
            border-radius: 4px;
            margin-top: 40px;
        }

        .section {
            background: white;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            padding: 10px 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #f8f9fa;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background-color: #fbfbfb;
        }

        .highlight {
            background-color: #fff3cd !important;
        }

        @media screen and (max-width: 768px) {
            table, thead, tbody, th, td, tr {
                display: block;
            }

            th {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }

            td {
                border: none;
                border-bottom: 1px solid #eee;
                position: relative;
                padding-left: 50%;
            }

            td:before {
                position: absolute;
                left: 10px;
                top: 10px;
                white-space: nowrap;
                font-weight: bold;
            }
        }
    </style>
</head>
<body>

<h1>📊 Reports Dashboard</h1>

<div class="section">
    <h2>📌 Contribution Statistics by Faculty and Academic Year</h2>
    <table>
        <thead>
            <tr>
                <th>Faculty</th>
                <th>Academic Year</th>
                <th>Contributions</th>
                <th>Contributors</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($statistics as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['faculty_name']) ?></td>
                    <td><?= htmlspecialchars($row['academic_year']) ?></td>
                    <td><?= $row['total_contributions'] ?></td>
                    <td><?= $row['total_contributors'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="section">
    <h2>📈 Percentage of Contributions by Faculty</h2>
    <table>
        <thead>
            <tr>
                <th>Faculty</th>
                <th>Academic Year</th>
                <th>Faculty Contributions</th>
                <th>Total Contributions</th>
                <th>Percentage</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($percentages as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['faculty_name']) ?></td>
                    <td><?= htmlspecialchars($row['academic_year']) ?></td>
                    <td><?= $row['faculty_contributions'] ?></td>
                    <td><?= $row['total_contributions'] ?></td>
                    <td>
                        <?= $row['total_contributions'] > 0 
                            ? round(($row['faculty_contributions'] / $row['total_contributions']) * 100, 2) . '%' 
                            : '0%' ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="section">
    <h2>⚠️ Exception Report: Contributions with No Comment</h2>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Student</th>
                <th>Faculty</th>
                <th>Submission Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($noComment as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['title']) ?></td>
                    <td><?= htmlspecialchars($row['student_name']) ?></td>
                    <td><?= htmlspecialchars($row['faculty_name']) ?></td>
                    <td><?= date('F j, Y', strtotime($row['submission_date'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="section">
    <h2>⏳ Exception Report: No Comment After 14 Days</h2>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Student</th>
                <th>Faculty</th>
                <th>Submission Date</th>
                <th>Days Since Submission</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($noLateComment as $row): ?>
                <tr class="highlight">
                    <td><?= htmlspecialchars($row['title']) ?></td>
                    <td><?= htmlspecialchars($row['student_name']) ?></td>
                    <td><?= htmlspecialchars($row['faculty_name']) ?></td>
                    <td><?= date('F j, Y', strtotime($row['submission_date'])) ?></td>
                    <td><?= (new DateTime())->diff(new DateTime($row['submission_date']))->days ?> days</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>
