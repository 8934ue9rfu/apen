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
$notEvaluatedCount = []; // Array to store not evaluated count for each award

try {
    $pdo = new PDO('mysql:host=localhost;dbname=borangapen', 'root', 'Aleesya_2004');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($user_id) {
        // Fetch juror details
        $stmt = $pdo->prepare("SELECT juriName, juriID FROM jurydetail WHERE juriID = :id");
        $stmt->execute(['id' => $user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $juriName = $row['juriName'] ?? '';
        $juriID = $row['juriID'] ?? '';
    } else {
        // Handle the case where userID is not set
        $juriName = '';
        $juriID = '';
    }

    // Count total applications in the markah table
    $stmtTotal = $pdo->prepare("SELECT COUNT(DISTINCT userID, awardID) AS total FROM markah");
    $stmtTotal->execute();
    $totalApplications = $stmtTotal->fetchColumn();

    // Count total evaluated applications from jury table
    $stmtKira = $pdo->prepare("SELECT COUNT(DISTINCT statusID) AS evaluated 
                                FROM jury 
                                WHERE markStatus = 'Evaluated' 
                                AND juriID = :juriID;");
                                
    $stmtKira->bindParam(':juriID', $juriID, PDO::PARAM_INT);
    $stmtKira->execute();
    $evalKira = $stmtKira->fetchColumn();
    
    // Count evaluated applications in the jury table for the current juriID
    $stmtEvaluated = $pdo->prepare("
        SELECT COUNT(DISTINCT statusID) AS evaluated 
        FROM jury 
        WHERE markStatus = 'Evaluated' 
        AND juriID = :juriID;
    ");

    $stmtEvaluated->bindParam(':juriID', $juriID, PDO::PARAM_INT);
    $stmtEvaluated->execute();
    $evaluatedApplications = $stmtEvaluated->fetchColumn();

    // Calculate not yet evaluated applications
    $notEvaluatedApplications = $totalApplications - $evaluatedApplications;

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

    // Query to check for disabled awards based on jury ID
    $stmtCheck = $pdo->prepare("SELECT DISTINCT awardID FROM jury WHERE mark > 0 AND juriID = :user_id");
    $stmtCheck->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmtCheck->execute();
    $disabledAwards = $stmtCheck->fetchAll(PDO::FETCH_COLUMN);

    // Fetch total applications per award from 'markah' table
    $stmtTotalPerAward = $pdo->prepare("SELECT awardID, COUNT(userID) AS total FROM markah GROUP BY awardID");
    $stmtTotalPerAward->execute();
    $totalPerAward = $stmtTotalPerAward->fetchAll(PDO::FETCH_ASSOC);

    // Fetch evaluated applications per award from 'jury' table
    $stmtEvaluatedPerAward = $pdo->prepare("SELECT awardID, COUNT(userID) AS evaluated FROM jury WHERE markStatus = 'Evaluated' GROUP BY awardID");
    $stmtEvaluatedPerAward->execute();
    $evaluatedPerAward = $stmtEvaluatedPerAward->fetchAll(PDO::FETCH_ASSOC);

    // Map evaluated data into an associative array for quick lookup
    $evaluatedMap = [];
    foreach ($evaluatedPerAward as $evaluated) {
        $evaluatedMap[$evaluated['awardID']] = $evaluated['evaluated'];
    }

    // Calculate 'notEvaluatedCount' for each award
    foreach ($totalPerAward as $total) {
        $awardID = $total['awardID'];
        $totalApplications = $total['total'];
        $evaluatedApplications = $evaluatedMap[$awardID] ?? 0; // Default to 0 if no evaluations exist
        $notEvaluatedCount[$awardID] = $totalApplications - $evaluatedApplications;
    }

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle form submission to set awardID in the session and redirect
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['awardID'])) {
    $_SESSION['awardID'] = $_POST['awardID'];
    header("Location: juryEvaluate.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penilaian Markah</title>
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
            color: #441752; 
        }
        table th:nth-child(2),
        table td:nth-child(2) { 
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
            table th:nth-child(2), 
            table td:nth-child(2) { 
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

    <div class="container-fluid">
        <main class="px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="dashboard-heading" style="color: #441752;">Laman Utama Juri</h1>
                <h5 class="ms-auto" style="color: #441752;">Selamat Datang, <?php echo strtoupper(explode(' ', trim($juriName))[0]); ?></h5></div>
                <div class="main-content">
                    <div class="row justify-content-center"> <!-- Use justify-content-center to center the cards -->
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
                                    <p class="card-text"><?php echo $evalKira; ?></p>
                                </div>
                            </div>
                        </div>
                    </div><br><br>
                </div>

                <!-- Display Tables for Awards by gradeID -->
                <?php foreach ($awardsByGrade as $gradeID => $data): ?>
                    <div class="container">
                        <h4 style="color: #441752; margin: 0 auto;text-align: center;">
                            Kategori Anugerah Pemarkahan <?php echo htmlspecialchars($data['gradeName']); ?>
                        </h4><br>
                        
                        <table class="table table-bordered" style="width: 70%; margin: 0 auto;">
                            <thead>
                                <tr style="background-color: #A888B5;">
                                    <th class="text-center">Bil</th>
                                    <th class="text-center">Kategori</th>
                                    <th class="text-center">Bilangan Belum Disemak</th> <!-- New Column -->
                                    <th class="text-center">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['awards'] as $index => $award): ?>
                                    <tr>
                                        <td class="text-center"><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($award['awardName']); ?></td>
                                        <td class="text-center">
                                            <?php echo $notEvaluatedCount[$award['awardID']] ?? 0; ?> <!-- Display Remaining Count -->
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
