<?php
session_start();

$conn = new mysqli("localhost", "root", "Aleesya_2004", "borangapen");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$userID = $_SESSION['userID'];

// Handle Profile Picture Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profilePicture'])) {
    // Get the file details
    $profilePicture = $_FILES['profilePicture'];
    $fileName = $profilePicture['name'];
    $fileTmpName = $profilePicture['tmp_name'];
    $fileSize = $profilePicture['size'];
    $fileError = $profilePicture['error'];
    $fileType = $profilePicture['type'];

    // Get file extension
    $fileParts = explode('.', $fileName);  // First, store the result of explode() in a variable
    $fileExt = strtolower(end($fileParts));  // Now pass the variable to end()
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];

    // Validate file type
    if (in_array($fileExt, $allowedExts)) {
        // Check for upload errors
        if ($fileError === 0) {
            // Check file size (max 5MB)
            if ($fileSize <= 5000000) {
                // Generate unique file name and set file destination
                $fileNewName = uniqid('', true) . "." . $fileExt;
                $fileDestination = 'uploads/' . $fileNewName;

                // Move file to the upload folder
                if (move_uploaded_file($fileTmpName, $fileDestination)) {
                    // Save the file path to the database
                    $stmt = $conn->prepare("UPDATE users SET profilePicture = ? WHERE userID = ?");
                    $stmt->bind_param("si", $fileDestination, $userID);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    echo "There was an error uploading the file.";
                }
            } else {
                echo "Your file is too large!";
            }
        } else {
            echo "There was an error uploading your file!";
        }
    } else {
        echo "You cannot upload files of this type!";
    }
}

// Handle phone number submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['phoneNum'])) {
    $phoneNum = $_POST['phoneNum'];

    // Save the phone number to the database
    $stmt = $conn->prepare("UPDATE users SET phoneNum = ? WHERE userID = ?");
    $stmt->bind_param("si", $phoneNum, $userID);
    $stmt->close();
}

// Handle Profile Picture Removal
if (isset($_POST['removeProfilePicture'])) {
    // Remove the profile picture from the database and file system
    $stmt = $conn->prepare("SELECT profilePicture FROM users WHERE userID = ?");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $stmt->bind_result($profilePicturePath);
    $stmt->fetch();
    $stmt->close();

    // Delete the file from the server if it exists
    if ($profilePicturePath && file_exists($profilePicturePath)) {
        unlink($profilePicturePath); // Delete the file
    }

    // Remove the file path from the database
    $stmt = $conn->prepare("UPDATE users SET profilePicture = NULL WHERE userID = ?");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $stmt->close();
}

// Fetch user data (includes fetching gradeID)
$query = "
    SELECT 
        users.userID, 
        users.staffName, 
        grades.gradeName, 
        users.gradeID, 
        users.LNPT2021, 
        users.LNPT2022, 
        users.LNPT2023,
        users.gaji,
        users.sspa, 
        users.tahunBerkhidmat, 
        users.kesahan, 
        users.statusStaff,
        users.jawatan,
        users.jabatan,
        users.unit,
        users.tatatertib, 
        users.profilePicture, 
        users.phoneNum
    FROM 
        users 
    LEFT JOIN 
        grades ON users.gradeID = grades.gradeID 
    WHERE 
        users.userID = ?
    ";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userID);
$stmt->execute();
$stmt->bind_result(
    $id, $staffName, $gredName, $gradeID, $lnpt2021, $lnpt2022, $lnpt2023, $gaji,
    $sspa, $tahunBerkhidmat, $kesahan, $statusStaff, $jawatan, $jabatan, 
    $unit, $tatatertib, $profilePicture, $phoneNum
);
$stmt->fetch();
$stmt->close();

// Set the dashboard link based on gradeID
if ($gradeID == 1) {
    $dashboardLink = "dashboard.php";
} elseif ($gradeID == 2) {
    $dashboardLink = "dashboard.php";
} else {
    $dashboardLink = "dashboard.php"; // Fallback in case of an undefined gradeID
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Pengguna</title>
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
        #profilePicturePreview {
            border-radius: 50%;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 150px;
            width: 100%;
            aspect-ratio: 1 / 1;
            object-fit: cover;
        }
        .btn-primary {
            background-color: rgb(152, 125, 154); 
            border: none;
            max-width: 300px;
        }
        .btn-primary:hover {
            background-color: rgb(105, 79, 142); 
        }
        .btn-primary:focus,
        .btn-primary:active,
        .btn-primary:focus:active,
        .btn-primary:focus-visible {
            outline: none !important;
            box-shadow: none !important;
            background-color: #b58868 !important;
            border-color:  #b58868 !important;
        }
        .table-bordered {
            border-color: #ffebcc; 
        }
        .table-striped > tbody > tr:nth-of-type(odd) {
            background-color: #fff7e6; 
        }
        thead {
            background-color: rgb(152, 125, 154); 
            color: #333;
        }
        h1, h2, h4 {
            color: #333; 
        }
        .alert-info {
            background-color: #d1ecf1; 
            color: #0c5460;
        }
        footer {
            background-color: #441752; 
            color: white; 
            text-align: center;
            padding: 10px;
            margin-top: auto; 
        }
        .white-container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch; 
        }
        .table-responsive::-webkit-scrollbar {
            height: 7px;
        }
        .table-responsive::-webkit-scrollbar-thumb {
            background-color: #d3bca2;
            border-radius: 4px;
        }
        .table-responsive::-webkit-scrollbar-track {
            background-color: #f1f1f1;
        }
        h1.h2, h4 {
            color: #5b4636; 
        }
        .ineligible {
            background-color: #f8d7da !important;
            color: red !important;
            font-weight: bold;
            border: 1px solid #f5c6cb;
        }
        @media (max-width: 768px) {
            .container-fluid {
                font-size: 12px;
                max-width: 350px;
            }
            h5 {
            font-size: 15px;
            }
            .btn-primary {
                font-size: 15px;
            }
            .btn-danger {
                font-size: 15px;
            }
        }
        @media (max-width: 480px) {
            .container-fluid {
                font-size: 12px;
                max-width: 350px;
            }
            h5 {
                font-size: 15px;
            }
            .btn-primary {
                font-size: 15px;
            }
            .btn-danger {
                font-size: 15px;
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
                        <a class="nav-link active" href="<?php echo $dashboardLink; ?>">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="confirmLogout()">Log Out</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Content Area -->
    <div class="container-fluid">
        <main class="px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="dashboard-heading" style="color:#3B1E54">Profile</h1>
            </div>

            <!-- User Info Section -->
            <div class="row">
                <div class="col-md-4 text-center">

                    <!-- Removed White Container for Profile Picture Section -->
                    <img id="profilePicturePreview" src="<?php echo !empty($profilePicture) ? $profilePicture : 'picture/user_icon.png'; ?>" alt="Profile Picture" class="img-fluid mb-3" style="border-radius: 50%; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); max-width: 150px; width: 100%; aspect-ratio: 1 / 1; object-fit: cover;">

                    <!-- Upload Button -->
                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="btn btn-primary" for="profilePicture">Upload Profile Picture</label>
                            <input class="form-control" type="file" id="profilePicture" name="profilePicture" accept="image/*" style="display: none;" onchange="this.form.submit()">
                        </div>
                    </form>

                    <!-- Remove Profile Picture Button -->
                    <form action="" method="post">
                        <div class="mb-3">
                            <button type="submit" name="removeProfilePicture" class="btn btn-danger">Remove Profile Picture</button>
                        </div>
                    </form>
                </div>

                <div class="col-md-8">
                    <div class="white-container mb-3">
                        <div class="row">
                            <!-- Nama -->
                            <div class="col-md-6">
                                <h5>Nama:</h5>
                                <p><?php echo htmlspecialchars(strtoupper($staffName)); ?></p>
                            </div>

                            <!-- Tempoh Perkhidmatan -->
                            <div class="col-md-6">
                                <h5>Tempoh Perkhidmatan:</h5>
                                <p style="<?php echo ($tahunBerkhidmat < 3) ? 'color: red; font-weight: bold;' : ''; ?>">
                                    <?php echo htmlspecialchars(strtoupper($tahunBerkhidmat)); ?> TAHUN
                                </p>
                            </div>
                        </div>

                        <div class="row mb-3">
                        <!-- No Telefon -->
                        <div class="col-md-6">
                            <h5>No Telefon:</h5>
                            <?php if (!empty($phoneNum)): ?>
                                <p><?php echo htmlspecialchars($phoneNum); ?></p>
                            <?php else: ?>
                                <form action="" method="post">
                                    <input type="text" name="phoneNum" class="form-control" placeholder="Enter your phone number" required>
                                    <button type="submit" class="btn btn-primary mt-2">Simpan</button>
                                </form>
                            <?php endif; ?>
                        </div>

                        <!-- Jabatan -->
                        <div class="col-md-6">
                            <h5>Jabatan:</h5>
                            <p><?php echo htmlspecialchars(strtoupper($jabatan)); ?></p>
                        </div>
                    </div>
                </div>
            </div> 
        
            <!-- "Maklumat Tentang Pengguna" Section -->
            <div class="white-container mb-3">
                <center><h4 class="mt-3">Maklumat Tentang Pengguna</h4></center>

                <!-- Scrollable Table Wrapper -->
                <div class="table-responsive">
                    <!-- Table for All Users Information -->
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th scope="col">No Pekerja</th>
                                <th scope="col">Nama Staff</th>
                                <th scope="col">Gred</th>
                                <th scope="col">LPNT 2021 (%)</th>
                                <th scope="col">LPNT 2022 (%)</th>
                                <th scope="col">LPNT 2023 (%)</th>
                                <th scope="col">Gred Gaji</th>
                                <th scope="col">Gred SSPA</th>
                                <th scope="col">Status</th>
                                <th scope="col">Jawatan</th>
                                <th scope="col">Bahagian / Unit</th>
                                <th scope="col">Pengesahan Dalam Perkhidmatan</th>
                                <th scope="col">Tatatertib</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo htmlspecialchars(strtoupper($id ?: 'TIADA')); ?></td>
                                <td><?php echo htmlspecialchars(strtoupper($staffName ?: 'TIADA')); ?></td>
                                <td><?php echo htmlspecialchars(strtoupper($gredName ?: 'TIADA')); ?></td>
                                <td class="<?php echo ($lnpt2021 < 85) ? 'ineligible' : ''; ?>">
                                    <?php echo htmlspecialchars(strtoupper($lnpt2021 ?: 'TIADA')); ?>
                                </td>
                                <td class="<?php echo ($lnpt2022 < 85) ? 'ineligible' : ''; ?>">
                                    <?php echo htmlspecialchars(strtoupper($lnpt2022 ?: 'TIADA')); ?>
                                </td>
                                <td class="<?php echo ($lnpt2023 < 85) ? 'ineligible' : ''; ?>">
                                    <?php echo htmlspecialchars(strtoupper($lnpt2023 ?: 'TIADA')); ?>
                                </td>
                                <td><?php echo htmlspecialchars(strtoupper($gaji ?: 'TIADA')); ?></td>
                                <td><?php echo htmlspecialchars(strtoupper($sspa ?: 'TIADA')); ?></td>
                                <td class="<?php echo (strtolower($statusStaff) === 'berhenti') ? 'ineligible' : ''; ?>">
                                    <?php echo htmlspecialchars(strtoupper($statusStaff ?: 'TIADA')); ?>
                                </td>
                                <td><?php echo htmlspecialchars(strtoupper($jawatan ?: 'TIADA')); ?></td>
                                <td><?php echo htmlspecialchars(strtoupper($unit ?: 'TIADA')); ?></td>
                                <td class="<?php echo (strtolower($kesahan) === 'belum disahkan') ? 'ineligible' : ''; ?>">
                                    <?php echo htmlspecialchars(strtoupper($kesahan ?: 'TIADA')); ?>
                                </td>
                                <td class="<?php echo (strtolower($tatatertib) === 'tidak bebas') ? 'ineligible' : ''; ?>">
                                    <?php echo htmlspecialchars(strtoupper($tatatertib ?: 'TIADA')); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div> 

    <!-- Footer --> 
    <footer>
        <p>&copy; Bahagian Pentadbiran, UiTM Cawangan Perak</p> 
    </footer> 

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script> 
        // Handle profile picture upload preview 
        document.getElementById('profilePicture').addEventListener('change', function(event) { 
            const file = event.target.files[0]; 
            if (file) { 
                const reader = new FileReader(); 
                reader.onload = function(e) { 
                    document.getElementById('profilePicturePreview').src = e.target.result; 
                }; 
                reader.readAsDataURL(file); 
            } 
        });

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
