<?php
session_start();

// Check for user login
if (!isset($_SESSION['userID']) || !isset($_SESSION['userGradeID'])) {
    header("Location: login.php");
    exit;
}

// Set HTTP headers to prevent caching of the page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: Thu, 19 Nov 1981 08:52:00 GMT"); // Expired date
header("Pragma: no-cache"); // For HTTP/1.0 compatibility

// Initialize variables
$user_id = $_SESSION['userID'] ?? null;
$eligibility_status = "Tidak Layak";
$applied_awards = [];
$userGrade = $_SESSION['userGradeID'] ?? null;

// Check if user ID is available
if (!$user_id) {
    die("Error: User ID not found in session.");
}

// Database connection using PDO
try {
    $pdo = new PDO('mysql:host=localhost;dbname=borangapen', 'root', 'Aleesya_2004');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch user eligibility data
    $stmt = $pdo->prepare("SELECT staffName, LNPT2021, LNPT2022, LNPT2023, tahunBerkhidmat, tatatertib, kesahan, statusStaff FROM users WHERE userID = :id");
    $stmt->execute(['id' => $user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $staffName = $row['staffName'] ?? '';

    // Determine eligibility based on fetched data
    if ($row) {
        $marks_2021 = $row['LNPT2021'];
        $marks_2022 = $row['LNPT2022'];
        $marks_2023 = $row['LNPT2023'];
        $tatatertib = $row['tatatertib'];
        $tahunBerkhidmat = $row['tahunBerkhidmat'];
        $kesahan = $row['kesahan'];
        $statusStaff = $row['statusStaff'];

        if ($marks_2021 >= 85 && $marks_2022 >= 85 && $marks_2023 >= 85 &&
            $tahunBerkhidmat >= 3 && strtolower($kesahan) == 'telah disahkan' && strtolower($tatatertib) == 'bebas' && strtolower($statusStaff) == 'aktif') {
            $eligibility_status = "Layak";
        }
    }

    // Fetch awards eligible for the user's grade
    $sql = "SELECT a.awardID, a.awardName 
            FROM awards a 
            JOIN awardgrades ag ON a.awardID = ag.awardID 
            JOIN grades g ON ag.gradeID = g.gradeID 
            WHERE g.gradeID = :gradeID";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['gradeID' => $userGrade]);
    $awards = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check if the user has already submitted any award for this grade
    $stmtSubmissionCheck = $pdo->prepare("
        SELECT COUNT(*) 
        FROM usersubmissions us
        JOIN users u ON us.userID = u.userID
        WHERE us.userID = :userID 
        AND u.gradeID = :gradeID 
        AND us.submitted = 1
    ");
    $stmtSubmissionCheck->execute(['userID' => $user_id, 'gradeID' => $userGrade]);
    $hasSubmitted = $stmtSubmissionCheck->fetchColumn() > 0;

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Fetch grade name from the Grades table based on userGradeID
$stmtGrade = $pdo->prepare("SELECT gradeName FROM grades WHERE gradeID = :gradeID");
$stmtGrade->execute(['gradeID' => $userGrade]);
$grade = $stmtGrade->fetch(PDO::FETCH_ASSOC);
$gradeName = $grade ? $grade['gradeName'] : 'Gred Tidak Ditemui';

// Function to check if a specific award has been confirmed (submitted)
function isAwardConfirmed($user_id, $awardID) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) 
                           FROM usersubmissions us 
                           WHERE us.userID = :userID 
                           AND us.awardID = :awardID 
                           AND us.submitted = 1");
    $stmt->execute(['userID' => $user_id, 'awardID' => $awardID]);
    
    return $stmt->fetchColumn() > 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
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
        .card { 
            background-color: #fffaf5; 
            border: none; 
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1); 
            padding: 10px; 
            border-radius: 10px; 
        }
        .card-title { 
            color: #3B1E54; 
        }
        .btn-primary, .btn[disabled] { 
            background-color: rgb(152, 125, 154); 
            border-color: rgb(152, 125, 154); 
            display: block; 
            margin: 0 auto; 
            width: 100%; 
            padding: 10px 15px; 
            font-size: 16px;
            text-align: center;
            border-radius: 5px;
        }
        .btn[disabled] { 
            background-color: #C6A9A3; 
            border-color: #C6A9A3; 
            cursor: not-allowed; 
            color: #white; 
        }
        .btn-primary:hover { 
            background-color: rgb(105, 79, 142); 
            border-color: rgb(105, 79, 142); 
        }
        .alert-info { 
            background-color: #E5D9F2; 
            color: rgb(127, 82, 131); 
            border-color: rgb(127, 82, 131); 
        }
        h1.h2, h4 { 
            color: #5b4636; 
        }
        th { 
            text-align: center; 
        }
        footer { 
            background-color: #441752; 
            color: white; 
            text-align: center; 
            padding: 15px; 
            margin-top: auto; 
        }
        footer p { 
            margin: 0; 
        }
        .btn-primary:focus, .btn-primary:active, .btn-primary:focus:active, .btn-primary:focus-visible {
            outline: none !important; 
            box-shadow: none !important; 
            background-color: rgb(105, 79, 142) !important; 
            border-color: rgb(105, 79, 142) !important;
        }
        @media (max-width: 768px) {
            h5 {
                font-size: 14px;
            }
            .dashboard-heading {
                font-size: 24px; 
            }
            .card-title {
                font-size: 15px;
            }
            .alert-info {
                font-size: 15px;
            }
            .container {
                font-size: 12px;
            }
            .btn-primary {
                font-size: 12px;
                padding: 5px;
            }
        }
        @media (max-width: 480px) {
            h5 {
                font-size: 12px; 
            }
            .dashboard-heading {
                font-size: 20px; 
            }
            .card-title {
                font-size: 15px;
            }
            .alert-info {
                font-size: 15px; 
            }
            .container {
                font-size: 12px;
            }
            .btn-primary {
                font-size: 12px;
                padding: 5px;
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
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
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
                <h1 class="dashboard-heading" style="color:#3B1E54">Dashboard</h1>
                <h5 class="ms-auto" style="color:#3B1E54">Selamat Datang, <?php echo strtoupper(explode(' ', trim($staffName))[0]); ?></h5>
            </div>

            <!-- User Notice Section -->
            <div class="alert alert-info" role="alert">
                Anda <strong><?php echo $eligibility_status; ?></strong> untuk memohon anugerah.

                <?php if ($eligibility_status == "Layak"): ?>
                    <?php
                    // Retrieve gradeID from session and determine rubric link and text
                    $rubricLink = "";
                    $rubricText = "";

                    switch ($_SESSION['userGradeID']) {
                        case 1:
                            $rubricLink = "rubricA.html";
                            $rubricText = "Rubrik Gred A";
                            break;
                        case 2:
                            $rubricLink = "rubricB.html";
                            $rubricText = "Rubrik Gred B";
                            break;
                        case 3:
                            $rubricLink = "rubricC.html";
                            $rubricText = "Rubrik Gred C";
                            break;
                        case 4:
                            $rubricLink = "rubricC.html";
                            $rubricText = "Rubrik Gred D";
                            break;
                        default:
                            $rubricText = "Rubrik Tidak Tersedia"; // Handle unexpected grade IDs
                    }
                    ?>
                    <!-- Display the link to the appropriate rubric -->
                    <?php if ($rubricLink): ?>
                        Sila klik <a href="<?php echo $rubricLink; ?>" style="color: rgb(127, 82, 131); font-weight: bold;">
                            <?php echo $rubricText; ?>
                        </a> untuk melihat rubrik.
                    <?php else: ?>
                        <p><?php echo $rubricText; ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <br>

            <div class="container">
                <div class="card" style="margin-bottom: 2px;">
                    <h4 class="card-title">Kategori Anugerah - <?php echo $gradeName; ?></h4>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Bil</th>
                                <th>Kategori Anugerah</th>
                                <th>Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($awards) > 0): ?>
                                <?php foreach ($awards as $index => $award): ?>
                                    <?php 
                                        $is_disabled = ($eligibility_status == "Tidak Layak" || isAwardConfirmed($user_id, $award['awardID']));
                                    ?>
                                    <tr>
                                        <td style="text-align: center;"><?= $index + 1 ?></td>
                                        <td><?= $award['awardName'] ?></td>
                                        <td style="text-align: center;">
                                            <?php if ($is_disabled): ?>
                                                <button class="btn btn-primary" disabled>Mohon</button>
                                            <?php else: ?>
                                                <?php if ($award['awardID'] == 3): ?>
                                                    <!-- If the awardID is 3, redirect to kategori_gemilang_dropdown.php with awardID and gradeID -->
                                                    <a href="kategori_gemilang_dropdown.php?awardID=<?= $award['awardID'] ?>&gradeID=<?= urlencode($userGrade) ?>" class="btn btn-primary">
                                                        Mohon
                                                    </a>
                                                <?php else: ?>
                                                    <!-- Default behavior for other awards -->
                                                    <a href="document_submission.php?awardID=<?= $award['awardID'] ?>&gradeID=<?= urlencode($userGrade) ?>" class="btn btn-primary">
                                                        Mohon
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="text-align: center;">Tiada kategori anugerah yang tersedia.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div><p>

    <footer>
        <p>&copy; Bahagian Pentadbiran, UiTM Cawangan Perak</p>
    </footer>

    <!-- Scripts -->
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