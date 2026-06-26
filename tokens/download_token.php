<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Firebase_Login/login.php");
    exit();
}

// Include database connection
include '../mysql_db.php';

// Include TCPDF library
require_once('../Tokens/vendor/autoload.php');  // Make sure this path is correct

// Start output buffering to avoid errors like "Some data has already been output"
ob_start();

// Get the token number from the query string
$token_number = $_GET['token_number'] ?? null;

if ($token_number) {
    // Fetch the token details from the database
    $sql = "SELECT token_number, token_date, user_name FROM tokens WHERE token_number = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $token_number, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $token = $result->fetch_assoc();

    if ($token) {
        // Create a new PDF document
        $pdf = new TCPDF();
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('helvetica', 'B', 16);
        
        // Add token details to PDF
        $pdf->Cell(0, 10, 'Token Information', 0, 1, 'C');
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'Token Number: ' . $token['token_number'], 0, 1);
        $pdf->Cell(0, 10, 'Token Date: ' . $token['token_date'], 0, 1);
        $pdf->Cell(0, 10, 'User Name: ' . $token['user_name'], 0, 1);

        // Output the PDF (force download)
        $pdf->Output('Token_' . $token['token_number'] . '.pdf', 'D');
    } else {
        echo '<script>alert("Token not found."); window.location.href = "token.php";</script>';
    }
} else {
    echo '<script>alert("Invalid request. Token number is missing."); window.location.href = "token.php";</script>';
}

$conn->close();

// End output buffering and flush
ob_end_flush();
?>
