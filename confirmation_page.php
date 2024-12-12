<?php
session_start();

// Check if awardID and gradeID are in the query string
if (isset($_GET['awardID']) && isset($_GET['gradeID'])) {
    $awardID = $_GET['awardID'];
    $gradeID = $_GET['gradeID'];
} else {
    // Redirect back to the document submission page if no awardID or gradeID is provided
    header("Location: document_submission.php");
    exit;
}

// Database connection
$pdo = new PDO('mysql:host=localhost;dbname=borangapen', 'root', 'Aleesya_2004');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get the award name based on awardID
$sqlAward = "SELECT awardName FROM Awards WHERE awardID = :awardID";
$stmtAward = $pdo->prepare($sqlAward);
$stmtAward->execute(['awardID' => $awardID]);
$awardName = $stmtAward->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation</title>
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
        .container {
            display: flex;
            justify-content: center;
            margin-top: 50px;
        }
        .main-content {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            border: 1px solid #ddd;
            text-align: center;
            width: 50%;
        }
        .btn-primary {
            background-color: #b58868;
            border-color: #b58868;
        }
        .btn-primary:hover {
            background-color: #a37555;
            border-color: #a37555;
        }
        footer {
            background-color: #d3bca2;
            color: #5b4636;
            text-align: center;
            padding: 15px;
            margin-top: auto;
        }
        footer p {
            margin: 0;
        }
    </style>
</head>
<body>

<!-- Top Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Anugerah Pentadbir</a>
    </div>
</nav>

<div class="container">
    <div class="main-content">
        <h2>Submission Confirmed</h2>
        <p>Your documents for <strong><?php echo htmlspecialchars($awardName); ?></strong> have been successfully submitted!</p>
        <p>You will be notified once your submission is reviewed.</p>
        <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
    </div>
</div>

<!-- Footer -->
<footer>
    <p>&copy; Bahagian Pentadbiran, UiTM Cawangan Perak</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
