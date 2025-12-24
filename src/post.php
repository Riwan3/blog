<?php
require_once "config.php";

// Periksa apakah slug post ada di URL
if (!isset($_GET['slug']) || empty($_GET['slug'])) {
    header("Location: /index.php");
    exit;
}

$post_slug = $_GET['slug'];
$post = null;

// Tentukan halaman kembali untuk tombol "Kembali"
$back_page = isset($_GET['from_page']) ? (int)$_GET['from_page'] : 1;

// Ambil data post dari database berdasarkan slug, pastikan sudah publish
$sql = "SELECT p.title, p.content, p.created_at, p.image_url, p.category, u.username 
        FROM posts p 
        JOIN users u ON p.author_id = u.id 
        WHERE p.slug = ? AND p.status = 'published'";

if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "s", $post_slug);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) == 1) {
            $post = mysqli_fetch_assoc($result);
        }
    }
    mysqli_stmt_close($stmt);
}

// Buat meta description dari konten
$meta_description = "";
if ($post) {
    $meta_description = htmlspecialchars(substr(strip_tags($post['content']), 0, 160));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Meta Tags -->
    <title><?= $post ? htmlspecialchars($post['title']) : "Postingan Tidak Ditemukan"; ?> - Duta Damai Kalsel</title>
    <meta name="description" content="<?= $meta_description ?>">

    <link rel="stylesheet" href="/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Schema.org JSON-LD -->
    <?php if ($post): ?>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Article",
      "headline": "<?= htmlspecialchars($post['title']) ?>",
      "image": "<?= !empty($post['image_url']) ? htmlspecialchars($post['image_url']) : '/assets/default_post_image.jpeg' ?>",
      "author": {
        "@type": "Person",
        "name": "<?= htmlspecialchars($post['username']) ?>"
      },
      "datePublished": "<?= date('c', strtotime($post['created_at'])) ?>",
      "articleSection": "<?= !empty($post['category']) ? htmlspecialchars($post['category']) : 'Artikel' ?>"
    }
    </script>
    <?php endif; ?>
    <link rel="icon" href="/assets/default_post_image.jpeg" type="image/jpeg">
</head>
<body>
    <div class="main-container">
        <header class="page-header">
            <a href="/index.php?page=<?= $back_page ?>" class="back-link"><i class="fa-solid fa-arrow-left"></i> Kembali ke Semua Postingan</a>
        </header>

        <div class="single-post-container">
            <?php if ($post): ?>
                <h1 class="post-full-title"><?= htmlspecialchars($post['title']) ?></h1>
                <div class="post-meta">
                    <span><i class="fa-solid fa-user"></i> <?= htmlspecialchars($post['username']) ?></span>
                    <span><i class="fa-solid fa-calendar-days"></i> <?= date("d F Y", strtotime($post['created_at'])) ?></span>
                    <?php if(!empty($post['category'])): ?>
                        <span><i class="fa-solid fa-folder"></i> <?= htmlspecialchars($post['category']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="post-full-image">
                    <?php
                        $image_source = !empty($post['image_url']) ? htmlspecialchars($post['image_url']) : '/assets/default_post_image.jpeg';
                    ?>
                    <img src="<?= $image_source ?>" alt="<?= htmlspecialchars($post['title']) ?>">
                </div>
                <div class="post-full-content">
                    <?= $post['content'] // Tampilkan HTML dari CKEditor ?>
                </div>
            <?php else: ?>
                <h1 class="post-full-title">404 - Postingan Tidak Ditemukan</h1>
                <p>Maaf, postingan yang Anda cari tidak ada atau telah dihapus.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
