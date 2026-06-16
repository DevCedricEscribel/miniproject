  <section class="container py-5" id="order">
      <div class="card shadow order-form">
          <div class="card-body p-5">
              <h2 class="mb-4">
                  <i class="bi bi-plus-circle"></i>
                  Create Laundry Order
              </h2>

              <form method="POST">
                  <div class="row">
                      <div class="col-md-6 mb-3">
                          <label class="form-label">Customer Name</label>
                          <input type="text" name="customer_name" class="form-control" required>
                      </div>

                      <div class="col-md-6 mb-3">
                          <label class="form-label">Phone Number</label>
                          <input type="text" name="phone" class="form-control" required>
                      </div>
                  </div>

                  <div class="mb-3">
                      <label class="form-label">Weight (kg)</label>
                      <input type="number" step="0.01" name="weight"
                          id="weight" class="form-control" required>
                  </div>

                  <h5 class="mt-4">Select Services</h5>

                  <div class="form-check">
                      <input class="form-check-input service"
                          type="checkbox"
                          name="wash"
                          id="wash"
                          value="1">
                      <label class="form-check-label">Wash (₱<?= $washPrice ?>/kg)</label>
                  </div>

                  <div class="form-check">
                      <input class="form-check-input service"
                          type="checkbox"
                          name="dry"
                          id="dry"
                          value="1">
                      <label class="form-check-label">Dry (₱<?= $dryPrice ?>/kg)</label>
                  </div>

                  <div class="form-check">
                      <input class="form-check-input service"
                          type="checkbox"
                          name="fold"
                          id="fold"
                          value="1">
                      <label class="form-check-label">Fold (₱<?= $foldPrice ?>/kg)</label>
                  </div>
                  <div class="alert alert-info mt-4">
                      Estimated Total:
                      <strong id="totalPrice">₱0.00</strong>
                  </div>

                  <script>
                      const weightInput = document.getElementById('weight');
                      const totalPriceInput = document.getElementById('total_price');
                      const totalPriceText = document.getElementById('totalPrice');
                      const serviceCheckboxes = document.querySelectorAll('.service');

                      const prices = {
                          wash: Number(<?= json_encode($washPrice) ?>),
                          dry: Number(<?= json_encode($dryPrice) ?>),
                          fold: Number(<?= json_encode($foldPrice) ?>)
                      };

                      function updateTotal() {
                          const weight = parseFloat(weightInput.value) || 0;
                          let total = 0;

                          serviceCheckboxes.forEach(checkbox => {
                              if (checkbox.checked && prices[checkbox.name]) {
                                  total += prices[checkbox.name] * weight;
                              }
                          });

                          totalPriceInput.value = total.toFixed(2);
                          totalPriceText.textContent = `₱${total.toFixed(2)}`;
                      }

                      weightInput.addEventListener('input', updateTotal);
                      serviceCheckboxes.forEach(checkbox => checkbox.addEventListener('change', updateTotal));

                      updateTotal();
                  </script>

                  <input type="hidden" name="total_price" id="total_price">

                  <button type="submit" name="submit_order" class="btn btn-primary btn-lg mt-3">
                      Submit Order
                  </button>
              </form>
          </div>
      </div>
  </section>