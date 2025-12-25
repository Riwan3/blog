<?php
/**
 * Duta Damai Kalimantan Selatan - Blog System
 * Security Helper Functions
 * © 2025 Duta Damai Kalimantan Selatan
 */

// ============================================
// 1. CSRF PROTECTION
// ============================================

/**
 * Generate CSRF Token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !isset($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF Input Field
 */
function csrfField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

// ============================================
// 2. BRUTE FORCE PROTECTION (Rate Limiting)
// ============================================

/**
 * Check Rate Limit for Login Attempts
 * @param string $identifier - IP address or username
 * @param int $max_attempts - Maximum attempts allowed
 * @param int $time_window - Time window in seconds (default 15 minutes)
 * @return bool - True if allowed, False if rate limited
 */
function checkRateLimit($identifier, $max_attempts = 5, $time_window = 900) {
    $key = 'rate_limit_' . md5($identifier);

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
    }

    $data = $_SESSION[$key];
    $elapsed = time() - $data['first_attempt'];

    // Reset if time window expired
    if ($elapsed > $time_window) {
        $_SESSION[$key] = ['count' => 1, 'first_attempt' => time()];
        return true;
    }

    // Check if limit exceeded
    if ($data['count'] >= $max_attempts) {
        return false;
    }

    // Increment counter
    $_SESSION[$key]['count']++;
    return true;
}

/**
 * Get remaining lockout time
 */
function getRemainingLockoutTime($identifier, $time_window = 900) {
    $key = 'rate_limit_' . md5($identifier);

    if (!isset($_SESSION[$key])) {
        return 0;
    }

    $elapsed = time() - $_SESSION[$key]['first_attempt'];
    $remaining = $time_window - $elapsed;

    return max(0, $remaining);
}

// ============================================
// 3. XSS PROTECTION
// ============================================

/**
 * Sanitize Output - Prevent XSS
 */
function sanitizeOutput($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize HTML Content (for post content)
 */
function sanitizeHTML($html) {
    // Allow only safe HTML tags
    $allowed_tags = '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img><blockquote><code><pre>';
    $clean = strip_tags($html, $allowed_tags);

    // Remove javascript: and data: protocols from links
    $clean = preg_replace('/(<a[^>]+href=")(?:javascript:|data:)/i', '$1#blocked', $clean);
    $clean = preg_replace('/(<img[^>]+src=")(?:javascript:|data:)/i', '$1#blocked', $clean);

    return $clean;
}

/**
 * Validate Email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate URL
 */
function validateURL($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

// ============================================
// 4. SQL INJECTION PROTECTION
// ============================================
// Already using prepared statements, but add extra validation

/**
 * Validate Integer ID
 */
function validateID($id) {
    return filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
}

/**
 * Sanitize String Input
 */
function sanitizeString($string, $max_length = 255) {
    $string = trim($string);
    $string = substr($string, 0, $max_length);
    return $string;
}

// ============================================
// 5. PASSWORD SECURITY
// ============================================

/**
 * Validate Password Strength
 */
function validatePasswordStrength($password, $min_length = 8) {
    if (strlen($password) < $min_length) {
        return ['valid' => false, 'message' => "Password minimal $min_length karakter"];
    }

    // Check for at least one number
    if (!preg_match('/\d/', $password)) {
        return ['valid' => false, 'message' => 'Password harus mengandung minimal 1 angka'];
    }

    // Check for at least one letter
    if (!preg_match('/[a-zA-Z]/', $password)) {
        return ['valid' => false, 'message' => 'Password harus mengandung minimal 1 huruf'];
    }

    return ['valid' => true, 'message' => 'Password kuat'];
}

/**
 * Check if password is commonly used
 */
function isCommonPassword($password) {
    $common_passwords = [
        'password', '123456', '12345678', 'qwerty', 'abc123',
        'monkey', '1234567', 'letmein', 'trustno1', 'dragon',
        'baseball', 'iloveyou', 'master', 'sunshine', 'ashley',
        'admin', 'welcome', 'login', 'password123', '123456789'
    ];

    return in_array(strtolower($password), $common_passwords);
}

// ============================================
// 6. SESSION SECURITY
// ============================================

/**
 * Regenerate Session ID (prevent session fixation)
 */
function secureSession() {
    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
    }

    // Add session timeout (2 hours)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 7200)) {
        session_unset();
        session_destroy();
        return false;
    }
    $_SESSION['last_activity'] = time();

    return true;
}

/**
 * Validate Session User Agent (prevent session hijacking)
 */
function validateUserAgent() {
    $current_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    if (!isset($_SESSION['user_agent'])) {
        $_SESSION['user_agent'] = $current_agent;
        return true;
    }

    return $_SESSION['user_agent'] === $current_agent;
}

// ============================================
// 7. FILE UPLOAD SECURITY
// ============================================

/**
 * Validate File Upload
 */
function validateFileUpload($file, $allowed_types = ['image/jpeg', 'image/png', 'image/gif'], $max_size = 5242880) {
    // Check if file exists
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['valid' => false, 'message' => 'File tidak valid'];
    }

    // Check file size (default 5MB)
    if ($file['size'] > $max_size) {
        return ['valid' => false, 'message' => 'File terlalu besar (maksimal ' . ($max_size / 1048576) . 'MB)'];
    }

    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, $allowed_types)) {
        return ['valid' => false, 'message' => 'Tipe file tidak diizinkan'];
    }

    // Check for PHP code in image files
    $content = file_get_contents($file['tmp_name']);
    if (preg_match('/<\?php/i', $content)) {
        return ['valid' => false, 'message' => 'File mengandung kode berbahaya'];
    }

    return ['valid' => true, 'message' => 'File valid'];
}

/**
 * Generate Safe Filename
 */
function generateSafeFilename($original_name) {
    $extension = pathinfo($original_name, PATHINFO_EXTENSION);
    $safe_name = bin2hex(random_bytes(16));
    return $safe_name . '.' . strtolower($extension);
}

// ============================================
// 8. SECURITY LOGGING
// ============================================

/**
 * Log Security Event
 */
function logSecurityEvent($event_type, $details, $severity = 'INFO') {
    $log_file = __DIR__ . '/../logs/security.log';
    $log_dir = dirname($log_file);

    // Create logs directory if not exists
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0750, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
    $user = $_SESSION['username'] ?? 'GUEST';

    $log_entry = sprintf(
        "[%s] [%s] [%s] IP:%s User:%s Event:%s Details:%s UA:%s\n",
        $timestamp,
        $severity,
        $event_type,
        $ip,
        $user,
        $event_type,
        $details,
        substr($user_agent, 0, 100)
    );

    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// ============================================
// 9. IP BLOCKING
// ============================================

/**
 * Check if IP is blocked
 */
function isIPBlocked($ip = null) {
    if ($ip === null) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    }

    $blocked_ips_file = __DIR__ . '/../config/blocked_ips.txt';

    if (!file_exists($blocked_ips_file)) {
        return false;
    }

    $blocked_ips = file($blocked_ips_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    return in_array($ip, $blocked_ips);
}

/**
 * Block IP Address
 */
function blockIP($ip, $reason = '') {
    $blocked_ips_file = __DIR__ . '/../config/blocked_ips.txt';
    $log_entry = $ip . ($reason ? " # $reason" : '') . "\n";

    file_put_contents($blocked_ips_file, $log_entry, FILE_APPEND | LOCK_EX);
    logSecurityEvent('IP_BLOCKED', "IP $ip blocked. Reason: $reason", 'WARNING');
}

// ============================================
// 10. SECURITY HEADERS
// ============================================

/**
 * Set Security Headers
 */
function setSecurityHeaders() {
    // Prevent XSS attacks
    header('X-XSS-Protection: 1; mode=block');

    // Prevent clickjacking
    header('X-Frame-Options: SAMEORIGIN');

    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');

    // Referrer Policy
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Content Security Policy (adjust as needed)
    header("Content-Security-Policy: default-src 'self' 'unsafe-inline' 'unsafe-eval' https: data:;");

    // Prevent directory listing
    header('X-Permitted-Cross-Domain-Policies: none');
}

// ============================================
// 11. HONEYPOT (Anti-Bot)
// ============================================

/**
 * Generate Honeypot Field
 */
function honeypotField() {
    return '<input type="text" name="website_url" style="display:none;" tabindex="-1" autocomplete="off">';
}

/**
 * Check Honeypot
 */
function checkHoneypot() {
    return empty($_POST['website_url']);
}
