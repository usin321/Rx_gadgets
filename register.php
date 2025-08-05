<?php
include 'header.php';
include 'db/db.php';

$success = $error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if ($username && $email && $password) {
        // Check if username or email already exists
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $error = "❌ Username or email already taken.";
        } else {
            // Store plain-text password (FOR TESTING ONLY)
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $password);

            if ($stmt->execute()) {
                $success = "✅ Registration successful. You can now <a href='login.php'>login</a>.";
                // Optionally: auto login after registration:
                // session_start();
                // $_SESSION['user'] = [
                //     'id' => $stmt->insert_id,
                //     'username' => $username
                // ];
                // header("Location: user_dashboard.php");
                // exit();
            } else {
                $error = "❌ Registration failed. Please try again.";
            }
        }
    } else {
        $error = "⚠️ All fields are required.";
    }
}
?>

<!-- Registration Form UI -->
<div class="container mt-5" style="max-width: 500px;">
  <h3>User Registration</h3>

  <?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= $success ?></div>
  <?php elseif (!empty($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST" action="register.php">
    <div class="mb-3">
      <label>Username</label>
      <input type="text" name="username" class="form-control" required>
    </div>
    <div class="mb-3">
      <label>Email</label>
      <input type="email" name="email" class="form-control" required>
    </div>
    <div class="mb-3">
      <label>Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <button class="btn btn-primary w-100" type="submit">Register</button>
    <div class="text-center mt-3">
      <a href="login.php">Already have an account? Log in</a>
    </div>
  </form>
</div>

<?php include 'footer.php'; ?>
