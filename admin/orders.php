<?php
session_start();
include '../db/db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Fetch orders by status
$statuses = ['All', 'Pending', 'Paid', 'To Ship', 'To Receive', 'Completed', 'Cancelled'];
$ordersByStatus = [];

foreach ($statuses as $status) {
    if ($status === 'All') {
        $query = "SELECT * FROM orders ORDER BY id DESC";
        $ordersByStatus[$status] = $conn->query($query);
    } else {
        $stmt = $conn->prepare("SELECT * FROM orders WHERE status = ? ORDER BY id DESC");
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $ordersByStatus[$status] = $stmt->get_result();
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Customer Orders - RX GADGETS Admin</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .badge-status {
      font-size: 0.85rem;
    }

    .proof-thumb {
      max-width: 80px;
      height: auto;
      border: 1px solid #ddd;
      border-radius: 5px;
    }

    .action-scroll-wrapper {
      overflow-x: auto;
      white-space: nowrap;
      max-width: 100%;
    }

    .action-scroll {
      display: inline-flex;
      gap: 5px;
      padding: 5px 0;
      min-width: 600px;
    }

    .action-scroll .btn {
      flex-shrink: 0;
    }

    .nav-tabs .nav-link.active {
      font-weight: bold;
    }

    /* Optional: Make table more mobile-friendly */
    @media (max-width: 768px) {
      table {
        font-size: 0.875rem;
      }
    }
  </style>
</head>
<body>
<div class="container mt-5 mb-5">
  <h3 class="mb-4">📦 Customer Orders</h3>

  <?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-info">
      ✅ Order status updated: <strong><?= htmlspecialchars($_GET['msg']) ?></strong>
    </div>
  <?php endif; ?>

  <!-- Tab Navigation -->
  <ul class="nav nav-tabs mb-3" id="orderTab" role="tablist">
    <?php foreach ($statuses as $index => $status): ?>
      <li class="nav-item" role="presentation">
        <button class="nav-link <?= $index === 0 ? 'active' : '' ?>" id="tab-<?= strtolower($status) ?>-tab" data-bs-toggle="tab" data-bs-target="#tab-<?= strtolower($status) ?>" type="button" role="tab">
          <?= $status ?>
        </button>
      </li>
    <?php endforeach; ?>
  </ul>

  <!-- Tab Contents -->
  <div class="tab-content" id="orderTabContent">
    <?php foreach ($statuses as $index => $status): ?>
      <?php $orders = $ordersByStatus[$status]; ?>
      <div class="tab-pane fade <?= $index === 0 ? 'show active' : '' ?>" id="tab-<?= strtolower($status) ?>" role="tabpanel">
        <?php if ($orders->num_rows === 0): ?>
          <div class="alert alert-info text-center mt-3">No <?= strtolower($status) ?> orders.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle mt-3">
              <thead class="table-dark">
                <tr>
                  <th>Order #</th>
                  <th>Customer</th>
                  <th>Email</th>
                  <th>Mobile</th>
                  <th>Total</th>
                  <th>Status</th>
                  <th>Payment</th>
                  <th>Delivery</th>
                  <th>Proof</th>
                  <th>Ordered On</th>
                  <th style="min-width: 250px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($order = $orders->fetch_assoc()): ?>
                  <?php
                    $status = $order['status'] ?? 'Pending';
                    $badgeClass = match ($status) {
                      'Completed' => 'success',
                      'Cancelled' => 'danger',
                      'To Ship' => 'primary',
                      'To Receive' => 'secondary',
                      'Paid' => 'warning',
                      default => 'dark',
                    };
                    $proofImage = $order['payment_proof'] ?? null;
                  ?>
                  <tr>
                    <td>#<?= $order['id'] ?></td>
                    <td><?= htmlspecialchars($order['fullname']) ?></td>
                    <td><?= htmlspecialchars($order['email']) ?></td>
                    <td><?= htmlspecialchars($order['mobile']) ?></td>
                    <td>₱<?= number_format($order['total'], 2) ?></td>
                    <td><span class="badge bg-<?= $badgeClass ?> badge-status"><?= htmlspecialchars($status) ?></span></td>
                    <td><?= htmlspecialchars($order['payment_method']) ?></td>
                    <td><?= htmlspecialchars($order['delivery_method']) ?></td>
                    <td>
                      <?php if ($proofImage): ?>
                        <a href="../uploads/proofs/<?= htmlspecialchars($proofImage) ?>" target="_blank">
                          <img src="../uploads/proofs/<?= htmlspecialchars($proofImage) ?>" class="proof-thumb" alt="Proof">
                        </a>
                      <?php else: ?>
                        <span class="text-muted small">None</span>
                      <?php endif; ?>
                    </td>
                    <td><?= date("F j, Y g:i A", strtotime($order['order_date'])) ?></td>
                    <td>
                      <div class="action-scroll-wrapper">
                        <div class="action-scroll">
                          <a href="view_order.php?id=<?= $order['id'] ?>" class="btn btn-info btn-sm">👁 View</a>
                          <?php if (!in_array($status, ['Completed', 'Cancelled'])): ?>
                            <a href="order_actions.php?action=paid&id=<?= $order['id'] ?>" class="btn btn-warning btn-sm">💸 Paid</a>
                            <a href="order_actions.php?action=ship&id=<?= $order['id'] ?>" class="btn btn-primary btn-sm">📦 Ship</a>
                            <a href="order_actions.php?action=receive&id=<?= $order['id'] ?>" class="btn btn-secondary btn-sm">📬 Receive</a>
                            <a href="order_actions.php?action=complete&id=<?= $order['id'] ?>" class="btn btn-success btn-sm" onclick="return confirm('Mark as completed?')">✅ Complete</a>
                            <a href="order_actions.php?action=cancel&id=<?= $order['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Cancel this order?')">❌ Cancel</a>
                          <?php else: ?>
                            <button class="btn btn-outline-secondary btn-sm" disabled>No Actions</button>
                          <?php endif; ?>
                        </div>
                      </div>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <a href="dashboard.php" class="btn btn-secondary mt-4">← Back to Dashboard</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
