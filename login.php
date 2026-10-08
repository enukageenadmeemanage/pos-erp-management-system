<?php
require_once __DIR__ . '/includes/auth.php';
redirect_if_logged_in();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT id, name, password_hash, role, status FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['status'] !== 'active') {
                $error = 'Your account has been deactivated.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                
                log_activity($pdo, $user['id'], 'Login', 'User logged in successfully.');
                
                header("Location: " . BASE_URL . "/pages/dashboard.php");
                exit;
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FreshMart</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #10B981;
            --primary-dark: #059669;
            --dark: #1F2937;
            --light: #F3F4F6;
            --danger: #EF4444;
            --font-family: 'Inter', sans-serif;
        }
        body {
            font-family: var(--font-family);
            background-color: var(--light);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .login-card {
            background: #fff;
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .login-card h2 {
            margin-top: 0;
            color: var(--dark);
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        .login-card .logo {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        .form-group {
            margin-bottom: 1.25rem;
            text-align: left;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--dark);
        }
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #D1D5DB;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-family: inherit;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
        }
        .btn-primary {
            width: 100%;
            padding: 0.75rem;
            background-color: var(--primary);
            color: #fff;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        .alert-danger {
            background-color: #FEE2E2;
            color: #991B1B;
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            text-align: left;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="logo"><i class="fas fa-shopping-basket"></i></div>
        <h2>FreshMart Admin</h2>
        
        <?php if ($error): ?>
            <div class="alert-danger"><?= esc($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <?= csrf_field() ?>
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" required autofocus placeholder="admin@grocery.test">
            </div>
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn-primary">Sign In</button>
        </form>
    </div>

</body>
</html>
