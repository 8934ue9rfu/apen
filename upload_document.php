<?php
session_start();

// Check if userID exists in the session
if (!isset($_SESSION['userID'])) {
    echo "User not logged in.";
    exit;
}

$userID = $_SESSION['userID']; // Get userID from session

// Database connection
$conn = new mysqli('localhost', 'root', 'Aleesya_2004', 'borangapen');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get URL parameters with default fallback
$requirementID = isset($_GET['requirementID']) ? $_GET['requirementID'] : null;
$awardID = isset($_GET['awardID']) ? $_GET['awardID'] : null;
$gradeID = isset($_GET['gradeID']) ? $_GET['gradeID'] : null;

// If any of the required parameters is missing, display an error
if (!$requirementID || !$awardID || !$gradeID) {
    echo "Missing required parameters.";
    exit;
}

// Fetch segment details for this requirement ID
$query = "
    SELECT dr.segment, dr.max
    FROM documentrequirements AS dr
    WHERE dr.requirementID = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $requirementID);
$stmt->execute();
$result = $stmt->get_result();

// Check if a segment was found
if ($result->num_rows > 0) {
    $segment = $result->fetch_assoc();
    $segmentType = $segment['segment'];
} else {
    $segmentType = null; // No segment found
}

// Handle the Hantar button form submission to update the submitted status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hantar'])) {
    // Update the 'submitted' column to 1 for all entries matching userID, awardID, and requirementID
    $updateQuery = "
        UPDATE usersubmissions 
        SET submitted = 1 
        WHERE userID = ? AND awardID = ? AND requirementID = ?
    ";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("iii", $userID, $awardID, $requirementID);
    $updateStmt->execute();

    if ($updateStmt->affected_rows > 0) {
        // Redirect the user to the document_submission.php page
        header("Location: document_submission.php?awardID=$awardID&gradeID=$gradeID");
        exit; // Ensure no further code runs after the redirect
    } else {
        echo "Error: Could not update submitted status.";
    }
}

// Define the mapping for segment types to labels
$segmentLabels = [
    "Pangkat / Pingat Kebesaran" => "Peringkat",
    "Penghargaan / Pengiktirafan" => "Peringkat",
    "Pakar Rujuk" => "Peringkat",
    "Impak Aktiviti / Sumbangan" => "Peringkat",
    "Peranan Terhadap Aktiviti / Sumbangan" => "Peranan",
    "Penglibatan Lain" => "Detail",
    "Penulisan" => "Jenis Penulisan",
    "Anugerah dan Pengiktirafan" => "Peringkat",
    "Peranan Dalam Penulisan" => "Peranan",
    "Peranan Dan Peringkat Penyertaan" => "Peranan",
    "Anugerah" => "Peringkat",
    "Pendaftaran Harta Intelek" => "Peringkat",
    "Peningkatan Kualiti Dan Impak Kepada Komuniti" => "Impak Kepada",
    "Impak / Sumbangan Aktiviti" => "Sumbangan",
    "Keberkesanan Kos" => "Projek",
    "Jaringan Kerjasama" => "Detail",
    "Peringkat Keahlian" => "Peringkat Keahlian",
    "Peranan" => "Peranan",
    "Peranan Khas Khidmat Komuniti" => "Peranan",
    "Penarafan" => "Peringkat",
    "Peranan Sukan & Rekreasi" => "Peranan",
    "Penyertaan" => "Peringkat",
    "Aktiviti / Sumbangan Dalam Tugas Rasmi" => "Peringkat",
    "Aktiviti / Sumbangan Dalam Kemasyarakatan / Luar Tugas Rasmi" => "Peringkat",
    "Penglibatan Dalam Aktiviti / Sumbangan" => "Peranan",
    "Anugerah / Pengiktirafan" => "Peringkat",
    "Peringkat Peningkatan Kualiti Dan Impak" => "Impak Kepada",
    "Impak / Sumbangan Projek Dan Inovasi" => "Sumbangan",
    "Lain-lain" => "Keterangan",
];

// Set a default label if no specific mapping exists
$label = isset($segmentLabels[$segmentType]) ? $segmentLabels[$segmentType] : "Keterangan";

// Handle file uploads and saving data to the database
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['documents'])) {
    $files = $_FILES['documents'];  // Array of files
    $uploadDir = "uploads/"; // Define the upload directory

    // Loop through each uploaded file
    for ($i = 0; $i < count($files['name']); $i++) {
        $fileName = $files['name'][$i];
        $fileTmpName = $files['tmp_name'][$i];
        $uploadFile = $uploadDir . basename($fileName);

        // Get the document name and dropdown option selected by the user
        $documentName = $_POST['documentName'][$i];
        $additionalPeringkat = isset($_POST['additionalPeringkat'][$i]) ? $_POST['additionalPeringkat'][$i] : null;
        $dropdownValue = isset($_POST['peringkat'][$i]) ? $_POST['peringkat'][$i] :
                (isset($_POST['peranan'][$i]) ? $_POST['peranan'][$i] :
                (isset($_POST['detail'][$i]) ? $_POST['detail'][$i] :
                (isset($_POST['penulisan'][$i]) ? $_POST['penulisan'][$i] : 
                (isset($_POST['peranankreativiti'][$i]) ? $_POST['peranankreativiti'][$i] :
                (isset($_POST['perananpenulisan'][$i]) ? $_POST['perananpenulisan'][$i] :
                (isset($_POST['sumbangan'][$i]) ? $_POST['sumbangan'][$i] :
                (isset($_POST['kos'][$i]) ? $_POST['kos'][$i] :
                (isset($_POST['jaringan'][$i]) ? $_POST['jaringan'][$i] :
                (isset($_POST['peranangkk'][$i]) ? $_POST['peranangkk'][$i] :
                (isset($_POST['sukan'][$i]) ? $_POST['sukan'][$i] :
                (isset($_POST['peranansukan'][$i]) ? $_POST['peranansukan'][$i] :
                (isset($_POST['peringkatharta'][$i]) ? $_POST['peringkatharta'][$i] : 
                (isset($_POST['lainlain'][$i]) ? $_POST['lainlain'][$i] : 
                (isset($_POST['additionalPeringkat'][$i]) ? $_POST['additionalPeringkat'][$i] : null))))))))))))));

        // Check if file is uploaded without errors
        if (move_uploaded_file($fileTmpName, $uploadFile)) {
            // Insert the submission directly into the database with awardID
            $query = "INSERT INTO usersubmissions (userID, requirementID, awardID, status, documentName, peringkat, filePath, additionalPeringkat) 
                      VALUES (?, ?, ?, 'Submitted', ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iiissss", $userID, $requirementID, $awardID, $documentName, $dropdownValue, $uploadFile, $additionalPeringkat);
            $stmt->execute();
        } else {
            echo "Failed to upload file: $fileName.<br>";
        }
    }
}

// Handle delete action
if (isset($_GET['delete']) && $requirementID && $awardID && $gradeID) {
    $documentNameToDelete = $_GET['delete'];

    // Delete from the database
    $query = "DELETE FROM usersubmissions WHERE userID = ? AND requirementID = ? AND documentName = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iis", $userID, $requirementID, $documentNameToDelete);
    $stmt->execute();
}

// Fetch user submissions from the database
$query = "
    SELECT documentName, peringkat, additionalPeringkat, filePath 
    FROM usersubmissions
    WHERE userID = ? AND requirementID = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $userID, $requirementID);
$stmt->execute();
$result = $stmt->get_result();
$submissionsFromDB = [];
while ($row = $result->fetch_assoc()) {
    $submissionsFromDB[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Documents</title>
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
        .container { 
            margin-top: 20px; 
            background-color: #ffffff; 
            border-radius: 10px; 
            padding: 20px; 
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); 
        }
        .btn-primary { 
            background-color: rgb(152, 125, 154); 
            border-color: rgb(152, 125, 154);  
            margin: 0 auto; 
            }
        .btn-primary:hover { 
            background-color: rgb(105, 79, 142); 
            border-color: rgb(105, 79, 142); 
        }
        .alert-info { 
            background-color: #f0e0c9; 
            color: #5b4636; 
            border-color: #b58868;
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
        .btn[disabled] { 
            background-color: #ccc; 
            border-color: #ccc; 
            cursor: not-allowed; 
        }
        label {
            margin-bottom: 5px;
            display: inline-block;
        }
        select {
            width: 100%; 
            padding: 10px; 
            font-size: 14px; 
            border: 1px solid #ccc; 
            border-radius: 5px; 
            background-color: #f9f9f9; 
            color: #333; 
            transition: border-color 0.3s;
        }
        select:focus {
            border-color: #007BFF; 
            outline: none;
        }
        select {
            margin-bottom: 10px;
        }
        .btn-primary:focus, .btn-primary:active, .btn-primary:focus:active, .btn-primary:focus-visible {
            outline: none !important; 
            box-shadow: none !important; 
            background-color: rgb(105, 79, 142) !important; 
            border-color: rgb(105, 79, 142) !important;
        }
        @media (max-width: 768px) {
            .container{
                font-size: 12px;
                max-width: 350px;
            }
            h2 {
                font-size: 20px;
            }
            table {
                font-size: 10px;
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
            table {
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
            <h2 class="mb-4"><?php echo htmlspecialchars($segmentType ?? 'Tiada segmen ditemui.'); ?></h2>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="documentName[]" class="form-label">Nama Dokumen:</label>
                    <input type="text" class="form-control" name="documentName[]" required>
                </div>

                <!-- Display the dynamic label based on segment type -->
                <div class="mb-3">
                    <label for="specificDropdown" class="form-label"><?php echo htmlspecialchars($label); ?>:</label>

                    <?php if ($segmentType == "Pangkat / Pingat Kebesaran" || $segmentType == "Penghargaan / Pengiktirafan" || $segmentType == "Pakar Rujuk" || $segmentType == "Impak Aktiviti / Sumbangan" || $segmentType == "Anugerah dan Pengiktirafan" || $segmentType == "Anugerah" || $segmentType == "Peningkatan Kualiti Dan Impak Kepada Komuniti" || $segmentType == "Peringkat Keahlian" || $segmentType == "Penyertaan" || $segmentType == "Aktiviti / Sumbangan Dalam Tugas Rasmi" || $segmentType == "Aktiviti / Sumbangan Dalam Kemasyarakatan / Luar Tugas Rasmi" || $segmentType == "Anugerah / Pengiktirafan" || $segmentType == "Peringkat Peningkatan Kualiti Dan Impak"): ?>
                        <select name="peringkat[]" required>
                            <option value="Sila Pilih">Sila Pilih</option>
                            <option value="Antarabangsa">Antarabangsa</option>
                            <option value="Kebangsaan">Kebangsaan</option>
                            <option value="Negeri">Negeri</option>
                            <option value="Komuniti">Komuniti</option>
                            <option value="Universiti">Universiti</option>
                        </select><br><br>

                        <?php elseif ($segmentType == "Pendaftaran Harta Intelek"): ?>
                            <select name="peringkatharta[]" required>
                                <option value="Sila Pilih">Sila Pilih</option>
                                <option value="Peringkat Pendaftaran Penuh">Peringkat Pendaftaran Penuh</option>
                                <option value="Peringkat Pra Pendaftaran">Peringkat Pra Pendaftaran</option>
                            </select><br><br>

                        <?php elseif ($segmentType == "Penarafan"): ?>
                            <select name="sukan[]" required>
                                <option value="Sila Pilih">Sila Pilih</option>
                                <option value="Emas">Emas</option>
                                <option value="Perak">Perak</option>
                                <option value="Gangsa">Gangsa</option>
                                <option value="Lain-lain Penarafan">Lain-lain Penarafan</option>
                            </select><br><br>

                        <?php elseif ($segmentType == "Peranan Sukan & Rekreasi"): ?>
                            <select name="peranansukan[]" required>
                                <option value="Sila Pilih">Sila Pilih</option>
                                <option value="Atlet">Atlet</option>
                                <option value="Pengurusan/ Penganjur/ Pengadil">Pengurusan / Penganjur / Pengadil</option>
                                <option value="Urusetia">Urusetia</option>
                                <option value="Sukarelawan">Sukarelawan</option>
                            </select><br><br>

                        <?php elseif ($segmentType == "Jaringan Kerjasama"): ?>
                            <select name="jaringan[]" required>
                                <option value="Sila Pilih">Sila Pilih</option>
                                <option value="Jaringan Kerjasama Dijalankan (Dalam UiTM)">Jaringan Kerjasama Dijalankan (Dalam UiTM)</option>
                                <option value="Jaringan Kerjasama Dijalankan (Luar UiTM)">Jaringan Kerjasama Dijalankan (Luar UiTM)</option>
                            </select><br><br>

                        <?php elseif ($segmentType == "Impak / Sumbangan Aktiviti" || $segmentType == "Impak / Sumbangan Projek Dan Inovasi"): ?>
                            <select name="sumbangan[]" required>
                                <option value="">Sila pilih</option>
                                <option value="Aktiviti Yang Menjana Pendapatan">Aktiviti Yang Menjana Pendapatan</option>
                                <option value="Aktiviti Yang Meningkatkan Kecekapan Dan Memudahkan Cara Kerja">Aktiviti Yang Meningkatkan Kecekapan Dan Memudahkan Cara Kerja</option>
                                <option value="Projek Yang Digunapakai Oleh Agensi Luar">Projek Yang Digunapakai Oleh Agensi Luar</option>
                            </select><br><br>

                        <?php elseif ($segmentType == "Peranan Khas Khidmat Komuniti"): ?>
                            <select name="peranangkk[]" required>
                                <option value="">Sila pilih</option>
                                <option value="Penyertaan Mana-Mana Ahli Badan Sukarela Beruniform Yang Diiktiraf Oleh Kerajaan ">Penyertaan Mana-Mana Ahli Badan Sukarela Beruniform Yang Diiktiraf Oleh Kerajaan</option>
                                <option value="Wakil Tetap Universiti Ke Agensi Kerajaan Dan Badan NGO Yang Diiktiraf">Wakil Tetap Universiti Ke Agensi Kerajaan Dan Badan NGO Yang Diiktiraf</option>
                                <option value=">Penyertaan Sebagai Jawatankuasa Masyarakat Setempat">Penyertaan Sebagai Jawatankuasa Masyarakat Setempat</option>
                                <option value="Ahli Penasihat / Jawatankuasa Kepada Badan Kerajaan Atau Swasta Di Peringkat Antarabangsa/ Kerajaan">Ahli Penasihat / Jawatankuasa Kepada Badan Kerajaan Atau Swasta Di Peringkat Antarabangsa/ Kerajaan</option>
                            </select><br><br>

                        <?php elseif ($segmentType == "Keberkesanan Kos"): ?>
                            <select name="kos[]" required>
                                <option value="">Sila pilih</option>
                                <option value="Projek Dengan Penjimatan RM10, 000.00 Atau Lebih">Projek Dengan Penjimatan RM10, 000.00 Atau Lebih</option>
                                <option value="Projek Dengan Penjimatan  Kurang RM10, 000.00">Projek Dengan Penjimatan  Kurang RM10, 000.00</option>
                            </select><br><br>

                        <?php elseif ($segmentType == "Peranan Terhadap Aktiviti / Sumbangan" || $segmentType == "Peranan" || $segmentType == "Penglibatan Dalam Aktiviti / Sumbangan"): ?>
                            <select name="peranan[]" required>
                                <option value="Sila Pilih">Sila Pilih</option>
                                <option value="Ketua / Pengerusi">Ketua / Pengerusi</option>
                                <option value="JK Induk">JK Induk</option>
                                <option value="AJK">AJK</option>
                                <option value="Ahli">Ahli</option>
                                <option value="Peserta">Peserta</option>
                            </select><br><br>

                        <?php elseif ($segmentType == "Peranan Dan Peringkat Penyertaan"): ?>
                            <select name="peranankreativiti[]" required>
                                <option value="Sila Pilih">Sila Pilih</option>
                                <option value="Ketua Projek Di Peringkat Antarabangsa">Ketua Projek Di Peringkat Antarabangsa</option>
                                <option value="Ahli Bersama Projek Di Peringkat Antarabangsa">Ahli Bersama Projek Di Peringkat Antarabangsa</option>
                                <option value="Ketua Projek Di Peringkat Universiti">Ketua Projek Di Peringkat Universiti</option>
                                <option value="Ahli Bersama Projek Di Peringkat Universiti">Ahli Bersama Projek Di Peringkat Universiti</option>
                            </select><br><br>

                        <?php elseif ($segmentType == "Penglibatan Lain"): ?>
                            <select name="detail[]" required>
                                <option value="">Sila pilih</option>
                                <option value="Perunding Kepada Projek / Program">Perunding Kepada Projek / Program</option>
                                <option value="Penglibatan Sebagai Penilai Pertandingan / Program">Penglibatan Sebagai Penilai Pertandingan / Program</option>
                                <option value="Penglibatan Sebagai Penceramah">Penglibatan Sebagai Penceramah</option>
                                <option value="Penglibatan Sebagai Ahli Penasihat Kepada Projek / Program">Penglibatan Sebagai Ahli Penasihat Kepada Projek / Program</option>
                                <option value="Keahlian Dalam Badan Profesional / Iktisas">Keahlian Dalam Badan Profesional / Iktisas</option>
                                <option value="Penyertaan Dalam Bidang Sukan / Kesenian / Badan Sukarela / Atau Badan NGO Yang Diiktiraf Oleh Kerajaan">Penyertaan Dalam Bidang Sukan / Kesenian / Badan Sukarela / Atau Badan NGO Yang Diiktiraf Oleh Kerajaan</option>
                            </select><br><br>

                        <?php elseif ($segmentType == "Penulisan"): ?>
                            <select name="penulisan[]" required>
                                <option value="">Sila pilih</option>
                                <option value="Artikel dalam Jurrnal bertaraf ISI dengan faktor impak">Artikel dalam Jurrnal bertaraf ISI dengan faktor impak</option>
                                <option value="Artikel dalam Jurnal bertaraf Scopus">Artikel dalam Jurnal bertaraf Scopus</option>
                                <option value="Artikel dalam Jurnal Kategori B">Artikel dalam Jurnal Kategori B</option>
                                <option value="Artikel dalam Prosiding Persidangan / Seminar Antarabangsa berwibawa / berwasit">Artikel dalam Prosiding Persidangan / Seminar Antarabangsa berwibawa / berwasit</option>
                                <option value="Artikel dalam Prosiding Persidangan / Seminar Kebangsaan / Universiti berwibawa / berwasit">Artikel dalam Prosiding Persidangan / Seminar Kebangsaan / Universiti berwibawa / berwasit</option>
                                <option value="Buku Karya Asli yang diterbitkan di peringkat Antarabangsa">Buku Karya Asli yang diterbitkan di peringkat Antarabangsa</option>
                                <option value="Buku Karya Asli yang diterbitkan di peringkat Kebangsaan">Buku Karya Asli yang diterbitkan di peringkat Kebangsaan</option>
                                <option value="Buku Suntingan / Edisi Baharu">Buku Suntingan / Edisi Baharu</option>
                                <option value="Buku Terjemahan">Buku Terjemahan</option>
                                <option value="Bab dalam Buku">Bab dalam Buku</option>
                                <option value="Paten atau Projek yang menghasilkan ilmu / kaedah baru">Paten atau Projek yang menghasilkan ilmu / kaedah baru</option>
                                <option value="Modul Pengajaran">Modul Pengajaran</option>
                                <option value="Artikel untuk Penilaian Jurnal / Prosiding Antarabangsa">Artikel untuk Penilaian Jurnal / Prosiding Antarabangsa</option>
                                <option value="Artikel untuk Penilaian Jurnal / Prosiding Kebangsaan">Artikel untuk Penilaian Jurnal / Prosiding Kebangsaan</option>
                                <option value="Laporan Teknikal / Penulisan Umum yang diterbitkan">Laporan Teknikal / Penulisan Umum yang diterbitkan</option>
                                <option value="Penilaian Buku">Penilaian Buku</option>
                                <option value="Menghasilkan poster persidangan peringkat Antarabangsa / Kebangsaan">Menghasilkan poster persidangan peringkat Antarabangsa / Kebangsaan</option>
                            </select><br><br>

                        <?php elseif ($segmentType == "Peranan Dalam Penulisan"): ?>
                            <select name="perananpenulisan[]" required>
                                <option value="">Sila pilih</option>
                                <option value="Penulis Pertama">Penulis Pertama</option>
                                <option value="Penulis Kedua (bersama)">Penulis Kedua (bersama)</option>
                                <option value="Penulis Ketiga dan seterusnya (bersama)">Penulis Ketiga dan seterusnya (bersama)</option>
                            </select><br><br>

                        <?php elseif ($segmentType == "Lain-lain"): ?>
                            <label for="lainlain[]" class="form-label"></label>
                            <textarea type="text" class="form-control" name="lainlain[]"></textarea><br>

                        <?php else: ?>
                            <label for="penerangan[]" class="form-label"></label>
                            <textarea type="text" class="form-control" name="penerangan[]"></textarea><br>

                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="documents" class="form-label">Dokumen sokongan (PDF):</label>
                            <input type="file" class="form-control" name="documents[]" multiple required>
                        </div>
                    <button type="submit" class="btn btn-primary">Tambah</button>
                </div>
            </form>
        
            <p><h2 class="mb-4">Senarai Permohonan</h2>
            <table class="table table-striped mt-4">
                <thead>
                    <tr>
                        <th style="text-align: left;">Bil</th>
                        <th style="text-align: left;">Nama Dokumen</th>
                        <th style="text-align: left;">Keterangan</th>
                        <th style="text-align: left;">Dokumen</th>
                        <th style="text-align: left;">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($submissionsFromDB)): ?>
                        <?php $bil = 1; ?>
                        <?php foreach ($submissionsFromDB as $submission): ?>
                            <tr>
                                <td><?php echo $bil++; ?></td>
                                <td><?php echo htmlspecialchars($submission['documentName']); ?></td>
                                <td><?php echo htmlspecialchars($submission['peringkat']); ?></td>
                                <td>
                                    <?php if (!empty($submission['filePath'])): ?>
                                        <a href="<?php echo htmlspecialchars($submission['filePath']); ?>" target="_blank">View PDF</a>
                                    <?php else: ?>
                                        <span>No PDF</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?delete=<?php echo urlencode($submission['documentName']); ?>&requirementID=<?php echo urlencode($requirementID); ?>&awardID=<?php echo urlencode($awardID); ?>&gradeID=<?php echo urlencode($gradeID); ?>" class="btn btn-danger btn-sm">Buang</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">Tiada dokumen ditemui.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Hantar Button -->
            <form method="post">
                <!-- Hantar button to trigger the update of 'submitted' status -->
                <button type="submit" name="hantar" class="btn btn-primary">Hantar</button>
            </form>

            <!-- Modal -->
            <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirmationModalLabel">Pengesahan</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Sila pastikan dokumen maklumat telah lengkap kerana ia tidak boleh diedit lagi selepas ini.
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" onclick="confirmSubmission(<?= $awardID ?>, <?= $gradeID ?>)">Confirm</button>
                        </div>
                    </div>
                </div>
            </div>
        </div><p><p>
    </div>

    <footer>
        <p>&copy; Bahagian Pentadbiran, UiTM Cawangan Perak</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
</body>
</html>