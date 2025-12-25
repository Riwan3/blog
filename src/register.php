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
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Registrasi Blog Duta Damai Kalimantan Selatan">
    <meta name="author" content="Duta Damai Kalsel">
    <title>Register - Blog Duta Damai Kalsel</title>

    <!-- Bootstrap CSS -->
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet">

    <style>
        .bd-placeholder-img {
            font-size: 1.125rem;
            text-anchor: middle;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        @media (min-width: 768px) {
            .bd-placeholder-img-lg {
                font-size: 3.5rem;
            }
        }
    </style>

    <!-- Custom signin styles -->
    <link href="/assets/css/signin.css" rel="stylesheet">
    <link rel="icon" href="/assets/default_post_image.jpeg" type="image/jpeg">
</head>
<body class="text-center">
    <form class="form-signin" method="POST" action="/register.php" autocomplete="off">
        <img class="mb-4" src="/assets/default_post_image.jpeg" alt="Logo" width="72" height="72" style="border-radius: 12px;">
        <h1 class="h3 mb-3 font-weight-normal">Daftar Akun Baru</h1>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success" role="alert">
                Registrasi berhasil! Silakan login.
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <label for="username" class="sr-only">Username</label>
        <input type="text" id="username" class="form-control" placeholder="Masukkan username" name="username" required autofocus value="<?= htmlspecialchars($username); ?>">

        <label for="password" class="sr-only">Password</label>
        <input type="password" id="password" class="form-control" placeholder="Password (min. 6 karakter)" name="password" required>

        <label for="confirm_password" class="sr-only">Konfirmasi Password</label>
        <input type="password" id="confirm_password" class="form-control" placeholder="Konfirmasi password" name="confirm_password" required>

        <button class="btn btn-lg btn-primary btn-block mt-3" type="submit">Daftar</button>

        <p class="mt-3 mb-2">
            <a href="/login.php" class="text-primary">Sudah punya akun? Login di sini</a>
        </p>

        <p class="mt-5 mb-3 text-muted">&copy; 2025 Duta Damai Kalsel</p>
    </form>

<!--
  Duta Damai Kalimantan Selatan - Blog System
  © 2025 Duta Damai Kalimantan Selatan

  Menggunakan komponen dari CMS Jawara
  © 2020 Djadjoel (MIT License)
  Repository: https://github.com/djadjoel/cmsjawara
-->
</body>
</html>
