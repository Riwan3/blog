<?php
session_start();
 
// Jika user sudah login, arahkan ke dashboard
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: /dashboard.php");
    exit;
}
 
require_once "config.php";
 
$username = $password = "";
$error = '';
 
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    if(empty(trim($_POST["username"]))){
        $error = "Tolong masukkan username.";
    } else{
        $username = trim($_POST["username"]);
    }
    
    if(empty(trim($_POST["password"]))){
        $error = "Tolong masukkan password Anda.";
    } else{
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
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $role);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($password, $hashed_password)){
                            session_start();
                            
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;                            
                            $_SESSION["role"] = $role;
                            
                            header("location: /dashboard.php");
                        } else{
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Blog Saya</title>
    <link rel="stylesheet" href="/auth.css">
    <link rel="icon" href="/assets/default_post_image.jpeg" type="image/jpeg">
</head>
<body>
    <div class="auth-container">
        <form class="auth-form" method="POST" action="/login.php">
            <h2>Login</h2>
            <?php if(isset($_GET['registered'])): ?>
                <p class="success-message">Registrasi berhasil! Silakan login.</p>
            <?php endif; ?>
            <?php if ($error): ?>
                <p class="error-message"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required value="<?= htmlspecialchars($username); ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="auth-button">Login</button>
            <p class="auth-switch">Belum punya akun? <a href="/register.php">Daftar di sini</a></p>
        </form>
    </div>
</body>
</html>
