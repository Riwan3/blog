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

// --- Logika untuk menangani POST request (update role) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_role = $_POST['role'];
    $allowed_roles = ['admin', 'editor', 'kontributor'];

    // Validasi role baru
    if (in_array($new_role, $allowed_roles)) {
        // Jika admin mencoba mengubah rolenya sendiri, cegah
        if ($is_self_edit && $new_role !== 'admin') {
            $error_message = "Anda tidak dapat mengubah peran Anda sendiri dari admin.";
        } else {
            // Update role pengguna di database
            $sql_update = "UPDATE users SET role = ? WHERE id = ?";
            $stmt = mysqli_prepare($link, $sql_update);
            mysqli_stmt_bind_param($stmt, "si", $new_role, $user_id);
            if (mysqli_stmt_execute($stmt)) {
                header('Location: /manage_users.php?success_message=Peran pengguna berhasil diperbarui.');
                exit;
            } else {
                $error_message = "Terjadi kesalahan. Gagal memperbarui peran.";
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $error_message = "Peran yang dipilih tidak valid.";
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
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pengguna</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
    <div class="main-container">
        <div class="dashboard-header">
            <h1>Edit Peran Pengguna: <?= htmlspecialchars($username) ?></h1>
            <a href="/manage_users.php" class="button-primary" style="background-color: #5bc0de;">Batal</a>
        </div>

        <?php if($error_message) echo '<p class="error-message">'.$error_message.'</p>'; ?>

        <form action="/edit_user.php?id=<?= $user_id ?>" method="post" class="post-form">
            <div class="form-group">
                <label>Username</label>
                <input type="text" value="<?= htmlspecialchars($username) ?>" disabled>
            </div>
            <div class="form-group">
                <label for="role">Peran (Role)</label>
                <select name="role" id="role" <?= $is_self_edit ? 'title="Anda tidak dapat mengubah peran Anda sendiri"' : '' ?>>
                    <option value="kontributor" <?= $current_role === 'kontributor' ? 'selected' : '' ?>>Kontributor</option>
                    <option value="editor" <?= $current_role === 'editor' ? 'selected' : '' ?>>Editor</option>
                    <option value="admin" <?= $current_role === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
                <?php if ($is_self_edit): ?>
                <small>Anda tidak dapat mengubah peran Anda sendiri dari 'admin'.</small>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <button type="submit" class="button-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</body>
</html>