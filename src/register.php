<?php
require_once "config.php";

$error = '';
$username = $password = $confirm_password = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validasi username
    if (empty($username)) {
        $error = 'Tolong masukkan username.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Username hanya bisa mengandung huruf, angka, dan underscore.';
    } else {
        // Cek jika username sudah ada
        $sql = "SELECT id FROM users WHERE username = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = $username;
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $error = 'Username ini sudah digunakan.';
                }
            } else {
                $error = 'Oops! Terjadi kesalahan. Coba lagi nanti.';
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Validasi password
    if (empty($password)) {
        $error = 'Tolong masukkan password.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal harus 6 karakter.';
    }

    // Validasi konfirmasi password
    if (empty($confirm_password)) {
        $error = 'Tolong konfirmasi password.';
    } else {
        if (empty($error) && ($password != $confirm_password)) {
            $error = 'Password tidak cocok.';
        }
    }

    // Jika tidak ada error, masukkan ke database
    if (empty($error)) {
        $sql = "INSERT INTO users (username, password, role) VALUES (?, ?, 'kontributor')";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ss", $param_username, $param_password);
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Hash password

            if (mysqli_stmt_execute($stmt)) {
                // Redirect ke halaman login setelah berhasil
                header('Location: /login.php?registered=true');
                exit;
            } else {
                $error = 'Terjadi kesalahan saat menyimpan data.';
            }
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Blog Saya</title>
    <link rel="stylesheet" href="/auth.css">
    <link rel="icon" href="/assets/default_post_image.jpeg" type="image/jpeg">
</head>
<body>
    <div class="auth-container">
        <form class="auth-form" method="POST" action="/register.php">
            <h2>Register</h2>
            <?php if ($error): ?>
                <p class="error-message"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
             <?php if (isset($_GET['success'])): ?>
                <p class="success-message">Registrasi berhasil! Silakan login.</p>
            <?php endif; ?>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required value="<?= htmlspecialchars($username); ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Konfirmasi Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="auth-button">Register</button>
            <p class="auth-switch">Sudah punya akun? <a href="/login.php">Login di sini</a></p>
        </form>
    </div>
</body>
</html>
