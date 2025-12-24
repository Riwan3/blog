<?php
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

$sql_select = "SELECT p.id, p.title, p.created_at, p.status, p.author_id, u.username";
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= htmlspecialchars($role) ?></title>
    <link rel="stylesheet" href="/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" href="/assets/default_post_image.jpeg" type="image/jpeg">
</head>
<body>
    <div class="main-container">
        <div class="user-info">
            <span>Selamat datang, <strong><?= htmlspecialchars($_SESSION['username']) ?> (<?= htmlspecialchars($role) ?>)</strong>!</span>
            <a href="/logout.php" class="logout-button">Logout</a>
        </div>
        
        <div class="dashboard-header">
            <h1>Manajemen Postingan</h1>
            <div>
                <?php if ($role === 'admin'): ?>
                    <a href="/manage_users.php" class="button-primary" style="background-color:#5bc0de;"><i class="fa-solid fa-users"></i> Kelola User</a>
                <?php endif; ?>
                <a href="/create_post.php" class="button-primary" <?php if ($role === 'admin') echo 'style="font-size: 0.8em; padding: 5px 10px;"'; ?>>
                    <i class="fa-solid fa-plus"></i> Tambah Postingan Baru
                </a>
            </div>
        </div>

        <?php if(isset($_GET['deleted']) && $_GET['deleted'] == 'true') echo '<p class="success-message">Postingan berhasil dihapus.</p>'; ?>
        <?php if(isset($_GET['error']) && $_GET['error'] == 'unauthorized') echo '<p class="error-message">Anda tidak memiliki izin untuk aksi tersebut.</p>'; ?>
        
        <div class="table-controls">
            <form action="/dashboard.php" method="GET" class="search-form">
                <input type="text" name="search" placeholder="Cari berdasarkan judul..." value="<?= htmlspecialchars($search_term) ?>">
                <input type="hidden" name="sort" value="<?= htmlspecialchars($sort_by) ?>">
                <input type="hidden" name="order" value="<?= htmlspecialchars($order) ?>">
                <button type="submit"><i class="fa fa-search"></i></button>
            </form>
        </div>

        <div class="posts-table-container">
            <table class="posts-table">
                <thead>
                    <tr>
                        <th><?= render_sort_link('title', 'Judul', $sort_by, $order, $search_term) ?></th>
                        <th><?= render_sort_link('author', 'Author', $sort_by, $order, $search_term) ?></th>
                        <th><?= render_sort_link('status', 'Status', $sort_by, $order, $search_term) ?></th>
                        <th><?= render_sort_link('date', 'Tanggal', $sort_by, $order, $search_term) ?></th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($posts)): ?>
                        <?php foreach ($posts as $post): ?>
                            <tr>
                                <td><?= htmlspecialchars($post['title']) ?></td>
                                <td><?= htmlspecialchars($post['username']) ?></td>
                                <td><span class="status-badge status-<?= htmlspecialchars($post['status']) ?>"><?= htmlspecialchars($post['status']) ?></span></td>
                                <td><?= date("d M Y", strtotime($post['created_at'])) ?></td>
                                <td>
                                    <a href="/post.php?id=<?= $post['id'] ?>" class="action-btn view-btn"><i class="fa-solid fa-eye"></i> Lihat</a>
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
                                        <a href="/edit_post.php?id=<?= $post['id'] ?>" class="action-btn edit-btn"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                                    <?php endif; ?>

                                    <?php if ($can_delete): ?>
                                        <a href="/delete_post.php?id=<?= $post['id'] ?>&page=<?= $page ?>" class="action-btn delete-btn" onclick="return confirm('Apakah Anda yakin?')"><i class="fa-solid fa-trash"></i> Hapus</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center;">Tidak ada postingan untuk ditampilkan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++):
                $query_params = [
                    'page' => $i,
                    'sort' => $sort_by,
                    'order' => $order,
                    'search' => $search_term,
                ];
                $query_params = array_filter($query_params, function($value) { return $value !== ''; });
            ?>
                <a href="?<?= http_build_query($query_params) ?>" class="<?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

    </div>
</body>
</html>
