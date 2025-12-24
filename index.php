<?php
require_once "src/config.php";

// --- Logika Pagination ---
$results_per_page = 6; // 6 post per halaman untuk layout grid

// 1. Dapatkan total jumlah semua post yang sudah publish
$sql_count = "SELECT COUNT(*) FROM posts WHERE status = 'published'";
$result_count = mysqli_query($link, $sql_count);
$total_results = mysqli_fetch_array($result_count)[0];
$total_pages = ceil($total_results / $results_per_page);

// 2. Dapatkan halaman saat ini atau default ke halaman 1
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;
if ($page < 1) $page = 1;

// 3. Hitung OFFSET untuk query SQL
$starting_limit_number = ($page - 1) * $results_per_page;

// 4. Ambil post untuk halaman saat ini
$posts = [];
$sql = "SELECT p.id, p.title, p.slug, p.content, p.created_at, p.image_url, p.category, u.username 
        FROM posts p 
        JOIN users u ON p.author_id = u.id 
        WHERE p.status = 'published'
        ORDER BY p.created_at DESC
        LIMIT ?, ?";

if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $starting_limit_number, $results_per_page);
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $posts[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duta Damai Kalimantan Selatan</title>
    <link rel="stylesheet" href="/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" href="/assets/default_post_image.jpeg" type="image/jpeg">
</head>
<body>

    <div class="main-container">
        <header class="page-header">
            <h1>Duta Damai Kalimantan Selatan</h1>
        </header>

        <div class="blog-grid">
            <?php if (!empty($posts)): ?>
                <?php foreach ($posts as $post): ?>
                    <a href="/artikel/<?= htmlspecialchars($post['slug']) ?>?from_page=<?= $page ?>" class="post-card-link">
                        <div class="blog-post-card">
                            <div class="post-image-container">
                                <?php
                                    $image_source = !empty($post['image_url']) ? htmlspecialchars($post['image_url']) : '/assets/default_post_image.jpeg';
                                ?>
                                <img src="<?= $image_source ?>" alt="<?= htmlspecialchars($post['title']) ?>">
                                <span class="category-tag"><?= !empty($post['category']) ? htmlspecialchars(strtoupper($post['category'])) : 'ARTIKEL' ?></span>
                            </div>
                            <div class="post-content">
                                <h2><?= htmlspecialchars($post['title']) ?></h2>
                                <div class="post-meta">
                                    <span><i class="fa-solid fa-user"></i> <?= htmlspecialchars($post['username']) ?></span>
                                    <span><i class="fa-solid fa-calendar-days"></i> <?= date("d M Y", strtotime($post['created_at'])) ?></span>
                                </div>
                                <p>
                                    <?php
                                        // Membuat excerpt singkat dari konten
                                        $excerpt = strip_tags($post['content']);
                                        if (strlen($excerpt) > 150) {
                                            $excerpt = substr($excerpt, 0, 150) . '...';
                                        }
                                        echo htmlspecialchars($excerpt);
                                    ?>
                                </p>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; width: 100%;">Belum ada postingan untuk ditampilkan.</p>
            <?php endif; ?>
        </div>
        
        <!-- Navigasi Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>">Sebelumnya</a>
            <?php else: ?>
                <span class="disabled">Sebelumnya</span>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>" class="<?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?>">Selanjutnya</a>
            <?php else: ?>
                <span class="disabled">Selanjutnya</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>


</body>
</html>
