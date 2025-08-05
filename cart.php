<?php
session_start();
include 'header.php';
include 'db/db.php';

// Require login
if (!isset($_SESSION['user'])) {
  echo "<div class='container mt-5'><div class='alert alert-warning text-center'>
          ⚠️ Please <a href='login.php'>log in</a> to view or manage your cart.
        </div></div>";
  include 'footer.php';
  exit();
}

// Initialize cart if not present
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

$successMsg = isset($_GET['success']) ? "✅ Item added to cart successfully." : "";
?>

<style>
  .cart-img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 5px;
    border: 1px solid #ddd;
  }
</style>

<div class="container mt-5 mb-5">
  <h2 class="mb-4 text-center">🛒 Your Shopping Cart</h2>

  <?php if ($successMsg): ?>
    <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
      <?= $successMsg ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if (empty($_SESSION['cart'])): ?>
    <div class="alert alert-info text-center">
      🧺 Your cart is currently empty. Start shopping for great deals!
    </div>
    <div class="text-center">
      <a href="products.php" class="btn btn-outline-primary btn-lg">← Browse Products</a>
    </div>
  <?php else: ?>
    <form method="post" action="checkout.php">
      <table class="table table-bordered align-middle text-center">
        <thead class="table-dark">
          <tr>
            <th><input type="checkbox" id="selectAll" onclick="toggleAll(this)"></th>
            <th>Image</th>
            <th>Product</th>
            <th>Category</th>
            <th style="width: 130px;">Quantity</th>
            <th>Price</th>
            <th>Subtotal</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php
            $total = 0;
            foreach ($_SESSION['cart'] as $id => $qty):
              $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
              $stmt->bind_param("i", $id);
              $stmt->execute();
              $result = $stmt->get_result();
              $product = $result->fetch_assoc();
              $stmt->close();

              if (!$product) continue;

              $subtotal = $product['price'] * $qty;
              $total += $subtotal;

              $images = json_decode($product['image'], true);
              if (!$images || !is_array($images)) {
                $images = [$product['image'] ?: 'placeholder.jpg'];
              }
              $firstImage = $images[0];
          ?>
          <tr>
            <td>
              <input type="checkbox" name="selected[]" value="<?= $product['id'] ?>" class="product-checkbox">
            </td>
            <td>
              <img src="assets/images/<?= htmlspecialchars($firstImage) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="cart-img"
                onerror="this.onerror=null;this.src='assets/images/placeholder.jpg';">
            </td>
            <td><strong><?= htmlspecialchars($product['name']) ?></strong></td>
            <td><span class="badge bg-secondary"><?= htmlspecialchars($product['category']) ?></span></td>
            <td>
              <input type="number" name="quantity[<?= $product['id'] ?>]" value="<?= $qty ?>" min="1" class="form-control form-control-sm text-center">
            </td>
            <td class="text-success">₱<?= number_format($product['price'], 2) ?></td>
            <td><strong>₱<?= number_format($subtotal, 2) ?></strong></td>
            <td>
              <a href="cart_remove.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-outline-danger">❌ Remove</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <!-- Estimated delivery -->
      <div class="text-muted mb-3 text-end">
        📦 Estimated delivery: <strong>3-5 business days</strong> | ✨ Free shipping on orders over ₱5,000!
      </div>

      <!-- Buttons -->
      <div class="d-flex justify-content-between align-items-center">
        <a href="products.php" class="btn btn-secondary">← Continue Shopping</a>
        <div>
          <button type="submit" formaction="update_cart.php" name="update_cart" class="btn btn-warning me-2">🔁 Update Quantities</button>
          <button type="submit" class="btn btn-success" onclick="return confirmCheckout()">✔️ Checkout Selected</button>
        </div>
      </div>
    </form>
  <?php endif; ?>
</div>

<script>
  function toggleAll(source) {
    const checkboxes = document.querySelectorAll('.product-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = source.checked);
  }

  function confirmCheckout() {
    const checked = document.querySelectorAll('.product-checkbox:checked');
    if (checked.length === 0) {
      alert('⚠️ Please select at least one product to checkout.');
      return false;
    }
    return true;
  }
</script>

<?php include 'footer.php'; ?>
