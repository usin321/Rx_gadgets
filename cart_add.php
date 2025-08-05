<?php
session_start();
include 'db/db.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Check if product ID is sent
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $productId = (int) $_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? max(1, (int) $_POST['quantity']) : 1; // Ensure at least 1

    // Initialize cart
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Add or update cart item
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] += $quantity;
    } else {
        $_SESSION['cart'][$productId] = $quantity;
    }

    // Redirect to checkout if it's a "Buy Now"
    if (isset($_POST['buy_now'])) {
        header("Location: checkout.php");
        exit();
    }

    // Otherwise, redirect back to products page with success message
    $_SESSION['success'] = "✅ Product added to cart successfully!";
    header("Location: products.php");
    exit();
} else {
    // Invalid access
    $_SESSION['error'] = "❌ Invalid request. Please try again.";
    header("Location: products.php");
    exit();
}
