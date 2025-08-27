<?php
session_start();
include 'connection.php'; // DB connection

// Create users table if not exists
$tableCheck = "SHOW TABLES LIKE 'users'";
$tableResult = $conn->query($tableCheck);

if ($tableResult->num_rows === 0) {
    $createTableSQL = "
        CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $conn->query($createTableSQL);
}

// Handle Signup
if (isset($_POST['action']) && $_POST['action'] === 'signup') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param("ss", $username, $hashedPassword);
            if ($stmt->execute()) {
                echo "<script>alert('Signup successful! You can now login.');</script>";
            } else {
                echo "<script>alert('Username already exists.');</script>";
            }
            $stmt->close();
        }
    } else {
        echo "<script>alert('Please fill all fields.');</script>";
    }
}

// Handle Login
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("Location: home.php");
                exit;
            } else {
                echo "<script>alert('Incorrect password.');</script>";
            }
        } else {
            echo "<script>alert('User not found.');</script>";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Auth Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            width: 350px;
        }
        .tabs {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .tabs button {
            flex: 1;
            padding: 10px;
            border: none;
            cursor: pointer;
            font-weight: bold;
            background: #e5e7eb;
            border-radius: 8px;
            margin: 2px;
        }
        .tabs button.active {
            background: #2563eb;
            color: white;
        }
        form {
            display: none;
        }
        form.active {
            display: block;
        }
        input {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border: 1px solid #d1d5db;
            border-radius: 8px;
        }
        button.submit {
            width: 100%;
            padding: 10px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        button.submit:hover {
            background: #1d4ed8;
        }
    </style>
    <script>
        function showForm(type) {
            document.getElementById("loginForm").classList.remove("active");
            document.getElementById("signupForm").classList.remove("active");
            document.getElementById(type + "Form").classList.add("active");

            document.getElementById("loginTab").classList.remove("active");
            document.getElementById("signupTab").classList.remove("active");
            document.getElementById(type + "Tab").classList.add("active");
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="tabs">
            <button id="loginTab" class="active" onclick="showForm('login')">Login</button>
            <button id="signupTab" onclick="showForm('signup')">Signup</button>
        </div>

        <!-- Login Form -->
        <form id="loginForm" method="POST" class="active">
            <input type="hidden" name="action" value="login">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" class="submit">Login</button>
        </form>

        <!-- Signup Form -->
        <form id="signupForm" method="POST">
            <input type="hidden" name="action" value="signup">
            <input type="text" name="username" placeholder="Choose Username" required>
            <input type="password" name="password" placeholder="Choose Password" required>
            <button type="submit" class="submit">Signup</button>
        </form>
    </div>
</body>
</html>
