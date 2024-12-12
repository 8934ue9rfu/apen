<?php
session_start();

// Check for user login
if (!isset($_SESSION['userID']) || !isset($_SESSION['userGradeID'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['userID'] ?? null;
if (!$user_id) {
    die("Error: User ID not found in session.");
}

$juriID = $_SESSION['userID'] ?? null;
$totalApplications = 0;
$notEvaluatedApplications = 0;
$evaluatedApplications = 0;
$disabledAwards = [];
$awardsByGrade = []; // Array to store awards grouped by gradeID
$participantCounts = []; // Array to store participant counts for each award

try {
    $pdo = new PDO('mysql:host=localhost;dbname=borangapen', 'root', 'Aleesya_2004');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

   // Count applications with totalMark IS NULL (not yet evaluated)
    $stmtNotEvaluated = $pdo->prepare("
    SELECT COUNT(DISTINCT userID) AS notEvaluated 
    FROM markah 
    WHERE totalMark IS NULL
    ");
    $stmtNotEvaluated->execute();
    $notEvaluatedApplications = $stmtNotEvaluated->fetchColumn();

    // Count applications with totalMark IS NOT NULL (evaluated)
    $stmtEvaluated = $pdo->prepare("
    SELECT COUNT(DISTINCT userID) AS evaluated 
    FROM markah 
    WHERE totalMark IS NOT NULL
    ");
    $stmtEvaluated->execute();
    $evaluatedApplications = $stmtEvaluated->fetchColumn();

    // Fetch all award names, IDs, and gradeID from awards table along with gradeName from grades table
    $stmtAwards = $pdo->prepare("SELECT a.awardID, a.awardName, a.gradeID, g.gradeName
                                 FROM awards a
                                 JOIN grades g ON a.gradeID = g.gradeID");
    $stmtAwards->execute();
    $allAwards = $stmtAwards->fetchAll(PDO::FETCH_ASSOC);

    // Group awards by gradeID and store the gradeName with the awards
    foreach ($allAwards as $award) {
        $awardsByGrade[$award['gradeID']]['gradeName'] = $award['gradeName'];
        $awardsByGrade[$award['gradeID']]['awards'][] = $award;
    }

    // Fetch the number of participants per awardID from the markah table
    $stmtParticipants = $pdo->prepare("
        SELECT awardID, COUNT(userID) AS participantCount 
        FROM markah 
        GROUP BY awardID
    ");
    $stmtParticipants->execute();
    $participantCountsData = $stmtParticipants->fetchAll(PDO::FETCH_ASSOC);

    // Store participant counts in an associative array for easy lookup
    foreach ($participantCountsData as $row) {
        $participantCounts[$row['awardID']] = $row['participantCount'];
    }

    // Fetch user data
    $stmt = $pdo->prepare("SELECT staffName FROM users WHERE userID = :id");
    $stmt->execute(['id' => $user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $staffName = $row['staffName'] ?? '';

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle form submission to set awardID in the session and redirect
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['awardID'])) {
    $_SESSION['awardID'] = $_POST['awardID'];
    header("Location: urusetiaDetail.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Urusetia Dashboard - Awards</title>
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
            width: 30%;
            border-collapse: collapse;
            background-color: #ffffff;
            margin-bottom: 20px;
            border-radius: 5px;
            overflow: hidden;
        }
        table td {
            max-width: 300px;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .btn.custom-button-color {
            background-color: #433878 !important;
            border-color: #433878 !important;
            color: #fff !important;
        }
        .btn.custom-button-color:hover {
            background-color: #441752 !important;
            border-color: #441752 !important;
        }
        .custom-button-color:disabled {
            background-color: #ccc;
            border-color: #ccc;
            color: #fff;
            cursor: not-allowed;
        }
        h1.h2, h4 { 
            color: #5b4636; 
        }
        table th:nth-child(2), table td:nth-child(2) {
            width: 500px;
        }
        @media (max-width: 768px) {
            body {
                padding: 0px;
                font-size: 14px;
            }
            .navbar { 
                flex-direction: column; 
                text-align: left-align; 
                padding: 10px; 
                width: 100%;
            }
            .navbar-brand, .nav-link {
                font-size: 14px;
            }
            .main-content {
                padding: 2px;
            }
            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                margin-bottom: 20px;
            }
            table {
                width: 100%;
                font-size: 7px;
                min-width: 100%; 
                margin-bottom: 10px;
                word-break: break-word; 
            }
            table th, table td {
                padding: 2px;
                text-align: left; 
                vertical-align: top; 
                white-space: normal;
            }
            .btn.custom-button-color {
                font-size: 8px;
                padding: 5px 10px;
                width: 100%; 
            }
            footer {
                font-size: 14px;
                padding: 8px;
            }
            h1.h2, h4 { 
                color: #5b4636; 
            }
            table th:nth-child(2), table td:nth-child(2) {
                width: 90px; 
            }
        }   
        @media (max-width: 992px) {
            .card {
                padding: 18px;
            }
            table {
                font-size: 10px;
            }
            .dashboard-heading {
                font-size: 20px; 
            }
            .ms-auto {
                font-size: 12px; 
            }
            h5 {
                font-size: 15px; 
            }
            footer {
                font-size: 14px;
                padding: 8px;
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
                        <a class="nav-link" href="urusetiadashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reportUrusetia.php">Rekod</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="confirmLogout()">Log Out</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <main class="px-md-4">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="dashboard-heading" style="color: #441752;">Laman Utama Urusetia</h1>
            <h5 class="ms-auto" style="color: #441752;">Selamat Datang, <?php echo strtoupper(explode(' ', trim($staffName))[0]); ?></h5></div>
            
            <div class="main-content">
                <div class="row justify-content-center">
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">Permohonan Yang Belum Disemak</h5>
                                <p class="card-text"><?php echo $notEvaluatedApplications; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">Permohonan Yang Telah Disemak</h5>
                                <p class="card-text"><?php echo $evaluatedApplications; ?></p>
                            </div>
                        </div>
                    </div>
                </div><br><br>
            </div>

            <!-- Display Tables for Awards by gradeID -->
            <?php foreach ($awardsByGrade as $gradeID => $data): ?>
                <div class="container">
                    <h4 style="color: #441752; margin: 0 auto; text-align: center;">
                        Kategori Anugerah Pemarkahan <?php echo htmlspecialchars($data['gradeName']); ?>
                    </h4><br>

                    <table class="table table-bordered" style="width: 70%; margin: 0 auto;">
                        <thead>
                            <tr style="background-color: #A888B5;">
                                <th class="text-center">Bil</th>
                                <th class="text-center">Kategori</th>
                                <th class="text-center">Bilangan Permohonan</th>
                                <th class="text-center">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['awards'] as $index => $award): ?>
                                <tr>
                                    <td class="text-center"><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($award['awardName']); ?></td>
                                    <td class="text-center">
                                        <?php echo $participantCounts[$award['awardID']] ?? 0; ?>
                                    </td>
                                    <td class="text-center">
                                        <form action="" method="post" style="display:inline;">
                                            <input type="hidden" name="awardID" value="<?php echo $award['awardID']; ?>">
                                            <button type="submit" 
                                                    class="btn custom-button-color <?php echo in_array($award['awardID'], $disabledAwards) ? 'disabled' : ''; ?>">
                                                Semak
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div><br><br>
            <?php endforeach; ?>
        </main>
    </div>

    <!-- Footer -->
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
