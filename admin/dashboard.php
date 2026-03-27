<?php
require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

// counts
$res = $mysqli->query("SELECT COUNT(*) AS total_users FROM users");
$usersCount = $res->fetch_assoc()['total_users'] ?? 0;

$res = $mysqli->query("SELECT COUNT(*) AS total_courses FROM courses");
$coursesCount = $res->fetch_assoc()['total_courses'] ?? 0;

$res = $mysqli->query("SELECT COUNT(*) AS pending_courses FROM courses WHERE status = 'pending'");
$pendingCount = $res->fetch_assoc()['pending_courses'] ?? 0;

include __DIR__ . '/../partials/header.php';
?>

<main class="dashboard-page">
    <div class="container dashboard-layout">
        <aside class="dashboard-sidebar">
            <div class="dash-user">
                <div class="dash-avatar">A</div>
                <div>
                    <div class="dash-hello">Hello,</div>
                    <div class="dash-name"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></div>
                    <div class="dash-role">Admin</div>
                </div>
            </div>
            <nav class="dash-nav">
                <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="active">Dashboard</a>
                <a href="<?php echo BASE_URL; ?>/admin/courses.php">Courses</a>
                <a href="<?php echo BASE_URL; ?>/admin/users.php">Users</a>
            </nav>
        </aside>

        <section class="dashboard-main">
            <header class="dashboard-header">
                <div>
                    <h1>Admin dashboard</h1>
                    <p>Review courses and manage the academy.</p>
                </div>
            </header>

            <div class="dashboard-stats">
                <div class="stat-card stat-1">
                    <div class="stat-label">Users</div>
                    <div class="stat-value"><?php echo $usersCount; ?></div>
                    <div class="stat-foot">Total accounts</div>
                </div>
                <div class="stat-card stat-2">
                    <div class="stat-label">Courses</div>
                    <div class="stat-value"><?php echo $coursesCount; ?></div>
                    <div class="stat-foot">Created by instructors</div>
                </div>
                <div class="stat-card stat-3">
                    <div class="stat-label">Pending courses</div>
                    <div class="stat-value"><?php echo $pendingCount; ?></div>
                    <div class="stat-foot">Need review</div>
                </div>
            </div>
        </section>
    </div>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>