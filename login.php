<?php
require_once 'includes/init.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = cleanInput($_POST['username']);
    $password = cleanInput($_POST['password']);
    $csrf_token = cleanInput($_POST['csrf_token']);

    // Validate CSRF token
    if (!verifyCSRFToken($csrf_token)) {
        setMessage('error', 'Invalid request');
        redirect('login.php');
    }

    // Validate credentials
    $query = "SELECT id, username, password, role FROM users WHERE username = :username AND status = 1";
    $stmt = $db->prepare($query);
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && verifyPassword($password, $user['password'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // Update last login
        $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
        $stmt->execute([':id' => $user['id']]);

        setMessage('success', Language::get('login_success'));
        redirect('index.php');
    } else {
        setMessage('error', Language::get('login_failed'));
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo CURRENT_LANG; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - <?php echo Language::get('login'); ?></title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">
                            <?php echo APP_NAME; ?>
                        </h2>
                        
                        <?php if ($message = getMessage()): ?>
                        <div class="alert alert-<?php echo $message['type'] === 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show">
                            <?php echo $message['text']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <?php echo Language::get('username'); ?>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           required autofocus>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    <?php echo Language::get('password'); ?>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           required>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt"></i> 
                                    <?php echo Language::get('login'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Language Selector -->
                <div class="text-center mt-3">
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle" type="button" 
                                data-bs-toggle="dropdown">
                            <i class="fas fa-globe"></i> 
                            <?php echo $GLOBALS['available_languages'][CURRENT_LANG]; ?>
                        </button>
                        <ul class="dropdown-menu">
                            <?php foreach ($GLOBALS['available_languages'] as $code => $name): ?>
                            <li>
                                <a class="dropdown-item <?php echo $code === CURRENT_LANG ? 'active' : ''; ?>" 
                                   href="?language=<?php echo $code; ?>">
                                    <?php echo $name; ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
