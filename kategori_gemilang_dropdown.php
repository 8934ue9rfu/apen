<?php
session_start();

// Check if the form is submitted via POST and kategoriGemilang exists
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kategoriGemilang'])) {
    // Save kategoriGemilang into the session
    $_SESSION['kategoriGemilang'] = $_POST['kategoriGemilang'];

    // Get awardID and gradeID from the form data
    $awardID = $_POST['awardID'] ?? null;
    $gradeID = $_POST['gradeID'] ?? null;

    // Check if awardID and gradeID are provided
    if ($awardID !== null && $gradeID !== null) {
        // Redirect to document_submission.php with the appropriate parameters
        header("Location: gemilangSubmission.php?kategoriGemilang=" . urlencode($_SESSION['kategoriGemilang']) . 
               "&awardID=$awardID&gradeID=$gradeID");
        exit;
    } else {
        echo "Error: awardID or gradeID is missing.";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Kategori Gemilang Selection</title>
    <link rel="icon" type="image/x-icon" href="picture/icon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="picture/icon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="picture/icon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="picture/icon/apple-touch-icon.png">
</head>
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
        color: #A888B5 !important; 
        }
    .card { 
        background-color: #fffaf5; 
        border: none; 
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.1); 
        padding: 10px; 
        border-radius: 10px; 
        }
    .card-title { 
        color: #5b4636; 
        }
    button[type="submit"] {
        display: block;
        width: 100%;
        padding: 12px 20px;
        font-size: 18px;
        font-weight: bold;
        color: #fff; 
        background: linear-gradient(135deg, rgb(152, 125, 154), rgb(152, 125, 154)); 
        border: none;
        border-radius: 8px; 
        cursor: pointer;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin-top: 30px; 
        transition: all 0.3s ease; 
    }
    button[type="submit"]:hover {
        background: linear-gradient(135deg, rgb(152, 125, 154), rgb(152, 125, 154)); 
        box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15); 
    }
    button[type="submit"]:active {
        transform: translateY(0); 
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); 
        background: rgb(152, 125, 154); 
    }
    .alert-info { 
        background-color: #f0e0c9; 
        color:rgb(127, 82, 131); 
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
    select#kategoriGemilang {
        display: block;
        width: 100%;
        padding: 10px 15px;
        font-size: 18px;
        color: #5b4636; 
        background-color: #fffaf5; 
        border: 2px solid rgb(127, 82, 131);
        border-radius: 5px; 
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); 
        appearance: none;
        -webkit-appearance: none; 
        -moz-appearance: none; 
        transition: all 0.3s ease; 
        cursor: pointer;
    }
    select#kategoriGemilang:focus {
        outline: none;
        border-color: rgb(127, 82, 131);
        box-shadow: 0 0 8px rgba(163, 117, 85, 0.5); 
    }
    select#kategoriGemilang::-ms-expand {
        display: none;
    }
    .custom-dropdown {
        position: relative;
        width: 100%; 
    }
    .custom-dropdown::after {
        content: "▼";
        font-size: 14px;
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #5b4636;
        pointer-events: none;
    }
</style>

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
                <h1>Kategori Segmen</h1>
            </div>
    
            <form action="" method="POST"> 
                <div class="custom-dropdown">
                    <select name="kategoriGemilang" id="kategoriGemilang" required>
                        <option value="" disabled selected>Pilih Kategori</option>
                        <option value="Penulisan Ilmiah / Sosial">Penulisan Ilmiah / Sosial</option>
                        <option value="Tadbir Urus Kreativiti & Inovasi">Tadbir Urus Kreativiti & Inovasi</option>
                        <option value="Khidmat Komuniti">Khidmat Komuniti</option>
                        <option value="Inovasi Kejuruteraan / Teknologi Maklumat & Komunikasi">Inovasi Kejuruteraan / Teknologi Maklumat & Komunikasi</option>
                        <option value="Sukan & Rekreasi">Sukan & Rekreasi</option>
                    </select>
                </div>
                
                <input type="hidden" name="awardID" value="3"> 
                <input type="hidden" name="gradeID" value="1"> 
                <p><p><button type="submit">Seterusnya</button>
            </form>
        </main>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="kategoriModal" tabindex="-1" aria-labelledby="kategoriModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="kategoriModalLabel">Makluman</h5>
                </div>
                <div class="modal-body">
                    <p>Sila pilih kategori anugerah sebelum meneruskan. Diingatkan setiap pemohon hanya boleh mendaftar untuk <b>SATU</b> kategori sahaja.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" style="background-color: rgb(127, 82, 131); border-color: rgb(127, 82, 131)" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
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

        document.addEventListener('DOMContentLoaded', function () {
            // Show the modal when the page loads
            var kategoriModal = new bootstrap.Modal(document.getElementById('kategoriModal'));
            kategoriModal.show();
        });
    </script>    
</body>
</html>
