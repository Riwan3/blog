<?php
/**
 * Duta Damai Kalimantan Selatan - Blog System
 * © 2025 Duta Damai Kalimantan Selatan
 *
 * Menggunakan komponen dari CMS Jawara
 * © 2020 Djadjoel (MIT License)
 * https://github.com/djadjoel/cmsjawara
 */

session_start();
require_once "security.php";

// Set security headers
setSecurityHeaders();

// Check if IP is blocked
if (isIPBlocked()) {
    die('Access denied. Your IP has been blocked due to suspicious activity.');
}

// Check for Remember Me cookie
if(!isset($_SESSION["loggedin"]) && isset($_COOKIE['remember_user']) && isset($_COOKIE['remember_token'])){
    require_once "config.php";

    $user_id = intval($_COOKIE['remember_user']);

    // Fetch user data
    $sql = "SELECT id, username, role FROM users WHERE id = ?";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        if(mysqli_stmt_execute($stmt)){
            mysqli_stmt_store_result($stmt);
            if(mysqli_stmt_num_rows($stmt) == 1){
                mysqli_stmt_bind_result($stmt, $id, $username, $role);
                if(mysqli_stmt_fetch($stmt)){
                    // Auto login
                    $_SESSION["loggedin"] = true;
                    $_SESSION["id"] = $id;
                    $_SESSION["username"] = $username;
                    $_SESSION["role"] = $role;

                    mysqli_stmt_close($stmt);
                    mysqli_close($link);
                    header("location: /dashboard.php");
                    exit;
                }
            }
        }
        mysqli_stmt_close($stmt);
    }
    mysqli_close($link);
}

// Jika user sudah login, arahkan ke dashboard
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: /dashboard.php");
    exit;
}
 
require_once "config.php";
 
$username = $password = "";
$error = '';

if($_SERVER["REQUEST_METHOD"] == "POST"){

    // Verify CSRF Token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        logSecurityEvent('CSRF_FAILED', 'CSRF token validation failed on login', 'WARNING');
        $error = "Invalid security token. Please try again.";
    }
    // Check honeypot (anti-bot)
    elseif (!checkHoneypot()) {
        logSecurityEvent('BOT_DETECTED', 'Honeypot triggered on login form', 'WARNING');
        $error = "Bot detected.";
    }
    // Check rate limit (brute force protection)
    elseif (!checkRateLimit($_SERVER['REMOTE_ADDR'], 5, 900)) {
        $remaining = getRemainingLockoutTime($_SERVER['REMOTE_ADDR'], 900);
        $minutes = ceil($remaining / 60);
        logSecurityEvent('RATE_LIMIT_EXCEEDED', 'Too many login attempts from ' . $_SERVER['REMOTE_ADDR'], 'WARNING');
        $error = "Terlalu banyak percobaan login. Coba lagi dalam $minutes menit.";
    }
    elseif(empty(trim($_POST["username"]))){
        $error = "Tolong masukkan username.";
    } else{
        $username = sanitizeString(trim($_POST["username"]), 50);
    }

    if(empty($error) && empty(trim($_POST["password"]))){
        $error = "Tolong masukkan password Anda.";
    } elseif(empty($error)){
        $password = trim($_POST["password"]);
    }
    
    // Validasi kredensial
    if(empty($error)){
        $sql = "SELECT id, username, password, role FROM users WHERE username = ?";

        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = $username;

            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);

                if(mysqli_stmt_num_rows($stmt) == 1){
                    // Inisialisasi variabel untuk bind_result
                    $id = 0;
                    $hashed_password = '';
                    $role = '';
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $role);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($password, (string)$hashed_password)){
                            session_start();

                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["role"] = $role;

                            // Log successful login
                            logSecurityEvent('LOGIN_SUCCESS', "User $username logged in successfully", 'INFO');

                            // Handle Remember Me
                            if(isset($_POST['remember']) && $_POST['remember'] == 'on'){
                                // Generate secure random token
                                $token = bin2hex(random_bytes(32));

                                // Set cookie for 30 days
                                setcookie('remember_token', $token, time() + (86400 * 30), '/', '', false, true); // HttpOnly
                                setcookie('remember_user', $id, time() + (86400 * 30), '/', '', false, true); // HttpOnly

                                // Store token in database (you may want to create a remember_tokens table)
                                // For now, we'll just use cookies
                            }

                            header("location: /dashboard.php");
                        } else{
                            logSecurityEvent('LOGIN_FAILED', "Failed login attempt for username: $username", 'WARNING');
                            $error = "Password yang Anda masukkan tidak valid.";
                        }
                    }
                } else{
                    $error = "Username tidak ditemukan.";
                }
            } else{
                $error = "Oops! Terjadi kesalahan. Coba lagi nanti.";
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
    <meta name="description" content="Blog Duta Damai Kalimantan Selatan">
    <meta name="author" content="Duta Damai Kalsel">
    <title>Login - Blog Duta Damai Kalsel</title>

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
    <form class="form-signin" method="POST" action="/login.php" autocomplete="off">
        <?= csrfField() ?>
        <?= honeypotField() ?>
        <img class="mb-4" src="/assets/default_post_image.jpeg" alt="Logo" width="72" height="72" style="border-radius: 12px;">
        <h1 class="h3 mb-3 font-weight-normal">Silakan Login</h1>

        <?php if(isset($_GET['registered'])): ?>
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
        <input type="password" id="password" class="form-control" placeholder="Password" name="password" required>

        <div class="checkbox mb-3">
            <label>
                <input type="checkbox" name="remember" value="on"> Ingat Saya
            </label>
        </div>

        <button class="btn btn-lg btn-primary btn-block" type="submit">Masuk</button>

        <p class="mt-3 mb-2">
            <a href="/register.php" class="text-primary">Belum punya akun? Daftar di sini</a>
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
