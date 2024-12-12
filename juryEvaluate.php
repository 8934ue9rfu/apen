<?php
session_start();

// Check for user login
if (!isset($_SESSION['userID']) || !isset($_SESSION['userGradeID'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['userID'] ?? null;
$awardID = $_SESSION['awardID'] ?? null;

if (!$user_id || !$awardID) {
    die("Error: User ID or Award ID not found in session.");
}

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    $pdo = new PDO('mysql:host=localhost;dbname=borangapen', 'root', 'Aleesya_2004');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

      // Initialize an array to hold user submissions based on awardID
    $userSubmissions = [];

    // SQL query to fetch user submissions along with awardName
    $query = "
        SELECT 
            us.submissionID, us.userID, us.documentName, us.submissionDate, us.status, 
            us.peringkat, us.filePath, us.catatan, dr.requirementID, dr.segment,
            a.awardName, u.staffName  -- Added userName from the users table
        FROM 
            usersubmissions us
        JOIN 
            documentrequirements dr ON us.requirementID = dr.requirementID
        JOIN 
            awards a ON dr.awardID = a.awardID
        JOIN 
            users u ON us.userID = u.userID  -- Join users table to get userName
        WHERE 
            dr.awardID = :awardID
        ORDER BY 
            us.userID
        ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([':awardID' => $awardID]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /// Organize the submissions by user and segment, including awardName
    $awardName = ""; // Initialize awardName variable
    foreach ($rows as $row) {
        $userID = $row['userID'];
        $staffName = $row['staffName'];
        $segment = $row['segment'];
        $requirementID = $row['requirementID']; // Include requirementID

        // Assign awardName if not already set
        if (empty($awardName)) {
            $awardName = $row['awardName'];
        }

        if (!isset($userSubmissions[$userID])) {
            $userSubmissions[$userID] = [
                'userID' => $userID,
                'catatan' => $row['catatan'], // Add 'catatan' at the user level
                'submissions' => []
            ];
        }

        // Include requirementID in the submission array
        $userSubmissions[$userID]['submissions'][$segment][] = [
            'submissionID' => $row['submissionID'],
            'documentName' => $row['documentName'],
            'submissionDate' => $row['submissionDate'],
            'status' => $row['status'],
            'peringkat' => $row['peringkat'],
            'filePath' => $row['filePath'],
            'requirementID' => $requirementID, // Store requirementID here
        ];
    }
    
    // Function to get the maximum marks for a given segment and requirementID
    function getMaxMarksForSegment($requirementID, $awardID) {
        global $pdo;

        $query = "
            SELECT max
            FROM documentrequirements
            WHERE requirementID = :requirementID AND awardID = :awardID
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute([':requirementID' => $requirementID, ':awardID' => $awardID]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['max'] ?? 100; // Default to 100 if no max found
    }

    // Check if form has been submitted
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['marks']) && is_array($_POST['marks']) && isset($_POST['comments']) && is_array($_POST['comments'])) {
            $marks = $_POST['marks'];
            $comments = $_POST['comments'];
            $juriID = $_SESSION['userID'];
            $markStatus = "EVALUATED";

            foreach ($marks as $applicantId => $applicantMarks) {
                $totalMark = array_sum($applicantMarks);
                $juryComment = isset($comments[$applicantId]) ? htmlspecialchars($comments[$applicantId]) : '';

                // Insert or update jury table
                $stmt = $pdo->prepare("
                    INSERT INTO jury (userID, mark, juriKomen, juriID, markStatus, awardID)
                    VALUES (:userID, :totalmark, :juryComment, :juriID, :markStatus, :awardID)
                    ON DUPLICATE KEY UPDATE 
                        mark = :totalmark, 
                        juriKomen = :juryComment, 
                        juriID = :juriID, 
                        markStatus = :markStatus, 
                        awardID = :awardID
                ");

                $stmt->execute([
                    ':userID' => $applicantId,
                    ':totalmark' => $totalMark,
                    ':juryComment' => $juryComment,
                    ':juriID' => $juriID,
                    ':markStatus' => $markStatus,
                    ':awardID' => $awardID
                ]);

                // Calculate total marks and evaluators for markah table
                $stmt = $pdo->prepare("
                    SELECT 
                        SUM(mark) AS total_marks, 
                        COUNT(DISTINCT juriID) AS total_evaluators
                    FROM jury
                    WHERE userID = :userID AND awardID = :awardID
                ");

                $stmt->execute([
                    ':userID' => $applicantId,
                    ':awardID' => $awardID
                ]);

                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                $totalMarks = $result['total_marks'] ?? 0;
                $totalEvaluators = $result['total_evaluators'] ?? 1;

                // Calculate average mark and round down
                $finalMark = floor($totalMarks / $totalEvaluators);

                // Update the markah table
                $stmt = $pdo->prepare("
                    INSERT INTO markah (userID, awardID, totalMark)
                    VALUES (:userID, :awardID, :totalMark)
                    ON DUPLICATE KEY UPDATE 
                        totalMark = :totalMark
                ");

                $stmt->execute([
                    ':userID' => $applicantId,
                    ':awardID' => $awardID,
                    ':totalMark' => $finalMark
                ]);
            }
            // Redirect to the jury dashboard to prevent duplicate submissions
            header("Location: jurydashboard.php");
            exit();
        }
    }

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penilaian Anugerah</title>
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
            padding: 20px;
            margin: 20px;
            border-radius: 10px; 
        }
        .card-title {
            color: #5b4636; 
            margin-bottom: 15px;
            font-size: 1.5em;
            text-align: center;
        }
        .btn-primary {
            background-color: #433878;
            border-color: #433878;
            display: block;
            margin: 0 auto;
            padding: 10px 20px;
        }
        .btn-primary:hover {
            background-color: #441752; 
            border-color: #441752;
        }
        .btn[disabled] {
            background-color: #ccc;
            border-color: #ccc;
            cursor: not-allowed;
        }
        .btn-primary:focus, .btn-primary:active, .btn-primary:focus:active, .btn-primary:focus-visible {
            outline: none !important; 
            box-shadow: none !important; 
            background-color: #433878 !important; 
            border-color: #433878 !important;
        }
        .alert-info {
            background-color: #E5D9F2; 
            color: rgb(127, 82, 131); 
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            border-color: rgb(127, 82, 131);
        }
        h1, h2, h4 {
            color: #441752; 
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #ffffff; 
            margin-bottom: 20px;
            border-radius: 5px;
            overflow: hidden; 
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px 8px;
            text-align: center;
        }
        th {
            background-color: #d3bca2; 
            color: black;
            font-weight: bold;
        }
        td {
            color: black;
        }
        .total {
            font-weight: bold;
        }
        .pdf-list a {
            display: block;
            margin-bottom: 5px;
            color: #5b4636;
            text-decoration: none;
        }
        .pdf-list a:hover {
            text-decoration: underline;
        }
        .text-center {
            text-align: center;
        }
        textarea {
            width: 100%;
            resize: vertical;
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        input[readonly] {
            background-color: #f4f4f4;
        }
        footer {
            background-color: #441752; 
            color: white;
            text-align: center;
            padding: 15px;
            margin-top: auto; 
            font-size: 0.9em;
        }
        footer p {
            margin: 0;
        }
        .title-center {
            text-align: center;
            color: black; 
            margin-top: 20px;
        }
        .responsive-input {
            width: 80px;
            font-weight: bold;
            text-align: center;
        }
        @media (max-width: 768px) {
            .navbar { 
                flex-direction: column;
                padding: 9px; 
            }

            .navbar-brand, .nav-link {
                font-size: 17px;
            }
            .card {
                margin: 10px;
                padding: 15px;
            }
            table {
                font-size: 6px;
                margin: 0;
                width: 80%;
                margin-left: auto;
                margin-right: auto;
            }
            th, td {
                padding: 2px 1px;
                text-align: center;
            }
            h1, h2, h4 {
                font-size: 18px;
                margin: 5px 0;
            }
            textarea {
                padding: 6px;
                font-size: 14px;
            }
 
            .responsive-input {
                width: 50px; 
                font-size: 12px; 
            }
            .container {
                margin-left: auto; 
                margin-right: auto; 
            }
            footer {
                font-size: 14px;
                padding: 8px;
            }   
        }
        @media (max-width: 992px) {
            .card {
                padding: 18px;
            }
            table {
                font-size: 6px;
                margin-left: auto; 
                margin-right: auto; 
                width: 100%;      
                border-collapse: collapse; 
            }
            .navbar-brand, .nav-link {
                font-size: 17px;
            }
            .responsive-input {
                width: 17px;
                font-size: 8px;
            }
            .container {
                margin-left: auto; 
                margin-right: auto;
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

    <div class="container mt-4" style="max-width: 100%;">
        <div class="alert alert-info" role="alert">
            Sila pastikan pemarkahan adalah <b>muktamad</b> sebelum klik Hantar.
        </div>
    </div>

    <div class="container my-4">
    <h2 class="text-center">Penilaian Markah  <?php echo htmlspecialchars($awardName); ?></h2><br>
        <form method="POST">
            <table class="table table-bordered" style="border-collapse: collapse;">
                <thead>
                    <tr style= "background-color: #A888B5;">
                        <th style="width: 150px;">No Pekerja</th>
                        <th style="width: 150px;">Nama</th>
                        <th style="width: 150px;">Catatan Staf</th>
                        <th style="width: 150px;">Penilaian Rubrik</th>
                        <th>Keterangan</th>
                        <th>Nama Dokumen </th>
                        <th>Dokumen Sokongan (PDF)</th>
                        <th style="width: 100px;">Markah</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($userSubmissions)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; font-weight: bold; color: red;">Tiada Permohonan</td>
                </tr>
                    <?php else: ?>
                        <?php foreach ($userSubmissions as $userID => $userData): ?>
                            <?php 
                                $firstEntryForUser = true; 
                                $numRowsForUser = array_sum(array_map('count', $userData['submissions']));
                            ?>
                            <?php foreach ($userData['submissions'] as $segment => $submissions): ?>
                                <?php $numRowsForSection = count($submissions); ?>
                                <?php foreach ($submissions as $index => $submission): ?>
                                    <tr>
                                        <?php if ($firstEntryForUser): ?>
                                            <td rowspan="<?php echo $numRowsForUser; ?>" style="text-align: center;"><strong><?php echo htmlspecialchars($userID); ?></strong></td>
                                            <td rowspan="<?php echo $numRowsForUser; ?>" style="text-align: center;"><?php echo htmlspecialchars($staffName); ?></td>
                                            <td rowspan="<?php echo $numRowsForUser; ?>" style="text-align: center;">
                                                <?php echo !empty($userData['catatan']) ? htmlspecialchars($userData['catatan']) : "Tiada Catatan"; ?>
                                            </td>
                                            <?php $firstEntryForUser = false; ?>
                                        <?php endif; ?>
                                        <?php if ($index === 0): ?>
                                            <td rowspan="<?php echo $numRowsForSection; ?>" style="text-align: center;"><?php echo htmlspecialchars($segment); ?></td>
                                        <?php endif; ?>

                                        <td><?php echo htmlspecialchars($submission['peringkat']); ?></td>
                                        <td><?php echo htmlspecialchars($submission['documentName']); ?></td>
                                        <td>
                                            <?php if (!empty($submission['filePath'])): ?>
                                                <a href="<?php echo htmlspecialchars($submission['filePath']); ?>" target="_blank">Document</a>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Markah input field -->
                                        <?php   // Fetch the max mark for the current segment and requirementID
                                            $maxMark = getMaxMarksForSegment($submission['requirementID'], $awardID);
                                        ?>
                                        <?php if ($index === 0): ?>
                                            <td rowspan="<?php echo $numRowsForSection; ?>" class="text-center">
                                                <input type="number" name="marks[<?php echo $userID; ?>][<?php echo $segment; ?>]" 
                                                min="0" max="<?php echo $maxMark; ?>" required value="0" 
                                                class="responsive-input" >
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>

                            <!-- Move Total Marks Row to below 'Dokumen' -->
                            <tr style="background-color: #f9f9f9; font-weight: bold;">
                                <td colspan="2" style="text-align: right;">Catatan Juri:</td>
                                <td colspan="3">
                                    <textarea name="comments[<?php echo $userID; ?>]" rows="1" style="width: 100%;"></textarea>
                                </td>
                                <td colspan="2" style="text-align: right;"> Jumlah Markah Penilaian:</td>
                                <td style="text-align: center;">
                                    <input type="number" id="totalMark_<?php echo $userID; ?>" 
                                          value="<?php 
                                            $totalMarks = 0;
                                            foreach ($userData['submissions'] as $section => $entries) {
                                            foreach ($entries as $entry) {
                                            // Add marks for each submission, with constraint on max marks
                                            $mark = isset($_POST['marks'][$userID][$section]) ? (int)$_POST['marks'][$userID][$section] : 0;
                                            $maxMark = getMaxMarksForSegment($entry['requirementID'], $awardID);
                                            $totalMarks += min($mark, $maxMark); // Constrain total marks by max allowed
                                            }
                                        }
                                        echo $totalMarks;
                                    ?>" 
                                    readonly class="responsive-input">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <button type="submit" class="btn btn-primary">Hantar</button>
        </form>
    </div>

    <footer>
        <p>&copy; Bahagian Pentadbiran, UiTM Cawangan Perak</p>
    </footer>

    <script>
        document.querySelector('form').addEventListener('submit', function(event) {
        // Show a confirmation dialog
        if (!confirm("Adakah anda selesai menilai semua pemohon?")) {
            // If the user clicks "Cancel," prevent form submission
            event.preventDefault();
        }
        });

        document.addEventListener("DOMContentLoaded", function() {
            // Get all mark input elements
            const markInputs = document.querySelectorAll('input[type="number"][name^="marks"]');

            // Add event listener to each mark input for real-time calculation
            markInputs.forEach(input => {
                input.addEventListener("input", function() {
                    const userId = input.name.match(/\[(.*?)\]/)[1]; // Extract userId from input name
                    calculateTotal(userId); // Call the calculateTotal function
                });
            });
        });

        function calculateTotal(userId) {
            console.log("Penilaian markah untuk ID:", userId);

            // Select all mark input elements for the specific user
            const inputs = document.querySelectorAll(`input[name^="marks[${userId}]"]`); // Fixed selector

            // Check if any inputs were found
            if (inputs.length === 0) {
                console.warn("Tiada elemen input ditemui untuk ID:", userId);
                return; // Exit the function if no inputs are found
            }

            // Calculate the total
            let total = Array.from(inputs).reduce((sum, input) => {
                const value = parseFloat(input.value) || 0; // Default to 0 if not a number
                return sum + value;
            }, 0);

            // Update the total mark input field for the user
            const totalMarkInput = document.getElementById('totalMark_' + userId);
            if (totalMarkInput) {
                totalMarkInput.value = total; // Display total
            } else {
                console.error("Input markah total tidak dijumpai untuk:", userId);
            }
        }

        function showCompletionAlert() {
            alert('Penilaian telah disimpan. Markah dan ulasan telah dikemas kini ke penjurian.');
            window.location.href = 'jurydashboard.php'; // Redirect to the desired page
        }

        // Function to handle form submission
        function handleFormSubmission(event) {
            event.preventDefault(); // Prevent the form's default submit action

            // Use AJAX to submit form data if necessary
            // For simplicity, this example just calls the alert and redirect
            showCompletionAlert(); // Show the alert and redirect the user
        }

        // Attach the form submission handler when the form is submitted
        document.getElementById("evaluationForm").addEventListener("submit", handleFormSubmission);

        function confirmSubmission(awardID, gradeID) {
            window.location.href = "document_submission.php?awardID=" + awardID + "&gradeID=" + gradeID;
        }
        
        function confirmLogout() {
            // Display confirmation popup
            var isConfirmed = confirm("Adakah anda pasti ingin log keluar?");
            
            // If user confirms, proceed with log out
            if (isConfirmed) {
                window.location.href = "logout.php"; // Redirect to logout page
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
