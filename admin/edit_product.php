<?php
session_start();
include '../db/db.php';
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Validate and fetch product
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    echo "<div class='alert alert-danger'>Product not found!</div>";
    exit();
}

// Handle update
if (isset($_POST['update'])) {
    $name = $_POST['name'];
    $model = $_POST['model'];
    $price = $_POST['price'];
    $desc = $_POST['description'];

    // Handle image
    if (!empty($_FILES['image']['name'])) {
        $imageName = $_FILES['image']['name'];
        $tmpName = $_FILES['image']['tmp_name'];
        move_uploaded_file($tmpName, "../assets/images/$imageName");
    } else {
        $imageName = $product['image'];
    }

    // Update using prepared statement
    $update = $conn->prepare("UPDATE products SET name=?, model=?, price=?, description=?, image=? WHERE id=?");
    $update->bind_param("ssissi", $name, $model, $price, $desc, $imageName, $id);

    if ($update->execute()) {
        header("Location: dashboard.php?msg=updated");
        exit();
    } else {
        $error = "❌ Update failed!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Edit Product</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
  <h3 class="mb-4">✏️ Edit Product</h3>
  <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
  <form method="POST" enctype="multipart/form-data">
    <div class="mb-3">
      <label>Name</label>
      <input name="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>" required>
    </div>
    <div class="mb-3">
      <label>Model</label>
      <input name="model" class="form-control" value="<?= htmlspecialchars($product['model']) ?>" required>
    </div>
    <div class="mb-3">
      <label>Price</label>
      <input name="price" type="number" class="form-control" value="<?= htmlspecialchars($product['price']) ?>" required>
    </div>
    <div class="mb-3">
      <label>Description</label>
      <textarea name="description" class="form-control" required><?= htmlspecialchars($product['description']) ?></textarea>
    </div>
    <div class="mb-3">
      <label>Current Image</label><br>
      <img src="../assets/images/<?= htmlspecialchars($product['image']) ?>" width="100" alt="Current Image"><br><br>
      <input type="file" name="image" class="form-control">
    </div>
    <button name="update" class="btn btn-primary">Update Product</button>
    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
  </form>
</div>
</body>
</html>
