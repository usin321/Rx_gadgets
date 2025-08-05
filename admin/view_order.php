<?php
session_start();
include '../db/db.php';

if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit();
}

// Validate order ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  echo "<div class='alert alert-danger'>❌ Invalid order ID.</div>";
  exit();
}

$order_id = intval($_GET['id']);

// Fetch order details
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$orderResult = $stmt->get_result();
$order = $orderResult->fetch_assoc();
$stmt->close();

if (!$order) {
  echo "<div class='alert alert-danger'>❌ Order not found.</div>";
  exit();
}

// Fetch order items with product name and category
$stmt = $conn->prepare("
  SELECT oi.*, p.name, p.category 
  FROM order_items oi
  JOIN products p ON oi.product_id = p.id
  WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$itemsResult = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Order #<?= $order_id ?> - RX GADGETS Admin</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
    }
    .container {
      background: white;
      border-radius: 10px;
      padding: 30px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body>

<div class="container mt-5 mb-5">
  <h3 class="mb-4">📦 Order Details (#<?= $order_id ?>)</h3>

  <!-- Order Info -->
  <div class="mb-4">
    <p><strong>Customer:</strong> <?= htmlspecialchars($order['fullname']) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
    <p><strong>Address:</strong> <?= htmlspecialchars($order['address']) ?></p>
    <p><strong>Status:</strong> 
      <span class="badge bg-<?= match($order['status']) {
        'Completed' => 'success',
        'Cancelled' => 'danger',
        default => 'secondary'
      } ?>">
        <?= htmlspecialchars($order['status'] ?? 'Pending') ?>
      </span>
    </p>
    <p><strong>Order Date:</strong> <?= date("F j, Y g:i A", strtotime($order['order_date'])) ?></p>
  </div>

  <!-- Items Table -->
  <div class="table-responsive">
    <table class="table table-bordered align-middle">
      <thead class="table-light">
        <tr>
          <th>Product Name</th>
          <th>Category</th>
          <th>Quantity</th>
          <th>Price (₱)</th>
          <th>Subtotal (₱)</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $total = 0;
        while ($item = $itemsResult->fetch_assoc()):
          $subtotal = $item['price'] * $item['quantity'];
          $total += $subtotal;
        ?>
          <tr>
            <td><?= htmlspecialchars($item['name']) ?></td>
            <td><?= htmlspecialchars($item['category']) ?></td>
            <td><?= $item['quantity'] ?></td>
            <td>₱<?= number_format($item['price'], 2) ?></td>
            <td>₱<?= number_format($subtotal, 2) ?></td>
          </tr>
        <?php endwhile; ?>
        <tr class="table-light">
          <th colspan="4" class="text-end">Total:</th>
          <th>₱<?= number_format($total, 2) ?></th>
        </tr>
      </tbody>
    </table>
  </div>

  <a href="orders.php" class="btn btn-secondary mt-3">← Back to Orders</a>
</div>

</body>
</html>
