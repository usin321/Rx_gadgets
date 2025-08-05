<?php
session_start();
include 'header.php';
include 'db/db.php';

$error = "";

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: products.php");
    exit();
}

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    // Check if user exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Plain-text password check (for testing)
        if ($password === $row["password"]) {
            // ✅ Set session variables
            $_SESSION['user_id'] = $row['id']; // for cart.php and others
            $_SESSION['username'] = $row['username']; // optional
            $_SESSION['user'] = [ // optional full user object
                'id' => $row['id'],
                'username' => $row['username']
            ];

            header("Location: user_dashboard.php");
            exit();
        } else {
            $error = "❌ Invalid password.";
        }
    } else {
        $error = "❌ Username not found.";
    }
}
?>

<!-- Login Form UI -->
<div class="container mt-5" style="max-width: 400px;">
  <h3 class="text-center mb-4">👤 User Login</h3>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="login.php">
    <div class="mb-3">
      <label class="form-label">Username</label>
      <input type="text" name="username" class="form-control" required 
             value="<?= isset($username) ? htmlspecialchars($username) : '' ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary w-100">🔐 Login</button>
    <div class="text-center mt-3">
      <a href="register.php">Don't have an account? Register</a>
    </div>
  </form>
</div>

<?php include 'footer.php'; ?>
