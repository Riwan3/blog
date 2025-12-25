<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Language" content="id">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?= isset($page_title) ? $page_title : 'Dashboard' ?> - Blog Duta Damai Kalsel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, shrink-to-fit=no" />
    <meta name="description" content="Blog Duta Damai Kalimantan Selatan">
    <meta name="msapplication-tap-highlight" content="no">
    <link href="/assets/css/main.css" rel="stylesheet">
    <link rel="icon" href="/assets/default_post_image.jpeg" type="image/jpeg">
    <style>
        /* Dark Mode Styles - Inline */
        body.dark-mode {
            background: #1a1a1a !important;
            color: #e0e0e0 !important;
        }

        body.dark-mode .app-container {
            background: #1a1a1a !important;
        }

        body.dark-mode .app-header {
            background: #2d2d2d !important;
            border-bottom: 1px solid #444 !important;
        }

        body.dark-mode .app-sidebar {
            background: #2d2d2d !important;
        }

        body.dark-mode .vertical-nav-menu {
            background: #2d2d2d !important;
        }

        body.dark-mode .vertical-nav-menu a {
            color: #b0b0b0 !important;
        }

        body.dark-mode .vertical-nav-menu a:hover,
        body.dark-mode .vertical-nav-menu a.mm-active {
            background: #3a3a3a !important;
            color: #e0e0e0 !important;
        }

        body.dark-mode .app-main__outer {
            background: #1a1a1a !important;
        }

        body.dark-mode .card {
            background: #2d2d2d !important;
            border-color: #444 !important;
            color: #e0e0e0 !important;
        }

        body.dark-mode .card-header {
            background: #3a3a3a !important;
            border-bottom-color: #444 !important;
            color: #e0e0e0 !important;
        }

        body.dark-mode .card-footer {
            background: #3a3a3a !important;
            border-top-color: #444 !important;
        }

        body.dark-mode .table {
            color: #e0e0e0 !important;
        }

        body.dark-mode .table-striped tbody tr:nth-of-type(odd) {
            background: #3a3a3a !important;
        }

        body.dark-mode .table-hover tbody tr:hover {
            background: #3a3a3a !important;
        }

        body.dark-mode thead th {
            color: #e0e0e0 !important;
            border-bottom-color: #444 !important;
        }

        body.dark-mode thead a {
            color: #e0e0e0 !important;
        }

        body.dark-mode .form-control {
            background: #3a3a3a !important;
            border-color: #444 !important;
            color: #e0e0e0 !important;
        }

        body.dark-mode .form-control:focus {
            background: #3a3a3a !important;
            border-color: #2980b9 !important;
        }

        body.dark-mode .form-control::placeholder {
            color: #888 !important;
        }

        body.dark-mode .text-muted {
            color: #888 !important;
        }

        body.dark-mode .page-title-heading,
        body.dark-mode .widget-heading {
            color: #e0e0e0 !important;
        }

        body.dark-mode .page-title-subheading,
        body.dark-mode .widget-subheading {
            color: #b0b0b0 !important;
        }

        body.dark-mode .pagination .page-link {
            background: #2d2d2d !important;
            border-color: #444 !important;
            color: #e0e0e0 !important;
        }

        body.dark-mode .pagination .page-link:hover {
            background: #3a3a3a !important;
        }

        body.dark-mode .pagination .page-item.active .page-link {
            background: #2980b9 !important;
            border-color: #2980b9 !important;
        }

        body.dark-mode .badge-success {
            background: #1e8449 !important;
        }

        body.dark-mode .badge-warning {
            background: #d68910 !important;
            color: #fff !important;
        }

        body.dark-mode .alert-success {
            background: #1e4620 !important;
            border-color: #2d6930 !important;
            color: #a9dfac !important;
        }

        body.dark-mode .alert-danger {
            background: #4a1a1a !important;
            border-color: #6d2828 !important;
            color: #f5b7b1 !important;
        }

        body.dark-mode #darkModeToggle {
            background: #2980b9 !important;
            border-color: #2980b9 !important;
            color: #fff !important;
        }

        body.dark-mode .logo-src::before {
            color: #fff !important;
        }

        /* Logo text visibility in light mode */
        .logo-src::before {
            color: #3f6ad8 !important;
        }

        #darkModeToggle {
            transition: all 0.3s ease;
        }

        #darkModeToggle:hover {
            transform: rotate(20deg);
        }

        body,
        .app-container,
        .app-header,
        .app-sidebar,
        .card,
        .table,
        .form-control {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }
    </style>
    <script>
        // Apply dark mode immediately to prevent flash
        (function() {
            const darkMode = localStorage.getItem('adminDarkMode');
            if (darkMode === 'enabled') {
                document.documentElement.classList.add('dark-mode');
                if (document.body) {
                    document.body.classList.add('dark-mode');
                }
            }
        })();
    </script>
</head>
