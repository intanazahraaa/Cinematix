<?php
session_start();
require_once 'db_connect.php'; // Koneksi database

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: /cinematix/admin/admin.php');
        exit();
    } elseif ($_SESSION['role'] === 'user') {
        header('Location: /cinematix/index1.php');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header('Location: /cinematix/admin/admin.php');
                exit();
            } else {
                header('Location: /cinematix/index1.php');
                exit();
            }
        } else {
            $error = "Username atau password salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cinematix</title>

<style>
        body {
    background: linear-gradient(to right,rgb(69, 134, 143),rgb(168, 218, 233));
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 0;
    padding: 0;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

.login-container {
    width: 360px;
    background-color: #fff;
    padding: 40px 30px;
    border-radius: 12px;
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.2);
    animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.login-container h2 {
    text-align: center;
    margin-bottom: 25px;
    color:rgb(12, 7, 1);
    font-size: 24px;
    font-weight: bold;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    color: #2c3e50;
}

.form-group input {
    width: 100%;
    padding: 10px 1px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.3s ease;
}

.form-group input:focus {
    border-color:rgb(10, 62, 97);
    outline: none;
}

.btn {
    width: 100%;
    padding: 12px;
    background: linear-gradient(to right,rgb(8, 58, 65),rgb(9, 41, 54));
    border: none;
    border-radius: 6px;
    color: white;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.3s ease;
}

.btn:hover {
    background: linear-gradient(to right, #2a5298, #1e3c72);
}

.error-message {
    background-color: #e74c3c;
    color: white;
    padding: 10px;
    margin-bottom: 20px;
    border-radius: 4px;
    text-align: center;
    font-weight: bold;
}

.register-link {
    text-align: center;
    margin-top: 18px;
}

.register-link a {
    color: #2a5298;
    text-decoration: none;
    font-weight: 500;
}

.register-link a:hover {
    text-decoration: underline;
}

footer {
    text-align: center;
    margin-top: 40px;
    padding: 10px;
    font-size: 13px;
    color: #eee;
    position: absolute;
    bottom: 10px;
    width: 100%;
}

    </style>
</head>
<body>

    <div class="login-container">
        <h2>Login sebagai User/Admin Cinematix</h2>

        <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
            </div>

            <button type="submit" class="btn">Login</button>
        </form>

        <div class="register-link">
            <p>Belum punya akun? <a href="register.php">Daftar Sekarang</a></p>
        </div>
    </div>

    <footer>
        <p>&copy; <?= date('Y') ?> Cinematix. All rights reserved.</p>
    </footer>

</body>
</html>
