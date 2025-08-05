<?php
include '../db/db.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Allowed categories
$validCategories = ['iPhone', 'iPad', 'Accessory'];
$selectedCategory = isset($_GET['category']) && in_array($_GET['category'], $validCategories) ? $_GET['category'] : '';

$name = $model = $price = $desc = $category = '';
$success = $error = '';

if (isset($_POST['add'])) {
    $name = trim($_POST['name']);
    $model = trim($_POST['model']);
    $price = floatval($_POST['price']);
    $desc = trim($_POST['description']);
    $category = $_POST['category'];

    $uploadedImages = [];
    $imageFiles = $_FILES['images'];

    if (!in_array($category, $validCategories)) {
        $error = "❌ Invalid category selected!";
    } else {
        // Handle multiple image uploads
        for ($i = 0; $i < count($imageFiles['name']); $i++) {
            $imageName = time() . '_' . basename($imageFiles['name'][$i]); // Unique filename
            $tmpName = $imageFiles['tmp_name'][$i];
            $uploadPath = "../assets/images/" . $imageName;

            if (move_uploaded_file($tmpName, $uploadPath)) {
                $uploadedImages[] = $imageName;
            }
        }

        if (count($uploadedImages) === 0) {
            $error = "❌ At least one image upload is required.";
        } else {
            $imageJson = json_encode($uploadedImages);

            $stmt = $conn->prepare("INSERT INTO products (name, model, price, description, image, category) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdsss", $name, $model, $price, $desc, $imageJson, $category);

            if ($stmt->execute()) {
                $success = "✅ Product added successfully!";
                $name = $model = $price = $desc = $category = '';
            } else {
                $error = "❌ Failed to add product. Please try again.";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Add Product - RX GADGETS</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5" style="max-width: 600px;">
  <h3 class="mb-4">➕ Add New Product</h3>

  <?php if (!empty($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
  <?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

  <form method="POST" enctype="multipart/form-data">
    <div class="mb-3">
      <label>Category</label>
      <select name="category" class="form-select" required>
        <option value="">-- Select Category --</option>
        <?php foreach ($validCategories as $cat): ?>
          <option value="<?= $cat ?>" <?= (($category ?: $selectedCategory) === $cat) ? 'selected' : '' ?>><?= $cat ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-3">
      <label>Name</label>
      <input name="name" class="form-control" value="<?= htmlspecialchars($name) ?>" required>
    </div>

    <div class="mb-3">
      <label>Model</label>
      <input name="model" class="form-control" value="<?= htmlspecialchars($model) ?>" required>
    </div>

    <div class="mb-3">
      <label>Price (₱)</label>
      <input name="price" type="number" step="0.01" class="form-control" value="<?= htmlspecialchars($price) ?>" required>
    </div>

    <div class="mb-3">
      <label>Description</label>
      <textarea name="description" class="form-control" rows="4" required><?= htmlspecialchars($desc) ?></textarea>
    </div>

    <div class="mb-3">
      <label>Product Images</label>
      <input type="file" name="images[]" class="form-control" accept="image/*" multiple required>
      <small class="text-muted">Hold Ctrl (Windows) or Command (Mac) to select multiple images.</small>
    </div>

    <button name="add" class="btn btn-primary w-100">💾 Save Product</button>
  </form>

  <!-- ✅ Correct back button -->
  <a href="products.php" class="btn btn-secondary w-100 mt-3">← Back to Products</a>
</div>
</body>
</html>
