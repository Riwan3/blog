<div class="app-sidebar sidebar-shadow">
    <div class="app-header__logo">
        <div class="logo-src"></div>
        <div class="header__pane ml-auto">
            <div>
                <button type="button" class="hamburger close-sidebar-btn hamburger--elastic" data-class="closed-sidebar">
                    <span class="hamburger-box">
                        <span class="hamburger-inner"></span>
                    </span>
                </button>
            </div>
        </div>
    </div>
    <div class="app-header__mobile-menu">
        <div>
            <button type="button" class="hamburger hamburger--elastic mobile-toggle-nav">
                <span class="hamburger-box">
                    <span class="hamburger-inner"></span>
                </span>
            </button>
        </div>
    </div>
    <div class="app-header__menu">
        <span>
            <button type="button" class="btn-icon btn-icon-only btn btn-primary btn-sm mobile-toggle-header-nav">
                <span class="btn-icon-wrapper">
                    <i class="fa fa-ellipsis-v fa-w-6"></i>
                </span>
            </button>
        </span>
    </div>
    <div class="scrollbar-sidebar">
        <div class="app-sidebar__inner">
            <ul class="vertical-nav-menu">
                <li class="app-sidebar__heading">Menu Utama</li>

                <li>
                    <a href="/dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'mm-active' : '' ?>">
                        <i class="metismenu-icon pe-7s-home"></i>Dashboard
                    </a>
                </li>

                <li>
                    <a href="/create_post.php" class="<?= basename($_SERVER['PHP_SELF']) == 'create_post.php' ? 'mm-active' : '' ?>">
                        <i class="metismenu-icon pe-7s-note2"></i>Tambah Postingan
                    </a>
                </li>

                <?php if ($_SESSION['role'] === 'admin'): ?>
                <li class="app-sidebar__heading">Admin</li>

                <li>
                    <a href="/manage_users.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_users.php' ? 'mm-active' : '' ?>">
                        <i class="metismenu-icon pe-7s-users"></i>Kelola User
                    </a>
                </li>

                <li>
                    <a href="/security_dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'security_dashboard.php' ? 'mm-active' : '' ?>">
                        <i class="metismenu-icon pe-7s-shield"></i>Security Dashboard
                    </a>
                </li>
                <?php endif; ?>

                <li class="app-sidebar__heading">Akun</li>

                <li>
                    <a href="/logout.php">
                        <i class="metismenu-icon pe-7s-power"></i>Keluar
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
