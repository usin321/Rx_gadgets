<?php
session_start();
include 'header.php';
include 'db/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$userId = $_SESSION['user_id'];

// Fetch complete user profile info
$stmt = $conn->prepare("SELECT id, username, email, name, bio, gender, birthday, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>User not found.</div></div>";
    include 'footer.php';
    exit();
}


// Delete address
if (isset($_GET['delete_address']) && is_numeric($_GET['delete_address'])) {
    $delId = intval($_GET['delete_address']);
    $chk = $conn->prepare("DELETE FROM orders WHERE id = ? AND user_id = ? AND status = 'Draft'");
    $chk->bind_param("ii", $delId, $userId);
    $chk->execute();
    $chk->close();
    header("Location: account.php");
    exit();
}

// Save address
if (isset($_POST['save_address'])) {
    $addressId     = intval($_POST['address_id'] ?? 0);
    $fullname      = trim($_POST['fullname']);
    $mobile        = trim($_POST['mobile']);
    $altmobile     = trim($_POST['altmobile']);
    $address       = trim($_POST['address']);
    $streetDetails = trim($_POST['street_details']);
    $barangay      = trim($_POST['barangay']);
    $city          = trim($_POST['city']);
    $province      = trim($_POST['province']);
    $region        = trim($_POST['region']);
    $postalCode    = trim($_POST['postal_code']);
    $label         = trim($_POST['label']);

    if ($fullname && $mobile && $address && $city && $province && $region && preg_match('/^\d{10,15}$/', $mobile)) {
        if ($addressId) {
            $upd = $conn->prepare("UPDATE orders SET fullname=?, mobile=?, altmobile=?, address=?, street_details=?, barangay=?, city=?, province=?, region=?, postal_code=?, label=? WHERE id=? AND user_id=? AND status='Draft'");
            $upd->bind_param("sssssssssssii", $fullname, $mobile, $altmobile, $address, $streetDetails, $barangay, $city, $province, $region, $postalCode, $label, $addressId, $userId);
            $upd->execute();
            $upd->close();
        } else {
            $ins = $conn->prepare("INSERT INTO orders (user_id, fullname, mobile, altmobile, address, street_details, barangay, city, province, region, postal_code, label, delivery_method, payment_method, total, delivery_fee, order_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '', '', 0, 0, NOW(), 'Draft')");
            $ins->bind_param("isssssssssss", $userId, $fullname, $mobile, $altmobile, $address, $streetDetails, $barangay, $city, $province, $region, $postalCode, $label);
            $ins->execute();
            $ins->close();
        }
        header("Location: account.php");
        exit();
    } else {
        $error = "❌ All fields are required and mobile must have 10–15 digits.";
    }
}

// Load addresses
$addressStmt = $conn->prepare("SELECT id, fullname, mobile, altmobile, address, street_details, barangay, city, province, region, postal_code, label FROM orders WHERE user_id = ? AND status = 'Draft' ORDER BY id DESC");
$addressStmt->bind_param("i", $userId);
$addressStmt->execute();
$userAddresses = $addressStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$addressStmt->close();

// Active address
$activeAddrStmt = $conn->prepare("SELECT fullname, mobile, altmobile, address, street_details, barangay, city, province, region, postal_code, label FROM orders WHERE user_id = ? AND status <> 'Draft' ORDER BY id DESC LIMIT 1");
$activeAddrStmt->bind_param("i", $userId);
$activeAddrStmt->execute();
$activeAddress = $activeAddrStmt->get_result()->fetch_assoc();
$activeAddrStmt->close();

// Load orders
$orderStatuses = ['Pending', 'Paid', 'To Ship', 'To Receive', 'Completed', 'Cancelled'];
$ordersByStatus = [];
foreach ($orderStatuses as $status) {
    $stmt = $conn->prepare("
        SELECT o.id, o.order_date, o.status, SUM(oi.quantity * p.price) AS total
        FROM orders o
        JOIN order_items oi ON oi.order_id = o.id
        JOIN products p ON oi.product_id = p.id
        WHERE o.user_id = ? AND o.status = ?
        GROUP BY o.id, o.order_date, o.status
        ORDER BY o.order_date DESC
    ");
    $stmt->bind_param("is", $userId, $status);
    $stmt->execute();
    $ordersByStatus[$status] = $stmt->get_result();
    $stmt->close();
}
?>

<!-- Continue to Part 2 for HTML + Address Form + Order History UI -->
<div class="container mt-5 mb-5">
  <h2 class="mb-4">👤 My Account</h2>

  <div class="card mb-4">
  <div class="card-body">
    <h5 class="card-title">👤 My Profile</h5>

    <div class="text-end">
      <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">✏️ Edit Profile</button>
    </div>

    <p><strong>Full Name:</strong> <?= htmlspecialchars($user['name']) ?></p>
    <p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
    <p><strong>Phone Number:</strong> <?= htmlspecialchars($user['phone'] ?? 'Not set') ?></p>
    <p><strong>Gender:</strong> <?= htmlspecialchars($user['gender'] ?? 'Not set') ?></p>
    <p><strong>Birthday:</strong> <?= htmlspecialchars($user['birthday'] ?? 'Not set') ?></p>
    <p><strong>Bio:</strong><br><?= nl2br(htmlspecialchars($user['bio'] ?? '')) ?></p>

    <a href="change_password.php" class="btn btn-sm btn-outline-warning mt-2">🔒 Change Password</a>
  </div>
</div>



  <!-- Saved Addresses -->
  <div class="card mb-4">
    <div class="card-body">
      <h5 class="card-title">📍 My Saved Addresses</h5>

      <?php if (!empty($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

      <?php if ($activeAddress): ?>
        <p><strong>Most Recent Used Address:</strong><br>
        <?=htmlspecialchars($activeAddress['label'])?> — <?=htmlspecialchars($activeAddress['fullname'])?>, <?=htmlspecialchars($activeAddress['address'])?>, <?=htmlspecialchars($activeAddress['street_details'])?>, <?=htmlspecialchars($activeAddress['barangay'])?>, <?=htmlspecialchars($activeAddress['city'])?>, <?=htmlspecialchars($activeAddress['province'])?>, <?=htmlspecialchars($activeAddress['region'])?>, <?=htmlspecialchars($activeAddress['postal_code'])?></p>
      <?php endif; ?>

      <?php if (!empty($userAddresses)): ?>
        <label for="addressSelect">Select Saved Address:</label>
        <select class="form-select mb-3" id="addressSelect" onchange="showAddressDetails(this.value)">
          <option value="">-- Choose an address --</option>
          <?php foreach ($userAddresses as $addr): ?>
            <option value="addr<?= $addr['id'] ?>">
              <?=htmlspecialchars($addr['label'])?> — <?=htmlspecialchars($addr['fullname'])?>, <?=htmlspecialchars($addr['address'])?>, <?=htmlspecialchars($addr['city'])?>
            </option>
          <?php endforeach; ?>
        </select>

        <?php foreach ($userAddresses as $addr): ?>
        <div class="address-block border p-3 mb-3 rounded shadow-sm" id="addr<?= $addr['id'] ?>" style="display: none;">
          <p><strong><?=htmlspecialchars($addr['label'])?> — <?=htmlspecialchars($addr['fullname'])?></strong></p>
          <p><?=htmlspecialchars($addr['mobile'])?><?= $addr['altmobile'] ? ' / '.htmlspecialchars($addr['altmobile']) : '' ?></p>
          <p><?=htmlspecialchars($addr['address'])?>, <?=htmlspecialchars($addr['street_details'])?>, <?=htmlspecialchars($addr['barangay'])?>, <?=htmlspecialchars($addr['city'])?>, <?=htmlspecialchars($addr['province'])?>, <?=htmlspecialchars($addr['region'])?>, <?=htmlspecialchars($addr['postal_code'])?></p>
          <div class="text-end">
            <a href="?edit_address=<?= $addr['id'] ?>" class="btn btn-sm btn-secondary">✏️ Edit</a>
            <a href="?delete_address=<?= $addr['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this address?')">🗑 Delete</a>
          </div>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="text-muted">No saved draft addresses yet.</p>
      <?php endif; ?>

      <a href="?edit_address=0" class="btn btn-sm btn-success">➕ Add New Address</a>
    </div>
  </div>

  <!-- Address Form -->
  <?php if (isset($_GET['edit_address'])):
    $editId = intval($_GET['edit_address']);
    $editAddr = null;
    foreach ($userAddresses as $a) if ($a['id'] === $editId) { $editAddr = $a; break; }
  ?>
  <div class="card mb-4">
    <div class="card-body">
      <h5 class="card-title"><?= $editAddr ? '✏️ Edit Address' : '➕ Add Address' ?></h5>
      <form method="POST">
        <input type="hidden" name="address_id" value="<?= $editAddr['id'] ?? 0 ?>">
        <div class="row g-2">
          <div class="col-md-6"><input name="fullname" class="form-control" placeholder="Full Name" value="<?= htmlspecialchars($editAddr['fullname'] ?? '') ?>" required></div>
          <div class="col-md-6"><input name="mobile" type="tel" pattern="\d{10,15}" class="form-control" placeholder="Mobile" value="<?= htmlspecialchars($editAddr['mobile'] ?? '') ?>" required></div>
          <div class="col-md-6"><input name="altmobile" type="tel" pattern="\d{10,15}" class="form-control" placeholder="Alternate Mobile (opt)" value="<?= htmlspecialchars($editAddr['altmobile'] ?? '') ?>"></div>
          <div class="col-md-6"><input name="label" class="form-control" placeholder="Address Label (e.g. Home, Office)" value="<?= htmlspecialchars($editAddr['label'] ?? '') ?>"></div>
          <div class="col-md-12"><textarea name="address" class="form-control" placeholder="Building / House No. / Street" required><?= htmlspecialchars($editAddr['address'] ?? '') ?></textarea></div>
          <div class="col-md-6"><input name="street_details" class="form-control" placeholder="Street Details (opt)" value="<?= htmlspecialchars($editAddr['street_details'] ?? '') ?>"></div>

          <div class="col-md-6">
            <select name="region" id="region" class="form-select" required></select>
          </div>
          <div class="col-md-6">
            <select name="province" id="province" class="form-select" required></select>
          </div>
          <div class="col-md-6">
            <select name="city" id="city" class="form-select" required></select>
          </div>
          <div class="col-md-6">
            <select name="barangay" id="barangay" class="form-select" required></select>
          </div>

          <div class="col-md-4"><input name="postal_code" class="form-control" placeholder="Postal Code" value="<?= htmlspecialchars($editAddr['postal_code'] ?? '') ?>"></div>
        </div>
        <button type="submit" name="save_address" class="btn btn-success mt-3">💾 Save Address</button>
        <a href="account.php" class="btn btn-secondary mt-3">Cancel</a>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <!-- Order History -->
  <div class="card mb-4"><div class="card-body"><h5 class="card-title">🧾 Order History</h5>
    <ul class="nav nav-tabs mb-3">
      <?php foreach ($orderStatuses as $i => $status): ?>
        <li class="nav-item"><a class="nav-link <?= $i===0?'active':'' ?>" data-bs-toggle="tab" href="#tab<?= $i ?>"><?= $status ?></a></li>
      <?php endforeach; ?>
    </ul>
    <div class="tab-content">
      <?php foreach ($orderStatuses as $i => $status): ?>
      <div class="tab-pane fade <?= $i===0?'show active':'' ?>" id="tab<?= $i ?>">
        <?php if ($ordersByStatus[$status]->num_rows > 0): ?>
        <table class="table table-sm">
          <thead><tr><th>#</th><th>Date</th><th>Status</th><th>Total</th><th>Action</th></tr></thead>
          <tbody>
            <?php while ($order = $ordersByStatus[$status]->fetch_assoc()): ?>
            <tr>
              <td>#<?= $order['id'] ?></td>
              <td><?= date("M d, Y", strtotime($order['order_date'])) ?></td>
              <td><span class="badge bg-<?= $status==='Cancelled'?'danger':'secondary' ?>"><?= $order['status'] ?></span></td>
              <td>₱<?= number_format($order['total'],2) ?></td>
              <td><a href="order_details.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-info">View</a>
                <?php if ($status==='Pending'): ?>
                  <a href="?cancel=<?= $order['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Cancel this order?')">Cancel</a>
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
  </div></div>

  <div class="text-end"><a href="logout.php" class="btn btn-danger">🚪 Logout</a></div>
</div>

<!-- JS for Address Handling -->
<script>
function showAddressDetails(sel) {
  document.querySelectorAll('.address-block').forEach(d => d.style.display = 'none');
  if (sel) {
    const el = document.getElementById(sel);
    if (el) el.style.display = 'block';
  }
}

document.addEventListener("DOMContentLoaded", function () {
  const select = document.getElementById("addressSelect");
  if (select && select.value) showAddressDetails(select.value);

  // Get PHP values from edit form (if available)
  const currentRegion   = "<?= isset($editAddr['region']) ? addslashes($editAddr['region']) : '' ?>";
  const currentProvince = "<?= isset($editAddr['province']) ? addslashes($editAddr['province']) : '' ?>";
  const currentCity     = "<?= isset($editAddr['city']) ? addslashes($editAddr['city']) : '' ?>";
  const currentBarangay = "<?= isset($editAddr['barangay']) ? addslashes($editAddr['barangay']) : '' ?>";

  // Load JSON for cascading location dropdowns

  
  // fetch('assets/data/location.json')
  //   .then(res => res.json())
  //   .then(data => {
  //     const regionSelect = document.getElementById("region");
  //     const provinceSelect = document.getElementById("province");
  //     const citySelect = document.getElementById("city");
  //     const barangaySelect = document.getElementById("barangay");

  //     function populate(select, items, selected = "") {
  //       select.innerHTML = '<option value="">Select</option>';
  //       items.forEach(i => {
  //         const opt = document.createElement("option");
  //         opt.value = i;
  //         opt.text = i;
  //         if (i === selected) opt.selected = true;
  //         select.appendChild(opt);
  //       });
  //     }



      const regions = Object.entries(data).map(([code, r]) => ({
        code: code,
        name: r.region_name
      }));

      populate(regionSelect, regions.map(r => r.name), currentRegion);

      if (currentRegion) {
        const selectedRegion = regions.find(r => r.name === currentRegion);
        if (selectedRegion) {
          const provinces = Object.keys(data[selectedRegion.code].province_list);
          populate(provinceSelect, provinces, currentProvince);

          if (currentProvince) {
            const provinceData = data[selectedRegion.code].province_list[currentProvince];
            const municipalities = provinceData.municipality_list.map(m => Object.keys(m)[0]);
            populate(citySelect, municipalities, currentCity);

            if (currentCity) {
              const municipalityObj = provinceData.municipality_list.find(m => Object.keys(m)[0] === currentCity);
              if (municipalityObj) {
                const barangays = municipalityObj[currentCity].barangay_list;
                populate(barangaySelect, barangays, currentBarangay);
              }
            }
          }
        }
      }

      regionSelect.onchange = function () {
        const selectedRegion = regions.find(r => r.name === this.value);
        if (!selectedRegion) return;
        const provinces = Object.keys(data[selectedRegion.code].province_list);
        populate(provinceSelect, provinces);
        citySelect.innerHTML = barangaySelect.innerHTML = '<option value="">Select</option>';
      };

      provinceSelect.onchange = function () {
        const selectedRegion = regions.find(r => r.name === regionSelect.value);
        if (!selectedRegion) return;
        const provinceData = data[selectedRegion.code].province_list[this.value];
        const municipalities = provinceData.municipality_list.map(m => Object.keys(m)[0]);
        populate(citySelect, municipalities);
        barangaySelect.innerHTML = '<option value="">Select</option>';
      };

      citySelect.onchange = function () {
        const selectedRegion = regions.find(r => r.name === regionSelect.value);
        if (!selectedRegion) return;
        const provinceData = data[selectedRegion.code].province_list[provinceSelect.value];
        const municipalityObj = provinceData.municipality_list.find(m => Object.keys(m)[0] === this.value);
        if (municipalityObj) {
          const barangays = municipalityObj[this.value].barangay_list;
          populate(barangaySelect, barangays);
        }
      };
    });
});
</script>
<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="update_profile.php" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editProfileModalLabel">Edit My Profile</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body row g-2">
        <div class="col-md-12">
          <label>Full Name</label>
          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
        </div>
        <div class="col-md-12">
          <label>Bio</label>
          <textarea name="bio" class="form-control"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
        </div>
        <div class="col-md-6">
          <label>Gender</label>
          <select name="gender" class="form-select">
            <option value="">Select</option>
            <option value="Male" <?= isset($user['gender']) && $user['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= isset($user['gender']) && $user['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
            <option value="Other" <?= isset($user['gender']) && $user['gender'] == 'Other' ? 'selected' : '' ?>>Other</option>
          </select>
        </div>
        <div class="col-md-6">
          <label>Birthday</label>
          <input type="date" name="birthday" class="form-control" value="<?= htmlspecialchars($user['birthday'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label>Phone Number</label>
          <input type="tel" name="phone" class="form-control" pattern="\d{10,15}" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label>Email</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">💾 Save Changes</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>



<?php include 'footer.php'; ?>
