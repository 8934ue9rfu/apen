<?php
session_start();

// Check if the user is logged in and the necessary session variables are set
if (!isset($_SESSION['userID']) || !isset($_SESSION['userGradeID'])) {
    header("Location: login.php");
    exit;
}

// Get user ID and grade from the session
$userGrade = $_SESSION['userGradeID'];
$userID = $_SESSION['userID'];

// Ensure awardID and gradeID are set from URL parameters
$awardID = isset($_GET['awardID']) ? $_GET['awardID'] : null;
$gradeID = isset($_GET['gradeID']) ? $_GET['gradeID'] : null;

// Ensure both awardID and gradeID are available before proceeding
if (!$awardID || !$gradeID) {
    echo "Error: Missing awardID or gradeID.";
    exit;
}

// Database connection
$pdo = new PDO('mysql:host=localhost;dbname=borangapen', 'root', 'Aleesya_2004');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Query to get the award name for the selected award ID
$sqlAward = "SELECT awardName FROM awards WHERE awardID = :awardID";
$stmtAward = $pdo->prepare($sqlAward);
$stmtAward->execute(['awardID' => $awardID]);
$awardName = $stmtAward->fetchColumn(); // Fetch the award name directly

// Query to get segments and their description for the selected award and grade
$sqlSegments = "SELECT dr.requirementID, dr.segment, dr.max
                FROM documentrequirements dr
                WHERE dr.awardID = :awardID AND dr.gradeID = :gradeID";
$stmtSegments = $pdo->prepare($sqlSegments);
$stmtSegments->execute(['awardID' => $awardID, 'gradeID' => $gradeID]);
$segments = $stmtSegments->fetchAll(PDO::FETCH_ASSOC);

// Query to check if a document has been uploaded for a segment from UserSubmissions
$sqlUploads = "SELECT requirementID, submitted FROM usersubmissions 
               WHERE userID = :userID AND awardID = :awardID AND requirementID = :requirementID";
$stmtUploads = $pdo->prepare($sqlUploads);

// Success flag
$showSuccessModal = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['confirm_data'])) {
        // Get the 'Catatan' value from the form
        $catatan = isset($_POST['catatan']) ? $_POST['catatan'] : '';

        // Update the `submitted` column and `catatan` column for all relevant segments for the user
        $sqlUpdateAll = "UPDATE usersubmissions 
                         SET submitted = 1, catatan = :catatan
                         WHERE userID = :userID AND awardID = :awardID";
        $stmtUpdateAll = $pdo->prepare($sqlUpdateAll);
        $stmtUpdateAll->execute([
            'userID' => $userID,
            'awardID' => $awardID,
            'catatan' => $catatan
        ]);
        
        // Insert `awardID` and `userID` into the `markah` table
        $sqlInsertMarkah = "INSERT INTO markah (userID, awardID) VALUES (:userID, :awardID)";
        $stmtInsertMarkah = $pdo->prepare($sqlInsertMarkah);
        $stmtInsertMarkah->execute([
            'userID' => $userID,
            'awardID' => $awardID
        ]);

        // Set a flag to indicate that the submission is successful
        $showSuccessModal = true;
    } else {
        echo "<script>alert('Sila sahkan bahawa data adalah benar sebelum menghantar.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Senarai Penilaian Rubrik</title>
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
        .btn-primary {
            background-color: rgb(152, 125, 154); 
            border-color: rgb(152, 125, 154); 
        }
        .btn-primary:hover {
            background-color: rgb(105, 79, 142);
            border-color: rgb(105, 79, 142);
        }
        .container {
            display: flex;
            margin-top: 20px;
        }
        .main-content {
            flex: 1;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
        }
        .left-align {
            text-align: left;
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
        .btn[disabled] {
            background-color: #ccc;
            border-color: #ccc;
            cursor: not-allowed;
        }
        .btn-primary:focus,
        .btn-primary:active,
        .btn-primary:focus:active,
        .btn-primary:focus-visible {
            outline: none !important;
            box-shadow: none !important;
            background-color: rgb(105, 79, 142) !important;
            border-color:  rgb(105, 79, 142) !important;
        }
        .form-check-input {
            width: 15px;
            height: 15px;
            background-color: #faf7f5; /* Grey color when unchecked */
            border: 1px solid #ccc; /* Darker grey border */
            border-radius: 3px;
            transition: background-color 0.2s;
        }
        .form-check-input:checked {
            background-color: blue; /* Darker grey when checked */
            border-color: blue;
        }
        .alert-info { 
            background-color: #E5D9F2; 
            color: rgb(127, 82, 131); 
            border-color: rgb(127, 82, 131); 
        }
        @media (max-width: 768px) {
            .main-content{
                max-width: 390px;
                font-size: 10px;
            }
            .btn-primary {
                font-size: 10px;
                padding: 5px;
            }
            .form-group {
                font-size: 10px;
            }
        }
        @media (max-width: 480px) {
            .main-content{
                max-width: 390px;
            }
            .btn-primary {
                font-size: 10px;
                padding: 5px;
            }
            .form-group {
                font-size: 10px;
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

    <div class="container">
        <div class="main-content">
            <h2 style="color:#3B1E54"><?= htmlspecialchars($awardName) ?></h2><br>

            <!-- User Notice Section -->
            <div class="alert alert-info" role="alert">
                Anda dikehendaki untuk klik <a href="kriteria.php?awardID=<?= htmlspecialchars($awardID) ?>" style="color: rgb(127, 82, 131)">
                <strong>Senarai Kriteria</strong></a> bagi menyemak kriteria setiap penilaian sebelum memohon anugerah.
            </div>

            <form action="" method="post">
                <table>
                    <thead>
                        <tr>
                            <th>Rubrik Penilaian</th>
                            <th>Status</th>
                            <th>Tindakan</th>
                            <th>Markah (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch rubriks and their segments for this award and grade
                        $stmtRubrik = $pdo->prepare("
                            SELECT DISTINCT rubrik 
                            FROM documentrequirements 
                            WHERE awardID = :awardID 
                            AND gradeID = :gradeID
                            ORDER BY rubrik
                        ");
                        $stmtRubrik->execute(['awardID' => $awardID, 'gradeID' => $gradeID]);
                        $rubriks = $stmtRubrik->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($rubriks as $rubrik):
                        ?>
                            <!-- Rubrik Row -->
                            <tr>
                                <td colspan="4" style="font-weight: bold; text-align: left; background-color: #f9f9f9;">
                                    <?= htmlspecialchars($rubrik['rubrik']) ?>
                                </td>
                            </tr>

                            <?php
                            // Fetch segments for this regular rubrik
                            $stmtSegments = $pdo->prepare("
                                SELECT requirementID, segment, max 
                                FROM documentrequirements 
                                WHERE rubrik = :rubrik 
                                AND awardID = :awardID 
                                AND gradeID = :gradeID
                            ");
                            $stmtSegments->execute([
                                'rubrik' => $rubrik['rubrik'],
                                'awardID' => $awardID,
                                'gradeID' => $gradeID
                            ]);
                            $segments = $stmtSegments->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($segments as $segment):
                                // Check if the segment has been submitted
                                $stmtUploads->execute([
                                    'userID' => $userID,
                                    'awardID' => $awardID,
                                    'requirementID' => $segment['requirementID']
                                ]);
                                $submission = $stmtUploads->fetch(PDO::FETCH_ASSOC);
                                $isSubmitted = $submission && $submission['submitted'] == 1;
                            ?>
                                <!-- Segment Row -->
                                <tr>
                                    <td class="left-align"><?= htmlspecialchars($segment['segment']) ?></td>
                                    <td>
                                        <input type="checkbox" <?= $isSubmitted ? 'checked' : '' ?> disabled>
                                    </td>
                                    <td>
                                        <a 
                                            <?php if (!$isSubmitted): ?>
                                                href="upload_document.php?requirementID=<?= $segment['requirementID'] ?>&awardID=<?= $awardID ?>&gradeID=<?= $gradeID ?>"
                                            <?php endif; ?>
                                            class="btn btn-primary" 
                                            <?= $isSubmitted ? 'disabled' : '' ?>>
                                            Klik Sini
                                        </a>
                                    </td>
                                    <td class="center-align"><?= htmlspecialchars($segment['max'] ?? 'N/A') ?> Markah</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>

                        <!-- Special Rows for requirementID 76, 77, 78 -->
                        <?php
                        $stmtSpecialRubrik = $pdo->prepare("
                            SELECT DISTINCT rubrik 
                            FROM documentrequirements 
                            WHERE requirementID IN (94, 95, 96, 97) 
                            AND gradeID = :gradeID
                            ORDER BY rubrik
                        ");
                        $stmtSpecialRubrik->execute(['gradeID' => $gradeID]);
                        $specialRubriks = $stmtSpecialRubrik->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($specialRubriks as $specialRubrik):
                        ?>
                            <!-- Special Rubrik Row -->
                            <tr>
                                <td colspan="4" style="font-weight: bold; text-align: left; background-color: #f2f2f2;">
                                    <?= htmlspecialchars($specialRubrik['rubrik']) ?>
                                </td>
                            </tr>

                            <?php
                            // Fetch segments for this special rubrik
                            $stmtSpecialSegments = $pdo->prepare("
                                SELECT requirementID, segment, max 
                                FROM documentrequirements 
                                WHERE rubrik = :rubrik 
                                AND requirementID IN (94, 95, 96, 97) 
                                AND gradeID = :gradeID
                            ");
                            $stmtSpecialSegments->execute([
                                'rubrik' => $specialRubrik['rubrik'],
                                'gradeID' => $gradeID
                            ]);
                            $specialSegments = $stmtSpecialSegments->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($specialSegments as $specialSegment):
                                // Check if the segment has been submitted
                                $stmtUploads->execute([
                                    'userID' => $userID,
                                    'awardID' => $awardID,
                                    'requirementID' => $specialSegment['requirementID']
                                ]);
                                $submission = $stmtUploads->fetch(PDO::FETCH_ASSOC);
                                $isSubmitted = $submission && $submission['submitted'] == 1;
                            ?>
                                <!-- Special Segment Row -->
                                <tr>
                                    <td class="left-align"><?= htmlspecialchars($specialSegment['segment']) ?></td>
                                    <td>
                                        <input type="checkbox" <?= $isSubmitted ? 'checked' : '' ?> disabled>
                                    </td>
                                    <td>
                                        <a 
                                            <?php if (!$isSubmitted): ?>
                                                href="upload_document.php?requirementID=<?= $specialSegment['requirementID'] ?>&awardID=<?= $awardID ?>&gradeID=<?= $gradeID ?>"
                                            <?php endif; ?>
                                            class="btn btn-primary" 
                                            <?= $isSubmitted ? 'disabled' : '' ?>>
                                            Klik Sini
                                        </a>
                                    </td>
                                    <td class="center-align"><?= htmlspecialchars($specialSegment['max'] ?? 'N/A') ?> Markah</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>

                        <!-- Markah Penuh -->
                        <tr>
                            <td colspan="3" style="text-align: center; font-weight: bold; background-color: #f9f9f9;">Markah Penuh Penilaian</td>
                            <td style="text-align: center; font-weight: bold; background-color: #f9f9f9;">100 Markah</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Catatan Section -->
                <br><div class="form-group">
                    <label for="catatan" style="text-align: left; font-weight: bold;">Catatan / rujukan tambahan bagi pembuktian pencalonan anugerah ini yang ingin dinyatakan:</label>
                    <br><textarea class="form-control" id="catatan" name="catatan" rows="4" placeholder="Sila masukkan catatan anda di sini..."></textarea>
                </div><br>

                <!-- Confirmation Checkbox -->
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="confirm_data" id="confirm_data" required>
                    <label class="form-check-label" for="confirm_data">Saya mengesahkan bahawa data yang dimasukkan adalah betul.</label>
                </div><br>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary" id="submitBtn">Hantar</button>
            </form>
        </div>
    </div>

    <!-- Success Modal (Bootstrap Modal) -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="successModalLabel">Tahniah !</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Permohonan anda telah dihantar kepada penjurian, inshaAllah semoga semuanya dipermudahkan...
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div><p><p>

    <!-- Footer -->
    <footer>
        <p>&copy; Bahagian Pentadbiran, UiTM Cawangan Perak</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show success modal if the submission is successful
        <?php if ($showSuccessModal): ?>
            var myModal = new bootstrap.Modal(document.getElementById('successModal'));
            myModal.show();
            
            // Redirect to dashboard after a short delay (3 seconds)
            setTimeout(function() {
                window.location.href = "dashboard.php"; // Redirect to the dashboard
            }, 3000); // 3000 milliseconds = 3 seconds
        <?php endif; ?>

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
