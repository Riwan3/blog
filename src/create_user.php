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
<?php $page_title = "Buat Pengguna Baru"; include "includes/head.php"; ?>

<body>
    <div class="app-container app-theme-white body-tabs-shadow fixed-sidebar fixed-header closed-sidebar">
        <?php include "includes/header.php" ?>

        <div class="app-main">
            <?php include "includes/sidebar.php" ?>

            <div class="app-main__outer">
                <div class="app-main__inner">
                    <div class="app-page-title">
                        <div class="page-title-wrapper">
                            <div class="page-title-heading">
                                <div class="page-title-icon">
                                    <i class="pe-7s-add-user icon-gradient bg-mean-fruit"></i>
                                </div>
                                <div>Buat Pengguna Baru
                                    <div class="page-title-subheading">Tambahkan pengguna baru ke sistem</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="main-card mb-3 card">
                                <div class="card-header">Form Pengguna Baru</div>
                                <div class="card-body">
                                    <?php if(!empty($errors)): ?>
                                        <div class="alert alert-danger" role="alert">
                                            <h5>Gagal membuat pengguna:</h5>
                                            <ul>
                                                <?php foreach($errors as $error): ?>
                                                    <li><?= htmlspecialchars($error) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>

                                    <form action="/create_user.php" method="post" class="needs-validation" novalidate>
                                        <div class="position-relative form-group">
                                            <label for="username">Username</label>
                                            <input type="text" id="username" name="username" class="form-control" value="<?= htmlspecialchars($username) ?>" required>
                                        </div>

                                        <div class="position-relative form-group">
                                            <label for="password">Password</label>
                                            <input type="password" id="password" name="password" class="form-control" required>
                                            <small class="form-text text-muted">Password minimal 6 karakter</small>
                                        </div>

                                        <div class="position-relative form-group">
                                            <label for="role">Peran (Role)</label>
                                            <select name="role" id="role" class="form-control">
                                                <option value="kontributor" <?= $role === 'kontributor' ? 'selected' : '' ?>>Kontributor</option>
                                                <option value="editor" <?= $role === 'editor' ? 'selected' : '' ?>>Editor</option>
                                                <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                                            </select>
                                        </div>

                                        <div class="mt-4">
                                            <button type="submit" class="btn btn-success">
                                                <i class="pe-7s-check"></i> Buat Pengguna
                                            </button>
                                            <a href="/manage_users.php" class="btn btn-danger">
                                                <i class="pe-7s-close"></i> Batal
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include "includes/footer.php" ?>

<!--
  Duta Damai Kalimantan Selatan - Blog System
  © 2025 Duta Damai Kalimantan Selatan

  Menggunakan komponen dari CMS Jawara
  © 2020 Djadjoel (MIT License)
  Repository: https://github.com/djadjoel/cmsjawara
-->
</body>
</html>