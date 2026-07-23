<?php
require_once __DIR__ . '/../config/db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php';

$email = $_GET['email'] ?? '';
if ($email) {
    $otp = rand(100000, 999999);

    $stmt = $mysqli->prepare("UPDATE users SET otp = ?, is_verified = 0 WHERE email = ?");
    $stmt->bind_param('ss', $otp, $email);
    $stmt->execute();
    $stmt->close();

    $mail = new PHPMailer(true);
    try {
        $mail->setFrom('yourapp@example.com', 'Smart Campus');
        $mail->addAddress($email);
        $mail->Subject = 'Your OTP Code';
        $mail->Body    = "Your OTP is: $otp";
        $mail->send();
        echo "OTP sent to your email. <a href='verify.php?email=$email'>Click here to verify</a>";
    } catch (Exception $e) {
        echo "Could not send OTP email.";
    }
} else {
    echo "No email provided.";
}
?>
