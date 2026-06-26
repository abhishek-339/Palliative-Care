<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php';
session_start();
if (!isset($_SESSION['staff_user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: Login/login.php");
    exit();
}
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include database connection
include '../mysql_db.php';

function deleteToken($token_id) {
    global $conn;

    // Fetch token details using token_id
    $sql = "SELECT id, token_number, token_date, user_name, user_email FROM tokens WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $token_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $token = $result->fetch_assoc();

    if ($token) {
        // Delete the token using token_id
        $sql = "DELETE FROM tokens WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $token_id);
        if ($stmt->execute()) {
            // Send email using PHPMailer
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'main.motherteresa@gmail.com'; // Your Gmail address
                $mail->Password = 'bjfv mtrk ngqt mlwh'; // Your Gmail App Password
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                // Recipients
                $mail->setFrom('no-reply@hospital.com', 'Hospital');
                $mail->addAddress($token['user_email'], $token['user_name']); // Patient's email
                $mail->addAddress('main.motherteresa@gmail.com', 'Hospital Office'); // Office email

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Token Deleted';
                $mail->Body    = "
                <html>
                <head>
                    <title>Token Deleted</title>
                </head>
                <body>
                    <h1>Token Deleted Successfully!</h1>
                    <p>Dear {$token['user_name']},</p>
                    <p>Your token number <strong>{$token['token_number']}</strong> for the date <strong>{$token['token_date']}</strong> has been deleted.</p>
                    <p>Thank you for using our service.</p>
                </body>
                </html>
                ";

                $mail->send();
            } catch (Exception $e) {
                echo "Error: " . $mail->ErrorInfo;
                exit();
            }

            echo "Token deleted successfully.";
        } else {
            echo "Failed to delete token. Please try again later.";
        }
    }
}

function sendEmail($token) {
    $email = $token['user_email'];
    $name = $token['user_name'];
    $tokenNumber = $token['token_number'];
    $tokenDate = $token['token_date'];
    global $conn;
    
     // Fetch the current token number from the database
     $sql = "SELECT token_number FROM current_token WHERE token_date = ?";
     $stmt = $conn->prepare($sql);
     $stmt->bind_param('s', $tokenDate);
     $stmt->execute();
     $result = $stmt->get_result();
     $currentTokenNumber = 0;
     if ($row = $result->fetch_assoc()) {
         $currentTokenNumber = $row['token_number'];
     }
     // Close the database connection
    $conn->close();

    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'main.motherteresa@gmail.com'; // Your Gmail address
        $mail->Password = 'bjfv mtrk ngqt mlwh'; // Your Gmail App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('no-reply@hospital.com', 'Hospital');
        $mail->addAddress($email, $name); // Patient's email
        $mail->addAddress('main.motherteresa@gmail.com', 'Hospital Office'); // Office email

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Token Notification';
        $mail->Body    = "
        <html>
        <head>
            <title>Token Notification</title>
        </head>
        <body>
            <h1>Token Notification</h1>
            <p>Dear {$name},</p>
            <p>Your token number is <strong>{$tokenNumber}</strong> for the date <strong>{$tokenDate}</strong>.</p>
            <p>The current token number is <strong>{$currentTokenNumber}</strong>.</p>
            <p>Thank you for using our service.</p>
        </body>
        </html>
        ";

        $mail->send();
        echo "Email sent successfully.";
    } catch (Exception $e) {
        echo "Error: " . $mail->ErrorInfo;
    }
}

function updateCurrentToken($currentTokenNumber, $tokenDate) {
    global $conn;

    // Update or insert the current token number in the database
    $sql = "REPLACE INTO current_token (token_number, token_date) VALUES (?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $currentTokenNumber, $tokenDate);
    if ($stmt->execute()) {
        echo "Current token number updated successfully.";
    } else {
        echo "Failed to update current token number.";
    }
}

function fetchTokens($current_date) {
    global $conn;

    $sql = "SELECT id, token_number, token_date, token_time, user_name, user_email FROM tokens WHERE token_date = ? ORDER BY token_number";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $current_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $tokens = [];
    while ($row = $result->fetch_assoc()) {
        $tokens[] = $row;
    }

    return $tokens;
}

function fetchCurrentTokenNumber($current_date) {
    global $conn;

    $sql = "SELECT token_number FROM current_token WHERE token_date = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $current_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentTokenNumber = 0;
    if ($row = $result->fetch_assoc()) {
        $currentTokenNumber = $row['token_number'];
    }

    return $currentTokenNumber;
}
?>
