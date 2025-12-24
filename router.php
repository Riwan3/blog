<?php
// Router Sederhana untuk Server Pengembangan PHP

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Aturan khusus untuk sitemap
if ($uri === '/sitemap.xml') {
    require __DIR__ . '/sitemap.php';
    exit;
}

// Cek apakah file yang diminta adalah file fisik di dalam direktori 'src', 'assets', atau 'uploads'
$public_dirs = ['/assets/', '/uploads/', '/'];
foreach ($public_dirs as $dir) {
    $file_path = __DIR__ . $dir . basename($uri);
     if ($dir === '/') $file_path = __DIR__ . $uri; // Untuk file di root seperti style.css

    if (is_file($file_path)) {
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $mime_types = [
            'css'  => 'text/css',
            'js'   => 'application/javascript',
            'jpeg' => 'image/jpeg',
            'jpg'  => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'svg'  => 'image/svg+xml',
            'ico'  => 'image/x-icon',
        ];

        if (isset($mime_types[$extension])) {
            header('Content-Type: ' . $mime_types[$extension]);
            readfile($file_path);
            exit;
        }
    }
}


// --- Penanganan Routing untuk Halaman Dinamis ---
$request_uri = rtrim($uri, '/');
if (empty($request_uri)) {
    $request_uri = '/';
}

// Aturan routing menggunakan if/elseif
if ($request_uri === '/' || $request_uri === '/index.php') {
    require __DIR__ . '/index.php';

} elseif ($request_uri === '/login' || $request_uri === '/login.php') {
    require __DIR__ . '/src/login.php';

} elseif ($request_uri === '/register' || $request_uri === '/register.php') {
    require __DIR__ . '/src/register.php';

} elseif ($request_uri === '/dashboard' || $request_uri === '/dashboard.php') {
    require __DIR__ . '/src/dashboard.php';

} elseif ($request_uri === '/create_post' || $request_uri === '/create_post.php') {
    require __DIR__ . '/src/create_post.php';

} elseif ($request_uri === '/logout' || $request_uri === '/logout.php') {
    require __DIR__ . '/src/logout.php';

// Aturan baru untuk URL artikel berdasarkan slug
} elseif (preg_match('/^\/artikel\/([a-z0-9-]+)$/', $request_uri, $matches)) {
    $_GET['slug'] = $matches[1];
    require __DIR__ . '/src/post.php';

// Aturan untuk halaman edit dan hapus yang masih menggunakan query string
} elseif (strpos($_SERVER['REQUEST_URI'], '/edit_post.php') === 0) {
    require __DIR__ . '/src/edit_post.php';

} elseif (strpos($_SERVER['REQUEST_URI'], '/delete_post.php') === 0) {
    require __DIR__ . '/src/delete_post.php';

} elseif (strpos($_SERVER['REQUEST_URI'], '/manage_users.php') === 0) {
    require __DIR__ . '/src/manage_users.php';
    
} elseif (strpos($_SERVER['REQUEST_URI'], '/edit_user.php') === 0) {
    require __DIR__ . '/src/edit_user.php';

} elseif (strpos($_SERVER['REQUEST_URI'], '/delete_user.php') === 0) {
    require __DIR__ . '/src/delete_user.php';

} elseif (strpos($_SERVER['REQUEST_URI'], '/create_user.php') === 0) {
    require __DIR__ . '/src/create_user.php';
    
} else {
    // Jika tidak ada aturan yang cocok, coba cari file di root sebagai fallback
    if(is_file(__DIR__ . $uri)){
        require __DIR__ . $uri;
    } else {
        http_response_code(404);
        echo "<h1>404 Not Found</h1> Halaman tidak ditemukan.";
    }
}
