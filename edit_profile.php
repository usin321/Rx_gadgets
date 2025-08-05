<?php
session_start();
include 'header.php';
include 'db/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);

    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssi", $name, $email, $userId);
    if ($stmt->execute()) {
        echo "<div class='container mt-4'><div class='alert alert-success'>✅ Profile updated!</div></div>";
    }
    $stmt->close();
}

// Load user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<div class="container mt-5 mb-5" style="max-width: 500px;">
  <h4>Edit Profile</h4>
  <form method="POST">
    <div class="mb-3">
      <label class="form-label">Full Name</label>
      <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
    </div>
    <button type="submit" class="btn btn-primary">💾 Save Changes</button>
    <a href="account.php" class="btn btn-secondary">Cancel</a>
  </form>
</div>

<?php include 'footer.php'; ?>
