<?php
session_start();
include 'db/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $current = trim($_POST['current_password']);
    $new = trim($_POST['new_password']);
    $confirm = trim($_POST['confirm_password']);

    if ($new !== $confirm) {
        $error = "❌ New passwords do not match.";
    } elseif (strlen($new) < 6) {
        $error = "❌ Password must be at least 6 characters.";
    } else {
        // Fetch current password (plain text)
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($dbPassword);
        $stmt->fetch();
        $stmt->close();

        if ($current !== $dbPassword) {
            $error = "❌ Current password is incorrect.";
        } else {
            $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $upd->bind_param("si", $new, $userId);
            $upd->execute();
            $upd->close();
            $success = "✅ Password changed successfully.";
        }
    }
}
?>
<?php include 'header.php'; ?>
<div class="container mt-5 mb-5">
  <h2>🔒 Change Password</h2>
  <?php if (isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
  <?php if (isset($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
  <form method="POST">
    <div class="mb-3">
      <label>Current Password</label>
      <input type="password" name="current_password" class="form-control" required>
    </div>
    <div class="mb-3">
      <label>New Password</label>
      <input type="password" name="new_password" class="form-control" required>
    </div>
    <div class="mb-3">
      <label>Confirm New Password</label>
      <input type="password" name="confirm_password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-warning">🔄 Change Password</button>
    <a href="account.php" class="btn btn-secondary">Back</a>
  </form>
</div>
<?php include 'footer.php'; ?>
