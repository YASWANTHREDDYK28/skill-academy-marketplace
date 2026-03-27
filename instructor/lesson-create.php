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

// Ensure course belongs to instructor
$stmt = $mysqli->prepare("SELECT id, title FROM courses WHERE id = ? AND instructor_id = ?");
stmt:
$stmt->bind_param('ii', $courseId, $instructorId);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$course) {
    header('Location: ' . BASE_URL . '/instructor/courses.php');
    exit;
}

$title = '';
$content_type = 'video';
$content_url = '';
$content_text = '';
$sort_order = 1;
$is_preview = 0;
$errors = [];
$success = '';

// Get suggested next sort_order
$res = $mysqli->prepare("SELECT MAX(sort_order) AS max_order FROM course_lessons WHERE course_id = ?");
$res->bind_param('i', $courseId);
$res->execute();
$maxRow = $res->get_result()->fetch_assoc();
$res->close();
$sort_order = (int)($maxRow['max_order'] ?? 0) + 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content_type = $_POST['content_type'] ?? 'video';
    $content_url = trim($_POST['content_url'] ?? '');
    $content_text = trim($_POST['content_text'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? $sort_order);
    $is_preview = isset($_POST['is_preview']) ? 1 : 0;

    if ($title === '') {
        $errors[] = 'Lesson title is required.';
    }
    if (!in_array($content_type, ['video','pdf','text'], true)) {
        $content_type = 'video';
    }

    if ($content_type === 'text') {
        if ($content_text === '') {
            $errors[] = 'Text content is required for text lessons.';
        }
        $content_url = '';
    } else {
        if ($content_url === '') {
            $errors[] = 'URL or file path is required for video/pdf lessons.';
        }
    }

    if (!$errors) {
        $stmt = $mysqli->prepare("INSERT INTO course_lessons 
            (course_id, title, content_type, content_url, content_text, sort_order, is_preview)
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            'issssii',
            $courseId,
            $title,
            $content_type,
            $content_url,
            $content_text,
            $sort_order,
            $is_preview
        );
        if ($stmt->execute()) {
            $success = 'Lesson added successfully.';
            $title = $content_url = $content_text = '';
            $is_preview = 0;
            $sort_order++;
        } else {
            $errors[] = 'Failed to save lesson.';
        }
        $stmt->close();
    }
}

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
                    <h1>Add lesson</h1>
                    <p>Course: <?php echo htmlspecialchars($course['title']); ?></p>
                </div>
            </header>

            <?php if ($errors): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $e): ?>
                        <div><?php echo htmlspecialchars($e); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="post" class="auth-form">
                <div class="form-group">
                    <label>Lesson title</label>
                    <input type="text" name="title" required
                           value="<?php echo htmlspecialchars($title); ?>">
                </div>

                <div class="form-group form-row">
                    <div>
                        <label>Content type</label>
                        <select name="content_type">
                            <option value="video" <?php echo $content_type === 'video' ? 'selected' : ''; ?>>Video (URL)</option>
                            <option value="pdf" <?php echo $content_type === 'pdf' ? 'selected' : ''; ?>>PDF (file path / URL)</option>
                            <option value="text" <?php echo $content_type === 'text' ? 'selected' : ''; ?>>Text content</option>
                        </select>
                    </div>
                    <div>
                        <label>Sort order</label>
                        <input type="number" name="sort_order" min="1"
                               value="<?php echo (int)$sort_order; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Video/PDF URL or path (for video/pdf)</label>
                    <input type="text" name="content_url"
                           value="<?php echo htmlspecialchars($content_url); ?>">
                </div>

                <div class="form-group">
                    <label>Text content (for text lessons)</label>
                    <textarea name="content_text" rows="4"><?php echo htmlspecialchars($content_text); ?></textarea>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_preview" value="1" <?php echo $is_preview ? 'checked' : ''; ?>>
                        Mark as free preview
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-full">Save lesson</button>
            </form>
        </section>
    </div>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>