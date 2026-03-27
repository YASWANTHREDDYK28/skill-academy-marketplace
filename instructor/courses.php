<?php
require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'instructor') {
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

$instructorId = $_SESSION['user']['id'];

$sql = "SELECT c.id, c.title, c.level, c.price, c.status, c.created_at,
               cat.name AS category_name
        FROM courses c
        JOIN categories cat ON c.category_id = cat.id
        WHERE c.instructor_id = ?
        ORDER BY c.created_at DESC";
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
                <a href="<?php echo BASE_URL; ?>/instructor/dashboard.php">Dashboard</a>
                <a href="<?php echo BASE_URL; ?>/instructor/courses.php" class="active">My Courses</a>
                <a href="<?php echo BASE_URL; ?>/instructor/course-create.php">Create Course</a>
            </nav>
        </aside>

        <section class="dashboard-main">
            <header class="dashboard-header">
                <div>
                    <h1>My courses</h1>
                    <p>Manage all the courses you have created.</p>
                </div>
                <a href="<?php echo BASE_URL; ?>/instructor/course-create.php" class="btn btn-primary">New course</a>
            </header>

            <?php if ($courses && $courses->num_rows > 0): ?>
                <div class="instructor-course-list">
                    <?php while ($c = $courses->fetch_assoc()): ?>
                        <div class="instructor-course-row">
                            <div>
                                <div class="row-title"><?php echo htmlspecialchars($c['title']); ?></div>
                                <div class="row-meta">
                                    <span><?php echo htmlspecialchars($c['category_name']); ?></span>
                                    <span><?php echo ucfirst($c['level']); ?></span>
                                    <span>₹<?php echo number_format($c['price'], 2); ?></span>
                                </div>
                            </div>
                            <div class="row-status">
    <span class="badge"><?php echo ucfirst($c['status']); ?></span>
    <a href="<?php echo BASE_URL; ?>/instructor/course-lessons.php?course_id=<?php echo $c['id']; ?>"
       class="btn btn-outline btn-enroll" style="margin-left:0.4rem;">
        Manage lessons
    </a>
</div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p>No courses yet. <a href="<?php echo BASE_URL; ?>/instructor/course-create.php" class="link-inline">Create your first course</a></p>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>