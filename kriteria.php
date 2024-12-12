<?php
session_start();

// Create the connection using mysqli
$conn = new mysqli("localhost", "root", "Aleesya_2004", "borangapen");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get awardID from the URL or use session
if (isset($_GET['awardID'])) {
    $awardID = (int)$_GET['awardID'];
    $_SESSION['awardID'] = $awardID; // Update session with the new awardID
} elseif (isset($_SESSION['awardID'])) {
    $awardID = $_SESSION['awardID'];
} else {
    $awardID = 1; // Default to 1 if neither session nor URL parameter is provided
}

// Fetch award name
$awardName = "Document Requirements"; // Default title
$awardQuery = "SELECT awardName FROM awards WHERE awardID = ?";
$awardStmt = $conn->prepare($awardQuery);
$awardStmt->bind_param("i", $awardID);
$awardStmt->execute();
$awardResult = $awardStmt->get_result();
if ($awardRow = $awardResult->fetch_assoc()) {
    $awardName = $awardRow['awardName'];
}

// Fetch rubrik and segment data dynamically based on the awardID
$query = "SELECT dr.rubrik, dr.segment, dr.requirementID, dr.keterangan
          FROM documentrequirements dr
          WHERE dr.awardID = ?
          ORDER BY dr.rubrik, dr.segment";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $awardID);
$stmt->execute();
$result = $stmt->get_result();

// Check if the query returned any rows
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// Group data by rubrik
$groupedData = [];
foreach ($data as $row) {
    $groupedData[$row['rubrik']][] = $row;
}

$stmt->close();

$awardStmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="picture/icon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="picture/icon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="picture/icon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="picture/icon/apple-touch-icon.png">
    <title><?= htmlspecialchars($awardName) ?></title>
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
        .container {
            max-width: 950px;
            margin: 30px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th {
            background-color: #E5D9F2;
            padding: 10px;
        }
        td {
            padding: 8px;
        }
        .bil-column {
            width: 10%;
            text-align: center;
        }
        .segment-column {
            width: 40%;
            text-align: center;
        }
        .details-column {
            width: 50%;
            text-align: left;
        }
        .group-header {
            background-color: #f9f9f9;
            text-align: center;
            font-weight: bold;
        }
        h2 {
            margin-top: 0;
            text-align: center;
            font-size: 1.8em;
            color: #441752;
        }
        @media (max-width: 768px) {
            .container{
                font-size: 12px;
                max-width: 350px;
            }
            h2 {
                font-size: 20px;
            }
        }
        @media (max-width: 480px) {
            .container{
                font-size: 12px;
                max-width: 350px;
            }
            h2 {
                font-size: 20px;
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
        <h2><?= htmlspecialchars($awardName) ?></h2><p>
        <table>
            <thead>
                <tr>
                    <th class="bil-column">Bil</th>
                    <th class="segment-column">Penilaian Rubrik</th>
                    <th class="details-column" style="text-align: center;">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($groupedData)): ?>
                    <?php 
                    $bil = 1; // Initialize the counter for "Bil"
                    foreach ($groupedData as $rubrik => $segments): 
                    ?>
                    <tr class="group-header">
                        <td colspan="3"><?= htmlspecialchars($rubrik) ?></td>
                    </tr>
                    <?php foreach ($segments as $segment): ?>
                        <tr>
                            <td class="bil-column"><?= $bil++ ?></td>
                            <td class="segment-column"><?= htmlspecialchars($segment['segment']) ?></td>
                            <td class="details-column">
                                <ol>
                                    <?php 
                                    // Split the keterangan into lines if needed (assuming it is stored in a multiline string)
                                    $keteranganItems = explode("\n", $segment['keterangan']); 
                                    foreach ($keteranganItems as $item): ?>
                                        <li><?= htmlspecialchars($item) ?></li>
                                    <?php endforeach; ?>
                                </ol>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="3" style="text-align: center;">No data available for this award</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
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
