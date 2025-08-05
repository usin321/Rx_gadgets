<?php
include 'header.php';
include 'db/db.php';

// Allowed categories to prevent invalid input
$allowedCategories = ['iPhone', 'iPad', 'Accessory'];

// Validate product ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  echo "<div class='container mt-4'><div class='alert alert-danger'>❌ Invalid product ID.</div></div>";
  include 'footer.php';
  exit();
}

$id = intval($_GET['id']);

// Fetch product
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product || !in_array($product['category'], $allowedCategories)) {
  echo "<div class='container mt-4'><div class='alert alert-warning'>⚠️ Product not found or category mismatch.</div></div>";
  include 'footer.php';
  exit();
}

// Prepare product details
$productImage = !empty($product['image']) ? htmlspecialchars($product['image']) : 'default.jpg';
$productName = htmlspecialchars($product['name']);
$productCategory = htmlspecialchars($product['category']);
$productPrice = number_format($product['price'], 2);
$productDescription = nl2br(htmlspecialchars($product['description']));
?>

<div class="container mt-5 mb-5">
  <div class="row">
    <!-- Product Image -->
    <div class="col-md-6 mb-4">
      <img src="assets/images/<?= $productImage ?>" 
           class="img-fluid rounded shadow-sm w-100" 
           alt="<?= $productName ?>">
    </div>

    <!-- Product Info -->
    <div class="col-md-6">
      <h2 class="fw-bold"><?= $productName ?></h2>
      <span class="badge bg-secondary mb-2"><?= $productCategory ?></span>
      <h4 class="text-success">₱<?= $productPrice ?></h4>

      <p class="mt-3"><?= $productDescription ?></p>

      <!-- Add to Cart -->
      <form method="POST" action="cart.php" class="mt-4">
        <input type="hidden" name="product_id" value="<?= $id ?>">

        <div class="mb-3" style="max-width: 120px;">
          <label for="quantity" class="form-label">Quantity</label>
          <input type="number" name="quantity" id="quantity" class="form-control" min="1" value="1" required>
        </div>

        <button type="submit" name="add_to_cart" class="btn btn-success btn-lg">
          🛒 Add to Cart
        </button>
        <a href="products.php?category=<?= urlencode($productCategory) ?>" class="btn btn-outline-secondary ms-2 btn-lg">← Back</a>
      </form>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
