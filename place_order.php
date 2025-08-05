<?php
session_start();

if (!isset($_SESSION['user']) || !isset($_POST['product_id'])) {
    header("Location: products.php");
    exit();
}

// You can add real order saving logic here
$_SESSION['success'] = "🎉 Order placed successfully!";
unset($_SESSION['buy_now']);

header("Location: products.php");
exit();
?>
