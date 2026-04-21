<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>QR Identity Scanner</title>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #1a202c; color: white; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
        #reader { width: 100%; max-width: 450px; border-radius: 15px; overflow: hidden; border: 4px solid #4a5568; background: #2d3748; }
        .info { margin-top: 20px; text-align: center; }
        h1 { font-size: 1.5rem; margin-bottom: 10px; }
    </style>
</head>
<body>

    <h1>ID SCANNER SYSTEM</h1>
    <div id="reader"></div>
    <div class="info">
        <p>Position the QR code inside the frame</p>
    </div>

<script>
    function onScanSuccess(decodedText) {
        // Only pause if the scanner is actually active (prevents the error)
        if (html5QrcodeScanner.getState() === Html5QrcodeScannerState.SCANNING) {
            html5QrcodeScanner.pause();
        }

        fetch('/verify', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ qr_code: decodedText })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({ title: 'VERIFIED', text: data.message, icon: 'success' })
                    .then(() => {
                        // Only resume if it was paused
                        if (html5QrcodeScanner.getState() === Html5QrcodeScannerState.PAUSED) {
                            html5QrcodeScanner.resume();
                        }
                    });
            } else {
                Swal.fire({ title: 'DENIED', text: data.message, icon: 'error' })
                    .then(() => {
                        if (html5QrcodeScanner.getState() === Html5QrcodeScannerState.PAUSED) {
                            html5QrcodeScanner.resume();
                        }
                    });
            }
        })
        .catch(err => {
            console.error(err);
            if (html5QrcodeScanner.getState() === Html5QrcodeScannerState.PAUSED) {
                html5QrcodeScanner.resume();
            }
        });
    }

    // Initialize scanner with improved settings
    let html5QrcodeScanner = new Html5QrcodeScanner("reader", { 
        fps: 15, 
        qrbox: {width: 250, height: 250},
        rememberLastUsedCamera: true,
        supportedScanTypes: [
            Html5QrcodeScanType.SCAN_TYPE_CAMERA,
            Html5QrcodeScanType.SCAN_TYPE_FILE  // This ensures both work
        ]
    });
    
    html5QrcodeScanner.render(onScanSuccess);
</script>
</body>
</html>