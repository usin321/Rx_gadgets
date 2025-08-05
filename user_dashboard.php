<?php
session_start();
include 'header.php';
include 'db/db.php';

// ✅ Ensure user is logged in
if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];
$userId = $user['id'];

// ✅ Optional: Fetch latest data from DB
$stmt = $conn->prepare("SELECT username, email, name FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$dbUser = $result->fetch_assoc();
$stmt->close();

if ($dbUser) {
    $user = array_merge($user, $dbUser);
    $_SESSION['user'] = $user;
}
?>

<div class="container mt-5">
  <div class="bg-light p-4 rounded shadow-sm">
    <h3 class="mb-3 text-center">👤 My Account</h3>

    <!-- Profile Info -->
    <div class="mb-4 text-center">
      <p><strong>Name:</strong> <?= htmlspecialchars($user['name'] ?? 'N/A') ?></p>
      <p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
      <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>

      <div class="d-flex justify-content-center flex-wrap gap-2 mt-3">
        <a href="edit_profile.php" class="btn btn-outline-primary">⚙️ Edit Profile</a>
        <a href="change_password.php" class="btn btn-outline-warning">🔒 Change Password</a>
      </div>
    </div>

    <hr>

    <!-- Actions -->
    <div class="d-flex justify-content-center flex-wrap gap-3 mb-4">
      <a href="products.php" class="btn btn-primary">🛒 Shop Products</a>
      <a href="cart.php" class="btn btn-success">🧺 View Cart</a>
      <a href="my_orders.php" class="btn btn-info">📦 My Orders</a>
    </div>

    <!-- Logout -->
    <div class="text-center mt-4">
      <a href="logout.php" class="btn btn-danger">🚪 Logout</a>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
