<?php
session_start();
if (!isset($_SESSION['staff_user_id'])) {
    header("Location: Login/login.php");
    exit;
}

$user_name = $_SESSION['user_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Staff Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h1>
        <p>You are now logged in as a staff member. From here, you can manage tokens, view reserved slots, and more.</p>

        <div class="mt-4">
            <a href="token_management.php" class="btn btn-primary w-100">Manage Tokens</a>
        </div>
        <div class="mt-2">
            <a href="logout.php" class="btn btn-danger w-100">Logout</a>
        </div>
    </div>
</body>
</html>
