<?php
session_start();

$juriID = $_SESSION['userID'] ?? null; // Get user ID from session

if (!$juriID) {
    header("Location: login.php");
    exit();
}

// Database connection using PDO
$dsn = "mysql:host=localhost;dbname=borangapen;charset=utf8mb4";
$username = "root";
$password = "Aleesya_2004";

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Prepare the SQL query using PDO
$sql = "
    SELECT 
        users.userID AS userID,
        users.staffName AS userName,
        GROUP_CONCAT(DISTINCT awards.awardName SEPARATOR ', ') AS awardName, -- Concatenate all award names
        users.gradeID AS gredID,
        grades.gradeName AS gredName
    FROM 
        jury
    JOIN 
        awards ON jury.awardID = awards.awardID
    JOIN 
        users ON jury.userID = users.userID
    JOIN 
        grades ON users.gradeID = grades.gradeID
    WHERE 
        jury.markStatus = 'EVALUATED'
    GROUP BY 
        users.userID, users.staffName, users.gradeID, grades.gradeName;
";

// Execute the query
$stmt = $pdo->prepare($sql);
$stmt->execute();
$evaluatedEntries = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permohonan Yang Telah Disemak</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="picture/icon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="picture/icon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="picture/icon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="picture/icon/apple-touch-icon.png">
    <style>
        body {
            background-color: #F7EFE5; 
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .navbar { 
            background-color: #441752; 
        }
        .navbar-brand, .nav-link { 
            color: white !important; 
        }
        .nav-link:hover { 
            color: rgb(105, 79, 142) !important; 
        }
        .main-content {
            flex: 1; 
            padding: 20px;
        }
        footer {
            background-color: #441752; 
            color: white; 
            text-align: center;
            padding: 10px;
            margin-top: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #ffffff;
            margin-bottom: 20px;
            border-radius: 5px;
            overflow: hidden; 
        }
        h1.h2, h4 {
            color: #441752; 
        }
        .table td {
            text-transform: uppercase;
        }
        .table td .btn {
            text-transform: none;
        }
        .no-records {
            text-transform: none !important;; 
            color: #888; 
        }
        @media (max-width: 768px) {
            .navbar { 
                flex-direction: column; 
                text-align: left-align; 
                padding: 10px; 
                width: 100%;
            }
            .navbar-brand, .nav-link {
                    font-size: 14px;
            }
            table {
                font-size: 6px;
            }
            table td {
                padding: 4px;
                word-wrap: break-word; 
                white-space: normal; 
            }
            h1.h2, h4 {
                font-size: 14px;
            }
        }  
        @media (max-width: 992px) {
            .card {
                padding: 18px;
            }
            .navbar-brand, .nav-link {
                font-size: 16px;
            }
            a.btn {
                padding: 2px 2px;  /* Larger padding for desktop */
                font-size: 8px;     /* Even larger font size for desktop */
            }
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
        <a class="navbar-brand" href="#">Anugerah Pentadbir</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="jurydashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="report.php">Rekod</a>
                    </li>
                    <li class="nav-item">
                    <a class="nav-link" href="#" onclick="confirmLogout()">Log Out</a>
                </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid">
        <main class="px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Rekod Permohonan</h1>
            </div>

            <div class="container mt-5">
                <h2 class="text-center" style="color: #441752;">Permohonan Yang Telah Disemak </h2><br>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th class="text-center">Bil</th> <!-- Bil Column Header -->
                            <th class="text-center">No Pekerja</th> <!-- Centered Header -->
                            <th class="text-center">Nama</th> <!-- Centered Header -->
                            <th class="text-center">Gred</th> <!-- Added Grade Column -->
                            <th class="text-center">Kategori Anugerah</th> <!-- Centered Header -->
                            <th class="text-center">Rekod</th> <!-- Rekod Button Column -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($evaluatedEntries)): ?>
                            <?php $bil = 1; // Initialize Bil counter ?>
                            <?php foreach ($evaluatedEntries as $entry): ?>
                                <tr>
                                    <td class="text-center"><?php echo $bil++; ?></td> <!-- Display Bil (Serial Number) -->
                                    <td class="text-center"><?php echo htmlspecialchars($entry['userID']); ?></td> <!-- Centered Cell -->
                                    <td class="text-center"><?php echo htmlspecialchars($entry['userName']); ?></td> <!-- Centered Cell -->
                                    <td class="text-center"><?php echo htmlspecialchars($entry['gredName']); ?></td> <!-- Display Gred Name -->
                                    <td class="text-center">
                                        <?php 
                                            // Loop through awardNames for this user and display each
                                            $awards = explode(',', $entry['awardName']); // Assuming awards are returned as comma-separated values
                                            foreach ($awards as $award) {
                                                echo htmlspecialchars($award) . '<br>';
                                            }
                                        ?>
                                    </td> <!-- Centered Award Names Cell -->
                                    <td class="text-center">
                                        <!-- Rekod Button for each user ID -->
                                        <a href="status.php?id=<?php echo $entry['userID']; ?>" class="btn" style="background-color: #b58868; color: white;">Lihat Rekod</a>
                                    </td> <!-- Rekod Button Cell -->
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center no-records">Tiada rekod ditemui.</td> <!-- Centered Cell for No Data -->
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <footer>
        <p>&copy; Bahagian Pentadbiran, UiTM Cawangan Perak</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmLogout() {
            // Display confirmation popup
            var isConfirmed = confirm("Adakah anda pasti ingin log keluar?");
            
            // If user confirms, proceed with log out
            if (isConfirmed) {
                window.location.href = "logout.php"; // Redirect to logout page
            }
        }
    </script>
</body>
</html>
