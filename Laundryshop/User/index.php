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
    <section class="container py-5" id="services">
        <h2 class="text-center mb-5 fw-bold">Our Services</h2>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card service-card shadow p-4 text-center">
                    <i class="bi bi-droplet-fill text-primary display-3"></i>

                    <h3 class="mt-3">Wash</h3>

                    <?php
                    echo '<h4 class="text-primary">' . $washPrice . '</h4>';
                    ?>

                    <p>Professional washing service for your clothes.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card service-card shadow p-4 text-center">
                    <i class="bi bi-sun-fill text-warning display-3"></i>

                    <h3 class="mt-3">Dry</h3>

                    <?php
                    echo '<h4 class="text-primary">' . $dryPrice . '</h4>';
                    ?>

                    <p>Fast and hygienic drying process.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card service-card shadow p-4 text-center">
                    <i class="bi bi-box-seam-fill text-success display-3"></i>

                    <h3 class="mt-3">Fold</h3>

                    <?php
                    echo '<h4 class="text-primary">' . $foldPrice . '</h4>';
                    ?>

                    <p>Neatly folded and organized clothes.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Track Order -->
    <section class="container py-4" id="track">
        <?php
        $customerName = 'N/A';
        $trackingNumber = '';
        $orderTotal = '₱0';
        $paymentStatus = 'Pending';
        $orderStatus = 'Pending';
        $paymentClass = 'bg-secondary';
        $statusClass = 'bg-secondary text-white';
        $searchMessage = '';

        $trackingInput = isset($_GET['tracking']) ? trim($_GET['tracking']) : '';

        try {
            $orderTables = ['orders', 'laundry_orders', 'order', 'laundry_order'];
            $orderTable = null;

            foreach ($orderTables as $table) {
                $checkStmt = $pdo->prepare("SHOW TABLES LIKE :table");
                $checkStmt->execute(['table' => $table]);
                if ($checkStmt->fetchColumn()) {
                    $orderTable = $table;
                    break;
                }
            }

            if ($orderTable !== null) {
                if ($trackingInput !== '') {
                    $stmt = $pdo->prepare(
                        "SELECT customer_name, tracking_number, total_amount, payment_status, status FROM {$orderTable} WHERE tracking_number = :tracking LIMIT 1"
                    );
                    $stmt->execute(['tracking' => $trackingInput]);
                } else {
                    $stmt = $pdo->query(
                        "SELECT customer_name, tracking_number, total_amount, payment_status, status FROM {$orderTable} LIMIT 1"
                    );
                }

                $order = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($order) {
                    $customerName = $order['customer_name'] ?? 'N/A';
                    $trackingNumber = $order['tracking_number'] ?? $trackingInput;
                    $orderTotal = '₱' . number_format($order['total_amount'] ?? 0, 2);
                    $paymentStatus = $order['payment_status'] ?? 'Pending';
                    $orderStatus = $order['status'] ?? 'Pending';

                    $paymentClass = strtolower($paymentStatus) === 'paid' ? 'bg-success' : 'bg-danger';

                    if (in_array(strtolower($orderStatus), ['drying', 'in progress', 'processing', 'washing'])) {
                        $statusClass = 'bg-warning text-dark';
                    } elseif (strtolower($orderStatus) === 'completed') {
                        $statusClass = 'bg-success';
                    } else {
                        $statusClass = 'bg-secondary text-white';
                    }
                } else {
                    $searchMessage = $trackingInput !== '' ? 'No order found for that tracking number.' : 'No orders available.';
                    if ($trackingInput !== '') {
                        $trackingNumber = $trackingInput;
                    }
                }
            } else {
                $searchMessage = 'Order table not found.';
            }
        } catch (PDOException $e) {
            $searchMessage = 'Unable to load order data.';
        }
        ?>

        <div class="card shadow tracking-box">
            <div class="card-body p-5">
                <h2 class="mb-4">
                    <i class="bi bi-search"></i>
                    Track Laundry Order
                </h2>

                <form method="get" class="row g-3">
                    <div class="col-md-9">
                        <input
                            type="text"
                            name="tracking"
                            class="form-control form-control-lg"
                            placeholder="Enter Tracking Number"
                            value="<?php echo htmlspecialchars($trackingInput); ?>" />
                    </div>

                    <div class="col-md-3">
                        <button class="btn btn-primary w-100 btn-lg" type="submit">Search</button>
                    </div>
                </form>

                <?php if ($searchMessage): ?>
                    <div class="alert alert-warning mt-4">
                        <?php echo htmlspecialchars($searchMessage); ?>
                    </div>
                <?php endif; ?>

                <hr />

                <div class="row mt-4">
                    <div class="col-md-6">
                        <p><strong>Customer:</strong> <?php echo htmlspecialchars($customerName); ?></p>
                        <p><strong>Tracking #:</strong> <?php echo htmlspecialchars($trackingNumber ?: 'N/A'); ?></p>
                        <p><strong>Total:</strong> <?php echo htmlspecialchars($orderTotal); ?></p>
                    </div>

                    <div class="col-md-6">
                        <p>
                            <strong>Payment:</strong>
                            <span class="badge <?php echo htmlspecialchars($paymentClass . ' status-badge'); ?>">
                                <?php echo htmlspecialchars($paymentStatus); ?>
                            </span>
                        </p>

                        <p>
                            <strong>Status:</strong>
                            <span class="badge <?php echo htmlspecialchars($statusClass . ' status-badge'); ?>">
                                <?php echo htmlspecialchars($orderStatus); ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- Footer -->
    <footer class="text-center">©2026 Laundry Shop Management System</footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>