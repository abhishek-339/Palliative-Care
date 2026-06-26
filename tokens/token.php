<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Firebase_Login/login.php");
    exit();
}

// Include database connection
include '../mysql_db.php';

// Fetch user details
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Guest';
$user_email = $_SESSION['user_email'] ?? '';

// Default to today's date if not provided
$selected_date = $_GET['date'] ?? date('Y-m-d');

// Fetch maximum tokens for the selected date
$sql = "SELECT max_tokens FROM token_management WHERE date = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $selected_date);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $max_tokens = $row['max_tokens'];
} else {
    // Default token value if no entry for the selected date
    $default_max_tokens = 5;
    $max_tokens = $default_max_tokens;
}

// Count the tokens issued for the selected date
$sql = "SELECT COUNT(*) as token_count FROM tokens WHERE token_date = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $selected_date);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$token_count = $row['token_count'];

// Determine the next token number
$next_token = $token_count + 1;

// Fetch all tokens issued to this user for today and future dates
$today = date('Y-m-d');  // Store today's date in a variable
$sql = "SELECT token_number, token_date FROM tokens WHERE user_id = ? AND token_date >= ? ORDER BY token_date ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('is', $user_id, $today);  // Bind parameters
$stmt->execute();
$result = $stmt->get_result();
$user_tokens = [];
while ($row = $result->fetch_assoc()) {
    $user_tokens[$row['token_date']][] = $row; // Group tokens by date
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Get Token</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous"></script>
    <style>
        /* Styling for the page */
        body {
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
            font-family: 'Roboto', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            padding: 20px;
            box-sizing: border-box;
        }

        .main-content, .side-box {
            background-color: #fff;
            padding: 20px;
            margin: 10px 0;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .side-box {
            max-width: 300px;
            width: 100%;
        }

        .token-list {
            list-style-type: none;
            padding: 0;
        }

        .token-list li {
            padding: 10px;
            border-bottom: 1px solid #ccc;
        }

        .token-list li:last-child {
            border-bottom: none;
        }

        button {
            background-color: #6200ea;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
            padding: 10px;
            border: none;
            border-radius: 5px;
        }

        button:hover {
            background-color: #3700b3;
        }

        .btn-danger {
            background-color: #ff3b30;
        }

        .btn-danger:hover {
            background-color: #ff2b29;
        }

        @media (min-width: 768px) {
            .content-wrapper {
                display: flex;
                justify-content: space-between;
            }

            .main-content {
                flex: 1;
                margin-right: 20px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Get Your Token</h1>
    <div class="content-wrapper">
        <div class="main-content">
            <p>Tokens Issued: <?php echo $token_count; ?></p>
            <p>Maximum Tokens Allowed: <?php echo $max_tokens; ?></p>
            <p>Your Next Token Number: <?php echo $next_token; ?></p>

            <!-- Date Selector Form -->
            <form id="tokenForm" method="get" action="">
                <div class="mb-3">
                    <label for="date" class="form-label">Select Date:</label>
                    <input type="date" class="form-control" id="date" name="date" required
                           min="<?php echo date('Y-m-d'); ?>"
                           max="<?php echo date('Y-m-d', strtotime('+1 week')); ?>"
                           value="<?php echo $selected_date; ?>"
                           onchange="this.form.submit()">
                </div>
            </form>

            <!-- Token Issuance Form -->
            <?php if ($next_token <= $max_tokens): ?>
                <form id="tokenForm" method="post" action="process_token.php">
                    <input type="hidden" name="date" value="<?php echo $selected_date; ?>">
                    <input type="hidden" name="token" value="<?php echo $next_token; ?>">
                    <!-- Add time selection -->
        <div class="mb-3">
            <label for="time" class="form-label">Select Time:</label>
            <input type="time" class="form-control" id="time" name="time" required>
        </div>
                    <button type="submit">Get Token</button>
                </form>
            <?php else: ?>
                <p>No more tokens available for the selected date.</p>
            <?php endif; ?>
        </div>

        <div class="side-box">
            <h2>Your Tokens</h2>
            <?php foreach ($user_tokens as $date => $tokens): ?>
                <h4>Tokens for <?php echo $date; ?></h4>
                <ul class="token-list">
                    <?php foreach ($tokens as $token): ?>
                        <li>
                            Token Number: <?php echo $token['token_number']; ?>
                            <button class="btn-danger btn-sm" onclick="confirmDelete(<?php echo $token['token_number']; ?>, '<?php echo $date; ?>')">Delete</button>
                            <button class="btn-sm" onclick="downloadToken(<?php echo $token['token_number']; ?>)">Download</button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal" tabindex="-1" id="deleteModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this token reservation?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Sure</button>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmDelete(tokenNumber, date) {
        // Show the confirmation modal
        var deleteBtn = document.getElementById('confirmDeleteBtn');
        deleteBtn.onclick = function () {
            window.location.href = "delete_token.php?token_number=" + tokenNumber + "&date=" + date;
        };
        var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }

    function downloadToken(tokenNumber) {
        // Redirect to the download URL
        window.location.href = "download_token.php?token_number=" + tokenNumber;
    }
</script>
</body>
</html>
