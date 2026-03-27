<?php
require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

$userId = $_SESSION['user']['id'];

$sql = "SELECT e.id AS enrollment_id, e.status,
               c.id AS course_id, c.title, c.level, c.thumbnail, c.short_description,
               u.name AS instructor_name
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        JOIN users u ON c.instructor_id = u.id
        WHERE e.student_id = ?
        ORDER BY e.enrolled_at DESC";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('i', $userId);
$stmt->execute();
$enrollments = $stmt->get_result();
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
                <a href="<?php echo BASE_URL; ?>/student/dashboard.php">Dashboard</a>
                <a href="<?php echo BASE_URL; ?>/student/my-courses.php" class="active">My Courses</a>
                <a href="<?php echo BASE_URL; ?>/public/courses.php">Browse New Courses</a>
            </nav>
        </aside>

        <section class="dashboard-main">
            <header class="dashboard-header">
                <div>
                    <h1>My courses</h1>
                    <p>All courses you have enrolled in.</p>
                </div>
            </header>

            <div class="grid course-grid">
                <?php if ($enrollments && $enrollments->num_rows > 0): ?>
                    <?php while ($row = $enrollments->fetch_assoc()): ?>
                        <a href="<?php echo BASE_URL; ?>/student/lesson.php?course_id=<?php echo $row['course_id']; ?>" class="course-card">
                            <div class="course-thumb">
                                <?php if (!empty($row['thumbnail'])): ?>
                                    <img src="<?php echo BASE_URL; ?>/assets/images/<?php echo htmlspecialchars($row['thumbnail']); ?>" alt="">
                                <?php else: ?>
                                    <div class="course-thumb-placeholder">Course</div>
                                <?php endif; ?>
                            </div>
                            <div class="course-body">
                                <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                                <p><?php echo htmlspecialchars($row['short_description']); ?></p>
                                <div class="course-meta">
                                    <span class="badge"><?php echo ucfirst($row['level']); ?></span>
                                    <span class="instructor"><?php echo htmlspecialchars($row['instructor_name']); ?></span>
                                </div>
                                <div class="course-footer">
                                    <span class="price <?php echo $row['status'] === 'completed' ? 'free' : ''; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
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
    </div>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>