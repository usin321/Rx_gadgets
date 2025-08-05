<?php
session_start();
include 'header.php';

if (!isset($_SESSION['user']) || !isset($_SESSION['buy_now'])) {
    header("Location: products.php");
    exit();
}

$product = $_SESSION['buy_now'];
$total = $product['price'] * $product['quantity'];
?>

<div class="container mt-5 mb-5">
  <h2 class="mb-4">🧾 Confirm Your Purchase</h2>
  <div class="card p-4 shadow-sm">
    <div class="row">
      <div class="col-md-4 text-center">
        <img src="assets/images/<?= htmlspecialchars($product['image']) ?>" class="img-fluid rounded" style="max-height: 200px;">
      </div>
      <div class="col-md-8">
        <h4><?= htmlspecialchars($product['name']) ?></h4>
        <p class="text-muted">Price: ₱<?= number_format($product['price'], 2) ?></p>
        <p>Quantity: <?= $product['quantity'] ?></p>
        <p class="fw-bold">Total: ₱<?= number_format($total, 2) ?></p>

        <!-- Confirm Button -->
        <form action="place_order.php" method="post">
          <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
          <input type="hidden" name="quantity" value="<?= $product['quantity'] ?>">
          <button type="submit" class="btn btn-success">✅ Confirm Purchase</button>
          <a href="products.php" class="btn btn-secondary ms-2">Cancel</a>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
