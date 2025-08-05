<?php
include '../db/db.php'; 
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm']);

    if ($password !== $confirm) {
        $error = "Passwords do not match!";
    } elseif (empty($username) || empty($password)) {
        $error = "All fields are required!";
    } else {
        $hashed = md5($password); // Match with login.php hashing

        // Check if username already exists
        $check = $conn->prepare("SELECT id FROM admin_users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $error = "Username already exists!";
        } else {
            // Insert new admin
            $stmt = $conn->prepare("INSERT INTO admin_users (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $hashed);

            if ($stmt->execute()) {
                $success = "✅ Admin account created successfully!";
            } else {
                $error = "❌ Something went wrong. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Register Admin - RX GADGETS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; }
    .container {
      max-width: 400px;
      background: #fff;
      padding: 30px;
      margin-top: 60px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body>
<div class="container">
  <h3 class="mb-4 text-center">👤 Register Admin</h3>

  <?php if (isset($error)) echo "<div class='alert alert-danger'>".htmlspecialchars($error)."</div>"; ?>
  <?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>

  <form method="POST">
    <div class="mb-3">
      <label class="form-label">Username</label>
      <input type="text" name="username" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Confirm Password</label>
      <input type="password" name="confirm" class="form-control" required>
    </div>
    <button type="submit" name="register" class="btn btn-primary w-100">➕ Create Admin</button>
    <a href="dashboard.php" class="btn btn-link mt-3 w-100">← Back to Dashboard</a>
  </form>
</div>
</body>
</html>
