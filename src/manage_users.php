<?php
session_start();
require_once "config.php";

// Periksa apakah pengguna sudah login dan memiliki peran admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    // Jika tidak, arahkan ke halaman login atau halaman error
    header('Location: /login.php'); 
    exit;
}

// Logika untuk menangani aksi (akan ditambahkan nanti)
// Contoh: ?action=deleted, ?action=role_changed

// Ambil semua pengguna dari database
$users = [];
$sql = "SELECT id, username, role, created_at FROM users ORDER BY created_at DESC";
if ($result = mysqli_query($link, $sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    mysqli_free_result($result);
}
mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna</title>
    <link rel="stylesheet" href="/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" href="/assets/default_post_image.jpeg" type="image/jpeg">
</head>
<body>
    <div class="main-container">
        <div class="user-info">
            <span>Admin Panel: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
            <a href="/dashboard.php" class="logout-button" style="background-color: #5bc0de;">Kembali ke Dashboard</a>
        </div>
        
        <div class="dashboard-header">
            <h1>Manajemen Pengguna</h1>
            <div>
                <a href="/create_user.php" class="button-primary"><i class="fa-solid fa-plus"></i> Tambah Pengguna</a>
            </div>
        </div>

        <?php if(isset($_GET['success_message'])) echo '<p class="success-message">'.htmlspecialchars($_GET['success_message']).'</p>'; ?>
        <?php if(isset($_GET['error_message'])) echo '<p class="error-message">'.htmlspecialchars($_GET['error_message']).'</p>'; ?>

        <div class="posts-table-container">
            <table class="posts-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Tanggal Registrasi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><span class="status-badge status-<?= htmlspecialchars($user['role']) ?>"><?= htmlspecialchars($user['role']) ?></span></td>
                                <td><?= date("d M Y", strtotime($user['created_at'])) ?></td>
                                <td>
                                    <!-- Tombol aksi akan dibuat nanti -->
                                    <a href="/edit_user.php?id=<?= $user['id'] ?>" class="action-btn edit-btn"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                                    
                                    <?php // Jangan biarkan admin menghapus diri sendiri ?>
                                    <?php if ($user['id'] !== $_SESSION['id']): ?>
                                        <a href="/delete_user.php?id=<?= $user['id'] ?>" class="action-btn delete-btn" onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?')"><i class="fa-solid fa-trash"></i> Hapus</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center;">Tidak ada pengguna untuk ditampilkan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>