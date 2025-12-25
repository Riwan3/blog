<?php
/**
 * Duta Damai Kalimantan Selatan - Blog System
 * Helper Functions untuk Optimasi
 * © 2025 Duta Damai Kalimantan Selatan
 */

/**
 * Asset Versioning - Auto cache bust when file changes
 */
function asset($path) {
    $file = $_SERVER['DOCUMENT_ROOT'] . $path;
    if (file_exists($file)) {
        $version = filemtime($file);
        return $path . '?v=' . $version;
    }
    return $path;
}

/**
 * Simple Cache System
 */
class SimpleCache {
    private static $cache_dir = __DIR__ . '/../cache/';
    private static $enabled = true;

    public static function get($key) {
        if (!self::$enabled) return null;

        $file = self::$cache_dir . md5($key) . '.cache';
        if (file_exists($file)) {
            $data = unserialize(file_get_contents($file));
            if ($data['expires'] > time()) {
                return $data['value'];
            }
            unlink($file);
        }
        return null;
    }

    public static function set($key, $value, $ttl = 3600) {
        if (!self::$enabled) return false;

        if (!is_dir(self::$cache_dir)) {
            mkdir(self::$cache_dir, 0750, true);
        }

        $file = self::$cache_dir . md5($key) . '.cache';
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        return file_put_contents($file, serialize($data), LOCK_EX) !== false;
    }

    public static function delete($key) {
        $file = self::$cache_dir . md5($key) . '.cache';
        if (file_exists($file)) {
            return unlink($file);
        }
        return false;
    }

    public static function clear() {
        if (!is_dir(self::$cache_dir)) return true;

        $files = glob(self::$cache_dir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        return true;
    }
}

/**
 * Lazy Load Image
 */
function lazy_img($src, $alt = '', $class = '') {
    return sprintf(
        '<img src="data:image/svg+xml,%%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 1 1\'%%3E%%3C/svg%%3E" data-src="%s" alt="%s" class="%s lazyload" loading="lazy">',
        htmlspecialchars($src),
        htmlspecialchars($alt),
        htmlspecialchars($class)
    );
}

/**
 * Compress HTML Output
 */
function compress_html($html) {
    // Remove comments
    $html = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $html);

    // Remove whitespace
    $html = preg_replace('/\s+/', ' ', $html);

    // Remove whitespace between tags
    $html = preg_replace('/>\s+</', '><', $html);

    return trim($html);
}

/**
 * Get Optimized Query - Use prepared statement template
 */
function prepare_query($link, $table, $where = [], $order = 'id DESC', $limit = null) {
    $sql = "SELECT * FROM $table";

    if (!empty($where)) {
        $conditions = [];
        foreach ($where as $key => $value) {
            $conditions[] = "$key = ?";
        }
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY $order";

    if ($limit) {
        $sql .= " LIMIT $limit";
    }

    return $sql;
}

/**
 * Minify CSS on the fly
 */
function minify_css($css) {
    // Remove comments
    $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);

    // Remove whitespace
    $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $css);

    // Remove unnecessary spaces
    $css = preg_replace('/ {2,}/', ' ', $css);
    $css = preg_replace('/ ?([,:;{}]) ?/', '$1', $css);

    return trim($css);
}

/**
 * Get Recent Posts with Cache
 */
function get_recent_posts_cached($link, $limit = 5, $ttl = 300) {
    $cache_key = "recent_posts_$limit";

    $posts = SimpleCache::get($cache_key);
    if ($posts !== null) {
        return $posts;
    }

    $sql = "SELECT id, title, slug, image_url, created_at, category
            FROM posts
            WHERE status = 'published'
            ORDER BY created_at DESC
            LIMIT ?";

    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "i", $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $posts = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $posts[] = $row;
    }
    mysqli_stmt_close($stmt);

    SimpleCache::set($cache_key, $posts, $ttl);

    return $posts;
}

/**
 * Get Categories with Cache
 */
function get_categories_cached($link, $ttl = 600) {
    $cache_key = "categories_list";

    $categories = SimpleCache::get($cache_key);
    if ($categories !== null) {
        return $categories;
    }

    $sql = "SELECT DISTINCT category, COUNT(*) as count
            FROM posts
            WHERE status = 'published' AND category IS NOT NULL AND category != ''
            GROUP BY category
            ORDER BY count DESC";

    $result = mysqli_query($link, $sql);

    $categories = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }

    SimpleCache::set($cache_key, $categories, $ttl);

    return $categories;
}

/**
 * Clear post-related caches when post changes
 */
function clear_post_caches() {
    SimpleCache::delete('recent_posts_5');
    SimpleCache::delete('recent_posts_10');
    SimpleCache::delete('categories_list');
}
