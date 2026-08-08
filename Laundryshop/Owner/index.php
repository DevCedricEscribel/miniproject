<?php
require_once '../config/config.php';

$pdo = getDbConnection();

$washPrice = '₱0/kg';
$dryPrice = '₱0/kg';
$foldPrice = '₱0/kg';
$deliveryPrice = '₱0';

try {
  $stmt = $pdo->prepare("
        SELECT `Wash`, `Dry`, `Fold`, `Delivery`
        FROM config
        WHERE id = 1
        LIMIT 1
    ");

  $stmt->execute();

  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($row) {
    $washPrice = '₱' . number_format($row['Wash'], 2) . '/kg';
    $dryPrice = '₱' . number_format($row['Dry'], 2) . '/kg';
    $foldPrice = '₱' . number_format($row['Fold'], 2) . '/kg';
    $deliveryPrice = '₱' . number_format($row['Delivery'], 2);
  }
} catch (PDOException $e) {
  echo "Error: " . $e->getMessage();
}

$stmt = $pdo->query("SELECT `Wash`, `Dry`, `Fold` FROM config WHERE id = 1");
$prices = $stmt->fetch(PDO::FETCH_ASSOC);

$washPrice = $prices['Wash'];
$dryPrice  = $prices['Dry'];
$foldPrice = $prices['Fold'];

if (isset($_POST['submit_order'])) {

  $customer_name = $_POST['customer_name'];
  $phone = $_POST['phone'];
  $weight = floatval($_POST['weight']);

  $wash = isset($_POST['wash']) ? 1 : 0;
  $dry  = isset($_POST['dry']) ? 1 : 0;
  $fold = isset($_POST['fold']) ? 1 : 0;

  // Generate tracking number
  $tracking_no = 'LDY-' . date('YmdHis');

  // Get prices
  $stmt = $pdo->query("SELECT `Wash`, `Dry`, `Fold` FROM config WHERE id = 1");
  $prices = $stmt->fetch(PDO::FETCH_ASSOC);

  $total = 0;

  if ($wash) {
    $total += $prices['Wash'] * $weight;
  }

  if ($dry) {
    $total += $prices['Dry'] * $weight;
  }

  if ($fold) {
    $total += $prices['Fold'] * $weight;
  }

  $payment_status = 'Unpaid';
  $laundry_status = 'Pending';

  $sql = "INSERT INTO laundry_orders
            (
                tracking_no,
                customer_name,
                phone,
                wash,
                dry,
                fold,
                weight,
                payment_status,
                laundry_status,
                total_price
            )
            VALUES
            (
                :tracking_no,
                :customer_name,
                :phone,
                :wash,
                :dry,
                :fold,
                :weight,
                :payment_status,
                :laundry_status,
                :total_price
            )";

  $stmt = $pdo->prepare($sql);

  $stmt->execute([
    ':tracking_no' => $tracking_no,
    ':customer_name' => $customer_name,
    ':phone' => $phone,
    ':wash' => $wash,
    ':dry' => $dry,
    ':fold' => $fold,
    ':weight' => $weight,
    ':payment_status' => $payment_status,
    ':laundry_status' => $laundry_status,
    ':total_price' => $total
  ]);

  echo "
        <div class='alert alert-success'>
            Order submitted successfully!<br>
            Tracking No: <strong>{$tracking_no}</strong>
        </div>
    ";
}

$order = null;

if (isset($_GET['tracking_no']) && !empty($_GET['tracking_no'])) {

  $tracking_no = trim($_GET['tracking_no']);

  $stmt = $pdo->prepare("
        SELECT *
        FROM laundry_orders
        WHERE tracking_no = :tracking_no
        LIMIT 1
    ");

  $stmt->execute([
    ':tracking_no' => $tracking_no
  ]);

  $order = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Laundry Shop System</title>

  <!-- Bootstrap 5 -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet" />

  <!-- Bootstrap Icons -->
  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />

  <style>
    body {
      background: #f5f7fb;
    }

    .hero {
      background: linear-gradient(135deg, #0d6efd, #4ea5ff);
      color: white;
      padding: 80px 0;
      border-radius: 0 0 30px 30px;
    }

    .service-card {
      transition: 0.3s;
      border: none;
      border-radius: 20px;
    }

    .service-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .tracking-box {
      border-radius: 20px;
    }

    .order-form {
      border-radius: 20px;
    }

    .status-badge {
      font-size: 14px;
    }

    footer {
      background: #0d6efd;
      color: white;
      padding: 20px;
    }
  </style>
</head>

<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow">
    <div class="container">
      <a class="navbar-brand fw-bold" href="#">
        <i class="bi bi-basket2-fill"></i> Laundry Shop
      </a>

      <button
        class="navbar-toggler"
        data-bs-toggle="collapse"
        data-bs-target="#nav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a href="#" class="nav-link">Home</a>
          </li>

          <li class="nav-item">
            <a href="#services" class="nav-link">Services</a>
          </li>

          <li class="nav-item">
            <a href="#track" class="nav-link">Track Order</a>
          </li>

          <li class="nav-item">
            <a href="#order" class="nav-link">New Order</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Hero -->
  <section class="hero text-center">
    <div class="container">
      <h1 class="display-4 fw-bold">Clean Clothes, Fast Service</h1>

      <p class="lead">Wash • Dry • Fold</p>

      <a href="#order" class="btn btn-light btn-lg"> Create Order </a>

      <a href="#track" class="btn btn-outline-light btn-lg"> Track Order </a>
    </div>
  </section>

  <!-- Services -->
  <?php include 'services.php'; ?>

  <!-- Track Order -->
  <?php include 'track_order.php'; ?>

  <!-- New Order -->
  <?php include 'new_order.php'; ?>

  <!-- Footer -->
  <footer class="text-center">© 2026 Laundry Shop Management System</footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>