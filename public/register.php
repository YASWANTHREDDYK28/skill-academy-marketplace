<?php
require_once __DIR__ . '/../config/db.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'student';

    if ($name === '' || $email === '' || $password === '' || $confirm === '') {
        $errors[] = 'All fields are required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    if (!in_array($role, ['student','instructor'], true)) {
        $role = 'student';
    }

    if (!$errors) {
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = 'Email already registered.';
        }
        $stmt->close();
    }

    if (!$errors) {
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $mysqli->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $name, $email, $passwordHash, $role);
        if ($stmt->execute()) {
            $success = 'Account created successfully. You can login now.';
        } else {
            $errors[] = 'Something went wrong, please try again.';
        }
        $stmt->close();
    }
}

include __DIR__ . '/../partials/header.php';
?>

<main class="auth-page">
    <div class="container auth-container">
        <div class="auth-card">
            <h2>Create your account</h2>
            <p class="auth-subtitle">Join as a student or instructor and start learning or teaching skills.</p>

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
                    <label>Full name</label>
                    <input type="text" name="name" required value="<?php echo htmlspecialchars($name ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Email address</label>
                    <input type="email" name="email" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
                </div>
                <div class="form-group form-row">
                    <div>
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <div>
                        <label>Confirm password</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Register as</label>
                    <select name="role">
                        <option value="student" <?php echo (isset($role) && $role === 'student') ? 'selected' : ''; ?>>Student</option>
                        <option value="instructor" <?php echo (isset($role) && $role === 'instructor') ? 'selected' : ''; ?>>Instructor</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-full">Create account</button>
                <p class="auth-meta">
                    Already have an account?
                    <a href="<?php echo BASE_URL; ?>/public/login.php" class="link-inline">Log in</a>
                </p>
            </form>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>