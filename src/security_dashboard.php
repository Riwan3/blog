<?php
/**
 * Duta Damai Kalimantan Selatan - Blog System
 * Security Dashboard - Monitor security events and threats
 * © 2025 Duta Damai Kalimantan Selatan
 */

session_start();
require_once "security.php";

// Only admin can access
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login.php');
    exit;
}

setSecurityHeaders();

$page_title = "Security Dashboard";
?>

<!DOCTYPE html>
<html lang="id">
<?php include "includes/head.php"; ?>

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
                                    <i class="pe-7s-shield icon-gradient bg-mean-fruit"></i>
                                </div>
                                <div>Security Dashboard
                                    <div class="page-title-subheading">Monitor keamanan sistem dan deteksi ancaman</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Security Stats -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">File Integrity</h5>
                                    <button class="btn btn-primary btn-sm" onclick="checkIntegrity()">
                                        <i class="pe-7s-refresh"></i> Check Now
                                    </button>
                                    <div id="integrity-status" class="mt-2"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Generate Baseline</h5>
                                    <button class="btn btn-warning btn-sm" onclick="generateBaseline()">
                                        <i class="pe-7s-diskette"></i> Generate
                                    </button>
                                    <p class="small text-muted mt-2">Generate setelah update code</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Security Log</h5>
                                    <button class="btn btn-info btn-sm" onclick="viewLogs()">
                                        <i class="pe-7s-note2"></i> View Logs
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Blocked IPs</h5>
                                    <button class="btn btn-danger btn-sm" onclick="viewBlockedIPs()">
                                        <i class="pe-7s-attention"></i> View List
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Results Area -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <strong>Security Results</strong>
                                </div>
                                <div class="card-body">
                                    <div id="results-area">
                                        <p class="text-muted">Pilih aksi di atas untuk melihat hasil</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Security Recommendations -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <strong><i class="pe-7s-check"></i> Proteksi Aktif</strong>
                                </div>
                                <div class="card-body">
                                    <ul>
                                        <li>✅ CSRF Protection - Melindungi dari Cross-Site Request Forgery</li>
                                        <li>✅ Rate Limiting - Mencegah brute force attack (max 5 percobaan / 15 menit)</li>
                                        <li>✅ XSS Protection - Header X-XSS-Protection aktif</li>
                                        <li>✅ Clickjacking Protection - Header X-Frame-Options: SAMEORIGIN</li>
                                        <li>✅ SQL Injection Protection - Prepared statements di semua query</li>
                                        <li>✅ Input Sanitization - Semua input user di-sanitize</li>
                                        <li>✅ Security Logging - Semua event security tercatat</li>
                                        <li>✅ Password Hashing - Bcrypt untuk semua password</li>
                                        <li>✅ Session Security - HttpOnly cookies & session timeout</li>
                                        <li>✅ File Upload Validation - MIME type & content checking</li>
                                        <li>✅ Honeypot - Anti-bot protection pada form</li>
                                        <li>✅ IP Blocking - Otomatis block IP mencurigakan</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include "includes/footer.php" ?>

    <script>
    function checkIntegrity() {
        document.getElementById('results-area').innerHTML = '<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Checking file integrity...</div>';

        fetch('/integrity_checker.php?action=check')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let html = '<h5>File Integrity Check Results</h5>';

                    if (data.total_modified === 0 && data.total_new === 0 && data.total_deleted === 0 && data.total_suspicious === 0) {
                        html += '<div class="alert alert-success"><i class="pe-7s-check"></i> All files are intact. No modifications detected.</div>';
                    } else {
                        if (data.total_suspicious > 0) {
                            html += '<div class="alert alert-danger"><i class="pe-7s-attention"></i> <strong>WARNING:</strong> Suspicious code patterns detected in ' + data.total_suspicious + ' files!</div>';
                            html += '<h6>Suspicious Files:</h6><ul class="list-group mb-3">';
                            data.results.suspicious.forEach(item => {
                                html += '<li class="list-group-item list-group-item-danger"><strong>' + item.file + '</strong><br>';
                                item.findings.forEach(finding => {
                                    html += '<small>Pattern: ' + finding.pattern + ' | Match: ' + finding.match + '</small><br>';
                                });
                                html += '</li>';
                            });
                            html += '</ul>';
                        }

                        if (data.total_modified > 0) {
                            html += '<div class="alert alert-warning"><i class="pe-7s-info"></i> ' + data.total_modified + ' files have been modified</div>';
                            html += '<h6>Modified Files:</h6><ul class="list-group mb-3">';
                            data.results.modified.forEach(file => {
                                html += '<li class="list-group-item">' + file + '</li>';
                            });
                            html += '</ul>';
                        }

                        if (data.total_new > 0) {
                            html += '<div class="alert alert-info"><i class="pe-7s-plus"></i> ' + data.total_new + ' new files detected</div>';
                            html += '<h6>New Files:</h6><ul class="list-group mb-3">';
                            data.results.new.forEach(file => {
                                html += '<li class="list-group-item">' + file + '</li>';
                            });
                            html += '</ul>';
                        }

                        if (data.total_deleted > 0) {
                            html += '<div class="alert alert-warning"><i class="pe-7s-trash"></i> ' + data.total_deleted + ' files have been deleted</div>';
                            html += '<h6>Deleted Files:</h6><ul class="list-group mb-3">';
                            data.results.deleted.forEach(file => {
                                html += '<li class="list-group-item">' + file + '</li>';
                            });
                            html += '</ul>';
                        }
                    }

                    document.getElementById('results-area').innerHTML = html;
                } else {
                    document.getElementById('results-area').innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
                }
            })
            .catch(error => {
                document.getElementById('results-area').innerHTML = '<div class="alert alert-danger">Error: ' + error + '</div>';
            });
    }

    function generateBaseline() {
        if (!confirm('Generate baseline akan menimpa baseline sebelumnya. Lanjutkan?')) {
            return;
        }

        document.getElementById('results-area').innerHTML = '<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Generating baseline...</div>';

        fetch('/integrity_checker.php?action=generate')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('results-area').innerHTML = '<div class="alert alert-success"><i class="pe-7s-check"></i> Baseline generated successfully! Total files: ' + data.files_count + '</div>';
                } else {
                    document.getElementById('results-area').innerHTML = '<div class="alert alert-danger">Error: ' + data.message + '</div>';
                }
            })
            .catch(error => {
                document.getElementById('results-area').innerHTML = '<div class="alert alert-danger">Error: ' + error + '</div>';
            });
    }

    function viewLogs() {
        document.getElementById('results-area').innerHTML = '<div class="alert alert-info">Security logs tersimpan di: <code>/logs/security.log</code><br>Gunakan SSH atau file manager untuk melihat log lengkap.</div>';
    }

    function viewBlockedIPs() {
        document.getElementById('results-area').innerHTML = '<div class="alert alert-info">Blocked IPs tersimpan di: <code>/config/blocked_ips.txt</code><br>Gunakan SSH atau file manager untuk melihat dan mengelola IP yang diblokir.</div>';
    }
    </script>

<!--
  Duta Damai Kalimantan Selatan - Blog System
  © 2025 Duta Damai Kalimantan Selatan
-->
</body>
</html>
