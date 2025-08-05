<?php
session_start();
include 'header.php';
include 'db/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Get user info (optional if already loaded)
$stmt = $conn->prepare("SELECT id, username, email, name FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Order statuses to group
$orderStatuses = ['Pending', 'Paid', 'To Ship', 'To Receive', 'Completed'];
$ordersByStatus = [];

foreach ($orderStatuses as $status) {
    $stmt = $conn->prepare("
        SELECT o.id, o.order_date, o.status, o.payment_proof, SUM(oi.quantity * p.price) AS total
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE o.user_id = ? AND o.status = ?
        GROUP BY o.id, o.order_date, o.status, o.payment_proof
        ORDER BY o.order_date DESC
    ");
    $stmt->bind_param("is", $userId, $status);
    $stmt->execute();
    $ordersByStatus[$status] = $stmt->get_result();
    $stmt->close();
}
?>

<div class="container mt-5 mb-5">
  <h2 class="mb-4">📦 My Orders</h2>

  <!-- Tabs -->
  <ul class="nav nav-tabs mb-3" id="orderTabs">
    <?php foreach ($orderStatuses as $i => $status): ?>
      <li class="nav-item">
        <a class="nav-link <?= $i === 0 ? 'active' : '' ?>" data-bs-toggle="tab" href="#tab-<?= strtolower(str_replace(' ', '-', $status)) ?>">
          <?= $status === 'Pending' ? '🕐 To Pay' :
              ($status === 'Paid' ? '💸 Paid' :
              ($status === 'To Ship' ? '📦 To Ship' :
              ($status === 'To Receive' ? '📬 To Receive' : '✅ Completed'))) ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>

  <!-- Tab Content -->
  <div class="tab-content">
    <?php foreach ($orderStatuses as $i => $status): ?>
      <?php $tabId = strtolower(str_replace(' ', '-', $status)); ?>
      <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="tab-<?= $tabId ?>">
        <?php if ($ordersByStatus[$status]->num_rows > 0): ?>
          <table class="table table-striped table-sm">
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Date</th>
                <th>Status</th>
                <th>Total</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($order = $ordersByStatus[$status]->fetch_assoc()): ?>
                <tr>
                  <td>#<?= $order['id'] ?></td>
                  <td><?= date("M d, Y", strtotime($order['order_date'])) ?></td>
                  <td><span class="badge bg-secondary"><?= $order['status'] ?></span></td>
                  <td>₱<?= number_format($order['total'], 2) ?></td>
                  <td>
                    <a href="order_details.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-info mb-1">View</a>

                    <?php if ($status === 'Pending'): ?>
                      <!-- Upload payment proof -->
                      <form action="upload_proof.php" method="POST" enctype="multipart/form-data" class="d-inline">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <input type="file" name="proof" accept="image/*" required style="display:inline-block; width: 150px;" class="form-control form-control-sm mb-1">
                        <button class="btn btn-sm btn-success" type="submit">Upload Proof</button>
                      </form>
                    <?php elseif ($status === 'To Receive'): ?>
                      <a href="confirm_receipt.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-success mb-1"
                         onclick="return confirm('Confirm receipt of this order?')">Confirm Received</a>
                    <?php elseif ($status === 'Completed'): ?>
                      <a href="reorder.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-secondary">Reorder</a>
                    <?php elseif ($status === 'To Ship'): ?>
                      <span class="text-muted">🚚 Waiting for shipment</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="text-muted">No orders in this category.</p>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php include 'footer.php'; ?>
