<?php
session_start();

// Check if the user is logged in and the necessary session variables are set
if (!isset($_SESSION['userID']) || !isset($_SESSION['grade'])) {
    header("Location: index.php");
    exit;
}

// Get user ID and grade from session
$userID = $_SESSION['userID'];
$userGrade = $_SESSION['grade'];

// Get the awardID and gradeID from the query string
$awardID = isset($_GET['awardID']) ? $_GET['awardID'] : null;
$gradeID = isset($_GET['gradeID']) ? $_GET['gradeID'] : null;

if (!$awardID || !$gradeID) {
    // Redirect if awardID or gradeID are missing
    header("Location: document_submission.php");
    exit;
}

// Database connection
$pdo = new PDO('mysql:host=localhost;dbname=borangapen', 'root', 'Aleesya_2004');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Check if any segments were selected
if (isset($_POST['selected_segments'])) {
    // Loop through the selected segments
    foreach ($_POST['selected_segments'] as $requirementID) {
        // Insert a new record in the UserSubmissions table for each selected segment
        $sqlInsert = "INSERT INTO usersubmissions (userID, awardGradeDocID, requirementID, status)
                      VALUES (:userID, :awardGradeDocID, :requirementID, 'Pending')";
        $stmt = $pdo->prepare($sqlInsert);
        $stmt->execute([
            'userID' => $userID,
            'awardGradeDocID' => $awardID, // The award ID for the document
            'requirementID' => $requirementID, // The requirement ID from selected segments
        ]);
    }

    // Redirect to a confirmation page or back to the document submission page
    header("Location: confirmation_page.php?awardID=$awardID&gradeID=$gradeID");
    exit;
} else {
    // No segments selected, redirect back with a message
    header("Location: document_submission.php?awardID=$awardID&gradeID=$gradeID");
    exit;
}
?>
