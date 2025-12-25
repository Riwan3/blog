<?php
/**
 * Duta Damai Kalimantan Selatan - Blog System
 * © 2025 Duta Damai Kalimantan Selatan
 *
 * Menggunakan komponen DataTables dari CMS Jawara
 * © 2020 Djadjoel (MIT License)
 * https://github.com/djadjoel/cmsjawara
 */

session_start();
require_once "config.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: /login.php');
    exit;
}

$role = $_SESSION['role'];
$user_id = $_SESSION['id'];

// --- Helper Function for Sorting Links ---
function render_sort_link($column, $text, $current_sort, $current_order, $current_search) {
    $order = ($current_sort === $column && $current_order === 'ASC') ? 'desc' : 'asc';
    $icon = '';
    if ($current_sort === $column) {
        $icon = $current_order === 'ASC' ? ' <i class="fa-solid fa-arrow-up-short-wide"></i>' : ' <i class="fa-solid fa-arrow-down-wide-short"></i>';
    }
    
    $query_params = [
        'sort' => $column,
        'order' => $order,
        'search' => $current_search
    ];
    // Hapus parameter kosong
    $query_params = array_filter($query_params, function($value) {
        return $value !== '';
    });

    return '<a href="?' . http_build_query($query_params) . '">' . $text . $icon . '</a>';
}


// --- Logika Search, Sort & Pagination ---
$results_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$starting_limit_number = ($page - 1) * $results_per_page;

$sort_columns = ['title' => 'p.title', 'author' => 'u.username', 'status' => 'p.status', 'date' => 'p.created_at'];
$sort_by = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sort_columns) ? $_GET['sort'] : 'date';
$order = isset($_GET['order']) && in_array(strtoupper($_GET['order']), ['ASC', 'DESC']) ? strtoupper($_GET['order']) : 'DESC';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql_order = " ORDER BY " . $sort_columns[$sort_by] . " " . $order;

// --- Membangun Query Berdasarkan Role, Search, dan Sort ---
$posts = [];
$total_results = 0;

$sql_select = "SELECT p.id, p.title, p.slug, p.created_at, p.status, p.author_id, u.username";
$sql_count = "SELECT COUNT(*)";
$sql_from = " FROM posts p JOIN users u ON p.author_id = u.id";
$sql_limit = " LIMIT ?, ?";

$where_clauses = [];
$params = [];
$types = "";

if ($role === 'kontributor') {
    $where_clauses[] = "p.author_id = ?";
    $params[] = $user_id;
    $types .= "i";
}

if (!empty($search_term)) {
    $where_clauses[] = "p.title LIKE ?";
    $search_param = "%" . $search_term . "%";
    $params[] = $search_param;
    $types .= "s";
}

$sql_where = "";
if (!empty($where_clauses)) {
    $sql_where = " WHERE " . implode(" AND ", $where_clauses);
}

// Dapatkan total hasil
$stmt_count = mysqli_prepare($link, $sql_count . $sql_from . $sql_where);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt_count, $types, ...$params);
}
mysqli_stmt_execute($stmt_count);
$total_results = mysqli_stmt_get_result($stmt_count)->fetch_row()[0];
$total_pages = ceil($total_results / $results_per_page);
mysqli_stmt_close($stmt_count);

// Dapatkan post untuk halaman ini
$params_select = array_merge($params, [$starting_limit_number, $results_per_page]);
$types_select = $types . "ii";

$stmt_select = mysqli_prepare($link, $sql_select . $sql_from . $sql_where . $sql_order . $sql_limit);
if (!empty($params_select)) {
     mysqli_stmt_bind_param($stmt_select, $types_select, ...$params_select);
}
mysqli_stmt_execute($stmt_select);
$result = mysqli_stmt_get_result($stmt_select);
while ($row = mysqli_fetch_assoc($result)) {
    $posts[] = $row;
}
mysqli_stmt_close($stmt_select);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Language" content="id">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Dashboard - Blog Duta Damai Kalsel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, shrink-to-fit=no" />
    <meta name="description" content="Dashboard Blog Duta Damai Kalimantan Selatan">
    <meta name="msapplication-tap-highlight" content="no">
    <link href="/assets/css/main.css" rel="stylesheet">
    <link href="/assets/css/loading.css" rel="stylesheet">
    <link rel="icon" href="/assets/default_post_image.jpeg" type="image/jpeg">
    <script src="/assets/scripts/loading.js"></script>
    <style>
        /* Hover effect untuk sortable headers */
        thead a:hover {
            color: #2980b9 !important;
        }

        /* Dark Mode Styles */
        body.dark-mode {
            background: #1a1a1a !important;
            color: #e0e0e0 !important;
        }

        body.dark-mode .app-container {
            background: #1a1a1a !important;
        }

        body.dark-mode .app-header {
            background: #2d2d2d !important;
            border-bottom: 1px solid #444 !important;
        }

        body.dark-mode .app-sidebar {
            background: #2d2d2d !important;
        }

        body.dark-mode .vertical-nav-menu {
            background: #2d2d2d !important;
        }

        body.dark-mode .vertical-nav-menu a {
            color: #b0b0b0 !important;
        }

        body.dark-mode .vertical-nav-menu a:hover,
        body.dark-mode .vertical-nav-menu a.mm-active {
            background: #3a3a3a !important;
            color: #e0e0e0 !important;
        }

        body.dark-mode .app-main__outer {
            background: #1a1a1a !important;
        }

        body.dark-mode .card {
            background: #2d2d2d !important;
            border-color: #444 !important;
            color: #e0e0e0 !important;
        }

        body.dark-mode .card-header {
            background: #3a3a3a !important;
            border-bottom-color: #444 !important;
            color: #e0e0e0 !important;
        }

        body.dark-mode .card-footer {
            background: #3a3a3a !important;
            border-top-color: #444 !important;
        }

        body.dark-mode .table {
            color: #e0e0e0 !important;
        }

        body.dark-mode .table-striped tbody tr:nth-of-type(odd) {
            background: #3a3a3a !important;
        }

        body.dark-mode .table-hover tbody tr:hover {
            background: #3a3a3a !important;
        }

        body.dark-mode thead th {
            color: #e0e0e0 !important;
            border-bottom-color: #444 !important;
        }

        body.dark-mode thead a {
            color: #e0e0e0 !important;
        }

        body.dark-mode .form-control {
            background: #3a3a3a !important;
            border-color: #444 !important;
            color: #e0e0e0 !important;
        }

        body.dark-mode .form-control:focus {
            background: #3a3a3a !important;
            border-color: #2980b9 !important;
        }

        body.dark-mode .form-control::placeholder {
            color: #888 !important;
        }

        body.dark-mode .text-muted {
            color: #888 !important;
        }

        body.dark-mode .page-title-heading,
        body.dark-mode .widget-heading {
            color: #e0e0e0 !important;
        }

        body.dark-mode .page-title-subheading,
        body.dark-mode .widget-subheading {
            color: #b0b0b0 !important;
        }

        body.dark-mode .pagination .page-link {
            background: #2d2d2d !important;
            border-color: #444 !important;
            color: #e0e0e0 !important;
        }

        body.dark-mode .pagination .page-link:hover {
            background: #3a3a3a !important;
        }

        body.dark-mode .pagination .page-item.active .page-link {
            background: #2980b9 !important;
            border-color: #2980b9 !important;
        }

        body.dark-mode .badge-success {
            background: #1e8449 !important;
        }

        body.dark-mode .badge-warning {
            background: #d68910 !important;
            color: #fff !important;
        }

        body.dark-mode .alert-success {
            background: #1e4620 !important;
            border-color: #2d6930 !important;
            color: #a9dfac !important;
        }

        body.dark-mode .alert-danger {
            background: #4a1a1a !important;
            border-color: #6d2828 !important;
            color: #f5b7b1 !important;
        }

        body.dark-mode #darkModeToggle {
            background: #2980b9 !important;
            border-color: #2980b9 !important;
            color: #fff !important;
        }

        body.dark-mode .logo-src::before {
            color: #fff !important;
        }

        #darkModeToggle {
            transition: all 0.3s ease;
        }

        #darkModeToggle:hover {
            transform: rotate(20deg);
        }

        body,
        .app-container,
        .app-header,
        .app-sidebar,
        .card,
        .table,
        .form-control {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        /* Mobile Responsive Table */
        .mobile-posts-container {
            display: none;
        }

        @media (max-width: 768px) {
            .table-responsive {
                overflow-x: visible !important;
            }

            .table-responsive table {
                display: none !important;
            }

            .mobile-posts-container {
                display: block !important;
            }

            .mobile-post-card {
                display: block;
                border: 1px solid #dee2e6;
                border-radius: 8px;
                padding: 15px;
                margin-bottom: 15px;
                background: #fff;
            }

            body.dark-mode .mobile-post-card {
                background: #2d2d2d !important;
                border-color: #444 !important;
            }

            .mobile-post-card .post-title {
                font-weight: bold;
                font-size: 16px;
                margin-bottom: 10px;
                color: #333;
            }

            body.dark-mode .mobile-post-card .post-title {
                color: #e0e0e0 !important;
            }

            .mobile-post-card .post-meta {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 8px;
                font-size: 14px;
            }

            .mobile-post-card .post-actions {
                display: flex;
                gap: 5px;
                flex-wrap: wrap;
                margin-top: 10px;
            }

            .mobile-post-card .post-actions .btn {
                flex: 1;
                min-width: 70px;
            }
        }
    </style>
    <script>
        // Apply dark mode immediately to prevent flash
        (function() {
            const darkMode = localStorage.getItem('adminDarkMode');
            if (darkMode === 'enabled') {
                document.documentElement.classList.add('dark-mode');
                if (document.body) {
                    document.body.classList.add('dark-mode');
                }
            }
        })();
    </script>
</head>

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
                                    <i class="pe-7s-home icon-gradient bg-mean-fruit"></i>
                                </div>
                                <div>Dashboard
                                    <div class="page-title-subheading">Kelola postingan blog Anda di sini</div>
                                </div>
                            </div>
                            <div class="page-title-actions">
                                <?php if ($role === 'admin'): ?>
                                    <a href="/manage_users.php" class="btn-shadow btn btn-info">
                                        <i class="pe-7s-users"></i> Kelola User
                                    </a>
                                <?php endif; ?>
                                <a href="/create_post.php" class="btn-shadow btn btn-primary">
                                    <i class="pe-7s-plus"></i> Tambah Postingan
                                </a>
                            </div>
                        </div>
                    </div>

                    <?php if(isset($_GET['message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($_GET['message']) ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php if(isset($_GET['deleted']) && $_GET['deleted'] == 'true'): ?>
                        <div class="alert alert-success" role="alert">
                            Postingan berhasil dihapus.
                        </div>
                    <?php endif; ?>

                    <?php if(isset($_GET['error']) && $_GET['error'] == 'unauthorized'): ?>
                        <div class="alert alert-danger" role="alert">
                            Anda tidak memiliki izin untuk aksi tersebut.
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="main-card mb-3 card">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                        <div>
                                            <strong>Daftar Postingan</strong>
                                            <span class="text-muted ms-2">(<?= $total_results ?> total)</span>
                                        </div>
                                        <div>
                                            <form action="/dashboard.php" method="GET" class="d-flex gap-2 align-items-center">
                                                <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari judul..." value="<?= htmlspecialchars($search_term) ?>" style="min-width: 200px;">
                                                <input type="hidden" name="sort" value="<?= htmlspecialchars($sort_by) ?>">
                                                <input type="hidden" name="order" value="<?= htmlspecialchars($order) ?>">
                                                <button type="submit" class="btn btn-sm btn-primary">Cari</button>
                                                <?php if (!empty($search_term)): ?>
                                                    <a href="/dashboard.php" class="btn btn-sm btn-secondary">Reset</a>
                                                <?php endif; ?>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="align-middle mb-0 table table-borderless table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th class="text-center">#</th>
                                                <th>
                                                    <a href="?sort=title&order=<?= ($sort_by === 'title' && $order === 'ASC') ? 'DESC' : 'ASC' ?><?= !empty($search_term) ? '&search=' . urlencode($search_term) : '' ?>" class="text-dark text-decoration-none">
                                                        Judul
                                                        <?php if ($sort_by === 'title'): ?>
                                                            <i class="fa fa-sort-<?= $order === 'ASC' ? 'up' : 'down' ?>"></i>
                                                        <?php else: ?>
                                                            <i class="fa fa-sort text-muted"></i>
                                                        <?php endif; ?>
                                                    </a>
                                                </th>
                                                <th class="text-center">
                                                    <a href="?sort=author&order=<?= ($sort_by === 'author' && $order === 'ASC') ? 'DESC' : 'ASC' ?><?= !empty($search_term) ? '&search=' . urlencode($search_term) : '' ?>" class="text-dark text-decoration-none">
                                                        Author
                                                        <?php if ($sort_by === 'author'): ?>
                                                            <i class="fa fa-sort-<?= $order === 'ASC' ? 'up' : 'down' ?>"></i>
                                                        <?php else: ?>
                                                            <i class="fa fa-sort text-muted"></i>
                                                        <?php endif; ?>
                                                    </a>
                                                </th>
                                                <th class="text-center">
                                                    <a href="?sort=status&order=<?= ($sort_by === 'status' && $order === 'ASC') ? 'DESC' : 'ASC' ?><?= !empty($search_term) ? '&search=' . urlencode($search_term) : '' ?>" class="text-dark text-decoration-none">
                                                        Status
                                                        <?php if ($sort_by === 'status'): ?>
                                                            <i class="fa fa-sort-<?= $order === 'ASC' ? 'up' : 'down' ?>"></i>
                                                        <?php else: ?>
                                                            <i class="fa fa-sort text-muted"></i>
                                                        <?php endif; ?>
                                                    </a>
                                                </th>
                                                <th class="text-center">
                                                    <a href="?sort=date&order=<?= ($sort_by === 'date' && $order === 'ASC') ? 'DESC' : 'ASC' ?><?= !empty($search_term) ? '&search=' . urlencode($search_term) : '' ?>" class="text-dark text-decoration-none">
                                                        Tanggal
                                                        <?php if ($sort_by === 'date'): ?>
                                                            <i class="fa fa-sort-<?= $order === 'ASC' ? 'up' : 'down' ?>"></i>
                                                        <?php else: ?>
                                                            <i class="fa fa-sort text-muted"></i>
                                                        <?php endif; ?>
                                                    </a>
                                                </th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($posts)): ?>
                                                <?php $no = 1; foreach ($posts as $post): ?>
                                                    <tr>
                                                        <td class="text-center text-muted">#<?= $no++ ?></td>
                                                        <td>
                                                            <div class="widget-content p-0">
                                                                <div class="widget-content-wrapper">
                                                                    <div class="widget-content-left flex2">
                                                                        <div class="widget-heading"><?= htmlspecialchars($post['title']) ?></div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="text-center"><?= htmlspecialchars($post['username']) ?></td>
                                                        <td class="text-center">
                                                            <?php
                                                            $badge_class = 'badge-secondary';
                                                            if ($post['status'] === 'published') $badge_class = 'badge-success';
                                                            elseif ($post['status'] === 'draft') $badge_class = 'badge-warning';
                                                            ?>
                                                            <div class="badge <?= $badge_class ?>"><?= htmlspecialchars($post['status']) ?></div>
                                                        </td>
                                                        <td class="text-center"><?= date("d M Y", strtotime($post['created_at'])) ?></td>
                                                        <td class="text-center">
                                                            <?php
                                                            $can_edit = false;
                                                            $can_delete = false;

                                                            if ($role === 'admin') {
                                                                $can_edit = true;
                                                                $can_delete = true;
                                                            } elseif ($role === 'editor') {
                                                                $can_edit = true;
                                                                if ($post['status'] !== 'published') {
                                                                    $can_delete = true;
                                                                }
                                                            } elseif ($role === 'kontributor') {
                                                                if ($post['author_id'] == $user_id && $post['status'] !== 'published') {
                                                                    $can_edit = true;
                                                                    $can_delete = true;
                                                                }
                                                            }
                                                            ?>
                                                            <a href="/artikel/<?= htmlspecialchars($post['slug']) ?>" class="btn btn-primary btn-sm" target="_blank">Lihat</a>
                                                            <?php if ($can_edit): ?>
                                                                <a href="/edit_post.php?id=<?= $post['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                                            <?php endif; ?>
                                                            <?php if ($can_delete): ?>
                                                                <a href="/delete_post.php?id=<?= $post['id'] ?>&page=<?= $page ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin?')">Hapus</a>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr><td colspan="6" class="text-center">Tidak ada postingan untuk ditampilkan.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Mobile Card View -->
                                <div class="mobile-posts-container p-3">
                                    <?php if (!empty($posts)): ?>
                                        <?php foreach ($posts as $post): ?>
                                            <div class="mobile-post-card">
                                                <div class="post-title"><?= htmlspecialchars($post['title']) ?></div>
                                                <div class="post-meta">
                                                    <span class="text-muted">
                                                        <i class="pe-7s-user"></i> <?= htmlspecialchars($post['username']) ?>
                                                    </span>
                                                    <span>
                                                        <?php
                                                        $badge_class = 'badge-secondary';
                                                        if ($post['status'] === 'published') $badge_class = 'badge-success';
                                                        elseif ($post['status'] === 'draft') $badge_class = 'badge-warning';
                                                        ?>
                                                        <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($post['status']) ?></span>
                                                    </span>
                                                </div>
                                                <div class="post-meta">
                                                    <span class="text-muted">
                                                        <i class="pe-7s-date"></i> <?= date("d M Y", strtotime($post['created_at'])) ?>
                                                    </span>
                                                </div>
                                                <div class="post-actions">
                                                    <a href="/artikel/<?= htmlspecialchars($post['slug']) ?>" class="btn btn-primary btn-sm" target="_blank">Lihat</a>
                                                    <?php
                                                    $can_edit = false;
                                                    $can_delete = false;

                                                    if ($role === 'admin') {
                                                        $can_edit = true;
                                                        $can_delete = true;
                                                    } elseif ($role === 'editor') {
                                                        $can_edit = true;
                                                        if ($post['status'] !== 'published') {
                                                            $can_delete = true;
                                                        }
                                                    } elseif ($role === 'kontributor') {
                                                        if ($post['author_id'] == $user_id && $post['status'] !== 'published') {
                                                            $can_edit = true;
                                                            $can_delete = true;
                                                        }
                                                    }
                                                    ?>
                                                    <?php if ($can_edit): ?>
                                                        <a href="/edit_post.php?id=<?= $post['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                                    <?php endif; ?>
                                                    <?php if ($can_delete): ?>
                                                        <a href="/delete_post.php?id=<?= $post['id'] ?>&page=<?= $page ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin?')">Hapus</a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-center text-muted p-4">Tidak ada postingan untuk ditampilkan.</div>
                                    <?php endif; ?>
                                </div>

                                <!-- Pagination -->
                                <?php if ($total_pages > 1): ?>
                                <div class="card-footer">
                                    <nav>
                                        <ul class="pagination justify-content-center mb-0">
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= $page - 1 ?><?= !empty($search_term) ? '&search=' . urlencode($search_term) : '' ?><?= !empty($sort_by) ? '&sort=' . $sort_by : '' ?><?= !empty($order) ? '&order=' . $order : '' ?>">Sebelumnya</a>
                                                </li>
                                            <?php endif; ?>

                                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                                    <a class="page-link" href="?page=<?= $i ?><?= !empty($search_term) ? '&search=' . urlencode($search_term) : '' ?><?= !empty($sort_by) ? '&sort=' . $sort_by : '' ?><?= !empty($order) ? '&order=' . $order : '' ?>"><?= $i ?></a>
                                                </li>
                                            <?php endfor; ?>

                                            <?php if ($page < $total_pages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= $page + 1 ?><?= !empty($search_term) ? '&search=' . urlencode($search_term) : '' ?><?= !empty($sort_by) ? '&sort=' . $sort_by : '' ?><?= !empty($order) ? '&order=' . $order : '' ?>">Selanjutnya</a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                </div>
                                <?php endif; ?>
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
