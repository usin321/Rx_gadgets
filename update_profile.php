<?php
session_start();
include 'db/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Get form data
$name     = trim($_POST['name'] ?? '');
$bio      = trim($_POST['bio'] ?? '');
$gender   = trim($_POST['gender'] ?? '');
$birthday = trim($_POST['birthday'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$email    = trim($_POST['email'] ?? '');

if ($name && $email) {
    $stmt = $conn->prepare("UPDATE users SET name = ?, bio = ?, gender = ?, birthday = ?, phone = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssssssi", $name, $bio, $gender, $birthday, $phone, $email, $userId);
    $stmt->execute();
    $stmt->close();

    header("Location: account.php");
    exit();
} else {
    echo "<div class='alert alert-danger'>Name and email are required.</div>";
}
?>
