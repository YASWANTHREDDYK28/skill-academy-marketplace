<?php
require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'instructor') {
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

$instructorId = $_SESSION['user']['id'];

$title = $short = $full = '';
$level = 'beginner';
$price = '0';
$category_id = 0;
$errors = [];
$success = '';

// Categories
$categoriesResult = $mysqli->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = trim($_POST['title'] ?? '');
    $short   = trim($_POST['short_description'] ?? '');
    $full    = trim($_POST['full_description'] ?? '');
    $level   = $_POST['level'] ?? 'beginner';
    $price   = $_POST['price'] ?? '0';
    $category_id = (int)($_POST['category_id'] ?? 0);

    if ($title === '' || $short === '' || $full === '') {
        $errors[] = 'Title and descriptions are required.';
    }
    if (!in_array($level, ['beginner','intermediate','advanced'], true)) {
        $level = 'beginner';
    }
    if (!is_numeric($price) || (float)$price < 0) {
        $errors[] = 'Price must be zero or a positive number.';
    }
    if ($category_id <= 0) {
        $errors[] = 'Please select a category.';
    }

    if (!$errors) {
        $slugBase = preg_replace('/[^a-z0-9]+/i', '-', strtolower($title));
        $slugBase = trim($slugBase, '-');
        $slug = $slugBase;
        $i = 1;

        $stmt = $mysqli->prepare("SELECT id FROM courses WHERE slug = ?");
        while (true) {
            $stmt->bind_param('s', $slug);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows === 0) {
                break;
            }
            $slug = $slugBase . '-' . $i;
            $i++;
        }
        $stmt->close();

        $status = 'pending'; // wait for admin approval
        $priceVal = (float)$price;

        $stmt = $mysqli->prepare("INSERT INTO courses 
            (instructor_id, category_id, title, slug, short_description, full_description, level, price, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            'iisssssds',
            $instructorId,
            $category_id,
            $title,
            $slug,
            $short,
            $full,
            $level,
            $priceVal,
            $status
        );

        if ($stmt->execute()) {
            $success = 'Course created and sent for admin approval.';
            $title = $short = $full = '';
            $level = 'beginner';
            $price = '0';
            $category_id = 0;
        } else {
            $errors[] = 'Failed to save course. Please try again.';
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
                <a href="<?php echo BASE_URL; ?>/instructor/course-create.php" class="active">Create Course</a>
            </nav>
        </aside>

        <section class="dashboard-main">
            <header class="dashboard-header">
                <div>
                    <h1>Create new course</h1>
                    <p>Define your skill course details. It will be sent to admin for approval.</p>
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
                    <label>Course title</label>
                    <input type="text" name="title" required
                           value="<?php echo htmlspecialchars($title); ?>">
                </div>

                <div class="form-group">
                    <label>Short description</label>
                    <input type="text" name="short_description" maxlength="255" required
                           value="<?php echo htmlspecialchars($short); ?>">
                </div>

                <div class="form-group">
                    <label>Full description</label>
                    <textarea name="full_description" rows="5" required><?php echo htmlspecialchars($full); ?></textarea>
                </div>

                <div class="form-group form-row">
                    <div>
                        <label>Category</label>
                        <select name="category_id" required>
                            <option value="0">Select category</option>
                            <?php if ($categoriesResult && $categoriesResult->num_rows > 0): ?>
                                <?php while ($cat = $categoriesResult->fetch_assoc()): ?>
                                    <option value="<?php echo $cat['id']; ?>"
                                        <?php echo ($category_id === (int)$cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div>
                        <label>Level</label>
                        <select name="level">
                            <option value="beginner" <?php echo $level === 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                            <option value="intermediate" <?php echo $level === 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                            <option value="advanced" <?php echo $level === 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Price (₹)</label>
                    <input type="number" step="0.01" min="0" name="price" required
                           value="<?php echo htmlspecialchars($price); ?>">
                </div>

                <button type="submit" class="btn btn-primary btn-full">Save course for review</button>
            </form>
        </section>
    </div>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>