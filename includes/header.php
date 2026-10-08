<?php
// Layout Header
$page_title = $page_title ?? 'FreshMart';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($page_title) ?> - FreshMart</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/favicon.png">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar Inclusion -->
        <?php include_once 'sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Topbar -->
            <header class="topbar">
                <div class="topbar-left">
                    <button id="sidebar-toggle" class="btn btn-icon"><i class="fas fa-bars"></i></button>
                    <h2 class="page-title"><?= esc($page_title) ?></h2>
                </div>
                <div class="topbar-right">
                    <div class="user-profile dropdown">
                        <span class="user-name"><?= esc($_SESSION['user_name'] ?? 'User') ?> (<?= ucfirst(esc($_SESSION['user_role'] ?? '')) ?>)</span>
                        <a href="<?= BASE_URL ?>/logout.php" class="btn btn-outline-danger btn-sm ml-2">Logout <i class="fas fa-sign-out-alt"></i></a>
                    </div>
                </div>
            </header>
            
            <!-- Content Body -->
            <div class="content-body">
                <?php display_flash_message(); ?>
