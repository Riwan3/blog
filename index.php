<?php
/**
 * Duta Damai Kalimantan Selatan - Blog System
 * © 2025 Duta Damai Kalimantan Selatan
 *
 * Menggunakan komponen dari CMS Jawara
 * © 2020 Djadjoel (MIT License)
 * https://github.com/djadjoel/cmsjawara
 */

require_once "src/config.php";
require_once "src/helpers.php";

// --- Get Search and Filter Parameters ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';

// --- Get All Categories for Filter with Cache ---
$categories = get_categories_cached($link, 600);

// --- Logika Pagination ---
$results_per_page = 6; // 6 post per halaman untuk layout grid

// Build WHERE clause for filtering
$where_conditions = ["status = 'published'"];
$params = [];
$types = "";

if (!empty($search)) {
    $where_conditions[] = "(p.title LIKE ? OR p.content LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if (!empty($category_filter)) {
    $where_conditions[] = "p.category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

// 1. Dapatkan total jumlah post sesuai filter
$sql_count = "SELECT COUNT(*) FROM posts p WHERE {$where_clause}";
$stmt_count = mysqli_prepare($link, $sql_count);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt_count, $types, ...$params);
}
mysqli_stmt_execute($stmt_count);
$total_results = mysqli_stmt_get_result($stmt_count)->fetch_row()[0];
$total_pages = ceil($total_results / $results_per_page);
mysqli_stmt_close($stmt_count);

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
        WHERE {$where_clause}
        ORDER BY p.created_at DESC
        LIMIT ?, ?";

$params_with_limit = array_merge($params, [$starting_limit_number, $results_per_page]);
$types_with_limit = $types . "ii";

if ($stmt = mysqli_prepare($link, $sql)) {
    if (!empty($params_with_limit)) {
        mysqli_stmt_bind_param($stmt, $types_with_limit, ...$params_with_limit);
    }
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $posts[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}

// 5. Get Featured/Latest Posts for Hero
$featured_posts = [];
$sql_featured = "SELECT p.id, p.title, p.slug, p.content, p.created_at, p.image_url, p.category, u.username
                 FROM posts p
                 JOIN users u ON p.author_id = u.id
                 WHERE p.status = 'published'
                 ORDER BY p.created_at DESC
                 LIMIT 3";
$result_featured = mysqli_query($link, $sql_featured);
while ($row_feat = mysqli_fetch_assoc($result_featured)) {
    $featured_posts[] = $row_feat;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duta Damai Kalimantan Selatan - Blog</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/style.css">
    <link href="/assets/css/loading.css" rel="stylesheet">
    <link rel="icon" href="/assets/default_post_image.jpeg" type="image/jpeg">
    <script src="/assets/scripts/loading.js"></script>
    <style>
        /* Duta Damai Kalsel Theme - Inspired by Logo */
        :root {
            --primary-gradient: linear-gradient(135deg, #1e5f8c 0%, #2980b9 100%);
            --secondary-gradient: linear-gradient(135deg, #f4d03f 0%, #f7b731 100%);
            --accent-gradient: linear-gradient(135deg, #1e5f8c 0%, #f4d03f 100%);
            --primary-color: #2980b9;
            --secondary-color: #f4d03f;
            --dark-blue: #1e5f8c;
            --light-blue: #5dade2;
            --gold: #f7b731;
            --dark-bg: #1a252f;
            --card-shadow: 0 0.46875rem 2.1875rem rgba(30,95,140,.1), 0 0.9375rem 1.40625rem rgba(30,95,140,.1), 0 0.25rem 0.53125rem rgba(30,95,140,.12), 0 0.125rem 0.1875rem rgba(30,95,140,.1);
            --card-shadow-hover: 0 1rem 3rem rgba(30,95,140,.175);
        }

        /* Dark Mode Variables */
        body.dark-mode {
            --bg-color: #1a1a1a;
            --text-color: #e0e0e0;
            --card-bg: #2d2d2d;
            --navbar-bg: #212121;
        }

        body.dark-mode {
            background: var(--bg-color);
            color: var(--text-color);
        }

        body.dark-mode .blog-card {
            background: var(--card-bg);
            color: var(--text-color);
        }

        body.dark-mode .card-title {
            color: var(--text-color);
        }

        body.dark-mode .navbar-custom {
            background: var(--navbar-bg);
        }

        body.dark-mode .footer {
            background: linear-gradient(135deg, #121212 0%, #1a1a1a 100%);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f8f9fa;
        }

        /* ArchitectUI Navbar */
        .navbar-custom {
            background: var(--primary-gradient);
            box-shadow: 0 0.125rem 0.625rem rgba(90,97,105,.12);
            padding: 1rem 0;
            backdrop-filter: blur(10px);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: white !important;
            text-shadow: 0 2px 4px rgba(0,0,0,.1);
            transition: all 0.3s cubic-bezier(.25,.8,.25,1);
        }

        .navbar-brand img {
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,.2);
        }

        .navbar-brand:hover {
            transform: scale(1.02);
        }

        .navbar-brand:hover img {
            transform: rotate(5deg) scale(1.1);
            box-shadow: 0 4px 12px rgba(244,208,63,.4);
        }

        .navbar-custom .nav-link {
            color: rgba(255,255,255,0.9) !important;
            margin: 0 10px;
            padding: 8px 16px !important;
            border-radius: 8px;
            transition: all 0.3s cubic-bezier(.25,.8,.25,1);
            font-weight: 500;
        }

        .navbar-custom .nav-link:hover,
        .navbar-custom .nav-link.active {
            background: rgba(255,255,255,0.15);
            color: white !important;
            transform: translateY(-2px);
        }

        /* Hero Section - Simplified */
        .hero-section {
            background: var(--primary-gradient);
            color: white;
            padding: 40px 0 30px;
            margin-bottom: 30px;
        }

        .hero-section h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .hero-section p {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 20px;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Blog Card - Mirip Baniakoy */
        .blog-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
            background: white;
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
        }

        body.dark-mode .blog-card {
            border-color: #444;
        }

        .blog-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,.1);
            transform: translateY(-3px);
        }

        .blog-card img {
            height: 180px;
            object-fit: cover;
            width: 100%;
        }

        .blog-card .card-body {
            padding: 15px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .blog-card .card-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            font-size: 1rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        body.dark-mode .blog-card .card-title {
            color: #e0e0e0;
        }

        .blog-card:hover .card-title {
            color: var(--primary-color);
        }

        .blog-card .card-text {
            color: #666;
            font-size: 0.85rem;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            flex: 1;
        }

        body.dark-mode .blog-card .card-text {
            color: #aaa;
        }

        .category-badge {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 3px 10px;
            border-radius: 3px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-bottom: 8px;
        }

        /* Card Horizontal - untuk list */
        .blog-card-horizontal {
            flex-direction: row;
            border: none;
            border-bottom: 1px solid #e0e0e0;
            border-radius: 0;
            padding: 15px 0;
        }

        body.dark-mode .blog-card-horizontal {
            border-bottom-color: #444;
        }

        .blog-card-horizontal img {
            width: 120px;
            height: 90px;
            border-radius: 6px;
            margin-right: 15px;
        }

        .blog-card-horizontal .card-body {
            padding: 0;
        }

        .blog-card-horizontal .card-title {
            font-size: 0.95rem;
            -webkit-line-clamp: 2;
        }

        .blog-meta {
            font-size: 0.75rem;
            color: #999;
            margin-bottom: 8px;
            display: flex;
            gap: 10px;
        }

        body.dark-mode .blog-meta {
            color: #888;
        }

        .blog-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .blog-meta i {
            font-size: 0.7rem;
        }

        /* ArchitectUI Footer */
        .footer {
            background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
            color: white;
            padding: 40px 0 20px;
            margin-top: 80px;
            box-shadow: 0 -4px 6px rgba(0,0,0,.05);
        }

        .footer h5 {
            font-weight: 700;
            margin-bottom: 15px;
        }

        .footer a {
            transition: all 0.3s;
        }

        .footer a:hover {
            color: var(--primary-color) !important;
            transform: translateX(3px);
            display: inline-block;
        }

        /* ArchitectUI Pagination */
        .pagination {
            margin-top: 50px;
        }

        .pagination .page-link {
            color: var(--primary-color);
            border: 2px solid transparent;
            margin: 0 4px;
            border-radius: 10px;
            padding: 10px 18px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(.25,.8,.25,1);
        }

        .pagination .page-link:hover {
            background: var(--primary-gradient);
            color: white;
            border-color: transparent;
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(41, 128, 185, 0.4);
        }

        .pagination .active .page-link {
            background: var(--primary-gradient);
            border-color: transparent;
            box-shadow: 0 4px 8px rgba(41, 128, 185, 0.4);
        }

        /* Smooth Scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Loading Animation */
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Search Bar in Hero */
        .search-form-hero .input-group {
            transition: all 0.3s cubic-bezier(.25,.8,.25,1);
        }

        .search-form-hero .input-group:focus-within {
            transform: translateY(-5px);
            box-shadow: 0 1rem 3rem rgba(0,0,0,.3) !important;
        }

        .search-form-hero input {
            padding: 1rem 1.5rem;
            font-size: 1rem;
        }

        .search-form-hero input:focus {
            box-shadow: none;
            outline: none;
        }

        /* Filter Buttons */
        .btn-group .btn {
            transition: all 0.3s cubic-bezier(.25,.8,.25,1);
        }

        .btn-group .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,.1);
        }

        /* Simple fade in */
        .blog-card {
            opacity: 0;
            animation: fadeIn 0.3s ease-out forwards;
        }

        /* Empty State */
        .empty-state {
            padding: 60px 20px;
            text-align: center;
        }

        .empty-state i {
            font-size: 4rem;
            color: #cbd5e0;
            margin-bottom: 20px;
        }

        /* Sticky Navbar Effect */
        .navbar-custom.scrolled {
            padding: 0.5rem 0;
            box-shadow: 0 0.25rem 1.25rem rgba(90,97,105,.2);
        }

        /* Loading Skeleton */
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }

        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 1000px 100%;
            animation: shimmer 2s infinite;
        }

        /* Sidebar Menu - Mirip Baniakoy */
        .sidebar-menu {
            position: fixed;
            left: -300px;
            top: 0;
            width: 300px;
            height: 100%;
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,.1);
            transition: left 0.3s ease;
            z-index: 9999;
            overflow-y: auto;
        }

        body.dark-mode .sidebar-menu {
            background: #2d2d2d;
        }

        .sidebar-menu.active {
            left: 0;
        }

        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,.5);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 9998;
        }

        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        body.dark-mode .sidebar-header {
            border-bottom-color: #444;
        }

        .sidebar-menu ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu ul li a {
            display: block;
            padding: 15px 20px;
            color: #333;
            text-decoration: none;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s;
        }

        body.dark-mode .sidebar-menu ul li a {
            color: #e0e0e0;
            border-bottom-color: #444;
        }

        .sidebar-menu ul li a:hover {
            background: #f8f9fa;
            padding-left: 30px;
        }

        body.dark-mode .sidebar-menu ul li a:hover {
            background: #3a3a3a;
        }

        .badge-new {
            background: #dc3545;
            color: white;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 0.65rem;
            margin-left: 5px;
            font-weight: bold;
        }

        /* Dark Mode Toggle */
        .dark-mode-toggle {
            cursor: pointer;
            font-size: 1.2rem;
            color: white;
            transition: all 0.3s;
        }

        .dark-mode-toggle:hover {
            transform: rotate(180deg);
        }

        /* Scroll to Top Button */
        .scroll-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: var(--dark-bg);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
            z-index: 999;
            box-shadow: 0 4px 12px rgba(0,0,0,.3);
        }

        .scroll-to-top.show {
            opacity: 1;
            visibility: visible;
        }

        .scroll-to-top:hover {
            background: var(--primary-color);
            transform: translateY(-5px);
        }

        /* Horizontal Scroll Container */
        .horizontal-scroll {
            display: flex;
            overflow-x: auto;
            gap: 15px;
            padding: 20px 0;
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
        }

        .horizontal-scroll::-webkit-scrollbar {
            height: 8px;
        }

        .horizontal-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .horizontal-scroll::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 4px;
        }

        .horizontal-scroll .blog-card {
            min-width: 280px;
            scroll-snap-align: start;
        }

        /* CTA Button Green */
        .btn-success-custom {
            background: #28a745;
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-success-custom:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40,167,69,.3);
        }

        /* Responsive Enhancements */
        @media (max-width: 768px) {
            .hero-section h1 {
                font-size: 2rem;
            }
            .hero-section {
                padding: 50px 0;
            }
            .search-form-hero input {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
            }
            .btn-group {
                display: flex;
                flex-wrap: wrap;
            }
            .sidebar-menu {
                width: 280px;
                left: -280px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Menu -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="sidebar-menu" id="sidebarMenu">
        <div class="sidebar-header">
            <strong>Menu</strong>
            <button class="btn btn-sm" id="closeSidebar" style="font-size: 1.5rem; padding: 0; border: none; background: none;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <ul>
            <li><a href="/"><i class="fas fa-home me-2"></i> Beranda</a></li>
            <li><a href="#"><i class="fas fa-fire me-2"></i> Trending <span class="badge-new">NEW</span></a></li>
            <li><a href="#"><i class="fas fa-book me-2"></i> Tutorial <span class="badge-new">NEW</span></a></li>
            <li><a href="#"><i class="fas fa-briefcase me-2"></i> Lowongan <span class="badge-new">NEW</span></a></li>
            <li><a href="#"><i class="fas fa-shopping-cart me-2"></i> Toko Online</a></li>
            <li><a href="#"><i class="fas fa-info-circle me-2"></i> Info</a></li>
            <li><a href="#"><i class="fas fa-graduation-cap me-2"></i> Lecture</a></li>
            <li><a href="#"><i class="fas fa-calendar me-2"></i> Acara</a></li>
            <li><a href="/dashboard"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
        </ul>
    </div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
        <div class="container">
            <button class="btn text-white me-2 d-lg-none" id="menuToggle" style="font-size: 1.5rem; padding: 0; border: none; background: none;">
                <i class="fas fa-bars"></i>
            </button>
            <a class="navbar-brand d-flex align-items-center" href="/">
                <img src="/assets/default_post_image.jpeg" alt="Logo Duta Damai Kalsel" height="40" class="me-2" style="border-radius: 8px;">
                <span class="d-none d-md-inline">Duta Damai Kalsel</span>
            </a>
            <div class="d-flex align-items-center ms-auto">
                <button class="btn dark-mode-toggle me-2" id="darkModeToggle">
                    <i class="fas fa-moon"></i>
                </button>
                <a href="/dashboard" class="btn btn-sm btn-light d-none d-lg-inline-block">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </div>
        </div>
    </nav>

    <!-- Scroll to Top Button -->
    <div class="scroll-to-top" id="scrollToTop">
        <i class="fas fa-arrow-up"></i>
    </div>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1>Duta Damai Kalimantan Selatan</h1>
            <p class="lead">Bersama Membangun Perdamaian dan Harmoni di Bumi Banua</p>

            <!-- Search Bar -->
            <div class="row justify-content-center mt-4">
                <div class="col-md-8 col-lg-6">
                    <form method="GET" action="/" class="search-form-hero">
                        <div class="input-group input-group-lg shadow">
                            <input type="text" name="search" class="form-control" placeholder="Cari artikel..." value="<?= htmlspecialchars($search) ?>" style="border-radius: 50px 0 0 50px; border: none;">
                            <button class="btn btn-light" type="submit" style="border-radius: 0 50px 50px 0; border: none; font-weight: 600; padding: 0 30px;">
                                <i class="fas fa-search"></i> Cari
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Filter Section - Simple -->
    <?php if (!empty($categories)): ?>
    <div class="container mb-3">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="/" class="btn btn-sm <?= empty($category_filter) ? 'btn-primary' : 'btn-outline-secondary' ?>">Semua</a>
            <?php foreach ($categories as $cat): ?>
                <a href="?category=<?= urlencode($cat['category']) ?>" class="btn btn-sm <?= $category_filter === $cat['category'] ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    <?= htmlspecialchars($cat['category']) ?> <span class="badge bg-light text-dark"><?= $cat['count'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Container -->
    <div class="container">
        <?php if (!empty($search) || !empty($category_filter)): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert" style="border-radius: 12px; border-left: 4px solid var(--primary-color);">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Hasil pencarian:</strong>
                <?php if (!empty($search)): ?>
                    Kata kunci "<em><?= htmlspecialchars($search) ?></em>"
                <?php endif; ?>
                <?php if (!empty($category_filter)): ?>
                    <?= !empty($search) ? 'dalam' : '' ?> Kategori "<em><?= htmlspecialchars($category_filter) ?></em>"
                <?php endif; ?>
                - Ditemukan <?= $total_results ?> artikel
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <?php if (!empty($posts)): ?>
                <?php foreach ($posts as $post): ?>
                    <div class="col-md-6 col-lg-4">
                        <a href="/artikel/<?= htmlspecialchars($post['slug']) ?>" class="text-decoration-none">
                            <div class="card blog-card">
                                <?php
                                    $image_source = !empty($post['image_url']) ? htmlspecialchars($post['image_url']) : '/assets/default_post_image.jpeg';
                                ?>
                                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1 1'%3E%3C/svg%3E"
                                     data-src="<?= $image_source ?>"
                                     alt="<?= htmlspecialchars($post['title']) ?>"
                                     class="lazyload"
                                     loading="lazy">
                                <div class="card-body">
                                    <?php if (!empty($post['category'])): ?>
                                        <span class="category-badge"><?= htmlspecialchars($post['category']) ?></span>
                                    <?php endif; ?>
                                    <h5 class="card-title"><?= htmlspecialchars($post['title']) ?></h5>
                                    <div class="blog-meta">
                                        <span><i class="fa-solid fa-user"></i> <?= htmlspecialchars($post['username']) ?></span>
                                        <span><i class="fa-solid fa-calendar"></i> <?= date("d M Y", strtotime($post['created_at'])) ?></span>
                                    </div>
                                    <p class="card-text">
                                        <?php
                                            $excerpt = strip_tags($post['content']);
                                            if (strlen($excerpt) > 100) {
                                                $excerpt = substr($excerpt, 0, 100) . '...';
                                            }
                                            echo htmlspecialchars($excerpt);
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle me-2"></i>Belum ada postingan untuk ditampilkan.
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Navigasi Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="d-flex justify-content-center">
            <ul class="pagination">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?>">
                            <i class="fas fa-chevron-left"></i> Sebelumnya
                        </a>
                    </li>
                <?php else: ?>
                    <li class="page-item disabled">
                        <span class="page-link"><i class="fas fa-chevron-left"></i> Sebelumnya</span>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?>">
                            Selanjutnya <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="page-item disabled">
                        <span class="page-link">Selanjutnya <i class="fas fa-chevron-right"></i></span>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>

    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                    <div class="d-flex align-items-center justify-content-center justify-content-md-start">
                        <img src="/assets/default_post_image.jpeg" alt="Logo" height="50" class="me-3" style="border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,.3);">
                        <div>
                            <h5 class="mb-1">Duta Damai Kalimantan Selatan</h5>
                            <p class="mb-0 opacity-75" style="font-size: 0.9rem;">
                                <i class="fas fa-shield-alt me-1" style="color: var(--secondary-color);"></i>
                                Badan Nasional Penanggulangan Terorisme
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="mb-2">&copy; <?= date('Y') ?> Duta Damai Kalsel. All rights reserved.</p>
                    <div class="mt-2">
                        <a href="#" class="text-white mx-2"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white mx-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white mx-2"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white mx-2"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom Scripts -->
    <script>
        // Sidebar Toggle
        const menuToggle = document.getElementById('menuToggle');
        const closeSidebar = document.getElementById('closeSidebar');
        const sidebarMenu = document.getElementById('sidebarMenu');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        menuToggle.addEventListener('click', () => {
            sidebarMenu.classList.add('active');
            sidebarOverlay.classList.add('active');
        });

        closeSidebar.addEventListener('click', () => {
            sidebarMenu.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        });

        sidebarOverlay.addEventListener('click', () => {
            sidebarMenu.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        });

        // Dark Mode Toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        const darkModeIcon = darkModeToggle.querySelector('i');

        // Check saved dark mode preference
        if (localStorage.getItem('darkMode') === 'enabled') {
            document.body.classList.add('dark-mode');
            darkModeIcon.classList.remove('fa-moon');
            darkModeIcon.classList.add('fa-sun');
        }

        darkModeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');

            if (document.body.classList.contains('dark-mode')) {
                localStorage.setItem('darkMode', 'enabled');
                darkModeIcon.classList.remove('fa-moon');
                darkModeIcon.classList.add('fa-sun');
            } else {
                localStorage.setItem('darkMode', 'disabled');
                darkModeIcon.classList.remove('fa-sun');
                darkModeIcon.classList.add('fa-moon');
            }
        });

        // Scroll to Top Button
        const scrollToTop = document.getElementById('scrollToTop');

        window.addEventListener('scroll', function() {
            // Navbar scroll effect
            const navbar = document.querySelector('.navbar-custom');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }

            // Show/hide scroll to top button
            if (window.scrollY > 300) {
                scrollToTop.classList.add('show');
            } else {
                scrollToTop.classList.remove('show');
            }
        });

        scrollToTop.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Cards will auto-fade in with CSS animation

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>

    <!-- Lazy Loading Script -->
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const lazyImages = document.querySelectorAll('img.lazyload');

        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazyload');
                        imageObserver.unobserve(img);
                    }
                });
            });

            lazyImages.forEach(function(img) {
                imageObserver.observe(img);
            });
        } else {
            // Fallback for browsers without IntersectionObserver
            lazyImages.forEach(function(img) {
                img.src = img.dataset.src;
            });
        }
    });
    </script>

<!--
  Duta Damai Kalimantan Selatan - Blog System
  © 2025 Duta Damai Kalimantan Selatan

  Menggunakan komponen dari CMS Jawara
  © 2020 Djadjoel (MIT License)
  Repository: https://github.com/djadjoel/cmsjawara
-->
</body>
</html>
