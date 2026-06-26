<?php
session_start();
if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
</head>
<body>
<nav>
    <a href="index.php">Home</a>
    <a href="logout.php">Logout</a>
</nav>
