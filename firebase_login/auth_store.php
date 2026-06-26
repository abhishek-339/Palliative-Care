<?php
// Start the session
session_start();

// Store the user details in session variables
if (isset($_POST['name']) && isset($_POST['email'])) {
    $_SESSION['user_name'] = $_POST['name'];
    $_SESSION['user_email'] = $_POST['email'];
}

// Redirect to the index page
header('Location: index.php');
exit;
?>
