<?php
session_start();
require_once "config.php";

// Periksa apakah pengguna sudah login dan memiliki peran admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header('Location: /login.php');
    exit;
}

// Periksa apakah ID pengguna disediakan
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: /manage_users.php?error_message=ID pengguna tidak valid.');
    exit;
}

$user_id_to_delete = (int)$_GET['id'];
$admin_user_id = (int)$_SESSION['id'];

// Admin tidak boleh menghapus akunnya sendiri
if ($user_id_to_delete === $admin_user_id) {
    header('Location: /manage_users.php?error_message=Anda tidak dapat menghapus akun Anda sendiri.');
    exit;
}

// Hapus pengguna dari database
$sql = "DELETE FROM users WHERE id = ?";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id_to_delete);

if (mysqli_stmt_execute($stmt)) {
    // Jika berhasil, arahkan kembali dengan pesan sukses
    header('Location: /manage_users.php?success_message=Pengguna berhasil dihapus.');
} else {
    // Jika gagal, arahkan kembali dengan pesan error
    header('Location: /manage_users.php?error_message=Gagal menghapus pengguna.');
}

mysqli_stmt_close($stmt);
mysqli_close($link);
exit;
?>