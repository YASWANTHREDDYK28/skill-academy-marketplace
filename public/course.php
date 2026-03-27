<?php
require_once __DIR__ . '/../config/db.php';

$courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($courseId <= 0) {
    header('Location: ' . BASE_URL . '/public/courses.php');
    exit;
}

// Load course (only published)
$sql = "SELECT c.*, 
               u.name AS instructor_name, 
               cat.name AS category_name
        FROM courses c
        JOIN users u ON c.instructor_id = u.id
        JOIN categories cat ON c.category_id = cat.id
        WHERE c.id = ? AND c.status = 'published'";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('i', $courseId);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$course) {
    header('Location: ' . BASE_URL . '/public/courses.php');
    exit;
}

// Check if current user (student) already enrolled
$isEnrolled = false;
if (!empty($_SESSION['user']) && $_SESSION['user']['role'] === 'student') {
    $studentId = $_SESSION['user']['id'];
    $stmt = $mysqli->prepare("SELECT id FROM enrollments WHERE course_id = ? AND student_id = ?");
    $stmt->bind_param('ii', $courseId, $studentId);
    $stmt->execute();
    $enrollment = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $isEnrolled = (bool)$enrollment;
}

include __DIR__ . '/../partials/header.php';
?>

<main class="section">
    <div class="container course-layout">
        <section class="course-main">
            <div class="course-hero-card">
                <span class="badge"><?php echo htmlspecialchars($course['category_name']); ?></span>
                <h1><?php echo htmlspecialchars($course['title']); ?></h1>
                <p class="course-subtitle">
                    <?php echo htmlspecialchars($course['short_description']); ?>
                </p>
                <div class="course-meta-line">
                    <span><?php echo ucfirst($course['level']); ?> • Skill course</span>
                    <span>By <?php echo htmlspecialchars($course['instructor_name']); ?></span>
                </div>
            </div>

            <div class="course-description">
                <h2>About this course</h2>
                <p>
                    <?php echo nl2br(htmlspecialchars($course['full_description'])); ?>
                </p>
            </div>
            <?php
// Load reviews for this course
$reviewsSql = "SELECT r.rating, r.comment, r.created_at, u.name 
               FROM reviews r
               JOIN users u ON r.student_id = u.id
               WHERE r.course_id = ?
               ORDER BY r.created_at DESC";
$stmt = $mysqli->prepare($reviewsSql);
$stmt->bind_param('i', $courseId);
$stmt->execute();
$reviewsRes = $stmt->get_result();
$stmt->close();

// Compute average
$avgSql = "SELECT AVG(rating) AS avg_rating, COUNT(*) AS total_reviews 
           FROM reviews WHERE course_id = ?";
$stmt = $mysqli->prepare($avgSql);
$stmt->bind_param('i', $courseId);
$stmt->execute();
$avgRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

$avgRating = $avgRow['avg_rating'] ? round($avgRow['avg_rating'], 1) : 0;
$totalReviews = (int)($avgRow['total_reviews'] ?? 0);
?>
<?php
$reviewErrors = [];
$reviewSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
        $reviewErrors[] = 'You must be logged in as a student to leave a review.';
    } else {
        // Check enrollment
        $studentId = $_SESSION['user']['id'];
        $stmt = $mysqli->prepare("SELECT id FROM enrollments WHERE course_id = ? AND student_id = ?");
        $stmt->bind_param('ii', $courseId, $studentId);
        $stmt->execute();
        $enr = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$enr) {
            $reviewErrors[] = 'You can only review courses you are enrolled in.';
        } else {
            $rating = (int)($_POST['rating'] ?? 0);
            $comment = trim($_POST['comment'] ?? '');

            if ($rating < 1 || $rating > 5) {
                $reviewErrors[] = 'Rating must be between 1 and 5.';
            }

            if (!$reviewErrors) {
                // Check if already reviewed
                $stmt = $mysqli->prepare("SELECT id FROM reviews WHERE course_id = ? AND student_id = ?");
                $stmt->bind_param('ii', $courseId, $studentId);
                $stmt->execute();
                $existing = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($existing) {
                    $stmt = $mysqli->prepare("UPDATE reviews SET rating = ?, comment = ?, created_at = NOW() WHERE id = ?");
                    $id = $existing['id'];
                    $stmt->bind_param('isi', $rating, $comment, $id);
                } else {
                    $stmt = $mysqli->prepare("INSERT INTO reviews (course_id, student_id, rating, comment, created_at)
                                              VALUES (?, ?, ?, ?, NOW())");
                    $stmt->bind_param('iiis', $courseId, $studentId, $rating, $comment);
                }

                if ($stmt->execute()) {
                    $reviewSuccess = 'Your review has been saved.';
                } else {
                    $reviewErrors[] = 'Failed to save your review.';
                }
                $stmt->close();
            }
        }
    }
}
?>
<div class="course-description">
    <h2>Write a review</h2>

    <?php if ($reviewErrors): ?>
        <div class="alert alert-error">
            <?php foreach ($reviewErrors as $e): ?>
                <div><?php echo htmlspecialchars($e); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($reviewSuccess): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($reviewSuccess); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['user']) && $_SESSION['user']['role'] === 'student'): ?>
        <form method="post" class="auth-form">
            <div class="form-group form-row" style="align-items:flex-end;">
                <div>
                    <label>Rating (1–5)</label>
                    <input type="number" name="rating" min="1" max="5" required>
                </div>
            </div>
            <div class="form-group">
                <label>Comment (optional)</label>
                <textarea name="comment" rows="3"></textarea>
            </div>
            <button type="submit" name="submit_review" class="btn btn-primary btn-full">Submit review</button>
        </form>
    <?php else: ?>
        <p>You must <a href="<?php echo BASE_URL; ?>/public/login.php" class="link-inline">log in as student</a> to leave a review.</p>
    <?php endif; ?>
</div>

<div class="course-description">
    <h2>Student reviews</h2>

    <?php if ($totalReviews > 0): ?>
        <div class="course-review-summary">
            <div class="course-review-score"><?php echo $avgRating; ?>/5</div>
            <div class="course-review-meta">
                <div><?php echo $totalReviews; ?> reviews</div>
                <div>Based on enrolled students</div>
            </div>
        </div>

        <div class="course-review-list">
            <?php while ($r = $reviewsRes->fetch_assoc()): ?>
                <div class="course-review-item">
                    <div class="course-review-header">
                        <span class="course-review-name"><?php echo htmlspecialchars($r['name']); ?></span>
                        <span class="course-review-rating"><?php echo (int)$r['rating']; ?>/5</span>
                    </div>
                    <?php if (!empty($r['comment'])): ?>
                        <p><?php echo nl2br(htmlspecialchars($r['comment'])); ?></p>
                    <?php endif; ?>
                    <div class="course-review-date">
                        <?php echo htmlspecialchars(substr($r['created_at'], 0, 10)); ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p>No reviews yet. Be the first to review this course.</p>
    <?php endif; ?>
</div>
        </section>

        <aside class="course-sidebar">
            
            <div class="course-side-card">
                <div class="course-price-block">
                    <?php if ((float)$course['price'] == 0): ?>
                        <div class="course-price-main">Free</div>
                    <?php else: ?>
                        <div class="course-price-main">₹<?php echo number_format($course['price'], 2); ?></div>
                    <?php endif; ?>
                    <div class="course-price-sub">One-time access to all lessons</div>
                </div>

                <?php if (!empty($_SESSION['user']) && $_SESSION['user']['role'] === 'student'): ?>
                    <?php if ($isEnrolled): ?>
                        <a href="<?php echo BASE_URL; ?>/student/lesson.php?course_id=<?php echo $courseId; ?>"
                           class="btn btn-primary btn-full">Go to course</a>
                    <?php else: ?>
                        <form method="post" action="<?php echo BASE_URL; ?>/student/enroll.php">
                            <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">
                            <button type="submit" class="btn btn-primary btn-full">Enroll now</button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>/public/login.php" class="btn btn-primary btn-full">
                        Login to enroll
                    </a>
                <?php endif; ?>

                <ul class="course-side-list">
                    <li>Secure enrollment and progress tracking</li>
                    <li>Role-based access for your account</li>
                    <li>Access from your student dashboard</li>
                </ul>
            </div>
        </aside>
    </div>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>