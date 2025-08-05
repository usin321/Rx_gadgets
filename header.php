<?php
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}

$isLoggedIn = isset($_SESSION['user']);
$userName = $isLoggedIn ? htmlspecialchars($_SESSION['user']['name'] ?? 'User') : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>RX GADGETS | iOS Shop</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f4f4f4;
      font-family: Arial, sans-serif;
    }
    .navbar {
      margin-bottom: 20px;
    }
    .navbar-brand {
      font-weight: bold;
      font-size: 1.6rem;
      display: flex;
      align-items: center;
      gap: 10px;
      color: #fff;
      text-decoration: none;
    }
    .navbar-brand img {
      height: 38px;
      width: auto;
      border-radius: 6px;
      background-color: #fff;
      padding: 2px;
    }
    .card img {
      height: 250px;
      object-fit: cover;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      <img src="assets/images/logo.png" alt="RX GADGETS Logo" onerror="this.style.display='none';">
      <span>RX <strong>GADGETS</strong></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="index.php">🏠 Home</a></li>
        <li class="nav-item"><a class="nav-link" href="products.php">📱 Products</a></li>
        <li class="nav-item"><a class="nav-link" href="cart.php">🛒 Cart</a></li>
        <li class="nav-item"><a class="nav-link" href="checkout.php">✅ Checkout</a></li>
        <?php if ($isLoggedIn): ?>
          <li class="nav-item"><a class="nav-link" href="account.php">👤 My Account</a></li>
          <li class="nav-item"><a class="nav-link text-danger" href="logout.php">🚪 Logout</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="login.php">🔑 Login</a></li>
          <li class="nav-item"><a class="nav-link" href="register.php">📝 Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="container">
