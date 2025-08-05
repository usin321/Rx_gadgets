<?php
session_start();
include '../db/db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Category mapping: database key => label
$categories = [
    'iPhone' => 'iPhones',
    'iPad' => 'iPads',
    'Accessory' => 'Accessories'
];

// Fetch products grouped by category
$productsByCategory = [];
foreach ($categories as $key => $label) {
    $stmt = $conn->prepare("SELECT id, name, model, price FROM products WHERE category = ? ORDER BY id DESC");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    $productsByCategory[$key] = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Product Management - RX GADGETS</title>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .category-header {
      font-size: 1.25rem;
      font-weight: 600;
    }
    .sidebar {
      position: sticky;
      top: 20px;
      background: #fff;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .sidebar .btn {
      width: 100%;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>

<div class="container-fluid mt-4">
  <div class="row">
    <!-- Sidebar -->
    <div class="col-md-3 mb-4">
      <div class="sidebar">
        <h5 class="mb-3">🔧 Admin Panel</h5>
        <a href="dashboard.php" class="btn btn-light">📊 Dashboard</a>
        <a href="orders.php" class="btn btn-info text-white">📦 View Orders</a>
        <a href="manage_admins.php" class="btn btn-secondary">👥 Manage Admins</a>
        <a href="logout.php" class="btn btn-danger">🚪 Logout</a>
      </div>
    </div>

    <!-- Main Content -->
    <div class="col-md-9">
      <h3 class="mb-4">📱 Manage Products</h3>

      <!-- Alerts -->
      <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
        <div class="alert alert-success">✅ Product deleted successfully!</div>
      <?php elseif (isset($_GET['error']) && $_GET['error'] === 'linked'): ?>
        <div class="alert alert-warning">⚠️ Cannot delete product — it is linked to existing orders.</div>
      <?php elseif (isset($_GET['error']) && $_GET['error'] === 'failed'): ?>
        <div class="alert alert-danger">❌ Failed to delete product. Please try again.</div>
      <?php endif; ?>

      <!-- Products by Category -->
      <?php foreach ($categories as $categoryKey => $categoryLabel): ?>
        <div class="mt-5">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="category-header"><?= htmlspecialchars($categoryLabel) ?></div>
            <a href="add_product.php?category=<?= urlencode($categoryKey) ?>" class="btn btn-success btn-sm">
              ➕ Add <?= htmlspecialchars($categoryKey) ?>
            </a>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
              <thead class="table-dark">
                <tr>
                  <th style="width: 50px;">ID</th>
                  <th>Name</th>
                  <th>Model</th>
                  <th>Price (₱)</th>
                  <th style="width: 180px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($productsByCategory[$categoryKey])): ?>
                  <?php foreach ($productsByCategory[$categoryKey] as $row): ?>
                    <tr>
                      <td><?= $row['id'] ?></td>
                      <td><?= htmlspecialchars($row['name']) ?></td>
                      <td><?= htmlspecialchars($row['model']) ?></td>
                      <td>₱<?= number_format($row['price'], 2) ?></td>
                      <td>
                        <a href="edit_product.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">✏️ Edit</a>
                        <a href="delete_product.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm"
                           onclick="return confirm('Are you sure you want to delete this product?')">🗑️ Delete</a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="5" class="text-center text-muted">No <?= strtolower($categoryLabel) ?> found.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

</body>
</html>
