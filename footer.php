<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>RX GADGETS</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    html, body {
      height: 100%;
    }

    body {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    main.container {
      flex: 1;
    }

    footer {
      background-color: #212529;
      color: #fff;
      font-size: 0.9rem;
      padding: 12px 0;
    }

    footer p, footer small {
      margin: 0;
    }
  </style>
</head>
<body>

  <!-- Main Content -->
  <main class="container mt-5">
    <h1 class="text-center">Welcome to RX GADGETS</h1>
    <p class="text-center">Your premium iOS mobile store in the Philippines.</p>
  </main>

  <!-- Footer -->
  <footer class="mt-auto shadow-sm">
    <div class="container text-center">
      <p>&copy; <?= date("Y") ?> <strong>RX GADGETS</strong> — iOS Mobile Shop PH</p>
      <small class="text-secondary">All rights reserved.</small>
    </div>
  </footer>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
