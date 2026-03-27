<?php
require_once __DIR__ . '/../config/db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Email and password are required.';
    } else {
        $stmt = $mysqli->prepare("SELECT id, name, email, password_hash, role, status FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Invalid email or password.';
        } elseif ($user['status'] !== 'active') {
            $errors[] = 'Account is blocked. Contact admin.';
        } else {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
            ];

            if ($user['role'] === 'student') {
                header('Location: ' . BASE_URL . '/student/dashboard.php');
            } elseif ($user['role'] === 'instructor') {
                header('Location: ' . BASE_URL . '/instructor/dashboard.php');
            } else {
                header('Location: ' . BASE_URL . '/admin/dashboard.php');
            }
            exit;
        }
    }
}

include __DIR__ . '/../partials/header.php';
?>

<main class="auth-page">
    <div class="container auth-container">
        <div class="auth-card">
            <h2>Welcome back</h2>
            <p class="auth-subtitle">Log in securely to access your dashboard and courses.</p>

            <?php if ($errors): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $e): ?>
                        <div><?php echo htmlspecialchars($e); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" class="auth-form">
                <div class="form-group">
                    <label>Email address</label>
                    <input type="email" name="email" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-full">Log in</button>
                <p class="auth-meta">
                    New here?
                    <a href="<?php echo BASE_URL; ?>/public/register.php" class="link-inline">Create an account</a>
                </p>
            </form>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>