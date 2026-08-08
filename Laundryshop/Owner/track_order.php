<section class="container py-4" id="track">
    <div class="card shadow tracking-box">
        <div class="card-body p-5">

            <h2 class="mb-4">
                <i class="bi bi-search"></i>
                Track Laundry Order
            </h2>

            <form method="GET">
                <div class="row">
                    <div class="col-md-9">
                        <input
                            type="text"
                            name="tracking_no"
                            class="form-control form-control-lg"
                            placeholder="Enter Tracking Number"
                            value="<?= htmlspecialchars($_GET['tracking_no'] ?? '') ?>"
                            required>
                    </div>

                    <div class="col-md-3">
                        <button class="btn btn-primary w-100 btn-lg">
                            Search
                        </button>
                    </div>
                </div>
            </form>

            <?php if (isset($_GET['tracking_no'])): ?>
                <hr>

                <?php if ($order): ?>

                    <div class="row mt-4">

                        <div class="col-md-6">
                            <p>
                                <strong>Customer:</strong>
                                <?= htmlspecialchars($order['customer_name']) ?>
                            </p>

                            <p>
                                <strong>Tracking #:</strong>
                                <?= htmlspecialchars($order['tracking_no']) ?>
                            </p>

                            <p>
                                <strong>Weight:</strong>
                                <?= $order['weight'] ?> kg
                            </p>

                            <p>
                                <strong>Total:</strong>
                                ₱<?= number_format($order['total_price'], 2) ?>
                            </p>

                            <p>
                                <strong>Services:</strong>

                                <?php
                                $services = [];

                                if ($order['wash']) $services[] = 'Wash';
                                if ($order['dry']) $services[] = 'Dry';
                                if ($order['fold']) $services[] = 'Fold';

                                echo implode(', ', $services);
                                ?>
                            </p>
                        </div>

                        <div class="col-md-6">

                            <p>
                                <strong>Payment:</strong>

                                <span class="badge bg-<?= $order['payment_status'] == 'Paid' ? 'success' : 'danger' ?>">
                                    <?= $order['payment_status'] ?>
                                </span>
                            </p>

                            <p>
                                <strong>Status:</strong>

                                <span class="badge bg-warning text-dark">
                                    <?= $order['laundry_status'] ?>
                                </span>
                            </p>

                            <p>
                                <strong>Date:</strong>
                                <?= date('F d, Y h:i A', strtotime($order['created_at'])) ?>
                            </p>

                        </div>

                    </div>

                <?php else: ?>

                    <div class="alert alert-danger mt-4">
                        Tracking number not found.
                    </div>

                <?php endif; ?>

            <?php endif; ?>

        </div>
    </div>
</section>