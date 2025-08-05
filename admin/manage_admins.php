<?php
session_start();
include '../db/db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Handle password update
if (isset($_POST['update'])) {
    $id = intval($_POST['id']);
    $new_password = trim($_POST['new_password']);

    if (!empty($new_password)) {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed, $id);
        if ($stmt->execute()) {
            $success = "✅ Password updated successfully!";
        } else {
            $error = "❌ Failed to update password.";
        }
    } else {
        $error = "⚠️ Password cannot be empty.";
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $conn->query("DELETE FROM admin_users WHERE id = $delete_id");
    header("Location: manage_admins.php");
    exit();
}

// Fetch admin accounts
$admins = $conn->query("SELECT * FROM admin_users ORDER BY id ASC");
?>
<!DOCTYPE html>
<html>
<head>
  <title>Manage Admin Accounts</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
  <h3>⚙️ Manage Admin Accounts</h3>

  <?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
  <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

  <a href="register_admin.php" class="btn btn-primary mb-3">➕ Add Admin</a>
  <a href="dashboard.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>

  <table class="table table-bordered table-striped align-middle">
    <thead class="table-dark">
      <tr>
        <th>ID</th>
        <th>Username</th>
        <th>New Password</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php while ($row = $admins->fetch_assoc()): ?>
      <tr>
        <form method="POST">
          <td><?= $row['id'] ?></td>
          <td>
            <input type="text" value="<?= htmlspecialchars($row['username']) ?>" class="form-control" readonly>
            <input type="hidden" name="id" value="<?= $row['id'] ?>">
          </td>
          <td>
            <input type="text" name="new_password" class="form-control" placeholder="Enter new password" required>
          </td>
          <td>
            <button type="submit" name="update" class="btn btn-success btn-sm">Update</button>
            <a href="manage_admins.php?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this admin?')">Delete</a>
          </td>
        </form>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div>
</body>
</html>
