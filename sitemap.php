<?php
require_once "src/config.php";

// Set header ke XML
header("Content-Type: application/xml; charset=utf-8");

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

// URL Halaman statis (jika ada)
$base_url = "https://dutadamai-kalsel.com"; // Domain production
echo '<url>';
echo '  <loc>' . $base_url . '/</loc>';
echo '  <priority>1.0</priority>';
echo '</url>';

// Ambil semua slug dari post
$sql = "SELECT slug, created_at FROM posts ORDER BY created_at DESC";
if ($result = mysqli_query($link, $sql)) {
    while ($post = mysqli_fetch_assoc($result)) {
        echo '<url>';
        echo '  <loc>' . $base_url . '/artikel/' . htmlspecialchars($post['slug']) . '</loc>';
        echo '  <lastmod>' . date('c', strtotime($post['created_at'])) . '</lastmod>';
        echo '  <priority>0.8</priority>';
        echo '</url>';
    }
}

echo '</urlset>';

mysqli_close($link);
?>
