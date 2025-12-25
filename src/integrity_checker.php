<?php
/**
 * Duta Damai Kalimantan Selatan - Blog System
 * File Integrity Checker - Detects backdoors and unauthorized modifications
 * © 2025 Duta Damai Kalimantan Selatan
 */

require_once 'security.php';

session_start();

// Only admin can access this
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    die('Access denied');
}

$base_dir = dirname(__DIR__);
$hash_file = $base_dir . '/config/file_hashes.json';
$action = $_GET['action'] ?? 'check';

/**
 * Generate hash for all PHP files
 */
function generateFileHashes($dir, $base_dir) {
    $hashes = [];
    $exclude_dirs = ['vendor', 'node_modules', 'cache', 'logs', 'cmsjawara-master'];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $relative_path = str_replace($base_dir . '/', '', $file->getPathname());

            // Skip excluded directories
            $skip = false;
            foreach ($exclude_dirs as $exclude) {
                if (strpos($relative_path, $exclude) === 0) {
                    $skip = true;
                    break;
                }
            }

            if (!$skip) {
                $hashes[$relative_path] = hash_file('sha256', $file->getPathname());
            }
        }
    }

    return $hashes;
}

/**
 * Scan for suspicious code patterns
 */
function scanForMalware($file_path) {
    $suspicious_patterns = [
        '/eval\s*\(/i',
        '/base64_decode\s*\(/i',
        '/shell_exec\s*\(/i',
        '/exec\s*\(/i',
        '/system\s*\(/i',
        '/passthru\s*\(/i',
        '/proc_open\s*\(/i',
        '/popen\s*\(/i',
        '/curl_exec\s*\(/i',
        '/curl_multi_exec\s*\(/i',
        '/parse_ini_file\s*\(/i',
        '/show_source\s*\(/i',
        '/file_get_contents.*http/i',
        '/fsockopen\s*\(/i',
        '/\$_FILES.*move_uploaded_file/i',
        '/assert\s*\(/i',
        '/preg_replace.*\/e/i',
        '/create_function\s*\(/i',
        '/include.*\$_(GET|POST|REQUEST)/i',
        '/require.*\$_(GET|POST|REQUEST)/i',
    ];

    $content = file_get_contents($file_path);
    $findings = [];

    foreach ($suspicious_patterns as $pattern) {
        if (preg_match($pattern, $content, $matches)) {
            $findings[] = [
                'pattern' => $pattern,
                'match' => $matches[0]
            ];
        }
    }

    return $findings;
}

// ============================================
// HANDLE ACTIONS
// ============================================

if ($action === 'generate') {
    // Generate baseline hashes
    $hashes = generateFileHashes($base_dir, $base_dir);

    // Create config directory if not exists
    $config_dir = dirname($hash_file);
    if (!is_dir($config_dir)) {
        mkdir($config_dir, 0750, true);
    }

    file_put_contents($hash_file, json_encode($hashes, JSON_PRETTY_PRINT));

    logSecurityEvent('INTEGRITY_BASELINE', 'File integrity baseline generated', 'INFO');

    echo json_encode([
        'success' => true,
        'message' => 'Baseline generated successfully',
        'files_count' => count($hashes)
    ]);
    exit;
}

if ($action === 'check') {
    if (!file_exists($hash_file)) {
        echo json_encode([
            'success' => false,
            'message' => 'No baseline found. Please generate baseline first.'
        ]);
        exit;
    }

    $baseline_hashes = json_decode(file_get_contents($hash_file), true);
    $current_hashes = generateFileHashes($base_dir, $base_dir);

    $results = [
        'modified' => [],
        'new' => [],
        'deleted' => [],
        'suspicious' => []
    ];

    // Check for modifications and new files
    foreach ($current_hashes as $file => $hash) {
        if (!isset($baseline_hashes[$file])) {
            $results['new'][] = $file;

            // Scan new files for malware
            $malware = scanForMalware($base_dir . '/' . $file);
            if (!empty($malware)) {
                $results['suspicious'][] = [
                    'file' => $file,
                    'findings' => $malware
                ];
            }
        } elseif ($baseline_hashes[$file] !== $hash) {
            $results['modified'][] = $file;

            // Scan modified files for malware
            $malware = scanForMalware($base_dir . '/' . $file);
            if (!empty($malware)) {
                $results['suspicious'][] = [
                    'file' => $file,
                    'findings' => $malware
                ];
            }
        }
    }

    // Check for deleted files
    foreach ($baseline_hashes as $file => $hash) {
        if (!isset($current_hashes[$file])) {
            $results['deleted'][] = $file;
        }
    }

    // Log if suspicious files found
    if (!empty($results['suspicious'])) {
        logSecurityEvent('MALWARE_DETECTED', 'Suspicious code patterns detected in ' . count($results['suspicious']) . ' files', 'CRITICAL');
    }

    echo json_encode([
        'success' => true,
        'results' => $results,
        'total_modified' => count($results['modified']),
        'total_new' => count($results['new']),
        'total_deleted' => count($results['deleted']),
        'total_suspicious' => count($results['suspicious'])
    ]);
    exit;
}
