<div class="app-header header-shadow">
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
    <div class="app-header__content">
        <div class="app-header-left">
            <div class="search-wrapper">
                <div class="input-holder">
                    <input type="text" class="search-input" placeholder="Cari postingan...">
                    <button class="search-icon"><span></span></button>
                </div>
                <button class="close"></button>
            </div>
        </div>
        <div class="app-header-right">
            <div class="header-btn-lg pr-0">
                <div class="widget-content p-0">
                    <div class="widget-content-wrapper">
                        <div class="widget-content-left mr-3">
                            <button class="btn btn-sm btn-outline-primary" id="darkModeToggle" title="Toggle Dark Mode">
                                <i class="pe-7s-moon" id="darkModeIcon"></i>
                            </button>
                        </div>
                        <div class="widget-content-left">
                            <img width="42" class="rounded" src="/assets/default_post_image.jpeg" alt="Duta Damai Kalsel">
                        </div>
                        <div class="widget-content-left ml-3 header-user-info">
                            <div class="widget-heading">
                                <?= htmlspecialchars($_SESSION['username']) ?>
                            </div>
                            <div class="widget-subheading">
                                <?= ucfirst(htmlspecialchars($_SESSION['role'])) ?>
                            </div>
                        </div>
                        <div class="widget-content-right ml-3">
                            <a href="/logout.php" class="btn btn-sm btn-danger">
                                <i class="pe-7s-power"></i> Keluar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
