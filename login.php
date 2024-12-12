<?php
session_start();

try {
    $pdo = new PDO('mysql:host=localhost;dbname=borangapen', 'root', 'Aleesya_2004');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fetch form data
    $userID = $_POST['userID'];

    $sql = "SELECT u.userID, g.gradeID, u.role
            FROM users u
            JOIN grades g ON u.gradeID = g.gradeID
            WHERE u.userID = :userID";  

    // Prepare the statement
    $stmt = $pdo->prepare($sql);
    
    // Bind the parameter
    $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);  

    // Execute the query
    $stmt->execute();

    // Fetch user data if exists
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['userID'] = $user['userID'];
        $_SESSION['userGradeID'] = $user['gradeID'];  
        $_SESSION['role'] = $user['role'];  

        if ($_SESSION['role'] == 'moderator') {
            header("Location: moderatorDashboard.php");
        } elseif ($_SESSION['role'] == 'jury') {
            header("Location: jurydashboard.php");
        } elseif ($_SESSION['role'] == 'urusetia') {
            header("Location: urusetiadashboard.php");
        }else {
            header("Location: dashboard.php");
        }
        exit;
    } else {
        $error = "Invalid user ID.";
    }
}
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage(); 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Anugerah Pentadbir (APEN)</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="picture/icon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="picture/icon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="picture/icon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="picture/icon/apple-touch-icon.png">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Roboto', sans-serif;
            background: #f7f8fc;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background-image: url("si5.jpg"); 
            background-size: cover;
            background-repeat: no-repeat; 
            background-position: center; 
        }
        .container {
            width: 100%;
            max-width: 500px;
            background: #ffffff;
            padding: 2rem;
            border-radius: 2px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .container img {
            display: block;
            margin: 0 auto;
            width: 180px;
            margin-bottom: 1rem;
        }
        .container h2 {
            text-align: center;
            color: #333333;
            margin-bottom: 0.5rem;
        }
        .container h4 {
            text-align: center;
            color: #777777;
            margin-bottom: 2rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .form-group input {
            width: 94%;
            padding: 0.8rem;
            border: 1px solid #cccccc;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        .form-group input:focus {
            border-color: #3498db;
            outline: none;
        }
        .btn-primary {
            width: 100%;
            padding: 0.8rem;
            font-size: 16px;
            color: #ffffff;
            background: #A888B5;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .btn-primary:focus, .btn-primary:active, .btn-primary:focus:active, .btn-primary:focus-visible {
            outline: none !important; 
            box-shadow: none !important; 
            background-color: rgb(105, 79, 142) !important; 
            border-color: rgb(105, 79, 142) !important;
        }
        .btn-primary:hover {
            background: rgb(105, 79, 142);
        }
        .error-message {
            margin-top: 1rem;
            color: #e74c3c;
            font-size: 14px;
            text-align: center;
        }
        .syarat-section {
            margin-top: 30px;
            text-align: left;
        }
        .syarat-section h4 {
            font-size: 16px;
            font-weight: 600;
            color: #2C3E50;
            margin-bottom: 15px;
        }
        .syarat-section ul {
            list-style: none;
            padding-left: 30px;
        }
        .syarat-section ul li {
            font-size: 14px;
            color: ;
            margin-bottom: 8px;
            position: relative;
        }
        .syarat-section ul li::before {
            content: "\2022";
            color: #3498db;
            font-size: 18px;
            position: absolute;
            left: -25px;
            top: -2px;
        }
        footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 12px;
            color: #888888;
        }
        @media only screen and (max-width: 768px) {
            .container {
                padding: 1.5rem;
                border-radius: 8px;
            }
            .container img {
                width: 120px;
            }
            .container h2, .container h4 {
                font-size: 18px;
            }
            .form-group input {
                padding: 0.6rem;
                font-size: 13px;
            }
            .btn-primary {
                font-size: 14px;
                padding: 0.7rem;
            }
            .syarat-section h4 {
                font-size: 14px;
            }
            .syarat-section ul li {
                font-size: 12px;
            }
            footer {
                font-size: 10px;
            }
        }
        @media only screen and (max-width: 768px) {
            .container {
                padding: 1.5rem;
                border-radius: 2px;
            }
            .container img {
                width: 200px;
            }
            .container h2, .container h4 {
                font-size: 18px;
            }
            .form-group input {
                padding: 0.6rem;
                font-size: 13px;
            }
            .btn-primary {
                font-size: 14px;
                padding: 0.7rem;
            }
            .syarat-section h4 {
                font-size: 14px;
            }
            .syarat-section ul li {
                font-size: 12px;
            }
            footer {
                font-size: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="uitm3.png" alt="UiTM Logo">
        <h2>ANUGERAH PENTADBIR</h2>
        <h4>UNIVERSITI TEKNOLOGI MARA CAWANGAN PERAK</h4>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="userID">No Pekerja</label>
                <input type="text" name="userID" id="userID" required>
            </div>
            <button type="submit" class="btn-primary">Log Masuk</button>
        </form>

        <div class="syarat-section">
            <h4>Syarat Kelayakan</h4>
            <ul>
                <li>Tidak pernah memenangi sebarang anugerah sebelum ini.</li>
                <li>Tempoh perkhidmatan sekurang-kurangnya 3 tahun.</li>
                <li>Terbuka kepada semua kakitangan tetap atau kontrak.</li>
                <li>Bebas daripada salahlaku tatatertib.</li>
                <li>Telah sah dalam perkhidmatan.</li>
                <li>Markah LPNT sekurang-kurangnya 85% ke atas.</li>
            </ul>
        </div>
    </div>
</body>
</html>
