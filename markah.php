<?php
session_start();

$juriID = $_SESSION['userID'] ?? null; // Get user ID from session

if (!$juriID) {
    header("Location: index.php");
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

// Update the SQL query to include the markah column
$sql = "
   SELECT 
    markah.userID,
    users.staffName,
    grades.gradeName, -- Fetch gradeName from the grades table
    markah.awardID,
    awards.awardName,
    markah.totalmark
    FROM  
        markah
    JOIN 
        awards ON markah.awardID = awards.awardID
    JOIN 
        users ON markah.userID = users.userID
    JOIN 
        grades ON users.gradeID = grades.gradeID
    WHERE 
        markah.totalmark > 0;
     -- Join grades table to fetch gradeName
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
        .nav-link:hover { 
            color: #8b6a52 !important; 
        }
        .main-content {
            flex: 1; 
            padding: 20px;
        }
        footer {
            background-color: #d3bca2; 
            color: #5b4636; 
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
            color: #5b4636; 
        }
        .table td {
            text-transform: uppercase;
        }
        .table td .btn {
            text-transform: none;
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
                    <a class="nav-link" href="jurydashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="markah.php">Markah</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="statistik.php">Statistik</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="confirmLogout()">Log Out</a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid">
        <main class="px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Rekod Permohonan</h1>
            </div>

            <div class="container mt-5">
                <h2 class="text-center">Permohonan Yang Telah Disemak </h2><br>
                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th class="text-center">Bil</th>
                            <th class="text-center">ID</th>
                            <th class="text-center">Nama Staff</th>
                            <th class="text-center">Gred</th>
                            <th class="text-center">Kategori Anugerah</th>
                            <th class="text-center">Markah (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($evaluatedEntries)): ?>
                            <?php $bil = 1; ?>
                            <?php foreach ($evaluatedEntries as $entry): ?>
                                <tr>
                                    <td class="text-center"><?php echo $bil++; ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($entry['userID']); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($entry['staffName']); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($entry['gradeName']); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($entry['awardName']); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($entry['totalmark']); ?></td> <!-- Display markah -->
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">Tiada rekod ditemui.</td>
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
