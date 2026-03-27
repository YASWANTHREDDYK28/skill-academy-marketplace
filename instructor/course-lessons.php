<?php
require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'instructor') {
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

$instructorId = $_SESSION['user']['id'];
$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
if ($courseId <= 0) {
    header('Location: ' . BASE_URL . '/instructor/courses.php');
    exit;
}

// Ensure course belongs to this instructor
$stmt = $mysqli->prepare("SELECT id, title FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->bind_param('ii', $courseId, $instructorId);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$course) {
    header('Location: ' . BASE_URL . '/instructor/courses.php');
    exit;
}

// Fetch lessons
$stmt = $mysqli->prepare("SELECT id, title, content_type, sort_order, is_preview 
                          FROM course_lessons 
                          WHERE course_id = ?
                          ORDER BY sort_order ASC, id ASC");
$stmt->bind_param('i', $courseId);
$stmt->execute();
$lessons = $stmt->get_result();
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
                <a href="<?php echo BASE_URL; ?>/instructor/dashboard.php">Dashboard</a>
                <a href="<?php echo BASE_URL; ?>/instructor/courses.php">My Courses</a>
                <a href="<?php echo BASE_URL; ?>/instructor/course-create.php">Create Course</a>
            </nav>
        </aside>

        <section class="dashboard-main">
            <header class="dashboard-header">
                <div>
                    <h1>Lessons - <?php echo htmlspecialchars($course['title']); ?></h1>
                    <p>Manage lessons inside this course.</p>
                </div>
                <a href="<?php echo BASE_URL; ?>/instructor/lesson-create.php?course_id=<?php echo $courseId; ?>" class="btn btn-primary">
                    Add lesson
                </a>
            </header>

            <?php if ($lessons && $lessons->num_rows > 0): ?>
                <div class="instructor-course-list">
                    <?php while ($l = $lessons->fetch_assoc()): ?>
                        <div class="instructor-course-row">
                            <div>
                                <div class="row-title">
                                    <?php echo (int)$l['sort_order']; ?>. <?php echo htmlspecialchars($l['title']); ?>
                                </div>
                                <div class="row-meta">
                                    <span><?php echo ucfirst($l['content_type']); ?></span>
                                    <?php if ($l['is_preview']): ?>
                                        <span>Preview</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="row-status">
                                <!-- Later: add edit/delete -->
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p>No lessons yet. <a href="<?php echo BASE_URL; ?>/instructor/lesson-create.php?course_id=<?php echo $courseId; ?>" class="link-inline">Add first lesson</a></p>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>