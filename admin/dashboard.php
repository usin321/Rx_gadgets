<?php
session_start();
include '../db/db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Categories and summary
$categories = [
    'iPhone' => 'iPhones',
    'iPad' => 'iPads',
    'Accessory' => 'Accessories'
];

// Sales summary per category
$salesSummary = [];
foreach ($categories as $key => $label) {
    $stmt = $conn->prepare("
        SELECT SUM(oi.quantity) AS total_units, SUM(oi.quantity * oi.price) AS total_sales
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE p.category = ? AND o.status = 'Completed'
    ");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $salesSummary[$key] = $stmt->get_result()->fetch_assoc() ?: ['total_units' => 0, 'total_sales' => 0];
    $stmt->close();
}

// Top 3 selling products
$topProducts = $conn->query("
    SELECT p.name, SUM(oi.quantity) AS sold
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'Completed'
    GROUP BY oi.product_id
    ORDER BY sold DESC
    LIMIT 3
");

// Dashboard summary
$summary = [
    'products' => (int)$conn->query("SELECT COUNT(*) AS count FROM products")->fetch_assoc()['count'],
    'orders' => (int)$conn->query("SELECT COUNT(*) AS count FROM orders")->fetch_assoc()['count'],
    'revenue' => $conn->query("
        SELECT SUM(oi.quantity * oi.price) AS total
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE o.status='Completed'
    ")->fetch_assoc()['total'] ?? 0
];

// Prepare sales data
$salesData = ['weekly' => [], 'monthly' => [], 'yearly' => []];

// Weekly
$res = $conn->query("
    SELECT DATE(order_date) AS label, SUM(oi.quantity * oi.price) AS total
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    WHERE o.status='Completed' AND order_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(order_date)
    ORDER BY DATE(order_date)
");
while ($r = $res->fetch_assoc()) $salesData['weekly'][] = $r;

// Monthly
$res = $conn->query("
    SELECT DATE(order_date) AS label, SUM(oi.quantity * oi.price) AS total
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    WHERE o.status='Completed' AND order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(order_date)
    ORDER BY DATE(order_date)
");
while ($r = $res->fetch_assoc()) $salesData['monthly'][] = $r;

// Yearly
$res = $conn->query("
    SELECT DATE_FORMAT(order_date, '%Y-%m') AS label, SUM(oi.quantity * oi.price) AS total
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    WHERE o.status='Completed' AND order_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
    GROUP BY DATE_FORMAT(order_date, '%Y-%m')
    ORDER BY label
");
while ($r = $res->fetch_assoc()) $salesData['yearly'][] = $r;

// Totals for charts
$totalWeeklySales = array_sum(array_column($salesData['weekly'], 'total'));
$totalMonthlySales = array_sum(array_column($salesData['monthly'], 'total'));
$totalYearlySales = array_sum(array_column($salesData['yearly'], 'total'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin Dashboard - RX GADGETS</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body { background-color: #f8f9fa; }
    .sidebar {
      position: sticky;
      top: 20px;
      background: #fff;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.05);
    }
    .sidebar .btn { width: 100%; margin-bottom: 10px; }
  </style>
</head>
<body>
<div class="container-fluid mt-4">
  <div class="row">
    <div class="col-md-3 mb-4">
      <div class="sidebar">
        <h5 class="mb-3">🔧 Admin Panel</h5>
        <a href="products.php" class="btn btn-primary">📱 Products</a>
        <a href="orders.php" class="btn btn-info">📦 View Orders</a>
        <a href="manage_admins.php" class="btn btn-secondary">⚙️ Manage Admins</a>
        <a href="logout.php" class="btn btn-danger">🚪 Logout</a>
      </div>
    </div>

    <div class="col-md-9">
      <h3 class="mb-4">📊 Dashboard Summary</h3>
      <div class="row mb-4">
        <div class="col-md-4">
          <div class="card shadow-sm mb-3">
            <div class="card-body">
              <h6>Total Products</h6>
              <h3><?= $summary['products'] ?></h3>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card shadow-sm mb-3">
            <div class="card-body">
              <h6>Total Orders</h6>
              <h3><?= $summary['orders'] ?></h3>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card shadow-sm mb-3">
            <div class="card-body">
              <h6>Total Revenue</h6>
              <h3>₱<?= number_format($summary['revenue'], 2) ?></h3>
            </div>
          </div>
        </div>
      </div>

      <!-- Category Sales Summary -->
      <div class="row mb-4">
        <?php foreach ($categories as $key => $label): ?>
          <div class="col-md-4 mb-3">
            <div class="card shadow-sm">
              <div class="card-body">
                <h5><?= htmlspecialchars($label) ?> Sales</h5>
                <p>🛒 Sold: <strong><?= $salesSummary[$key]['total_units'] ?? 0 ?></strong></p>
                <p>💰 Revenue: <strong>₱<?= number_format($salesSummary[$key]['total_sales'], 2) ?></strong></p>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Sales Charts with Totals -->
      <div class="row mb-5">
        <div class="col-md-4">
          <div class="card shadow-sm p-3">
            <h6>📅 Last 7 Days</h6>
            <canvas id="weeklyChart"></canvas>
            <div class="mt-2 text-end text-success fw-bold">Total: ₱<?= number_format($totalWeeklySales, 2) ?></div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card shadow-sm p-3">
            <h6>📅 Last 30 Days</h6>
            <canvas id="monthlyChart"></canvas>
            <div class="mt-2 text-end text-success fw-bold">Total: ₱<?= number_format($totalMonthlySales, 2) ?></div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card shadow-sm p-3">
            <h6>📅 Last 12 Months</h6>
            <canvas id="yearlyChart"></canvas>
            <div class="mt-2 text-end text-success fw-bold">Total: ₱<?= number_format($totalYearlySales, 2) ?></div>
          </div>
        </div>
      </div>

      <!-- Top Products -->
      <div class="mb-4">
        <h5>🏆 Top 3 Best Sellers</h5>
        <ul class="list-group">
          <?php while ($top = $topProducts->fetch_assoc()): ?>
            <li class="list-group-item d-flex justify-content-between">
              <?= htmlspecialchars($top['name']) ?>
              <span class="badge bg-success">Sold: <?= htmlspecialchars($top['sold']) ?></span>
            </li>
          <?php endwhile; ?>
        </ul>
      </div>
    </div>
  </div>
</div>

<script>
// Chart.js rendering
const data = <?= json_encode($salesData) ?>;

function drawChart(ctxId, obj) {
  const labels = obj.map(r => r.label);
  const values = obj.map(r => r.total);
  new Chart(document.getElementById(ctxId).getContext('2d'), {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Sales (₱)',
        data: values,
        backgroundColor: '#007bff'
      }]
    },
    options: {
      scales: { y: { beginAtZero: true } },
      plugins: { legend: { display: false } },
      responsive: true
    }
  });
}

drawChart('weeklyChart', data.weekly);
drawChart('monthlyChart', data.monthly);
drawChart('yearlyChart', data.yearly);
</script>
</body>
</html>
