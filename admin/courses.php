<?php
require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

// Handle approve / reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseId = (int)($_POST['course_id'] ?? 0);
    $action   = $_POST['action'] ?? '';

    if ($courseId > 0 && in_array($action, ['approve','reject'], true)) {
        $newStatus = $action === 'approve' ? 'published' : 'rejected';

        $stmt = $mysqli->prepare("UPDATE courses SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $newStatus, $courseId);
        $stmt->execute();
        $stmt->close();
    }

    header('Location: ' . BASE_URL . '/admin/courses.php');
    exit;
}

// Load all courses (pending first)
$sql = "SELECT c.id, c.title, c.status, c.level, c.price, c.created_at,
               u.name AS instructor_name,
               cat.name AS category_name
        FROM courses c
        JOIN users u ON c.instructor_id = u.id
        JOIN categories cat ON c.category_id = cat.id
        ORDER BY FIELD(c.status, 'pending','published','draft','rejected'), c.created_at DESC";
$result = $mysqli->query($sql);

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
                <a href="<?php echo BASE_URL; ?>/admin/dashboard.php">Dashboard</a>
                <a href="<?php echo BASE_URL; ?>/admin/courses.php" class="active">Courses</a>
                <a href="<?php echo BASE_URL; ?>/admin/users.php">Users</a>
            </nav>
        </aside>

        <section class="dashboard-main">
            <header class="dashboard-header">
                <div>
                    <h1>All courses</h1>
                    <p>Approve or reject instructor courses.</p>
                </div>
            </header>

            <?php if ($result && $result->num_rows > 0): ?>
                <div class="instructor-course-list">
                    <?php while ($c = $result->fetch_assoc()): ?>
                        <div class="instructor-course-row">
                            <div>
                                <div class="row-title">
                                    <?php echo htmlspecialchars($c['title']); ?>
                                    <span style="font-size:0.78rem; color:var(--text-muted);">
                                        • <?php echo htmlspecialchars($c['instructor_name']); ?>
                                    </span>
                                </div>
                                <div class="row-meta">
                                    <span><?php echo htmlspecialchars($c['category_name']); ?></span>
                                    <span><?php echo ucfirst($c['level']); ?></span>
                                    <span>₹<?php echo number_format($c['price'], 2); ?></span>
                                </div>
                            </div>
                            <div class="row-status">
                                <span class="badge"><?php echo ucfirst($c['status']); ?></span>

                                <?php if ($c['status'] === 'pending'): ?>
                                    <form method="post" class="inline-form" style="margin-left:0.4rem;">
                                        <input type="hidden" name="course_id" value="<?php echo $c['id']; ?>">
                                        <button type="submit" name="action" value="approve"
                                                class="btn btn-primary btn-enroll">Approve</button>
                                    </form>
                                    <form method="post" class="inline-form">
                                        <input type="hidden" name="course_id" value="<?php echo $c['id']; ?>">
                                        <button type="submit" name="action" value="reject"
                                                class="btn btn-outline btn-enroll">Reject</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p>No courses found.</p>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>