<?php
session_start();

// Check for user login
if (!isset($_SESSION['userID']) || !isset($_SESSION['userGradeID'])) {
    header("Location: index.php");
    exit;
}

// Initialize variables
$user_id = $_SESSION['userID'] ?? null;

if (!$user_id) {
    die("Error: User ID not found in session.");
}

// Database connection using PDO
try {
    $pdo = new PDO('mysql:host=localhost;dbname=borangapen', 'root', 'Aleesya_2004');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

     // Adding new staff
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'addStaff') {
        $userID = $_POST['userID'];  // Changed from username to userID
        $staffName = $_POST['staffName'];
        $gradeID = $_POST['userGradeID'];
        $LNPT2021 = $_POST['LNPT2021'];
        $LNPT2022 = $_POST['LNPT2022'];
        $LNPT2023 = $_POST['LNPT2023'];
        $tatatertib = $_POST['gaji'];
        $tatatertib = $_POST['sspa'];
        $tahunBerkhidmat = $_POST['tahunBerkhidmat'];
        $tatatertib = $_POST['tatatertib'];
        $kesahan = $_POST['kesahan'];
        $statusStaff = $_POST['statusStaff'];
        $jawatan = $_POST['jawatan'];
        $jabatan = $_POST['jabatan'];
        $jabatan = $_POST['unit'];
        $email = $_POST['email'];

        // Check if the userID already exists in the users table
        $stmt = $pdo->prepare("SELECT userID FROM users WHERE userID = :userID");
        $stmt->execute(['userID' => $userID]);

        if ($stmt->rowCount() > 0) {
            echo "<script>alert('ID Staf sudah wujud. Sila gunakan ID yang berbeza.');</script>";
        } else {
            // Insert the new staff member into the database
            $stmt = $pdo->prepare("
                INSERT INTO users (userID, staffName, LNPT2021, LNPT2022, LNPT2023, gaji, sspa, tahunBerkhidmat, tatatertib, kesahan, statusStaff, role, gradeID, jawatan, jabatan, unit, email) 
                VALUES (:userID, :staffName, :LNPT2021, :LNPT2022, :LNPT2023, :gaji, :sspa, :tahunBerkhidmat, :tatatertib, :kesahan, :statusStaff, 'staff', :gradeID, :jawatan, :jabatan, :unit, :email)
            ");
            $stmt->execute([
                'userID' => $userID,
                'staffName' => $staffName,
                'LNPT2021' => $LNPT2021,
                'LNPT2022' => $LNPT2022,
                'LNPT2023' => $LNPT2023,
                'gaji' => $gaji,
                'sspa' => $sspa,
                'tahunBerkhidmat' => $tahunBerkhidmat,
                'tatatertib' => $tatatertib,
                'kesahan' => $kesahan,
                'statusStaff' => $statusStaff,
                'gradeID' => $gradeID,
                'jawatan' => $jawatan,
                'jabatan' => $jabatan,
                'unit' => $unit,
                'email' => $email
            ]);
            echo "<script>alert('Staf berjaya ditambah.');</script>";
        }
    }

    // Removing staff
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'removeStaff') {
        $staffIdToRemove = $_POST['staffId'];
        $stmt = $pdo->prepare("DELETE FROM users WHERE userID = :userID AND role = 'staff'");
        $stmt->execute(['userID' => $staffIdToRemove]);
    }

    // Fetch the users data from the database

    $stmt = $pdo->prepare("
        SELECT    
            u.userID,  
            u.staffName, 
            COALESCE(g.gradeName, 'No Grade') AS gradeName,
            u.LNPT2021, 
            u.LNPT2022, 
            u.LNPT2023,
            u.gaji,
            u.sspa, 
            u.tahunBerkhidmat, 
            u.tatatertib,
            u.kesahan,
            u.statusStaff,
            u.jawatan,
            u.jabatan,
            u.unit,
            u.email,
            COALESCE(DATE_FORMAT(u.tarikhBerpencen, '%d-%m-%Y'), 'Tiada') AS tarikhBerpencen,
            u.phoneNum
        FROM 
            users u
        LEFT JOIN 
            grades g ON g.gradeID = u.gradeID 
        WHERE 
            u.role = 'staff'
    ");

    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get the count of staff members
    $stmt = $pdo->prepare("SELECT COUNT(*) as staff_count FROM users WHERE role = 'staff'");
    $stmt->execute();
    $staffCount = $stmt->fetchColumn();

    // Fetch available grades from the grades table to populate the dropdown
    $grades = [];
    $stmt = $pdo->prepare("SELECT gradeID, gradeName FROM grades");
    $stmt->execute();
    $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count the awards in the awards table
    $stmt = $pdo->prepare("SELECT COUNT(*) as award_count FROM awards");
    $stmt->execute();
    $awardCount = $stmt->fetchColumn();

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
        .main-content {
            flex: 1;
            padding: 20px;
        }
        footer {
            background-color: #d3bca2;
            color: #5b4636;
            text-align: center;
            padding: 10px;
            margin-top: auto;
        }
        table td {
            text-transform: uppercase;
        }
        .table-bordered {
            border: 1px solid #ccc;
            border-collapse: collapse;
            width: 100%;
        }
        .table-bordered th, .table-bordered td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        .table-bordered th {
            font-weight: bold;
            text-align: center;
            background-color: #f9f9f9;
        }
        .table-bordered tbody tr:hover {
            background-color: #f5f5f5;
        }
        .btn-danger {
            background-color: #d9534f;
            border: none;
            padding: 5px 10px;
            font-size: 12px;
        }
        .btn-danger:hover {
            background-color: #c9302c;
        }
        .small-font td {
            font-size: 5px;
        }
        .large-table {
        width: 150%; 
        table-layout: fixed;
        }

        .large-table th, .large-table td {
            padding: 5px; 
            text-align: center; 
        }

        .large-table th {
            font-size: 0.8rem; 
        }

        .large-table td {
            font-size: 0.8rem;
        }
        .large-table tbody tr {
            height: 60px; 
        }
        .lowercase {
            text-transform: none;
        }
    </style>
</head>
<body>
<!-- Top Navigation Bar -->
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
                    <a class="nav-link" href="markah.php">Markah</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="statistik.php">Statistik</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="confirmLogout()">Log Out</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

 <!-- Main Content -->
<div class="container-fluid">
    <main class="px-md-4">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Dashboard</h1>
        </div>

        <div class="row mb-4 justify-content-center">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Jumlah Staff</h5>
                        <p class="card-text"><?php echo $staffCount; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Senarai Anugerah</h5>
                        <p class="card-text"><?php echo $awardCount; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <h2>Maklumat Staf</h2>
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Senarai Staf</h5>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addStaffModal">Tambah Staf</button>
                </div>

                <!-- Responsive Table Container -->
                <div class="table-responsive mt-3" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-bordered" style="font-size: 14px; text-align: left;">
                        <thead style="background-color: #f4f4f4;">
                            <tr>
                                <th style="text-align: center;">Bil</th>
                                <th>No Pekerja</th>
                                <th>Nama</th>
                                <th>Gred</th>
                                <th>LNPT 2021 (%)</th>
                                <th>LNPT 2022 (%)</th>
                                <th>LNPT 2023 (%)</th>
                                <th>Gred Gaji</th>
                                <th>Gred SSPA</th>
                                <th>Tahun Berkhidmat</th>
                                <th>Tatatertib</th>
                                <th>Pengesahan Dalam Perkhidmatan</th>
                                <th>Status Pekerja</th>
                                <th>Jawatan</th>
                                <th>Jabatan</th>
                                <th>Bahagian / Unit</th>
                                <th>Tarikh Berpencen</th>
                                <th>No Telefon</th>
                                <th>Email</th>
                                <th style="text-align: center;">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody style="background-color: #ffffff;">
                            <?php
                            // Sort the $users array by 'staffName' alphabetically
                            usort($users, function($a, $b) {
                                return strcmp($a['staffName'], $b['staffName']);
                            });
                            $counter = 1; // Initialize counter
                            foreach ($users as $user): ?>
                                <tr style="border-bottom: 1px solid #e0e0e0;">
                                    <td style="text-align: center;"><?= $counter++ ?></td>
                                    <td><?= htmlspecialchars($user['userID']) ?></td>
                                    <td><?= htmlspecialchars($user['staffName'] ?: 'Tiada') ?></td>
                                    <td><?= htmlspecialchars($user['gradeName'] ?: 'Tiada') ?></td>
                                    <td><?= htmlspecialchars($user['LNPT2021'] ?: 'Tiada') ?></td>
                                    <td><?= htmlspecialchars($user['LNPT2022'] ?: 'Tiada') ?></td>
                                    <td><?= htmlspecialchars($user['LNPT2023'] ?: 'Tiada') ?></td>
                                    <td><?= htmlspecialchars($user['gaji'] ?: 'Tiada') ?></td>
                                    <td><?= htmlspecialchars($user['sspa'] ?: 'Tiada') ?></td>
                                    <td><?= htmlspecialchars($user['tahunBerkhidmat'] ?: 'Tiada') ?> TAHUN</td>
                                    <td><?= htmlspecialchars($user['tatatertib'] ?: 'Tiada') ?></td>
                                    <td><?= htmlspecialchars($user['kesahan'] ?: 'Tiada') ?></td>
                                    <td><?= htmlspecialchars($user['statusStaff'] ?: 'Tiada') ?></td>
                                    <td><?= htmlspecialchars($user['jawatan'] ?: 'Tiada') ?></td>
                                    <td><?= htmlspecialchars($user['jabatan'] ?: 'Tiada') ?></td>
                                    <td><?= htmlspecialchars($user['unit'] ?: 'Tiada') ?></td>
                                    <td><?= htmlspecialchars($user['tarikhBerpencen'] ?: 'Tiada') ?></td>
                                    <td><?= htmlspecialchars($user['phoneNum'] ?: 'Tiada') ?></td>
                                    <td>
                                        <span class="lowercase"><?= htmlspecialchars($user['email'] ?: 'TIADA' ) ?></span>
                                    </td>
                                    <td style="text-align: center;">
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="action" value="removeStaff">
                                            <input type="hidden" name="staffId" value="<?= htmlspecialchars($user['userID']) ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Buang</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Add Staff Modal -->
<div class="modal fade" id="addStaffModal" tabindex="-1" aria-labelledby="addStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addStaffModalLabel">Tambah Staf</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post">
                    <input type="hidden" name="action" value="addStaff">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="id" class="form-label">ID Staf</label>
                            <input type="text" class="form-control" id="userID" name="userID" required>
                        </div>
                        <div class="col-md-6">
                            <label for="staffName" class="form-label">Nama Staf</label>
                            <input type="text" class="form-control" id="staffName" name="staffName" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="gred" class="form-label">Gred</label>
                            <select class="form-select" id="userGradeID" name="userGradeID" required>
                                <option value="">Sila pilih</option>
                                <?php foreach ($grades as $grade) : ?>
                                         <option value="<?= $grade['gradeID'] ?>"><?= $grade['gradeName'] ?></option>
                                <?php endforeach; ?>   
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="LNPT2021" class="form-label">LNPT 2021</label>
                            <input type="text" class="form-control" id="LNPT2021" name="LNPT2021" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="LNPT2022" class="form-label">LNPT 2022</label>
                            <input type="text" class="form-control" id="LNPT2022" name="LNPT2022" required>
                        </div>
                        <div class="col-md-6">
                            <label for="LNPT2023" class="form-label">LNPT 2023</label>
                            <input type="text" class="form-control" id="LNPT2023" name="LNPT2023" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="gaji" class="form-label">Gred Gaji</label>
                            <input type="text" class="form-control" id="gaji" name="gaji" required>
                        </div>
                        <div class="col-md-6">
                            <label for="sspa" class="form-label">Gred SSPA</label>
                            <input type="text" class="form-control" id="sspa" name="sspa" required>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="tahunBerkhidmat" class="form-label">Tahun Berkhidmat</label>
                            <input type="text" class="form-control" id="tahunBerkhidmat" name="tahunBerkhidmat" required>
                        </div>
                        <div class="col-md-6">
                            <label for="tatatertib" class="form-label">Tatatertib</label>
                            <select class="form-control" id="tatatertib" name="tatatertib" required>
                                <option value="">Sila pilih</option>
                                <option value="Bebas">Bebas</option>
                                <option value="Tidak Bebas">Tidak Bebas</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="kesahan" class="form-label">Pengesahan Dalam Perkhidmatan</label>
                            <select class="form-select" id="kesahan" name="kesahan" required>
                                <option value="">Sila pilih</option>
                                <option value="Telah Disahkan">Telah Disahkan</option>
                                <option value="Belum Disahkan">Belum Disahkan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="statusStaff" class="form-label">Status Staff</label>
                            <select class="form-control" id="jenisStaff" name="jenisStaff" required>
                                <option value="">Sila pilih</option>
                                <option value="Aktif">Aktif</option>
                                <option value="Berhenti">Berhenti</option>
                                <option value="Lain-lain">Lain-lain</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="jawatan" class="form-label">Jawatan</label>
                            <input type="text" class="form-control" id="jawatan" name="jawatan" required>
                        </div>
                        <div class="col-md-6">
                            <label for="jabatan" class="form-label">Jabatan</label>
                            <select class="form-control" id="jabatan" name="jabatan" required>
                                <option value="">Sila pilih</option>
                                <option value="UiTM Kampus Sri Iskandar">UiTM Kampus Sri Iskandar</option>
                                <option value="UiTM Kampus Tapah">UiTM Kampus Tapah</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="unit" class="form-label">Unit /  Bahagian</label>
                            <input type="text" class="form-control" id="unit" name="unit" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary float-end">Tambah</button>
                </form>
            </div>
        </div>
    </div>
</div><p><p>

<!-- Footer -->
<footer>
    <p>&copy; Bahagian Pentadbiran, UiTM Cawangan Perak</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.min.js"></script>

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
