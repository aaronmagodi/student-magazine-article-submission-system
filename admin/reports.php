<?php
require '../includes/config.php';
require '../includes/auth.php';
require '../includes/admin_auth.php';

$db = (new Database())->getConnection();

// Get academic years
$years = $db->query("SELECT DISTINCT academic_year FROM submissions ORDER BY academic_year DESC")->fetchAll(PDO::FETCH_COLUMN);

// Set default year
$current_year = $_GET['year'] ?? $db->query("SELECT academic_year FROM settings LIMIT 1")->fetchColumn();

// Generate reports
$reports = [];

// Faculty Contributions
$reports['faculty_contributions'] = $db->prepare("
    SELECT faculty, COUNT(*) as count 
    FROM submissions 
    WHERE academic_year = :year
    GROUP BY faculty
    ORDER BY count DESC
");
$reports['faculty_contributions']->execute([':year' => $current_year]);

// Contributors per Faculty
$reports['contributors'] = $db->prepare("
    SELECT faculty, COUNT(DISTINCT student_id) as count 
    FROM submissions 
    WHERE academic_year = :year
    GROUP BY faculty
    ORDER BY count DESC
");
$reports['contributors']->execute([':year' => $current_year]);

// Overdue Comments
$reports['overdue_comments'] = $db->prepare("
    SELECT s.id, s.title, u.name as student, s.created_at
    FROM submissions s
    JOIN users u ON s.student_id = u.id
    WHERE s.academic_year = :year
    AND s.status = 'submitted'
    AND s.created_at < DATE_SUB(NOW(), INTERVAL 14 DAY)
    AND s.coordinator_comment IS NULL
");
$reports['overdue_comments']->execute([':year' => $current_year]);

// Export to CSV
if(isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="report_' . $current_year . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    switch($_GET['export']) {
        case 'faculty':
            fputcsv($output, ['Faculty', 'Submissions']);
            while($row = $reports['faculty_contributions']->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, $row);
            }
            break;
            
        case 'contributors':
            fputcsv($output, ['Faculty', 'Unique Contributors']);
            while($row = $reports['contributors']->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, $row);
            }
            break;
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="container">
        <h1>Reporting System</h1>
        
        <form method="get" class="year-selector">
            <label>Academic Year:</label>
            <select name="year">
                <?php foreach($years as $year): ?>
                <option value="<?= $year ?>" <?= $year == $current_year ? 'selected' : '' ?>>
                    <?= $year ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Generate Reports</button>
        </form>
        
        <div class="report-section">
            <h2>Faculty Contributions (<?= $current_year ?>)</h2>
            
            <div class="row">
                <div class="chart-container">
                    <canvas id="facultyChart"></canvas>
                </div>
                
                <div class="table-actions">
                    <table>
                        <tr>
                            <th>Faculty</th>
                            <th>Submissions</th>
                            <th>Percentage</th>
                        </tr>
                        <?php 
                        $total = 0;
                        $facultyData = $reports['faculty_contributions']->fetchAll(PDO::FETCH_ASSOC);
                        $total = array_sum(array_column($facultyData, 'count'));
                        ?>
                        
                        <?php foreach($facultyData as $row): ?>
                        <tr>
                            <td><?= $row['faculty'] ?></td>
                            <td><?= $row['count'] ?></td>
                            <td><?= round(($row['count'] / $total) * 100, 1) ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total">
                            <td>Total</td>
                            <td><?= $total ?></td>
                            <td>100%</td>
                        </tr>
                    </table>
                    
                    <a href="reports.php?year=<?= $current_year ?>&export=faculty" class="btn export">Export to CSV</a>
                </div>
            </div>
        </div>
        
        <div class="report-section">
            <h2>Overdue Comments (<?= $current_year ?>)</h2>
            
            <?php if($reports['overdue_comments']->rowCount() > 0): ?>
            <table>
                <tr>
                    <th>Submission Title</th>
                    <th>Student</th>
                    <th>Submitted On</th>
                    <th>Days Overdue</th>
                </tr>
                <?php while($row = $reports['overdue_comments']->fetch(PDO::FETCH_ASSOC)): 
                    $days = floor((time() - strtotime($row['created_at'])) / (60 * 60 * 24)) - 14;
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['title']) ?></td>
                    <td><?= htmlspecialchars($row['student']) ?></td>
                    <td><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                    <td class="<?= $days > 7 ? 'critical' : 'warning' ?>"><?= $days ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
            <?php else: ?>
                <p>No submissions with overdue comments.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Faculty Contributions Chart
        const facultyData = [
            <?php 
            foreach($facultyData as $row) {
                echo "{faculty: '" . addslashes($row['faculty']) . "', count: " . $row['count'] . "},";
            }
            ?>
        ];
        
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
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>