<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['quantity'])) {
  foreach ($_POST['quantity'] as $productId => $qty) {
    $qty = max(1, (int)$qty);
    $_SESSION['cart'][$productId] = $qty;
  }
}

header("Location: cart.php");
exit();
