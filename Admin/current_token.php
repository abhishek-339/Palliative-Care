<?php
include 'functions.php';

$current_date = date('Y-m-d');
$tokens = fetchTokens($current_date);
$currentTokenNumber = fetchCurrentTokenNumber($current_date);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['start'])) {
        if (!empty($tokens)) {
            $currentTokenNumber = $tokens[0]['token_number'];
            updateCurrentToken($currentTokenNumber, $current_date);
            foreach ($tokens as $token) {
                sendEmail($token);
            }
        }
    } elseif (isset($_POST['next'])) {
        $currentIndex = array_search($currentTokenNumber, array_column($tokens, 'token_number'));
        if ($currentIndex !== false && $currentIndex < count($tokens) - 1) {
            $currentTokenNumber = $tokens[$currentIndex + 1]['token_number'];
            updateCurrentToken($currentTokenNumber, $current_date);
            sendEmail($tokens[$currentIndex + 1]);
        } else {
            echo "No more tokens for today.";
        }
    } elseif (isset($_POST['delete_token_id'])) {
        deleteToken($_POST['delete_token_id']);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Current Tokens</title>
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
            overflow-x: auto;
        }

        .calendar {
            display: flex;
            flex-wrap: wrap;
        }

        .day {
            width: 100%;
            border: 1px solid #ccc;
            box-sizing: border-box;
            padding: 10px;
            background-color: #fff;
            min-width: 300px;
        }

        .day h5 {
            margin: 0 0 10px;
        }

        .token-slot {
            background-color: #e0e0e0;
            padding: 5px;
            margin-bottom: 5px;
            border-radius: 3px;
            position: relative;
        }

        .token-slot .delete-icon {
            position: absolute;
            top: 5px;
            right: 5px;
            cursor: pointer;
        }

        .slot-not-issued {
            color: #888;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Current Tokens</h1>
        <form method="post">
            <button type="submit" name="start" class="btn btn-primary">Start</button>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#currentTokenModal">Next</button>
        </form>
        <h2>Current Token Number: <span id="current-token-number"><?php echo $currentTokenNumber; ?></span></h2>
        <div class="calendar">
            <?php if (empty($tokens)): ?>
                <div class="day">
                    <h5><?php echo date('D, M j', strtotime($current_date)); ?></h5>
                    <div class="slot-not-issued">No tokens issued for today.</div>
                </div>
            <?php else: ?>
                <div class="day">
                    <h5><?php echo date('D, M j', strtotime($current_date)); ?></h5>
                    <?php foreach ($tokens as $token): ?>
                        <div class="token-slot">
                            <button class="btn btn-sm reserved">
                                <?php echo "Token Number: {$token['token_number']}"; ?>
                            </button>
                            <div>
                                <strong>Time:</strong> <?php echo $token['token_time']; ?>
                            </div>
                            <div>
                                <strong>Patient:</strong> <?php echo $token['user_name']; ?>
                            </div>
                            <div>
                                <strong>Email:</strong> <?php echo $token['user_email']; ?>
                            </div>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="delete_token_id" value="<?php echo $token['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Current Token Modal -->
    <div class="modal fade" id="currentTokenModal" tabindex="-1" aria-labelledby="currentTokenModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="currentTokenModalLabel">Current Patient</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="current-patient-details">
                    <?php if ($currentTokenNumber > 0): ?>
                        <?php
                        $currentIndex = array_search($currentTokenNumber, array_column($tokens, 'token_number'));
                        if ($currentIndex !== false) {
                            $currentPatient = $tokens[$currentIndex];
                        ?>
                            <div>
                                <strong>Token Number:</strong> <?php echo $currentPatient['token_number']; ?>
                            </div>
                            <div>
                                <strong>Time:</strong> <?php echo $currentPatient['token_time']; ?>
                            </div>
                            <div>
                                <strong>Patient:</strong> <?php echo $currentPatient['user_name']; ?>
                            </div>
                            <div>
                                <strong>Email:</strong> <?php echo $currentPatient['user_email']; ?>
                            </div>
                        <?php } else { ?>
                            <div>No current patient.</div>
                        <?php } ?>
                    <?php else: ?>
                        <div>No current patient.</div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <form method="post">
                        <button type="submit" name="next" class="btn btn-primary">Next</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>