<?php
session_start(); // Start the session

// Destroy the session and all session variables
session_unset();
session_destroy();

// Redirect to the login page
header("Location: login.php");
exit;
?>
