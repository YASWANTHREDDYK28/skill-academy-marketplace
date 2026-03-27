<?php
require_once __DIR__ . '/../config/db.php';
include __DIR__ . '/../partials/header.php';

// Fetch some published courses and categories
$coursesSql = "SELECT c.id, c.title, c.short_description, c.price, c.level, c.thumbnail,
                      u.name AS instructor_name
               FROM courses c
               JOIN users u ON c.instructor_id = u.id
               WHERE c.status = 'published'
               ORDER BY c.created_at DESC
               LIMIT 6";
$coursesResult = $mysqli->query($coursesSql);

$categoriesSql = "SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name LIMIT 8";
$categoriesResult = $mysqli->query($categoriesSql);
?>

<main>
    <!-- Hero -->
    <section class="hero">
        <div class="container hero-grid">
            <div class="hero-text">
                <h1>Level up your skills with a smooth learning experience.</h1>
                <p>Discover curated courses from expert instructors, track your progress, and learn at your own pace with a modern full‑stack academy platform.</p>
                <div class="hero-actions">
                    <a href="<?php echo BASE_URL; ?>/public/courses.php" class="btn btn-primary">Browse Courses</a>
                    <a href="<?php echo BASE_URL; ?>/public/register.php" class="btn btn-ghost">Become an Instructor</a>
                </div>
                <div class="hero-meta">
                    <span>Secure authentication</span>
                    <span>Role-based dashboards</span>
                    <span>Smooth UI</span>
                </div>
            </div>
            <div class="hero-card">
                <div class="hero-stat">
                    <span class="hero-stat-number">50+</span>
                    <span class="hero-stat-label">Skill categories</span>
                </div>
                <div class="hero-stat">
                    <span class="hero-stat-number">1000+</span>
                    <span class="hero-stat-label">Enrollments</span>
                </div>
                <div class="hero-badge">
                    Real‑time progress tracking &amp; reviews
                </div>
            </div>
        </div>
    </section>

    <!-- Categories -->
    <section class="section" id="categories">
        <div class="container">
            <div class="section-head">
                <h2>Trending skill categories</h2>
                <a href="<?php echo BASE_URL; ?>/public/courses.php" class="link-inline">View all</a>
            </div>
            <div class="grid categories-grid">
                <?php if ($categoriesResult && $categoriesResult->num_rows > 0): ?>
                    <?php while ($cat = $categoriesResult->fetch_assoc()): ?>
                        <div class="category-pill">
                            <span><?php echo htmlspecialchars($cat['name']); ?></span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No categories yet. Admin can add some from dashboard.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Featured courses -->
    <section class="section">
        <div class="container">
            <div class="section-head">
                <h2>Featured courses</h2>
                <a href="<?php echo BASE_URL; ?>/public/courses.php" class="link-inline">Browse all</a>
            </div>
            <div class="grid course-grid">
                <?php if ($coursesResult && $coursesResult->num_rows > 0): ?>
                    <?php while ($course = $coursesResult->fetch_assoc()): ?>
                        <a href="<?php echo BASE_URL; ?>/public/course.php?id=<?php echo $course['id']; ?>" class="course-card">
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
                                <div class="course-footer">
                                    <?php if ((float)$course['price'] == 0): ?>
                                        <span class="price free">Free</span>
                                    <?php else: ?>
                                        <span class="price">₹<?php echo number_format($course['price'], 2); ?></span>
                                    <?php endif; ?>
                                    <span class="link-inline">View details</span>
                                </div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No published courses yet. Instructors can create courses from their dashboard.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Why section -->
    <section class="section" id="why">
        <div class="container why-grid">
            <div>
                <h2>Built for a secure and smooth learning experience</h2>
                <p>From secure authentication to role-based dashboards, this platform is designed as a full-stack project that feels like a real product.</p>
            </div>
            <ul class="why-list">
                <li>Secure login with hashed passwords and protected sessions.</li>
                <li>Separate panels for students, instructors, and admins.</li>
                <li>Progress tracking and structured course content.</li>
            </ul>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>