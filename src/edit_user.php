<?php
session_start();
require_once "config.php";

// Periksa apakah pengguna sudah login dan memiliki peran admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header('Location: /login.php');
    exit;
}

// Inisialisasi variabel
$user_id = null;
$username = '';
$current_role = '';
$error_message = '';
$success_message = '';
$is_self_edit = false;

// Periksa ID dari URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $user_id = (int)$_GET['id'];
} else {
    header('Location: /manage_users.php?error_message=ID pengguna tidak valid.');
    exit;
}

// Cek apakah admin mengedit profilnya sendiri
if ($user_id === (int)$_SESSION['id']) {
    $is_self_edit = true;
}

// --- Logika untuk menangani POST request (update role dan password) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_role = $_POST['role'];
    $new_password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $allowed_roles = ['admin', 'editor', 'kontributor'];

    // Validasi role baru
    if (!in_array($new_role, $allowed_roles)) {
        $error_message = "Peran yang dipilih tidak valid.";
    }
    // Jika admin mencoba mengubah rolenya sendiri, cegah
    elseif ($is_self_edit && $new_role !== 'admin') {
        $error_message = "Anda tidak dapat mengubah peran Anda sendiri dari admin.";
    }
    // Validasi password jika diisi
    elseif (!empty($new_password)) {
        if (strlen($new_password) < 6) {
            $error_message = "Password minimal 6 karakter.";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "Password dan konfirmasi password tidak cocok.";
        } else {
            // Update role dan password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql_update = "UPDATE users SET role = ?, password = ? WHERE id = ?";
            $stmt = mysqli_prepare($link, $sql_update);
            mysqli_stmt_bind_param($stmt, "ssi", $new_role, $hashed_password, $user_id);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                header('Location: /manage_users.php?success_message=Data pengguna berhasil diperbarui.');
                exit;
            } else {
                $error_message = "Terjadi kesalahan. Gagal memperbarui data.";
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        // Update hanya role (password tidak diubah)
        $sql_update = "UPDATE users SET role = ? WHERE id = ?";
        $stmt = mysqli_prepare($link, $sql_update);
        mysqli_stmt_bind_param($stmt, "si", $new_role, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header('Location: /manage_users.php?success_message=Peran pengguna berhasil diperbarui.');
            exit;
        } else {
            $error_message = "Terjadi kesalahan. Gagal memperbarui peran.";
        }
        mysqli_stmt_close($stmt);
    }
}

// --- Ambil data pengguna untuk ditampilkan di form (GET request) ---
$sql_select = "SELECT username, role FROM users WHERE id = ?";
$stmt = mysqli_prepare($link, $sql_select);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if ($user) {
    $username = $user['username'];
    $current_role = $user['role'];
} else {
    header('Location: /manage_users.php?error_message=Pengguna tidak ditemukan.');
    exit;
}
mysqli_stmt_close($stmt);
mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="id">
<?php $page_title = "Edit Pengguna"; include "includes/head.php"; ?>

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
                                    <i class="pe-7s-config icon-gradient bg-mean-fruit"></i>
                                </div>
                                <div>Edit Peran Pengguna
                                    <div class="page-title-subheading">Perbarui peran pengguna: <?= htmlspecialchars($username) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="main-card mb-3 card">
                                <div class="card-header">Form Edit Pengguna</div>
                                <div class="card-body">
                                    <?php if($error_message): ?>
                                        <div class="alert alert-danger" role="alert">
                                            <?= htmlspecialchars($error_message) ?>
                                        </div>
                                    <?php endif; ?>

                                    <form action="/edit_user.php?id=<?= $user_id ?>" method="post" class="needs-validation" novalidate>
                                        <div class="position-relative form-group">
                                            <label for="username-display">Username</label>
                                            <input type="text" id="username-display" class="form-control" value="<?= htmlspecialchars($username) ?>" disabled>
                                            <small class="form-text text-muted">Username tidak dapat diubah</small>
                                        </div>

                                        <div class="position-relative form-group">
                                            <label for="role">Peran (Role)</label>
                                            <select name="role" id="role" class="form-control" <?= $is_self_edit ? 'title="Anda tidak dapat mengubah peran Anda sendiri"' : '' ?>>
                                                <option value="kontributor" <?= $current_role === 'kontributor' ? 'selected' : '' ?>>Kontributor</option>
                                                <option value="editor" <?= $current_role === 'editor' ? 'selected' : '' ?>>Editor</option>
                                                <option value="admin" <?= $current_role === 'admin' ? 'selected' : '' ?>>Admin</option>
                                            </select>
                                            <?php if ($is_self_edit): ?>
                                                <small class="form-text text-muted">Anda tidak dapat mengubah peran Anda sendiri dari 'admin'.</small>
                                            <?php endif; ?>
                                        </div>

                                        <hr class="my-4">
                                        <h5 class="mb-3">Ubah Password (Opsional)</h5>
                                        <p class="text-muted small">Kosongkan jika tidak ingin mengubah password</p>

                                        <div class="position-relative form-group">
                                            <label for="password">Password Baru</label>
                                            <input type="password" name="password" id="password" class="form-control" placeholder="Minimal 6 karakter">
                                            <small class="form-text text-muted">Kosongkan jika tidak ingin mengubah password</small>
                                        </div>

                                        <div class="position-relative form-group">
                                            <label for="confirm_password">Konfirmasi Password Baru</label>
                                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Ulangi password baru">
                                        </div>

                                        <div class="mt-4">
                                            <button type="submit" class="btn btn-success">
                                                <i class="pe-7s-check"></i> Simpan Perubahan
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