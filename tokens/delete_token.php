<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Firebase_Login/login.php");
    exit();
}

// Include database connection
include '../mysql_db.php';

// Get the token number and date from the query string
$token_number = $_GET['token_number'] ?? null;
$token_date = $_GET['date'] ?? null;

// Check if token number and date are provided
if ($token_number && $token_date) {
    // Delete the token from the database
    $sql = "DELETE FROM tokens WHERE token_number = ? AND token_date = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('isi', $token_number, $token_date, $_SESSION['user_id']);

    if ($stmt->execute()) {
        // Redirect back to the token page with a success message
        echo '<script>alert("Token deleted successfully!"); window.location.href = "token.php";</script>';
    } else {
        // Error message if deletion fails
        echo '<script>alert("Failed to delete the token. Please try again."); window.location.href = "token.php";</script>';
    }
} else {
    echo '<script>alert("Invalid request. Token number or date is missing."); window.location.href = "token.php";</script>';
}

$conn->close();
?>
