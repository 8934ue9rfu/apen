<?php
// Database connection
$conn = new mysqli("localhost", "root", "Aleesya_2004", "borangapen");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to get applications count per award for each grade
function getApplicationsByGrade($gradeID, $awardIDStart, $awardIDEnd, $conn) {
    $query = "
        SELECT a.awardName, a.awardID, COUNT(m.userID) AS applications_count
        FROM awards AS a
        LEFT JOIN markah AS m ON a.awardID = m.awardID
        LEFT JOIN awardgrades AS ag ON a.awardID = ag.awardID
        WHERE ag.gradeID = $gradeID
        AND a.awardID BETWEEN $awardIDStart AND $awardIDEnd
        GROUP BY a.awardID, a.awardName
        ORDER BY a.awardID
    ";

    $result = $conn->query($query);
    $awardNames = [];
    $applicationsCounts = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $awardNames[] = $row['awardName'];
            $applicationsCounts[] = $row['applications_count'];
        }
    }
    return [$awardNames, $applicationsCounts];
}

// Get data for each grade (Gred A, Gred B, Gred C)
// Grade A: awardID 1 to 3
$gredAData = getApplicationsByGrade(1, 1, 3, $conn);
// Grade B: awardID 4 to 6
$gredBData = getApplicationsByGrade(2, 4, 6, $conn);
// Grade C: awardID 7 to 10
$gredCData = getApplicationsByGrade(3, 7, 10, $conn);

// Count for awardID = 1 (example query for a specific award)
$awardID = 1; // Example for awardID 1
$queryAwardID = "
    SELECT COUNT(userID) AS countForAward
    FROM markah
    WHERE awardID = $awardID
";
$result = $conn->query($queryAwardID);
$countForAward = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['countForAward'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #f7f1e5;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .navbar {
            background-color: #d3bca2;
        }
        .navbar-brand, .nav-link {
            color: #5b4636 !important;
        }
        footer {
            background-color: #d3bca2;
            color: #5b4636;
            text-align: center;
            padding: 10px;
            margin-top: auto;
        }
        .container-white {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
<!-- Top Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Anugerah Pentadbir</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="moderatorDashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="markah.php">Markah</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="statistik.php">Statistik</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Log Out</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content -->
<div class="container-fluid">
    <main class="px-md-4">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Statistik Permohonan</h1>
        </div>

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs" id="gradeTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" id="gredA-tab" data-bs-toggle="tab" href="#gredA" role="tab" aria-controls="gredA" aria-selected="true">Pengurusan dan Profesional</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="gredB-tab" data-bs-toggle="tab" href="#gredB" role="tab" aria-controls="gredB" aria-selected="false">Kumpulan Pelaksana (Gred 29-40)</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="gredC-tab" data-bs-toggle="tab" href="#gredC" role="tab" aria-controls="gredC" aria-selected="false">Kumpulan Pelaksana (Gred 11-28)</a>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="gradeTabsContent">

            <!-- Gred A Tab -->
            <div class="tab-pane fade show active" id="gredA" role="tabpanel" aria-labelledby="gredA-tab">
                <div class="container-white">
                    <h5 class="card-title">Jumlah Permohonan untuk Pengurusan dan Profesional</h5>
                    <canvas id="applicationsChartGredA" width="400" height="200"></canvas>
                    <script>
                        const ctxGredA = document.getElementById('applicationsChartGredA').getContext('2d');
                        const applicationsChartGredA = new Chart(ctxGredA, {
                            type: 'bar',
                            data: {
                                labels: <?php echo json_encode($gredAData[0]); ?>,
                                datasets: [{
                                    label: 'Jumlah Permohonan Pengurusan dan Profesional',
                                    data: <?php echo json_encode($gredAData[1]); ?>,
                                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                    borderColor: 'rgba(75, 192, 192, 1)',
                                    borderWidth: 1
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
                </div>

                <div class="container-white">
                    <h5 class="card-title">Jumlah Permohonan untuk Pengurusan dan Profesional</h5>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Anugerah</th>
                                <th>Jumlah Permohonan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($gredAData[0] as $index => $awardName): ?>
                                <tr>
                                    <td><?php echo $awardName; ?></td>
                                    <td><?php echo $gredAData[1][$index]; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Gred B Tab -->
            <div class="tab-pane fade" id="gredB" role="tabpanel" aria-labelledby="gredB-tab">
                <div class="container-white">
                    <h5 class="card-title">Jumlah Permohonan untuk Kumpulan Pelaksana (Gred 29-40)</h5>
                    <canvas id="applicationsChartGredB" width="400" height="200"></canvas>
                    <script>
                        const ctxGredB = document.getElementById('applicationsChartGredB').getContext('2d');
                        const applicationsChartGredB = new Chart(ctxGredB, {
                            type: 'bar',
                            data: {
                                labels: <?php echo json_encode($gredBData[0]); ?>,
                                datasets: [{
                                    label: 'Jumlah Permohonan Kumpulan Pelaksana (Gred 29-40)',
                                    data: <?php echo json_encode($gredBData[1]); ?>,
                                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                                    borderColor: 'rgba(54, 162, 235, 1)',
                                    borderWidth: 1
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
                </div>

                <div class="container-white">
                    <h5 class="card-title">Jumlah Permohonan untuk Kumpulan Pelaksana (Gred 29-40)</h5>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Anugerah</th>
                                <th>Jumlah Permohonan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($gredBData[0] as $index => $awardName): ?>
                                <tr>
                                    <td><?php echo $awardName; ?></td>
                                    <td><?php echo $gredBData[1][$index]; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Gred C Tab -->
            <div class="tab-pane fade" id="gredC" role="tabpanel" aria-labelledby="gredC-tab">
                <div class="container-white">
                    <h5 class="card-title">Jumlah Permohonan untuk Kumpulan Pelaksana (Gred 11-28)</h5>
                    <canvas id="applicationsChartGredC" width="400" height="200"></canvas>
                    <script>
                        const ctxGredC = document.getElementById('applicationsChartGredC').getContext('2d');
                        const applicationsChartGredC = new Chart(ctxGredC, {
                            type: 'bar',
                            data: {
                                labels: <?php echo json_encode($gredCData[0]); ?>,
                                datasets: [{
                                    label: 'Jumlah Permohonan Kumpulan Pelaksana (Gred 11-28)',
                                    data: <?php echo json_encode($gredCData[1]); ?>,
                                    backgroundColor: 'rgba(255, 159, 64, 0.2)',
                                    borderColor: 'rgba(255, 159, 64, 1)',
                                    borderWidth: 1
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
                </div>

                <div class="container-white">
                    <h5 class="card-title">Jumlah Permohonan untuk Kumpulan Pelaksana (Gred 11-28)</h5>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Anugerah</th>
                                <th>Jumlah Permohonan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($gredCData[0] as $index => $awardName): ?>
                                <tr>
                                    <td><?php echo $awardName; ?></td>
                                    <td><?php echo $gredCData[1][$index]; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Footer -->
<footer>
    <p>&copy; Bahagian Pentadbiran, UiTM Cawangan Perak</p>
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>

</body>
</html>
