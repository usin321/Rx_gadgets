<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id']) && isset($_POST['quantity'])) {
    $product_id = (int) $_POST['product_id'];
    $quantity = (int) $_POST['quantity'];

    if ($product_id > 0 && $quantity > 0) {
        $_SESSION['buy_now'] = [
            'product_id' => $product_id,
            'quantity' => $quantity
        ];

        header("Location: checkout.php?buynow=1");
        exit();
    }
}

header("Location: products.php");
exit();
?>
