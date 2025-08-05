<?php
session_start();
include 'header.php';
include 'db/db.php';

if (!isset($_SESSION['user'])) {
  echo "<div class='container mt-5'><div class='alert alert-warning text-center'>
          ⚠️ Please <a href='login.php'>log in</a> to proceed with checkout.
        </div></div>";
  include 'footer.php';
  exit();
}

$user_id = $_SESSION['user']['id'];
$isBuyNow = isset($_GET['buynow']) && isset($_SESSION['buy_now']);

if (isset($_GET['success']) && $_GET['success'] == 1) {
  echo "<div class='container mt-5 mb-5'>
          <div class='card text-center p-5 shadow-sm'>
            <h2 class='text-success'>✅ Thank you for your order!</h2>
            <p class='fs-5'>Your order has been placed successfully. We will process it shortly.</p>
            <a href='products.php' class='btn btn-primary mt-3'>🛒 Continue Shopping</a>
          </div>
        </div>";
  include 'footer.php';
  exit();
}

// Handle quantity update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_qty'])) {
  foreach ($_POST['qty'] as $id => $newQty) {
    $newQty = max(1, intval($newQty));
    if ($isBuyNow && isset($_SESSION['buy_now']['product_id']) && $_SESSION['buy_now']['product_id'] == $id) {
      $_SESSION['buy_now']['quantity'] = $newQty;
    } elseif (isset($_SESSION['cart'][$id])) {
      $_SESSION['cart'][$id] = $newQty;
    }
  }
  header("Location: checkout.php");
  exit();
}

if (!$isBuyNow && (!isset($_SESSION['cart']) || empty($_SESSION['cart']))) {
  echo "<div class='container mt-5'><div class='alert alert-info'>
          Your cart is empty. <a href='products.php'>Go back to shop</a>.
        </div></div>";
  include 'footer.php';
  exit();
}

// Load saved addresses
$addrStmt = $conn->prepare("SELECT id, fullname, mobile, altmobile, address, city, province, region FROM orders WHERE user_id = ? AND status = 'Draft'");
$addrStmt->bind_param("i", $user_id);
$addrStmt->execute();
$addrList = $addrStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$addrStmt->close();

$productTotal = 0;
$cartItems = [];

if ($isBuyNow) {
  $buyNowId = $_SESSION['buy_now']['product_id'];
  $qty = $_SESSION['buy_now']['quantity'];
  $stm = $conn->prepare("SELECT id, name, price FROM products WHERE id = ?");
  $stm->bind_param("i", $buyNowId);
  $stm->execute();
  $item = $stm->get_result()->fetch_assoc();
  $stm->close();
  if ($item) {
    $item['qty'] = $qty;
    $item['subtotal'] = $item['price'] * $qty;
    $productTotal += $item['subtotal'];
    $cartItems[] = $item;
  }
} else {
  foreach ($_SESSION['cart'] as $id => $qty) {
    $stm = $conn->prepare("SELECT id, name, price FROM products WHERE id = ?");
    $stm->bind_param("i", $id);
    $stm->execute();
    $item = $stm->get_result()->fetch_assoc();
    $stm->close();
    if ($item) {
      $item['qty'] = $qty;
      $item['subtotal'] = $item['price'] * $qty;
      $productTotal += $item['subtotal'];
      $cartItems[] = $item;
    }
  }
}

$error = '';
$proof = null;
$uploadDir = 'uploads/proofs/';

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
  $selectedAddressId = intval($_POST['select_address'] ?? 0);
  $selectedAddr = null;

  foreach ($addrList as $addr) {
    if ($addr['id'] == $selectedAddressId) {
      $selectedAddr = $addr;
      break;
    }
  }

  if (!$selectedAddr) {
    $error = "❌ Please select a valid saved address.";
  } else {
    $fullname  = $selectedAddr['fullname'];
    $mobile    = $selectedAddr['mobile'];
    $altmobile = $selectedAddr['altmobile'];
    $address   = $selectedAddr['address'];
    $city      = $selectedAddr['city'];
    $province  = $selectedAddr['province'];
    $region    = $selectedAddr['region'];
  }

  $delivery = trim($_POST['delivery'] ?? '');
  $payment  = trim($_POST['payment'] ?? '');

  if (!$error && (!$delivery || !$payment)) {
    $error = "❌ Please select delivery and payment method.";
  }

  if (!$error && in_array($payment, ['GCash', 'Bank Transfer'])) {
    if (isset($_FILES['proof']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
      $ext = strtolower(pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION));
      if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
        $error = "❌ Only JPG/PNG allowed.";
      } elseif ($_FILES['proof']['size'] > 5*1024*1024) {
        $error = "❌ Max file size is 5MB.";
      } else {
        $filename = uniqid('proof_', true) . "." . $ext;
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        if (!move_uploaded_file($_FILES['proof']['tmp_name'], $uploadDir . $filename)) {
          $error = "❌ Upload failed.";
        } else {
          $proof = $filename;
        }
      }
    } else {
      $error = "❌ Please upload proof of payment.";
    }
  }

  if (!$error) {
    $delivery_fee = ($delivery === 'Same Day') ? 200 : (($delivery === 'Standard') ? 100 : 0);
    $stm = $conn->prepare("INSERT INTO orders (user_id, fullname, email, mobile, altmobile, address, region, province, city, delivery_method, payment_method, payment_proof, total, delivery_fee, order_date, status) VALUES (?, ?, '', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'Pending')");
    $stm->bind_param("isssssssssssd", $user_id, $fullname, $mobile, $altmobile, $address, $region, $province, $city, $delivery, $payment, $proof, $productTotal, $delivery_fee);
    $stm->execute();
    $order_id = $stm->insert_id;
    $stm->close();

    $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($cartItems as $item) {
      $itemStmt->bind_param("iiid", $order_id, $item['id'], $item['qty'], $item['price']);
      $itemStmt->execute();
    }
    $itemStmt->close();

    if ($isBuyNow) unset($_SESSION['buy_now']);
    else unset($_SESSION['cart']);

    header("Location: checkout.php?success=1");
    exit();
  }
}
?>

<!-- HTML -->
<div class="container mt-5 mb-5">
  <h2 class="mb-4">📋 Checkout</h2>

  <!-- ✅ Order Summary -->
  <?php if (!empty($cartItems)): ?>
    <form method="POST">
      <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light">
          <strong>🧾 Order Summary (Adjust Quantities)</strong>
        </div>
        <ul class="list-group list-group-flush">
          <?php foreach ($cartItems as $item): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <div>
                <strong><?= htmlspecialchars($item['name']) ?></strong><br>
                <small>₱<?= number_format($item['price'], 2) ?> × 
                  <select name="qty[<?= $item['id'] ?>]" class="form-select d-inline w-auto">
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                      <option value="<?= $i ?>" <?= ($item['qty'] == $i) ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                  </select>
                </small>
              </div>
              <span class="text-end fw-bold">₱<?= number_format($item['subtotal'], 2) ?></span>
            </li>
          <?php endforeach; ?>
          <li class="list-group-item d-flex justify-content-between align-items-center bg-light">
            <strong>Total</strong>
            <strong>₱<?= number_format($productTotal, 2) ?></strong>
          </li>
        </ul>
      </div>
      <button type="submit" name="update_qty" class="btn btn-outline-primary mb-4">🔄 Update Quantities</button>
    </form>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- ✅ Checkout Form -->
  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="place_order" value="1">
    <div class="mb-3">
      <label>📍 Select Saved Address</label>
      <select name="select_address" class="form-select" required>
        <option value="">-- Choose --</option>
        <?php foreach ($addrList as $a): ?>
          <option value="<?= $a['id'] ?>">
            <?= htmlspecialchars($a['fullname']) ?> — <?= htmlspecialchars($a['address']) ?>, <?= htmlspecialchars($a['city']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <div class="form-text">To add/edit addresses, go to <a href="account.php">My Account</a>.</div>
    </div>

    <div class="mb-3">
      <label>🚚 Delivery Method</label>
      <select name="delivery" class="form-select" required>
        <option value="">-- Select --</option>
        <option value="Standard">Standard (₱100)</option>
        <option value="Same Day">Same Day (₱200)</option>
        <option value="Pick-Up">Pick-Up (₱0)</option>
      </select>
    </div>

    <div class="mb-3">
      <label>💳 Payment Method</label>
      <select name="payment" class="form-select" onchange="toggleProof(this.value)" required>
        <option value="">-- Select --</option>
        <option value="Cash on Delivery">Cash on Delivery</option>
        <option value="GCash">GCash</option>
        <option value="Bank Transfer">Bank Transfer</option>
      </select>
    </div>

    <div class="mb-3" id="proofBox" style="display:none;">
      <label>Upload Payment Proof</label>
      <input type="file" name="proof" class="form-control" accept=".jpg,.jpeg,.png">
    </div>

    <button type="submit" class="btn btn-success w-100">✅ Place Order</button>
  </form>
</div>

<script>
function toggleProof(val) {
  document.getElementById('proofBox').style.display = (val === 'GCash' || val === 'Bank Transfer') ? 'block' : 'none';
}
</script>

<?php include 'footer.php'; ?>
