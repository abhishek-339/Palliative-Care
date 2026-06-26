<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Firebase_Login/login.php");
    exit();
}

// Include database connection
include '../mysql_db.php';

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../Tokens/vendor/autoload.php';

// Fetch user details
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Guest';
$user_email = $_SESSION['user_email'] ?? '';

// Fetch the selected date or default to today's date
$selected_date = $_POST['date'] ?? date('Y-m-d');

// Fetch the selected time
$selected_time = $_POST['time'] ?? '00:00:00'; // Default time if not selected

// Fetch the maximum tokens for the selected date
$sql = "SELECT max_tokens FROM token_management WHERE date = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $selected_date);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $max_tokens = $row['max_tokens'];
} else {
    // Use default token value if no specific entry for the date
    $default_max_tokens = 5; // Default value for max tokens per day
    $max_tokens = $default_max_tokens;
}

// Count the number of tokens issued for the selected date
$query = "SELECT COUNT(*) as token_count FROM tokens WHERE token_date = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $selected_date);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$token_count = $row['token_count'];

// Check if the maximum tokens have been reached
if ($token_count >= $max_tokens) {
    echo '<script>alert("All tokens have been issued for the selected date."); window.location.href = "token.php";</script>';
    exit();
}

// Issue a new token
$new_token_number = $token_count + 1;
$query = "INSERT INTO tokens (token_number, user_id, user_name, user_email, token_date, token_time) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param('isssss', $new_token_number, $user_id, $user_name, $user_email, $selected_date, $selected_time);

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
        $mail->addAddress($user_email, $user_name); // Patient's email
        $mail->addAddress('main.motherteresa@gmail.com', 'Hospital Office'); // Office email

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Token Issued Successfully';
        $mail->Body    = "
        <html>
        <head>
            <title>Token Issued</title>
        </head>
        <body>
            <h1>Token Issued Successfully!</h1>
            <p>Dear $user_name,</p>
            <p>Your token number is <strong>$new_token_number</strong> for the date <strong>$selected_date</strong> at <strong>$selected_time</strong>.</p>
            <p>Thank you for using our service.</p>
        </body>
        </html>
        ";

        $mail->send();
    } catch (Exception $e) {
        echo "<script>alert('Message could not be sent. Mailer Error: {$mail->ErrorInfo}')</script>";
    }

    echo '<script>alert("Token issued successfully! Your token number is ' . $new_token_number . ' for the date ' . $selected_date . ' at ' . $selected_time . '."); window.location.href = "token.php";</script>';
} else {
    echo '<script>alert("Failed to issue token. Please try again later."); window.location.href = "token.php";</script>';
}
?>
