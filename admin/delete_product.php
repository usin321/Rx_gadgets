<?php
session_start();
include '../db/db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: products.php?error=invalid");
    exit();
}

$productId = intval($_GET['id']);

// Get image filename
$getImg = $conn->prepare("SELECT image FROM products WHERE id = ?");
$getImg->bind_param("i", $productId);
$getImg->execute();
$getImg->bind_result($imageFilename);
$getImg->fetch();
$getImg->close();

// Delete product from database
$delete = $conn->prepare("DELETE FROM products WHERE id = ?");
$delete->bind_param("i", $productId);

if ($delete->execute()) {
    $delete->close();

    // Delete image file if it exists
    $imagePath = "../assets/images/" . $imageFilename;
    if (file_exists($imagePath)) {
        unlink($imagePath);
    }

    header("Location: products.php?msg=deleted");
    exit();
} else {
    $delete->close();
    header("Location: products.php?error=failed");
    exit();
}
