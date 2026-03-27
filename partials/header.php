<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Skill Learning Academy</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
<header class="main-header">
    <div class="container nav-container">
        <div class="logo">
            <a href="<?php echo BASE_URL; ?>/public/index.php">
                <span class="logo-mark">S</span>
                <span class="logo-text">kill Academy</span>
            </a>
        </div>

        <nav class="main-nav">
            <a href="<?php echo BASE_URL; ?>/public/courses.php">Courses</a>
            <a href="<?php echo BASE_URL; ?>/public/index.php#categories">Skills</a>
            <a href="<?php echo BASE_URL; ?>/public/index.php#why">Why Us</a>
        </nav>

        <div class="nav-actions">
            <?php if (!empty($_SESSION['user'])): ?>
                <a href="<?php echo BASE_URL; ?>/public/logout.php" class="btn btn-outline">Logout</a>
                <?php if ($_SESSION['user']['role'] === 'student'): ?>
                    <a href="<?php echo BASE_URL; ?>/student/dashboard.php" class="btn btn-primary">Student Panel</a>
                <?php elseif ($_SESSION['user']['role'] === 'instructor'): ?>
                    <a href="<?php echo BASE_URL; ?>/instructor/dashboard.php" class="btn btn-primary">Instructor Panel</a>
                <?php elseif ($_SESSION['user']['role'] === 'admin'): ?>
                    <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="btn btn-primary">Admin Panel</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>/public/login.php" class="btn btn-outline">Log in</a>
                <a href="<?php echo BASE_URL; ?>/public/register.php" class="btn btn-primary">Join Free</a>
            <?php endif; ?>
        </div>
    </div>
</header>