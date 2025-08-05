<?php
session_start();
include 'header.php';
include 'db/db.php';

$isLoggedIn = isset($_SESSION['user']);

// Get categories from DB
$categories = [];
$catQuery = $conn->query("SELECT DISTINCT category FROM products");
while ($catRow = $catQuery->fetch_assoc()) {
    $categories[] = $catRow['category'];
}

// Filter logic
$categoryFilter = isset($_GET['category']) ? trim($_GET['category']) : null;
$filtered = false;

if ($categoryFilter && in_array($categoryFilter, $categories)) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE category = ? ORDER BY id DESC");
    $stmt->bind_param("s", $categoryFilter);
    $stmt->execute();
    $result = $stmt->get_result();
    $heading = "📦 " . htmlspecialchars($categoryFilter) . " Products";
    $filtered = true;
} else {
    $result = $conn->query("SELECT * FROM products ORDER BY id DESC");
    $heading = "📱 All iOS Products";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Products - RX GADGETS</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .carousel-inner img { height: 230px; object-fit: cover; border-radius: 6px; }
    .carousel-control-prev-icon, .carousel-control-next-icon {
      background-color: #000; border-radius: 50%; padding: 10px;
    }
    .card-title { font-size: 1rem; font-weight: 600; }
    .card-body { font-size: 0.95rem; }
    .card .btn { font-size: 0.85rem; }
    .badge { font-size: 0.75rem; }

    .modal-xl { max-width: 1000px; }

    .modal-content {
      background-color: #111;
      color: white;
      border-radius: 10px;
    }

    .modal-body {
      overflow: hidden;
      padding: 0;
    }

    .zoom-container {
      overflow: hidden;
      position: relative;
    }

    .zoom-container img {
      transition: transform 0.3s ease;
      width: 100%;
      height: auto;
      display: block;
      max-height: 90vh;
      object-fit: contain;
      cursor: zoom-in;
    }

    .zoom-container img.zoomed {
      transform: scale(2);
      cursor: zoom-out;
    }
  </style>
</head>
<body>

<div class="container mt-5 mb-4">
  <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success text-center">
      <?= $_SESSION['success']; unset($_SESSION['success']); ?>
    </div>
  <?php endif; ?>

  <h2 class="mb-4 text-center fw-bold"><?= $heading ?></h2>

  <!-- Category Filter -->
  <div class="mb-4 text-center">
    <div class="btn-group">
      <?php foreach ($categories as $cat): ?>
        <a href="products.php?category=<?= urlencode($cat) ?>" class="btn btn-outline-dark <?= ($cat == $categoryFilter) ? 'active' : '' ?>">
          <?= htmlspecialchars($cat) ?>
        </a>
      <?php endforeach; ?>
      <?php if ($filtered): ?>
        <a href="products.php" class="btn btn-danger ms-2">❌ Clear Filter</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Product Grid -->
  <div class="row">
    <?php if ($result && $result->num_rows > 0): ?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <?php
          $images = json_decode($row['image'], true);
          if (!$images || !is_array($images)) {
              $images = [$row['image'] ?: 'placeholder.jpg'];
          }
          $carouselId = "carousel" . $row['id'];
          $modalId = "modal" . $row['id'];
        ?>
        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
          <div class="card h-100 shadow-sm border-0 d-flex flex-column text-center">
            <!-- Image Carousel -->
            <div id="<?= $carouselId ?>" class="carousel slide" data-bs-ride="carousel">
              <div class="carousel-inner">
                <?php foreach ($images as $index => $img): ?>
                  <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                    <img src="assets/images/<?= htmlspecialchars($img) ?>" class="d-block w-100" alt="Product Image"
                         onerror="this.onerror=null;this.src='assets/images/placeholder.jpg';"
                         data-bs-toggle="modal" data-bs-target="#<?= $modalId ?>" style="cursor:pointer;">
                  </div>
                <?php endforeach; ?>
              </div>
              <?php if (count($images) > 1): ?>
                <button class="carousel-control-prev" type="button" data-bs-target="#<?= $carouselId ?>" data-bs-slide="prev">
                  <span class="carousel-control-prev-icon"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#<?= $carouselId ?>" data-bs-slide="next">
                  <span class="carousel-control-next-icon"></span>
                </button>
              <?php endif; ?>
            </div>

            <!-- Details -->
            <div class="card-body d-flex flex-column">
              <h5 class="card-title"><?= htmlspecialchars($row['name']) ?></h5>
              <span class="badge bg-secondary mb-2"><?= htmlspecialchars($row['category']) ?></span>
              <p class="text-success fw-bold mb-2">₱<?= number_format($row['price'], 2) ?></p>

              <?php if ($isLoggedIn): ?>
                <button type="button" class="btn btn-sm btn-success w-100 mb-2" onclick="confirmAddToCart(<?= $row['id'] ?>)">🛒 Add to Cart</button>
                <form action="buy_now.php" method="post">
                  <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
                  <input type="hidden" name="quantity" value="1">
                  <button type="submit" class="btn btn-sm btn-warning w-100">⚡ Buy Now</button>
                </form>
              <?php else: ?>
                <a href="login.php" class="btn btn-sm btn-outline-primary w-100">🔐 Login to Buy</a>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Modal Viewer -->
        <div class="modal fade" id="<?= $modalId ?>" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
              <div class="modal-header border-0">
                <h5 class="modal-title"><?= htmlspecialchars($row['name']) ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div id="modalCarousel<?= $row['id'] ?>" class="carousel slide" data-bs-ride="carousel">
                  <div class="carousel-inner">
                    <?php foreach ($images as $index => $img): ?>
                      <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                        <div class="zoom-container">
                          <img src="assets/images/<?= htmlspecialchars($img) ?>" alt="Full Image"
                               onerror="this.src='assets/images/placeholder.jpg';"
                               onclick="toggleZoom(this)">
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                  <?php if (count($images) > 1): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#modalCarousel<?= $row['id'] ?>" data-bs-slide="prev">
                      <span class="carousel-control-prev-icon"></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#modalCarousel<?= $row['id'] ?>" data-bs-slide="next">
                      <span class="carousel-control-next-icon"></span>
                    </button>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="col-12">
        <div class="alert alert-warning text-center">
          ⚠️ No products found <?= $filtered ? "in <strong>" . htmlspecialchars($categoryFilter) . "</strong>." : "currently." ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Add to Cart Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="cart_add.php">
        <div class="modal-header">
          <h5 class="modal-title" id="confirmModalLabel">🛒 Confirm Add to Cart</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">Are you sure you want to add this product to your cart?</div>
        <input type="hidden" name="product_id" id="modalProductId">
        <input type="hidden" name="quantity" value="1">
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Yes, Add</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function confirmAddToCart(productId) {
  document.getElementById('modalProductId').value = productId;
  new bootstrap.Modal(document.getElementById('confirmModal')).show();
}

// Zoom toggle on modal image click
function toggleZoom(img) {
  img.classList.toggle('zoomed');
}
</script>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
