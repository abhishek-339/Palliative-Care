<?php
require 'vendor/autoload.php';
session_start();
if (!isset($_SESSION['staff_user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: Login/login.php");
    exit();
}

// Include database connection
include '../mysql_db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Handle delete token request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_token_id'])) {
    $token_id = $_POST['delete_token_id'];

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
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'main.motherteresa@gmail.com'; 
                $mail->Password = 'bjfv mtrk ngqt mlwh'; 
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('no-reply@hospital.com', 'Hospital');
                $mail->addAddress($token['user_email'], $token['user_name']); 
                $mail->addAddress('main.motherteresa@gmail.com', 'Hospital Office'); 

                $mail->isHTML(true);
                $mail->Subject = 'Token Deleted';
                $mail->Body    = "<html><body><h1>Token Deleted Successfully!</h1><p>Dear {$token['user_name']},</p><p>Your token number <strong>{$token['token_number']}</strong> for the date <strong>{$token['token_date']}</strong> has been deleted.</p><p>Thank you for using our service.</p></body></html>";

                $mail->send();
            } catch (Exception $e) {
                echo "<script>alert('Message could not be sent. Mailer Error: {$mail->ErrorInfo}')</script>";
            }

            echo '<script>alert("Token deleted successfully."); window.location.href = "token_management.php";</script>';
        } else {
            echo '<script>alert("Failed to delete token. Please try again later."); window.location.href = "token_management.php";</script>';
        }
    }
}

// Handle Add Slot Request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_slot_date'])) {
    $add_slot_date = $_POST['add_slot_date'];
    
    // Check if the date exists in token_management
    $sql = "SELECT max_tokens FROM token_management WHERE date = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $add_slot_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update max_tokens
        $sql = "UPDATE token_management SET max_tokens = max_tokens + 1 WHERE date = ?";
    } else {
        // Insert new record with max_tokens = 1
        $sql = "INSERT INTO token_management (date, max_tokens) VALUES (?, 6)";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $add_slot_date);
    $stmt->execute();

    echo '<script>window.location.href = "token_management.php";</script>'; // Refresh page
    exit();
}

// Get the current date and the next 7 days
$current_date = date('Y-m-d');
$dates = [];
for ($i = 0; $i < 7; $i++) {
    $dates[] = date('Y-m-d', strtotime("+$i days"));
}

$slots = [];
foreach ($dates as $date) {
    $sql = "SELECT max_tokens FROM token_management WHERE date = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $slots[$date] = $row ? $row['max_tokens'] : 5; 
}

$sql = "SELECT id, token_number, token_date, token_time, user_name, user_email FROM tokens ORDER BY token_number ASC";
$result = $conn->query($sql);

$tokens = [];
while ($row = $result->fetch_assoc()) {
    $tokens[$row['token_date']][] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Token Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh every 5 minutes (300,000 ms)
        setInterval(function() {
            location.reload();
        }, 300000);
    </script>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0f0f0; margin: 0; padding: 0; }
        .container { margin-top: 20px; overflow-x: auto; }
        .calendar { display: flex; flex-wrap: wrap; }
        .day { width: 14.28%; border: 1px solid #ccc; box-sizing: border-box; padding: 10px; background-color: #fff; min-width: 300px; }
        .token-slot { background-color: #e0e0e0; padding: 5px; margin-bottom: 5px; border-radius: 3px; position: relative; }
        .delete-icon { position: absolute; top: 5px; right: 5px; cursor: pointer; }
        .slot-not-issued { color: #888; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Token Management <a href="current_token.php"><button class="btn btn-primary">Start Schedule</button></a></h1>
        <div class="calendar">
            <?php foreach ($dates as $date) { ?>
                <div class="day">
                    <h5><?php echo date('D, M j', strtotime($date)); ?></h5>
                    <p>Max Tokens: <?php echo ($slots[$date] ?? 'Not Set'); ?></p>
                    <?php if (isset($tokens[$date])) {
                        foreach ($tokens[$date] as $token) { ?>
                            <div class="token-slot">
                                <button class="btn btn-sm reserved">Token Number: <?php echo $token['token_number']; ?></button>
                                <div><strong>Time:</strong> <?php echo $token['token_time']; ?></div>
                                <div><strong>Patient:</strong> <?php echo $token['user_name']; ?></div>
                                <div><strong>Email:</strong> <?php echo $token['user_email']; ?></div>
                                <span class="delete-icon" onclick="confirmDelete(<?php echo $token['id']; ?>)">🗑️</span>
                            </div>
                        <?php }
                    } else {
                        echo "<div class='slot-not-issued'>No tokens issued for this day.</div>";
                    } ?>
                    <form method="post" action="">
                        <input type="hidden" name="add_slot_date" value="<?php echo $date; ?>">
                        <button type="submit" class="btn btn-primary">Add Slot</button>
                    </form>
                </div>
            <?php } ?>
        </div>
    </div>

    <script>
        function confirmDelete(tokenId) {
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            document.getElementById('delete_token_id').value = tokenId;
            deleteModal.show();
        }
    </script>
</body>
</html>
