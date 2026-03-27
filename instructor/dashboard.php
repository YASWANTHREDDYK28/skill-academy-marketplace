<?php
require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'instructor') {
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

$instructorId = $_SESSION['user']['id'];

// Counts
$totalCourses = 0;
$pendingCourses = 0;
$publishedCourses = 0;

$sql = "SELECT 
    SUM(status = 'draft' OR status = 'pending' OR status = 'published' OR status = 'rejected') AS total,
    SUM(status = 'pending') AS pending_count,
    SUM(status = 'published') AS published_count
    FROM courses
    WHERE instructor_id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('i', $instructorId);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$totalCourses     = (int)($stats['total'] ?? 0);
$pendingCourses   = (int)($stats['pending_count'] ?? 0);
$publishedCourses = (int)($stats['published_count'] ?? 0);

// Recent courses
$sql = "SELECT id, title, status, level, price, created_at 
        FROM courses 
        WHERE instructor_id = ?
        ORDER BY created_at DESC
        LIMIT 5";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('i', $instructorId);
$stmt->execute();
$courses = $stmt->get_result();
$stmt->close();

include __DIR__ . '/../partials/header.php';
?>

<main class="dashboard-page">
    <div class="container dashboard-layout">
        <aside class="dashboard-sidebar">
            <div class="dash-user">
                <div class="dash-avatar">
                    <?php echo strtoupper(substr($_SESSION['user']['name'], 0, 1)); ?>
                </div>
                <div>
                    <div class="dash-hello">Hello,</div>
                    <div class="dash-name"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></div>
                    <div class="dash-role">Instructor</div>
                </div>
            </div>
            <nav class="dash-nav">
                <a href="<?php echo BASE_URL; ?>/instructor/dashboard.php" class="active">Dashboard</a>
                <a href="<?php echo BASE_URL; ?>/instructor/courses.php">My Courses</a>
                <a href="<?php echo BASE_URL; ?>/instructor/course-create.php">Create Course</a>
            </nav>
        </aside>

        <section class="dashboard-main">
            <header class="dashboard-header">
                <div>
                    <h1>Instructor dashboard</h1>
                    <p>Manage your skill courses and track their status.</p>
                </div>
            </header>

            <div class="dashboard-stats">
                <div class="stat-card stat-1">
                    <div class="stat-label">Total courses</div>
                    <div class="stat-value"><?php echo $totalCourses; ?></div>
                    <div class="stat-foot">All courses you created</div>
                </div>
                <div class="stat-card stat-2">
                    <div class="stat-label">Published</div>
                    <div class="stat-value"><?php echo $publishedCourses; ?></div>
                    <div class="stat-foot">Visible to students</div>
                </div>
                <div class="stat-card stat-3">
                    <div class="stat-label">Pending approval</div>
                    <div class="stat-value"><?php echo $pendingCourses; ?></div>
                    <div class="stat-foot">Waiting for admin</div>
                </div>
            </div>

            <section class="dashboard-section">
                <div class="section-head">
                    <h2>Recent courses</h2>
                    <a href="<?php echo BASE_URL; ?>/instructor/courses.php" class="link-inline">View all</a>
                </div>

                <?php if ($courses && $courses->num_rows > 0): ?>
                    <div class="instructor-course-list">
                        <?php while ($c = $courses->fetch_assoc()): ?>
                            <div class="instructor-course-row">
                                <div>
                                    <div class="row-title"><?php echo htmlspecialchars($c['title']); ?></div>
                                    <div class="row-meta">
                                        <span><?php echo ucfirst($c['level']); ?></span>
                                        <span>₹<?php echo number_format($c['price'], 2); ?></span>
                                    </div>
                                </div>
                                <div class="row-status">
                                    <span class="badge">
                                        <?php echo ucfirst($c['status']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p>You haven’t created any courses yet. <a href="<?php echo BASE_URL; ?>/instructor/course-create.php" class="link-inline">Create your first course</a></p>
                <?php endif; ?>
            </section>
        </section>
    </div>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>