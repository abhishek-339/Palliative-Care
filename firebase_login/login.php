<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://www.gstatic.com/firebasejs/9.17.1/firebase-app.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
    <script src="https://www.gstatic.com/firebasejs/9.17.1/firebase-auth.js"></script>
    <title>Login</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');

        body {
            background-image: url('background.jpg');
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: 'Roboto', sans-serif;
        }

        #login-box {
            background: rgba(255, 255, 255, 0.9);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 400px;
            width: 90%;
            overflow: auto;
        }

        #loading-spinner {
            display: none;
            font-size: 30px;
        }

        #login-content {
            display: block;
        }

        #loading-content {
            display: none;
        }

        button,
        input {
            margin: 10px 0;
            padding: 12px;
            width: 100%;
            border: none;
            border-radius: 25px;
            font-size: 16px;
        }

        input {
            background-color: #f0f0f0;
            border: 1px solid #ccc;
        }

        button {
            background-color: #6200ea;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #3700b3;
        }

        #google-login-btn {
            background-color: #db4437;
            margin-top: 20px;
        }

        #google-login-btn:hover {
            background-color: #c23321;
        }

        .button-group {
            display: flex;
            justify-content: space-between;
        }

        .button-group button {
            width: 48%;
        }

        @media (max-width: 600px) {
            #login-box {
                margin: 10px;
                margin-top: -300px;
                padding-top: 20px;
                padding: 20px;
                padding-right: 40px;
            }

            button,
            input {
                padding: 10px;
                font-size: 14px;
            }

            .button-group button {
                width: 48%;
            }
        }
    </style>
</head>

<body>
    <div id="login-box">
        <h1>Login</h1>
        <form id="email-login-form">
            <input type="email" id="email" placeholder="Email" required><br>
            <input type="password" id="password" placeholder="Password" required><br>
            <div class="button-group">
                <button type="submit">Login</button>
                <button type="button" id="register-btn">Register</button>
            </div>
        </form>
        <button id="google-login-btn">Sign in with Google</button>
    </div>

    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/9.17.1/firebase-app.js";
        import { getAuth, GoogleAuthProvider, signInWithPopup, signInWithEmailAndPassword } from "https://www.gstatic.com/firebasejs/9.17.1/firebase-auth.js";

        const firebaseConfig = {
            apiKey: "API Key",
            authDomain: "domain,
            projectId: "project id",
            storageBucket: "url",
            messagingSenderId: "id",
            appId: "id",
            measurementId: "id"
        };

        const app = initializeApp(firebaseConfig);
        const auth = getAuth();

        document.getElementById('google-login-btn').addEventListener('click', googleLogin);

        function googleLogin() {
            const provider = new GoogleAuthProvider();
            signInWithPopup(auth, provider)
                .then(result => {
                    const user = result.user;
                    console.log('Google login successful:', user);

                    // Send user data to PHP backend using a form submission
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '';

                    const nameInput = document.createElement('input');
                    nameInput.type = 'hidden';
                    nameInput.name = 'name';
                    nameInput.value = user.displayName;
                    form.appendChild(nameInput);

                    const emailInput = document.createElement('input');
                    emailInput.type = 'hidden';
                    emailInput.name = 'email';
                    emailInput.value = user.email;
                    form.appendChild(emailInput);

                    document.body.appendChild(form);
                    form.submit();
                })
                .catch(error => console.error('Google login error:', error));
        }

        document.getElementById('email-login-form').addEventListener('submit', (e) => {
            e.preventDefault();
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            signInWithEmailAndPassword(auth, email, password)
                .then(userCredential => {
                    const user = userCredential.user;
                    console.log('Email login successful:', user);

                    // Send user data to PHP backend using a form submission
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '';

                    const nameInput = document.createElement('input');
                    nameInput.type = 'hidden';
                    nameInput.name = 'name';
                    nameInput.value = user.displayName || 'No Name';
                    form.appendChild(nameInput);

                    const emailInput = document.createElement('input');
                    emailInput.type = 'hidden';
                    emailInput.name = 'email';
                    emailInput.value = user.email;
                    form.appendChild(emailInput);

                    document.body.appendChild(form);
                    form.submit();
                })
                .catch(error => {
                    alert('Login failed: ' + error.message);
                });
        });
    </script>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require '../mysql_db.php'; // Include your DB connection file
    
        $email = $_POST['email'] ?? '';
        $name = $_POST['name'] ?? '';

        if (empty($email)) {
            echo '<script>alert("Email is required.");</script>';
            exit;
        }

        // Check if email exists in the patient table
        $query = "SELECT user_id FROM patient WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Email exists, fetch user_id
            $row = $result->fetch_assoc();
            session_start();
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['user_name'] = $_POST['name'];
            $_SESSION['user_email'] = $_POST['email'];
            echo '<script>window.location.href = "../index.php";</script>';
        } else {
            // Email does not exist, insert new record
            $user_id = uniqid('user_');
            $insertQuery = "INSERT INTO patient (user_id, name, email) VALUES (?, ?, ?)";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bind_param('sss', $user_id, $name, $email);

            if ($insertStmt->execute()) {
                session_start();
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $_POST['name'];
                $_SESSION['user_email'] = $_POST['email'];
                echo '<script>window.location.href = "../index.php";</script>';
            } else {
                echo '<script>alert("Failed to register user.");</script>';
            }
        }
    }
    ?>

</body>

</html>
