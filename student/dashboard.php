<?php
require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

$userId = $_SESSION['user']['id'];

// Total enrolled courses
$enrolledCount = 0;
$sqlEnrollCount = "SELECT COUNT(*) AS total FROM enrollments WHERE student_id = ?";
$stmt = $mysqli->prepare($sqlEnrollCount);
$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$enrolledCount = (int)$res['total'];
$stmt->close();

// Completed courses
$completedCount = 0;
$sqlCompleted = "SELECT COUNT(*) AS total FROM enrollments WHERE student_id = ? AND status = 'completed'";
$stmt = $mysqli->prepare($sqlCompleted);
$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$completedCount = (int)$res['total'];
$stmt->close();

// Recent enrolled courses (for now, will be empty until enroll)
$sqlRecent = "SELECT e.id AS enrollment_id, c.title, c.level, c.thumbnail, c.id AS course_id
              FROM enrollments e
              JOIN courses c ON e.course_id = c.id
              WHERE e.student_id = ?
              ORDER BY e.enrolled_at DESC
              LIMIT 4";
$stmt = $mysqli->prepare($sqlRecent);
$stmt->bind_param('i', $userId);
$stmt->execute();
$recentEnrollments = $stmt->get_result();
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
                    <div class="dash-hello">Hi,</div>
                    <div class="dash-name"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></div>
                    <div class="dash-role">Student</div>
                </div>
            </div>
            <nav class="dash-nav">
                <a href="<?php echo BASE_URL; ?>/student/dashboard.php" class="active">Dashboard</a>
                <a href="<?php echo BASE_URL; ?>/student/my-courses.php">My Courses</a>
                <a href="<?php echo BASE_URL; ?>/public/courses.php">Browse New Courses</a>
            </nav>
        </aside>

        <section class="dashboard-main">
            <header class="dashboard-header">
                <div>
                    <h1>Student dashboard</h1>
                    <p>Track your enrolled courses and continue learning smoothly.</p>
                </div>
            </header>

            <div class="dashboard-stats">
                <div class="stat-card stat-1">
                    <div class="stat-label">Enrolled courses</div>
                    <div class="stat-value"><?php echo $enrolledCount; ?></div>
                    <div class="stat-foot">Total courses you joined</div>
                </div>
                <div class="stat-card stat-2">
                    <div class="stat-label">Completed</div>
                    <div class="stat-value"><?php echo $completedCount; ?></div>
                    <div class="stat-foot">Marked as completed</div>
                </div>
                <div class="stat-card stat-3">
                    <div class="stat-label">In progress</div>
                    <div class="stat-value"><?php echo max(0, $enrolledCount - $completedCount); ?></div>
                    <div class="stat-foot">Courses you are learning</div>
                </div>
            </div>

            <section class="dashboard-section">
                <div class="section-head">
                    <h2>Continue learning</h2>
                    <a href="<?php echo BASE_URL; ?>/student/my-courses.php" class="link-inline">View all</a>
                </div>

                <div class="grid course-grid">
                    <?php if ($recentEnrollments && $recentEnrollments->num_rows > 0): ?>
                        <?php while ($row = $recentEnrollments->fetch_assoc()): ?>
                            <a href="<?php echo BASE_URL; ?>/student/lesson.php?course_id=<?php echo $row['course_id']; ?>" class="course-card small">
                                <div class="course-thumb">
                                    <?php if (!empty($row['thumbnail'])): ?>
                                        <img src="<?php echo BASE_URL; ?>/assets/images/<?php echo htmlspecialchars($row['thumbnail']); ?>" alt="">
                                    <?php else: ?>
                                        <div class="course-thumb-placeholder">Course</div>
                                    <?php endif; ?>
                                </div>
                                <div class="course-body">
                                    <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                                    <div class="course-meta">
                                        <span class="badge"><?php echo ucfirst($row['level']); ?></span>
                                        <span class="instructor">Enrolled</span>
                                    </div>
                                    <div class="course-footer">
                                        <span class="link-inline">Go to course</span>
                                    </div>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>You haven't enrolled in any courses yet. <a href="<?php echo BASE_URL; ?>/public/courses.php" class="link-inline">Browse courses</a></p>
                    <?php endif; ?>
                </div>
            </section>
        </section>
    </div>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>