<?php
require_once 'includes/init.php';

// Redirect to login if not authenticated
requireLogin();

// Get current page from URL or default to dashboard
$page = isset($_GET['page']) ? cleanInput($_GET['page']) : 'dashboard';

// Define allowed pages and their access requirements
$allowed_pages = [
    'dashboard' => ['view'],
    'bom' => ['view', 'edit'],
    'production' => ['view', 'edit'],
    'users' => ['view', 'edit'],
    'settings' => ['view', 'edit']
];

// Validate page access
if (!isset($allowed_pages[$page]) || !checkAccess($page, 'view')) {
    setMessage('error', Language::get('access_denied'));
    $page = 'dashboard';
}

// Get page title
$pageTitle = Language::get($page);
?>
<!DOCTYPE html>
<html lang="<?php echo CURRENT_LANG; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - <?php echo $pageTitle; ?></title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    
    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>">
                <?php echo APP_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'dashboard' ? 'active' : ''; ?>" 
                           href="?page=dashboard">
                            <i class="fas fa-tachometer-alt"></i> <?php echo Language::get('dashboard'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'bom' ? 'active' : ''; ?>" 
                           href="?page=bom">
                            <i class="fas fa-sitemap"></i> <?php echo Language::get('bom'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'production' ? 'active' : ''; ?>" 
                           href="?page=production">
                            <i class="fas fa-industry"></i> <?php echo Language::get('production'); ?>
                        </a>
                    </li>
                    <?php if (checkAccess('users', 'view')): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'users' ? 'active' : ''; ?>" 
                           href="?page=users">
                            <i class="fas fa-users"></i> <?php echo Language::get('users'); ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (checkAccess('settings', 'view')): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'settings' ? 'active' : ''; ?>" 
                           href="?page=settings">
                            <i class="fas fa-cog"></i> <?php echo Language::get('settings'); ?>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" 
                           data-bs-toggle="dropdown">
                            <i class="fas fa-globe"></i> <?php echo $GLOBALS['available_languages'][CURRENT_LANG]; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php foreach ($GLOBALS['available_languages'] as $code => $name): ?>
                            <li>
                                <a class="dropdown-item <?php echo $code === CURRENT_LANG ? 'active' : ''; ?>" 
                                   href="?language=<?php echo $code; ?>">
                                    <?php echo $name; ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> <?php echo Language::get('logout'); ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid my-4">
        <?php
        // Display messages if any
        if ($message = getMessage()) {
            echo '<div class="alert alert-' . ($message['type'] === 'error' ? 'danger' : 'success') . ' alert-dismissible fade show">
                    ' . $message['text'] . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                  </div>';
        }
        
        // Include the page content
        $page_file = "pages/{$page}.php";
        if (file_exists($page_file)) {
            include $page_file;
        } else {
            echo '<div class="alert alert-danger">
                    ' . Language::get('not_found') . '
                  </div>';
        }
        ?>
    </div>

    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container text-center">
            <span class="text-muted"><?php echo APP_NAME; ?> &copy; <?php echo date('Y'); ?></span>
        </div>
    </footer>

    <!-- Custom JavaScript -->
    <script src="assets/js/main.js"></script>
    <?php if (file_exists("assets/js/{$page}.js")): ?>
    <script src="assets/js/<?php echo $page; ?>.js"></script>
    <?php endif; ?>
</body>
</html>
