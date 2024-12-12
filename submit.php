<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['requirementID'], $_POST['awardID'], $_POST['kategoriGemilang'], $_POST['gradeID'])) {
    $requirementID = $_POST['requirementID'];
    $awardID = $_POST['awardID'];
    $kategoriGemilang = $_POST['kategoriGemilang'];
    $gradeID = $_POST['gradeID'];
    $userID = $_SESSION['userID']; // Assuming userID is stored in the session

    try {
        // Database connection
        $pdo = new PDO('mysql:host=localhost;dbname=borangapen', 'root', 'Aleesya_2004');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Update the `submitted` column in `usersubmissions` table
        $sqlUpdate = "UPDATE usersubmissions SET submitted = 1 
                      WHERE userID = :userID AND awardID = :awardID AND requirementID = :requirementID";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([
            'userID' => $userID,
            'awardID' => $awardID,
            'requirementID' => $requirementID
        ]);

        // Save success message in the session
        $_SESSION['success_message'] = "Dokumen berjaya dihantar!";
    } catch (Exception $e) {
        // Save error message in the session
        $_SESSION['error_message'] = "Ralat berlaku: " . $e->getMessage();
    }
} else {
    $_SESSION['error_message'] = "Maklumat yang diperlukan tidak lengkap.";
}

// Redirect back to `document_submission.php` with current state
$redirectURL = "document_submission.php?awardID=" . urlencode($awardID) .
    "&gradeID=" . urlencode($gradeID) .
    "&kategoriGemilang=" . urlencode($kategoriGemilang);

header("Location: $redirectURL");
exit;
?>
