<?php
session_start();
if (!isset($_SESSION['admin_user_id'])) {
    header("Location: ../ADMIN/Login/admin_login.php");
    exit();
}

// Include database connection
include '../mysql_db.php';

// Fetch all appointments
$sql = "SELECT token_number, token_date, user_name, user_email FROM tokens ORDER BY token_date";
$result = $conn->query($sql);

// Fetch maximum tokens per day
$sql_max_tokens = "SELECT date, max_tokens FROM token_management ORDER BY date";
$result_max_tokens = $conn->query($sql_max_tokens);

$appointments = [];
while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
}

$max_tokens = [];
while ($row = $result_max_tokens->fetch_assoc()) {
    $max_tokens[$row['date']] = $row['max_tokens'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
        }

        .container {
            margin-top: 20px;
        }

        .calendar {
            display: flex;
            flex-wrap: wrap;
        }

        .day {
            width: 14.28%;
            border: 1px solid #ccc;
            box-sizing: border-box;
            padding: 10px;
            background-color: #fff;
        }

        .day h5 {
            margin: 0 0 10px;
        }

        .appointment {
            background-color: #e0e0e0;
            padding: 5px;
            margin-bottom: 5px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Dashboard</h1>
        <h2>Appointments</h2>
        <div class="calendar">
            <?php
            $start_date = new DateTime();
            for ($i = 0; $i < 30; $i++) {
                $date = $start_date->format('Y-m-d');
                echo '<div class="day">';
                echo '<h5>' . $start_date->format('D, M j') . '</h5>';
                echo '<p>Max Tokens: ' . ($max_tokens[$date] ?? 'Not Set') . '</p>';
                foreach ($appointments as $appointment) {
                    if ($appointment['token_date'] == $date) {
                        echo '<div class="appointment">';
                        echo 'Token: ' . $appointment['token_number'] . '<br>';
                        echo 'Name: ' . $appointment['user_name'] . '<br>';
                        echo 'Email: ' . $appointment['user_email'];
                        echo '</div>';
                    }
                }
                echo '<form method="post" action="update_slots.php">';
                echo '<input type="hidden" name="date" value="' . $date . '">';
                echo '<input type="number" name="max_tokens" value="' . ($max_tokens[$date] ?? '') . '" placeholder="Max Tokens">';
                echo '<button type="submit" class="btn btn-primary btn-sm mt-2">Update</button>';
                echo '</form>';
                echo '</div>';
                $start_date->modify('+1 day');
            }
            ?>
        </div>
    </div>
</body>
</html>