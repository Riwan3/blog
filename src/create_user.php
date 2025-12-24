<?php
session_start();
require_once "config.php";

// Periksa apakah pengguna sudah login dan memiliki peran admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header('Location: /login.php');
    exit;
}

// Inisialisasi variabel
$username = '';
$password = '';
$role = 'kontributor'; // Default role
$errors = [];

// --- Logika untuk menangani POST request (create user) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    // Validasi
    if (empty($username)) {
        $errors[] = 'Username tidak boleh kosong.';
    }
    if (empty($password)) {
        $errors[] = 'Password tidak boleh kosong.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password minimal harus 6 karakter.';
    }
    $allowed_roles = ['admin', 'editor', 'kontributor'];
    if (!in_array($role, $allowed_roles)) {
        $errors[] = 'Peran yang dipilih tidak valid.';
    }

    // Periksa apakah username sudah ada
    if (empty($errors)) {
        $sql_check = "SELECT id FROM users WHERE username = ?";
        $stmt_check = mysqli_prepare($link, $sql_check);
        mysqli_stmt_bind_param($stmt_check, "s", $username);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        if (mysqli_num_rows($result_check) > 0) {
            $errors[] = 'Username sudah digunakan.';
        }
        mysqli_stmt_close($stmt_check);
    }

    // Jika tidak ada error, masukkan pengguna baru ke database
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql_insert = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
        $stmt_insert = mysqli_prepare($link, $sql_insert);
        mysqli_stmt_bind_param($stmt_insert, "sss", $username, $hashed_password, $role);
        
        if (mysqli_stmt_execute($stmt_insert)) {
            header('Location: /manage_users.php?success_message=Pengguna baru berhasil dibuat.');
            exit;
        } else {
            $errors[] = "Terjadi kesalahan. Gagal membuat pengguna.";
        }
        mysqli_stmt_close($stmt_insert);
    }
    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Pengguna Baru</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
    <div class="main-container">
        <div class="dashboard-header">
            <h1>Buat Pengguna Baru</h1>
            <a href="/manage_users.php" class="button-primary" style="background-color: #5bc0de;">Batal</a>
        </div>

        <?php if(!empty($errors)): ?>
            <div class="error-container">
                <h4>Gagal membuat pengguna:</h4>
                <ul>
                    <?php foreach($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="/create_user.php" method="post" class="post-form">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="role">Peran (Role)</label>
                <select name="role" id="role">
                    <option value="kontributor" <?= $role === 'kontributor' ? 'selected' : '' ?>>Kontributor</option>
                    <option value="editor" <?= $role === 'editor' ? 'selected' : '' ?>>Editor</option>
                    <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
            <div class="form-group">
                <button type="submit" class="button-primary">Buat Pengguna</button>
            </div>
        </form>
    </div>
</body>
</html>