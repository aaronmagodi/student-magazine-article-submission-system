<?php
require '../includes/config.php';
require '../includes/auth.php';
//require '../includes/admin_auth.php';

$db = (new Database())->getConnection();

// Get academic years
$years = $db->query("SELECT DISTINCT year FROM academic_years ORDER BY year DESC")->fetchAll(PDO::FETCH_COLUMN);

// Set default year
$current_year = $_GET['year'] ?? $db->query("SELECT year FROM academic_years ORDER BY year DESC LIMIT 1")->fetchColumn();
// Generate reports
$facultyStmt = $db->prepare("
    SELECT f.name AS faculty, COUNT(c.id) AS count
    FROM contributions c
    JOIN faculties f ON c.faculty_id = f.id
    JOIN academic_years ay ON c.academic_year_id = ay.id
    WHERE ay.year = :year
    GROUP BY f.name
    ORDER BY count DESC
");
$facultyStmt->execute([':year' => $current_year]);
$facultyData = $facultyStmt->fetchAll(PDO::FETCH_ASSOC);

$contributorStmt = $db->prepare("
    SELECT f.name AS faculty, COUNT(DISTINCT c.student_id) AS count
    FROM contributions c
    JOIN faculties f ON c.faculty_id = f.id
    JOIN academic_years ay ON c.academic_year_id = ay.id
    WHERE ay.year = :year
    GROUP BY f.name
    ORDER BY count DESC
");
$contributorStmt->execute([':year' => $current_year]);
$contributorData = $contributorStmt->fetchAll(PDO::FETCH_ASSOC);

$overdueStmt = $db->prepare("
    SELECT c.title, u.username AS student, c.submission_date
    FROM contributions c
    JOIN users u ON c.student_id = u.id
    JOIN academic_years ay ON c.academic_year_id = ay.id
    LEFT JOIN comments com ON com.contribution_id = c.id
    WHERE ay.year = :year
      AND c.status = 'submitted'
      AND c.submission_date < DATE_SUB(NOW(), INTERVAL 14 DAY)
      AND (com.comment IS NULL OR com.comment = '')
");

$overdueStmt->execute([':year' => $current_year]);
$overdueSubmissions = $overdueStmt->fetchAll(PDO::FETCH_ASSOC);

// Export to CSV
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="report_' . $current_year . '.csv"');
    $output = fopen('php://output', 'w');

    if ($_GET['export'] === 'faculty') {
        fputcsv($output, ['Faculty', 'Submissions']);
        foreach ($facultyData as $row) {
            fputcsv($output, $row);
        }
    } elseif ($_GET['export'] === 'contributors') {
        fputcsv($output, ['Faculty', 'Unique Contributors']);
        foreach ($contributorData as $row) {
            fputcsv($output, $row);
        }
    }

    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reports</title>
    <link href="../assets/css/admin.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>Reporting System</h1>
        
        <form method="get" class="year-selector">
        <label>Academic Year:</label>
        <select name="year" class="select-tab">
            <?php foreach ($years as $year): ?>
                <option value="<?= htmlspecialchars($year) ?>" <?= $year == $current_year ? 'selected' : '' ?>>
                    <?= htmlspecialchars($year) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn export">Generate Reports</button>
    </form>
        
    <div class="report-section">
        <h2>Faculty Contributions (<?= htmlspecialchars($current_year) ?>)</h2>

        <div class="row">
            <div class="chart-container">
                <canvas id="facultyChart"></canvas>
            </div>

                <table>
                    <tr>
                        <th>Faculty</th>
                        <th>Submissions</th>
                        <th>Percentage</th>
                    </tr>
                    <?php
                    $totalSubmissions = array_sum(array_column($facultyData, 'count'));
                    foreach ($facultyData as $row):
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($row['faculty']) ?></td>
                        <td><?= $row['count'] ?></td>
                        <td><?= $totalSubmissions ? round(($row['count'] / $totalSubmissions) * 100, 1) : 0 ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total">
                        <td>Total</td>
                        <td><?= $totalSubmissions ?></td>
                        <td>100%</td>
                    </tr>
                </table> 
           

                <a href="reports.php?year=<?= urlencode($current_year) ?>&export=faculty" class="btn export">Export to CSV</a>
           
        </div>
    </div>
        
    <div class="report-section">
        <h2>Overdue Comments (<?= htmlspecialchars($current_year) ?>)</h2>

        <?php if (count($overdueSubmissions) > 0): ?>
            <table>
                <tr>
                    <th>Submission Title</th>
                    <th>Student</th>
                    <th>Submitted On</th>
                    <th>Days Overdue</th>
                </tr>
                <?php foreach ($overdueSubmissions as $row):
                    $daysOverdue = floor((time() - strtotime($row['submission_date'])) / (60 * 60 * 24)) - 14;
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['title']) ?></td>
                    <td><?= htmlspecialchars($row['student']) ?></td>
                    <td><?= date('M j, Y', strtotime($row['submission_date'])) ?></td>
                    <td class="<?= $daysOverdue > 7 ? 'critical' : 'warning' ?>">
                        <?= $daysOverdue ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No submissions with overdue comments.</p>
        <?php endif; ?>
    </div>
</>
    
<script>
const facultyData = <?= json_encode($facultyData) ?>;

const ctx = document.getElementById('facultyChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: facultyData.map(item => item.faculty),
        datasets: [{
            label: 'Submissions',
            data: facultyData.map(item => item.count),
            backgroundColor: 'rgba(54, 162, 235, 0.7)'
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>
</body>
</html>