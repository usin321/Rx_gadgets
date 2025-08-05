<?php
session_start();
include 'header.php';
include 'db/db.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if order ID is set
if (!isset($_GET['id'])) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Invalid order ID.</div></div>";
    include 'footer.php';
    exit();
}

$orderId = intval($_GET['id']);
$userId = $_SESSION['user_id'];

// Fetch order info
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Order not found or access denied.</div></div>";
    include 'footer.php';
    exit();
}

// Fetch order items
$itemStmt = $conn->prepare("SELECT oi.quantity, oi.price, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$itemStmt->bind_param("i", $orderId);
$itemStmt->execute();
$items = $itemStmt->get_result();
$itemStmt->close();
?>

<div class="container mt-5 mb-5">
  <h2 class="mb-4">📄 Order Details</h2>

  <div class="card mb-4">
    <div class="card-body">
      <p><strong>Order ID:</strong> #<?= $order['id'] ?></p>
      <p><strong>Date:</strong> <?= date("F d, Y h:i A", strtotime($order['order_date'])) ?></p>
      <p><strong>Total:</strong> ₱<?= number_format($order['total'], 2) ?></p>
      <p><strong>Status:</strong> <?= htmlspecialchars($order['status'] ?? 'Pending') ?></p>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <h5 class="card-title">🛒 Items</h5>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>Product</th>
            <th>Quantity</th>
            <th>Price (each)</th>
            <th>Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($item = $items->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($item['name']) ?></td>
              <td><?= $item['quantity'] ?></td>
              <td>₱<?= number_format($item['price'], 2) ?></td>
              <td>₱<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3">
    <a href="account.php" class="btn btn-secondary">← Back to My Account</a>
  </div>
</div>

<?php include 'footer.php'; ?>
