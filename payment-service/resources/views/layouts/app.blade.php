<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Payin')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                colors: {
                    gblue:  { 50: '#e8f0fe', 100: '#d2e3fc', 200: '#aecbfa', 300: '#8ab4f8', 400: '#669df6', 500: '#4285F4', 600: '#1a73e8', 700: '#1967d2', 800: '#185abc', 900: '#174ea6' },
                    gred:   { 50: '#fce8e6', 100: '#fad2cf', 200: '#f6aea9', 300: '#f28b82', 400: '#ee675c', 500: '#EA4335', 600: '#d93025', 700: '#c5221f', 800: '#b31412', 900: '#a50e0e' },
                    gyellow:{ 50: '#fef7e0', 100: '#feefc3', 200: '#fde293', 300: '#fdd663', 400: '#fcc934', 500: '#FBBC05', 600: '#f9ab00', 700: '#f29900', 800: '#e37400', 900: '#d56e0a' },
                    ggreen: { 50: '#e6f4ea', 100: '#ceead6', 200: '#a8dab5', 300: '#81c995', 400: '#5bb974', 500: '#34A853', 600: '#1e8e3e', 700: '#188038', 800: '#137333', 900: '#0d652d' },
                }
            }
        }
    }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        @keyframes payin-spin { to { transform: rotate(360deg); } }
        .payin-loader { position: fixed; inset: 0; z-index: 9999; display: flex; flex-direction: column; align-items: center; justify-content: center; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); transition: opacity .4s ease; }
        .payin-loader.fade-out { opacity: 0; pointer-events: none; }
        .payin-loader .spinner { width: 48px; height: 48px; border: 4px solid rgba(255,255,255,.15); border-top-color: #3b82f6; border-radius: 50%; animation: payin-spin .8s linear infinite; }
        .payin-loader .loader-text { margin-top: 20px; color: #94a3b8; font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 500; letter-spacing: .5px; }
        .payin-loader .loader-brand { color: #fff; font-family: 'Inter', sans-serif; font-size: 24px; font-weight: 700; margin-bottom: 24px; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen font-sans">
    <!-- Global Loading Overlay -->
    <div id="payin-global-loader" class="payin-loader">
        <div class="loader-brand">Payin</div>
        <div class="spinner"></div>
        <div class="loader-text">Please wait...</div>
    </div>
    @yield('content')
    <script>
        // Hide loader once Alpine has initialized the page
        document.addEventListener('alpine:initialized', function() {
            setTimeout(function() {
                var loader = document.getElementById('payin-global-loader');
                if (loader) { loader.classList.add('fade-out'); setTimeout(function() { loader.remove(); }, 500); }
            }, 200);
        });
        // Fallback: hide after 6s max
        setTimeout(function() {
            var loader = document.getElementById('payin-global-loader');
            if (loader) { loader.classList.add('fade-out'); setTimeout(function() { loader.remove(); }, 500); }
        }, 6000);
    </script>
</body>
</html>
