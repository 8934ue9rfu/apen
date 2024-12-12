<?php
session_start();

// Check if user is logged in
$user_id = $_SESSION['userID'] ?? null;

if (!$user_id) {
    header("Location: index.php");
    exit();
}

// Check if the userID is set in the query string
if (isset($_GET['id'])) {
    $userID = $_GET['id'];

    try {
        // Connect to the database using PDO
        $conn = new PDO("mysql:host=localhost;dbname=borangapen", "root", "Aleesya_2004");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare and execute query to get details for the selected user
        $stmt = $conn->prepare("
        SELECT 
                users.staffName AS userName,
                users.userID AS userID,
                users.tahunBerkhidmat,
                users.gradeID AS gredID,
                grades.gradeName AS gredName,
                GROUP_CONCAT(DISTINCT awards.awardName SEPARATOR ', ') AS awardName
            FROM 
                users
            LEFT JOIN 
                jury ON users.userID = jury.juriID
            LEFT JOIN 
                awards ON jury.awardID = awards.awardID
            LEFT JOIN 
                grades ON users.gradeID = grades.gradeID
            WHERE 
                users.userID = :userID
            GROUP BY 
                users.userID, users.staffName, users.tahunBerkhidmat, users.gradeID, grades.gradeName;
        ");
        
        $stmt->bindParam(":userID", $userID, PDO::PARAM_INT);
        $stmt->execute();

        // Fetch the data
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$entry) {
            // If no data is found, set default values
            $entry = [
                'userName' => 'N/A',
                'userID' => 'N/A',
                'gredName' => 'N/A',
                'tahunBerkhidmat' => 'N/A',
                'awardName' => 'No awards',
            ];
        }

    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
} else {
    // If no userID is provided in the URL, redirect to the report page or show an error
    header("Location: report.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekod Pemohon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f4f4;
            padding: 20px;
            margin: 0;
        }
        .header-bar {
            background-color: #4b0082; /* Purple color */
            height: 10px;
            width: 100%;
        }
        .paper-content {
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 0 auto;
            text-align: left;
        }
        .content-wrapper {
            padding: 20px;
            text-align: left;
            margin: 0 auto;
            margin-left: 80px;
        }
        .logo-container {
            margin-bottom: 20px;
            text-align: center;
        }
        .logo-container img {
            max-width: 200px;
        }
        .university-details {
            text-align: center;
            font-size: 0.9em;
            color: #333;
            margin-bottom: 20px;
        }
        .header {
            font-size: 2em;
            margin-bottom: 30px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
        }
        .btn-back {
            display: inline-block;
            background-color: #b58868;
            color: #fff;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn-back:hover {
            background-color: #B7B7B7;
        }
        table {
            width: 80%;
            margin: 20px auto;
            border-collapse: collapse;
            text-align: left;
        }
        th, td {
            padding: 8px;
            border: 1px solid black;
        }
    </style>
</head>
<body>

    <div class="paper-content">
        <div class="header-bar"></div>

        <div class="logo-container">
            <img src="logouitm.png" alt="UiTM Logo" style="max-width: 200px;">
        </div>

        <h1 class="header">ANUGERAH PENTADBIR</h1>
        <center><h5>SLIP PERMOHONAN ANUGERAH STAF UITM</h5></center>

        <table style="width: 80%; margin: 20px auto; border-collapse: collapse; border: none;">
            <tr>
                <td style="border: none;"><strong>Nama Staff</strong></td>
                <td style="border: none;"><span style="text-transform: uppercase;">: <?php echo htmlspecialchars($entry['userName']); ?></td>
            </tr>
            <tr>
                <td style="border: none;"><strong>ID Staff</strong></td>
                <td style="border: none;"><span style="text-transform: uppercase;">: <?php echo htmlspecialchars($entry['userID']); ?></td>
            </tr>
            <tr>
                <td style="border: none;"><strong>Gred</strong></td>
                <td style="border: none;"><span style="text-transform: uppercase;">: <?php echo htmlspecialchars($entry['gredName']); ?></td>
            </tr>
            <tr>
                <td style="border: none;"><strong>Tahun Berkhidmat</strong></td>
                <td style="border: none;"><span style="text-transform: uppercase;">: <?php echo htmlspecialchars($entry['tahunBerkhidmat']); ?> Tahun</td>
            </tr>
        </table>
        <div class="header-bar"></div>
    </div>

    <div style="text-align: center; margin-top: 20px;">
        <a href="report.php" class="btn-back">Kembali</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
