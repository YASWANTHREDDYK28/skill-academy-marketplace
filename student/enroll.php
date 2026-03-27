<?php
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/courses.php');
    exit;
}

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

$studentId = $_SESSION['user']['id'];
$courseId  = (int)($_POST['course_id'] ?? 0);

if ($courseId <= 0) {
    header('Location: ' . BASE_URL . '/public/courses.php');
    exit;
}

// Check course exists & published
$stmt = $mysqli->prepare("SELECT id FROM courses WHERE id = ? AND status = 'published'");
$stmt->bind_param('i', $courseId);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$course) {
    header('Location: ' . BASE_URL . '/public/courses.php');
    exit;
}

// Check already enrolled
$stmt = $mysqli->prepare("SELECT id FROM enrollments WHERE course_id = ? AND student_id = ?");
$stmt->bind_param('ii', $courseId, $studentId);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing) {
    header('Location: ' . BASE_URL . '/student/my-courses.php');
    exit;
}

// Insert enrollment
$stmt = $mysqli->prepare("INSERT INTO enrollments (course_id, student_id, status) VALUES (?, ?, 'active')");
$stmt->bind_param('ii', $courseId, $studentId);
$stmt->execute();
$stmt->close();

header('Location: ' . BASE_URL . '/student/my-courses.php');
exit;