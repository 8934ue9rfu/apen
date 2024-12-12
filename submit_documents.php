<?php
session_start();

// Database connection
$conn = new mysqli('localhost', 'root', 'Aleesya_2004', 'borangapen');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user ID from session
$userID = $_SESSION['userID'];

// Check if segments are selected
if (isset($_POST['segments']) && is_array($_POST['segments'])) {
    $selectedSegments = $_POST['segments'];

    foreach ($selectedSegments as $requirementID) {
        // Handle file upload for each selected segment
        if (isset($_FILES["document_" . $requirementID])) {
            $file = $_FILES["document_" . $requirementID];
            $uploadDir = "uploads/"; // Define the upload directory
            $uploadFile = $uploadDir . basename($file["name"]);

            // Check if file is uploaded without errors
            if (move_uploaded_file($file["tmp_name"], $uploadFile)) {
                // Insert submission record into UserSubmissions table
                $query = "INSERT INTO usersubmissions (userID, requirementID, status, documentName) 
                          VALUES (?, ?, 'Submitted', ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iis", $userID, $requirementID, $file["name"]);
                $stmt->execute();
            } else {
                echo "Failed to upload document for segment: " . $requirementID;
            }
        }
    }

    echo "Documents submitted successfully!";
} else {
    echo "No segments selected!";
}
?>
