<?php
// Start the session to retrieve user details
session_start();

// Check if user details are set in the session
if (isset($_SESSION['user_name']) && isset($_SESSION['user_email'])) {
    $user_name = $_SESSION['user_name'];
    $user_email = $_SESSION['user_email'];
} else {
    // Redirect to login page if session doesn't contain user details
    header('Location: Firebase_Login/login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Index</title>
</head>
<body>
    <h1>Welcome, <?php echo $user_name; ?>!</h1>
    <p>Email: <?php echo $user_email; ?></p>
    <a href="Tokens/token.php">Get Tokens</a>
    <a href="Firebase_Login/logout.php">Logout</a>
</body>
</html>
