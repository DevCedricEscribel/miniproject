<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Fire Drill Alert</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet" />

    <style>
        body {
            background-color: #f1f3f5;
        }

        .card {
            border-radius: 18px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .alert-emergency {
            background: linear-gradient(135deg, #dc3545, #a71d2a);
            color: #fff;
            font-weight: 600;
            animation: pulse 1.2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.03);
            }

            100% {
                transform: scale(1);
            }
        }

        .notification-box {
            border-left: 6px solid #dc3545;
            background-color: #fff;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .icon-large {
            font-size: 3rem;
        }
    </style>
</head>

<body>

    <div class="container vh-100 d-flex align-items-center justify-content-center">
        <div class="card p-4 text-center w-100" style="max-width: 420px;">

            <div class="icon-large mb-2">🚨</div>
            <h3 class="mb-2">Fire Drill Alert</h3>
            <p class="text-muted mb-3">
                This system will vibrate, notify, and flash your phone during a fire drill.
            </p>

            <div id="notification" class="notification-box">
                <strong>Status:</strong> No active fire alert
            </div>

            <div id="status" class="alert alert-secondary mb-3">
                System Idle
            </div>

            <button class="btn btn-danger btn-lg w-100 mb-2" onclick="startFireAlert()">
                🔥 Sample Fire Drill
            </button>

            <button class="btn btn-outline-dark w-100" onclick="stopFireAlert()">
                🛑 Stop Alert
            </button>

        </div>
    </div>

    <script>
        let vibrationTimer = null;
        let flashStream = null;
        let flashTrack = null;
        let flashlightTimer = null;
        let torchOn = false;

        async function initFlashlight() {
            try {
                flashStream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: "environment"
                    }
                });

                flashTrack = flashStream.getVideoTracks()[0];
                const capabilities = flashTrack.getCapabilities();

                if (!capabilities.torch) {
                    console.warn("Torch not supported on this device.");
                    return;
                }

                // Toggle flashlight ON/OFF repeatedly
                flashlightTimer = setInterval(() => {
                    torchOn = !torchOn;
                    flashTrack.applyConstraints({
                        advanced: [{
                            torch: torchOn
                        }]
                    });
                }, 700); // blinking speed (ms)

            } catch (err) {
                console.warn("Flashlight error:", err);
            }
        }

        function stopFlashlight() {
            if (flashlightTimer) {
                clearInterval(flashlightTimer);
                flashlightTimer = null;
            }

            if (flashTrack) {
                flashTrack.applyConstraints({
                    advanced: [{
                        torch: false
                    }]
                });
                flashTrack.stop();
                flashTrack = null;
            }

            flashStream = null;
            torchOn = false;
        }

        function startFireAlert() {
            const status = document.getElementById("status");
            const notification = document.getElementById("notification");

            status.className = "alert alert-danger";
            status.innerHTML = "🚨 FIRE DETECTED! EVACUATE IMMEDIATELY!";

            notification.innerHTML = `
      <strong>🚨 Emergency Alert</strong><br>
      Fire reported. Please evacuate immediately.
    `;

            // Vibration loop
            if ("vibrate" in navigator) {
                const vibrationPattern = [1200, 400, 1200, 400, 1200];
                navigator.vibrate(vibrationPattern);

                vibrationTimer = setInterval(() => {
                    navigator.vibrate(vibrationPattern);
                }, 5000);
            }

            // Start blinking flashlight
            initFlashlight();
        }

        function stopFireAlert() {
            const status = document.getElementById("status");
            const notification = document.getElementById("notification");

            status.className = "alert alert-secondary";
            status.innerHTML = "System Idle";

            notification.innerHTML =
                "<strong>Status:</strong> No active fire alert";

            if ("vibrate" in navigator) {
                navigator.vibrate(0);
            }

            clearInterval(vibrationTimer);
            vibrationTimer = null;

            stopFlashlight();
        }
    </script>


</body>

</html>