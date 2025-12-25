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
<?php $page_title = "Kelola Pengguna"; include "includes/head.php"; ?>

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
                                    <i class="pe-7s-users icon-gradient bg-mean-fruit"></i>
                                </div>
                                <div>Manajemen Pengguna
                                    <div class="page-title-subheading">Kelola pengguna dan hak akses sistem</div>
                                </div>
                            </div>
                            <div class="page-title-actions">
                                <a href="/create_user.php" class="btn-shadow btn btn-info">
                                    <i class="pe-7s-plus"></i> Tambah Pengguna
                                </a>
                            </div>
                        </div>
                    </div>

                    <?php if(isset($_GET['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($_GET['success_message']) ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php if(isset($_GET['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($_GET['error_message']) ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="main-card mb-3 card">
                                <div class="card-header">Daftar Pengguna</div>
                                <div class="table-responsive">
                                    <table class="align-middle mb-0 table table-borderless table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Username</th>
                                                <th>Role</th>
                                                <th>Tanggal Registrasi</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($users)): ?>
                                                <?php foreach ($users as $user): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="widget-content p-0">
                                                                <div class="widget-content-wrapper">
                                                                    <div class="widget-content-left flex2">
                                                                        <div class="widget-heading"><?= htmlspecialchars($user['username']) ?></div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $badge_class = 'badge-info';
                                                            if ($user['role'] === 'admin') $badge_class = 'badge-danger';
                                                            elseif ($user['role'] === 'editor') $badge_class = 'badge-warning';
                                                            ?>
                                                            <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($user['role']) ?></span>
                                                        </td>
                                                        <td><?= date("d M Y", strtotime($user['created_at'])) ?></td>
                                                        <td class="text-center">
                                                            <a href="/edit_user.php?id=<?= $user['id'] ?>" class="btn btn-primary btn-sm">
                                                                <i class="pe-7s-pen"></i> Edit
                                                            </a>
                                                            <?php if ($user['id'] !== $_SESSION['id']): ?>
                                                                <a href="/delete_user.php?id=<?= $user['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?')">
                                                                    <i class="pe-7s-trash"></i> Hapus
                                                                </a>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">Tidak ada pengguna untuk ditampilkan.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
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