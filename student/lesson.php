<?php
require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

$studentId = $_SESSION['user']['id'];
$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$lessonId = isset($_GET['lesson_id']) ? (int)$_GET['lesson_id'] : 0;

if ($courseId <= 0) {
    header('Location: ' . BASE_URL . '/student/my-courses.php');
    exit;
}

// Check enrollment
$stmt = $mysqli->prepare("SELECT id FROM enrollments WHERE course_id = ? AND student_id = ? AND status IN ('active','completed')");
$stmt->bind_param('ii', $courseId, $studentId);
$stmt->execute();
$enrollment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$enrollment) {
    header('Location: ' . BASE_URL . '/public/course.php?id=' . $courseId);
    exit;
}
$enrollmentId = $enrollment['id'];

// Load course
$stmt = $mysqli->prepare("SELECT title FROM courses WHERE id = ?");
$stmt->bind_param('i', $courseId);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Load all lessons for sidebar
$stmt = $mysqli->prepare("SELECT id, title, content_type, sort_order 
                          FROM course_lessons
                          WHERE course_id = ?
                          ORDER BY sort_order ASC, id ASC");
$stmt->bind_param('i', $courseId);
$stmt->execute();
$lessonsRes = $stmt->get_result();

// If no lesson_id given, pick first lesson
if ($lessonId <= 0 && $lessonsRes->num_rows > 0) {
    $first = $lessonsRes->fetch_assoc();
    $lessonId = $first['id'];
    // reset pointer to reuse result
    $lessonsRes->data_seek(0);
}

// Load current lesson
$currentLesson = null;
if ($lessonId > 0) {
    $stmt = $mysqli->prepare("SELECT * FROM course_lessons WHERE id = ? AND course_id = ?");
    $stmt->bind_param('ii', $lessonId, $courseId);
    $stmt->execute();
    $currentLesson = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Mark progress when a lesson is opened
if ($currentLesson) {
    $stmt = $mysqli->prepare("SELECT id FROM lesson_progress WHERE enrollment_id = ? AND lesson_id = ?");
    $stmt->bind_param('ii', $enrollmentId, $lessonId);
    $stmt->execute();
    $already = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$already) {
        $stmt = $mysqli->prepare("INSERT INTO lesson_progress (enrollment_id, lesson_id) VALUES (?, ?)");
        $stmt->bind_param('ii', $enrollmentId, $lessonId);
        $stmt->execute();
        $stmt->close();
    }
}

include __DIR__ . '/../partials/header.php';
?>

<main class="dashboard-page">
    <div class="container course-lesson-layout">
        <aside class="lesson-sidebar">
            <div class="lesson-course-title">
                <?php echo htmlspecialchars($course['title']); ?>
            </div>
            <div class="lesson-list">
                <?php if ($lessonsRes && $lessonsRes->num_rows > 0): ?>
                    <?php while ($l = $lessonsRes->fetch_assoc()): ?>
                        <a href="<?php echo BASE_URL; ?>/student/lesson.php?course_id=<?php echo $courseId; ?>&lesson_id=<?php echo $l['id']; ?>"
                           class="lesson-item <?php echo ($l['id'] == $lessonId) ? 'active' : ''; ?>">
                            <span class="lesson-index"><?php echo (int)$l['sort_order']; ?>.</span>
                            <span class="lesson-title"><?php echo htmlspecialchars($l['title']); ?></span>
                            <span class="lesson-type"><?php echo ucfirst($l['content_type']); ?></span>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No lessons available for this course yet.</p>
                <?php endif; ?>
            </div>
        </aside>

        <section class="lesson-main">
            <?php if ($currentLesson): ?>
                <h1 class="lesson-main-title"><?php echo htmlspecialchars($currentLesson['title']); ?></h1>
                <div class="lesson-type-label"><?php echo ucfirst($currentLesson['content_type']); ?> lesson</div>

                <div class="lesson-content-card">
                    <?php if ($currentLesson['content_type'] === 'video'): ?>
                        <p>Video URL:</p>
                        <a href="<?php echo htmlspecialchars($currentLesson['content_url']); ?>" target="_blank" class="link-inline">
                            Open video
                        </a>
                    <?php elseif ($currentLesson['content_type'] === 'pdf'): ?>
                        <p>PDF file:</p>
                        <a href="<?php echo htmlspecialchars($currentLesson['content_url']); ?>" target="_blank" class="link-inline">
                            Open PDF
                        </a>
                    <?php else: ?>
                        <p><?php echo nl2br(htmlspecialchars($currentLesson['content_text'])); ?></p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p>No lesson selected.</p>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>