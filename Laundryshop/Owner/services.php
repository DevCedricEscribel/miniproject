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