<?php
session_start();
include 'header.php';
include 'db/db.php';

$isLoggedIn = isset($_SESSION['user']);
$userName = $isLoggedIn ? htmlspecialchars($_SESSION['user']['name'] ?? 'User') : null;

// Get search term
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
?>

<style>
  .hero {
    background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)),
                url('images/hero-banner.jpg') center/cover no-repeat;
    color: white;
    padding: 80px 20px;
    border-radius: 10px;
  }

  .hero h1,
  .hero p {
    text-shadow: 2px 2px 4px rgba(0,0,0,0.7);
  }

  .product-card img {
    height: 180px;
    object-fit: cover;
    border-radius: 5px;
    cursor: pointer;
  }

  .btn-custom {
    min-width: 130px;
  }

  .carousel-item img {
    border-radius: 5px;
  }

  .product-card {
    transition: transform 0.2s ease-in-out;
  }

  .product-card:hover {
    transform: scale(1.02);
  }

  .card-body h6 {
    font-size: 1rem;
    font-weight: 600;
  }

  .card-body p {
    margin-bottom: 0;
    color: #333;
    font-weight: 500;
  }
</style>

<!-- Hero Section -->
<div class="container mt-4">
  <div class="hero text-center">
    <h1 class="display-4 fw-bold">Welcome to RX GADGETS<?= $isLoggedIn ? ", {$userName}!" : "!" ?></h1>
    <p class="lead">Premium iPhones, iPads & Accessories at unbeatable prices</p>
    <a href="products.php" class="btn btn-warning btn-lg mt-3 btn-custom">🛒 Shop Now</a>
  </div>
</div>

<!-- Product Search -->
<div class="container mt-5 text-center">
  <h4 class="fw-bold">🔎 Find a Product</h4>
  <form method="get" class="d-flex justify-content-center mt-3">
    <input type="text" name="search" placeholder="Search by product name..." value="<?= htmlspecialchars($searchTerm) ?>" class="form-control form-control-lg w-50 me-2" required>
    <button type="submit" class="btn btn-primary btn-lg">Search</button>
  </form>
</div>

<!-- Products Display -->
<div class="container mt-5">
  <h4 class="mb-4 text-center fw-bold">
    <?= $searchTerm ? '🔍 Results for "' . htmlspecialchars($searchTerm) . '"' : '📦 All Products' ?>
  </h4>
  <div class="row">
    <?php
    if ($searchTerm) {
      $stmt = $conn->prepare("SELECT * FROM products WHERE name LIKE ? ORDER BY name ASC");
      $likeTerm = "%$searchTerm%";
      $stmt->bind_param("s", $likeTerm);
    } else {
      $stmt = $conn->prepare("SELECT * FROM products ORDER BY name ASC");
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0):
      while ($row = $result->fetch_assoc()):
        $images = json_decode($row['image'], true);
        if (!$images || !is_array($images)) $images = ['placeholder.jpg'];
        $carouselId = 'carousel_' . $row['id'];
    ?>
        <div class="col-md-3 col-6 mb-4">
          <div class="card product-card shadow-sm h-100 d-flex flex-column">
            <div id="<?= $carouselId ?>" class="carousel slide mb-2" data-bs-ride="carousel">
              <div class="carousel-inner">
                <?php foreach ($images as $index => $img): ?>
                  <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                    <img src="assets/images/<?= htmlspecialchars($img) ?>" class="d-block w-100" alt="<?= htmlspecialchars($row['name']) ?>" data-bs-toggle="modal" data-bs-target="#modal_<?= $row['id'] ?>">
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
            <div class="card-body text-center d-flex flex-column">
              <h6><?= htmlspecialchars($row['name']) ?></h6>
              <p class="text-muted">₱<?= number_format($row['price'], 2) ?></p>
            </div>
          </div>
        </div>

        <!-- Modal for All Products -->
        <div class="modal fade" id="modal_<?= $row['id'] ?>" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bg-dark text-white">
              <div class="modal-header border-0">
                <h5 class="modal-title"><?= htmlspecialchars($row['name']) ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <div id="modalCarousel_<?= $row['id'] ?>" class="carousel slide" data-bs-ride="carousel">
                  <div class="carousel-inner">
                    <?php foreach ($images as $index => $img): ?>
                      <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                        <img src="assets/images/<?= htmlspecialchars($img) ?>" class="d-block w-100" alt="Image">
                      </div>
                    <?php endforeach; ?>
                  </div>
                  <?php if (count($images) > 1): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#modalCarousel_<?= $row['id'] ?>" data-bs-slide="prev">
                      <span class="carousel-control-prev-icon"></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#modalCarousel_<?= $row['id'] ?>" data-bs-slide="next">
                      <span class="carousel-control-next-icon"></span>
                    </button>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
    <?php endwhile; else: ?>
      <div class="col-12">
        <div class="alert alert-info text-center">No products found.</div>
      </div>
    <?php endif;
    $stmt->close(); ?>
  </div>
</div>

<!-- Featured Products -->
<div class="container mt-5">
  <h4 class="mb-4 fw-bold">🔥 Featured Products</h4>
  <div class="row">
    <?php
    $sql = "
      SELECT p.*, SUM(oi.quantity) as total_sold
      FROM order_items oi
      JOIN products p ON oi.product_id = p.id
      GROUP BY p.id
      ORDER BY total_sold DESC
      LIMIT 10
    ";
    $topSelling = $conn->query($sql);
    $products = [];

    if ($topSelling && $topSelling->num_rows > 0) {
      while ($row = $topSelling->fetch_assoc()) {
        $products[] = $row;
      }

      shuffle($products);
      $featured = array_slice($products, 0, 4);

      foreach ($featured as $item):
        $images = json_decode($item['image'], true);
        if (!$images || !is_array($images)) $images = ['placeholder.jpg'];
        $carouselId = 'featured_' . $item['id'];
    ?>
        <div class="col-md-3 col-6 mb-4">
          <div class="card product-card shadow-sm h-100 d-flex flex-column">
            <div id="<?= $carouselId ?>" class="carousel slide mb-2" data-bs-ride="carousel">
              <div class="carousel-inner">
                <?php foreach ($images as $index => $img): ?>
                  <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                    <img src="assets/images/<?= htmlspecialchars($img) ?>" class="d-block w-100" alt="<?= htmlspecialchars($item['name']) ?>" data-bs-toggle="modal" data-bs-target="#modal_feat_<?= $item['id'] ?>">
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
            <div class="card-body text-center d-flex flex-column">
              <h6><?= htmlspecialchars($item['name']) ?></h6>
              <p class="text-muted">₱<?= number_format($item['price'], 2) ?></p>
            </div>
          </div>
        </div>

        <!-- Modal for Featured Product -->
        <div class="modal fade" id="modal_feat_<?= $item['id'] ?>" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bg-dark text-white">
              <div class="modal-header border-0">
                <h5 class="modal-title"><?= htmlspecialchars($item['name']) ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <div id="modalCarousel_feat_<?= $item['id'] ?>" class="carousel slide" data-bs-ride="carousel">
                  <div class="carousel-inner">
                    <?php foreach ($images as $index => $img): ?>
                      <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                        <img src="assets/images/<?= htmlspecialchars($img) ?>" class="d-block w-100" alt="Image">
                      </div>
                    <?php endforeach; ?>
                  </div>
                  <?php if (count($images) > 1): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#modalCarousel_feat_<?= $item['id'] ?>" data-bs-slide="prev">
                      <span class="carousel-control-prev-icon"></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#modalCarousel_feat_<?= $item['id'] ?>" data-bs-slide="next">
                      <span class="carousel-control-next-icon"></span>
                    </button>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
    <?php endforeach; } else { ?>
      <div class="col-12">
        <div class="alert alert-info text-center">No featured products available.</div>
      </div>
    <?php } ?>
  </div>
</div>

<!-- CTA -->
<div class="container mt-5 text-center mb-5">
  <h4>🛍️ Ready to experience premium gadgets?</h4>
  <?php if (!$isLoggedIn): ?>
    <a href="register.php" class="btn btn-outline-success btn-lg mt-3 btn-custom">Create Account</a>
    <a href="login.php" class="btn btn-outline-primary btn-lg mt-3 ms-2 btn-custom">Login</a>
  <?php endif; ?>
</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
