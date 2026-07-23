<?php
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $otp   = trim($_POST['otp']);

    $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ? AND otp = ?");
    $stmt->bind_param('ss', $email, $otp);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        $stmt = $mysqli->prepare("UPDATE users SET is_verified = 1, otp = NULL WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        echo "Account verified successfully. You can now log in.";
    } else {
        echo "Invalid OTP. Please try again.";
    }
}
?>

<form method="post">
    <input type="hidden" name="email" value="<?php echo htmlspecialchars($_GET['email']); ?>">
    <label>Enter OTP:</label>
    <input type="text" name="otp" required>
    <button type="submit">Verify</button>
</form>
