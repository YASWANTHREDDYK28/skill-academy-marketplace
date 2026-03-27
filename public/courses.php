<?php
require_once __DIR__ . '/../config/db.php';

// Filters
$search   = trim($_GET['q'] ?? '');
$level    = $_GET['level'] ?? '';
$price    = $_GET['price'] ?? '';
$category = (int)($_GET['category'] ?? 0);

$where = "WHERE c.status = 'published'";
$params = [];
$types  = '';

if ($search !== '') {
    $where .= " AND (c.title LIKE ? OR c.short_description LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ss';
}

if (in_array($level, ['beginner','intermediate','advanced'], true)) {
    $where .= " AND c.level = ?";
    $params[] = $level;
    $types   .= 's';
}

if ($price === 'free') {
    $where .= " AND c.price = 0";
} elseif ($price === 'paid') {
    $where .= " AND c.price > 0";
}

if ($category > 0) {
    $where .= " AND c.category_id = ?";
    $params[] = $category;
    $types   .= 'i';
}

// Load categories for filter
$categoriesResult = $mysqli->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");

// Build main query
$sql = "SELECT c.id, c.title, c.short_description, c.price, c.level, c.thumbnail,
               u.name AS instructor_name, cat.name AS category_name
        FROM courses c
        JOIN users u ON c.instructor_id = u.id
        JOIN categories cat ON c.category_id = cat.id
        $where
        ORDER BY c.created_at DESC";
$stmt = $mysqli->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$courses = $stmt->get_result();
$stmt->close();

include __DIR__ . '/../partials/header.php';
?>

<main class="section">
    <div class="container">
        <header class="courses-header">
            <div>
                <h1>All courses</h1>
                <p>Discover skill-based courses and enroll to start learning.</p>
            </div>
        </header>

        <!-- Filters -->
        <form method="get" class="courses-filters">
            <input type="text" name="q" placeholder="Search by course or skill..."
                   value="<?php echo htmlspecialchars($search); ?>">

            <select name="category">
                <option value="0">All categories</option>
                <?php if ($categoriesResult && $categoriesResult->num_rows > 0): ?>
                    <?php while ($cat = $categoriesResult->fetch_assoc()): ?>
                        <option value="<?php echo $cat['id']; ?>"
                            <?php echo ($category === (int)$cat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>

            <select name="level">
                <option value="">All levels</option>
                <option value="beginner" <?php echo $level === 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                <option value="intermediate" <?php echo $level === 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                <option value="advanced" <?php echo $level === 'advanced' ? 'selected' : ''; ?>>Advanced</option>
            </select>

            <select name="price">
                <option value="">All prices</option>
                <option value="free" <?php echo $price === 'free' ? 'selected' : ''; ?>>Free</option>
                <option value="paid" <?php echo $price === 'paid' ? 'selected' : ''; ?>>Paid</option>
            </select>

            <button type="submit" class="btn btn-outline">Apply</button>
        </form>

        <!-- Courses grid -->
        <div class="grid course-grid">
            <?php if ($courses && $courses->num_rows > 0): ?>
                <?php while ($course = $courses->fetch_assoc()): ?>
                    <div class="course-card">
                        <div class="course-thumb">
                            <?php if (!empty($course['thumbnail'])): ?>
                                <img src="<?php echo BASE_URL; ?>/assets/images/<?php echo htmlspecialchars($course['thumbnail']); ?>" alt="">
                            <?php else: ?>
                                <div class="course-thumb-placeholder">Skill</div>
                            <?php endif; ?>
                        </div>
                        <div class="course-body">
                            <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                            <p><?php echo htmlspecialchars($course['short_description']); ?></p>
                            <div class="course-meta">
                                <span class="badge"><?php echo ucfirst($course['level']); ?></span>
                                <span class="instructor"><?php echo htmlspecialchars($course['instructor_name']); ?></span>
                            </div>
                            <div class="course-meta" style="margin-top:0.25rem;">
                                <span class="instructor"><?php echo htmlspecialchars($course['category_name']); ?></span>
                            </div>
                            <div class="course-footer">
                                <?php if ((float)$course['price'] == 0): ?>
                                    <span class="price free">Free</span>
                                <?php else: ?>
                                    <span class="price">₹<?php echo number_format($course['price'], 2); ?></span>
                                <?php endif; ?>

                                <div class="course-actions">
                                    <a href="<?php echo BASE_URL; ?>/public/course.php?id=<?php echo $course['id']; ?>" class="link-inline">View</a>

                                    <?php if (!empty($_SESSION['user']) && $_SESSION['user']['role'] === 'student'): ?>
                                        <form method="post" action="<?php echo BASE_URL; ?>/student/enroll.php" class="inline-form">
                                            <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                            <button type="submit" class="btn btn-primary btn-enroll">Enroll</button>
                                        </form>
                                    <?php else: ?>
                                        <a href="<?php echo BASE_URL; ?>/public/login.php" class="btn btn-primary btn-enroll">Enroll</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No courses found for this filter.</p>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>