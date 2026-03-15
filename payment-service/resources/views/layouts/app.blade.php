<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self' https://*.payin.co.tz https://auth.payin.co.tz https://login.payin.co.tz https://tx.payin.co.tz https://wallet.payin.co.tz https://settle.payin.co.tz;">
    <title>@yield('title', 'Payin')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: { sans: ['Poppins', 'Inter', 'system-ui', 'sans-serif'] },
                colors: {
                    gblue:  { 50: '#e8f0fe', 100: '#d2e3fc', 200: '#aecbfa', 300: '#6ea8fe', 400: '#3d8bfd', 500: '#0d6efd', 600: '#0a58ca', 700: '#084dc7', 800: '#063aa0', 900: '#042b7a' },
                    gred:   { 50: '#fce8e6', 100: '#fad2cf', 200: '#f6aea9', 300: '#f28b82', 400: '#ee675c', 500: '#EA4335', 600: '#d93025', 700: '#c5221f', 800: '#b31412', 900: '#a50e0e' },
                    gyellow:{ 50: '#fefce8', 100: '#fef3c7', 200: '#fde68a', 300: '#fcd34d', 400: '#fbbf24', 500: '#f59e0b', 600: '#d97706', 700: '#b45309', 800: '#92400e', 900: '#78350f' },
                    ggreen: { 50: '#e6f4ea', 100: '#ceead6', 200: '#a8dab5', 300: '#81c995', 400: '#5bb974', 500: '#34A853', 600: '#1e8e3e', 700: '#188038', 800: '#137333', 900: '#0d652d' },
                }
            }
        }
    }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }

        /* Professional sidebar scrollbar */
        .sidebar-scroll {
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: transparent transparent;
        }
        .sidebar-scroll:hover {
            scrollbar-color: rgba(156,163,175,.35) transparent;
        }
        .sidebar-scroll::-webkit-scrollbar {
            width: 5px;
        }
        .sidebar-scroll::-webkit-scrollbar-track {
            background: transparent;
            margin: 8px 0;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: transparent;
            border-radius: 9999px;
            transition: background .2s;
        }
        .sidebar-scroll:hover::-webkit-scrollbar-thumb {
            background: rgba(156,163,175,.35);
        }
        .sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(107,114,128,.5);
        }
        .sidebar-scroll::-webkit-scrollbar-thumb:active {
            background: rgba(75,85,99,.6);
        }

        @keyframes payin-spin { to { transform: rotate(360deg); } }
        .payin-loader { position: fixed; inset: 0; z-index: 9999; display: flex; flex-direction: column; align-items: center; justify-content: center; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); transition: opacity .4s ease; }
        .payin-loader.fade-out { opacity: 0; pointer-events: none; }
        .payin-loader .spinner { width: 48px; height: 48px; border: 4px solid rgba(255,255,255,.15); border-top-color: #f59e0b; border-radius: 50%; animation: payin-spin .8s linear infinite; }
        .payin-loader .loader-text { margin-top: 20px; color: #94a3b8; font-family: 'Poppins', 'Inter', sans-serif; font-size: 14px; font-weight: 500; letter-spacing: .5px; }
        .payin-loader .loader-brand { color: #fff; font-family: 'Poppins', 'Inter', sans-serif; font-size: 28px; font-weight: 800; margin-bottom: 24px; letter-spacing: 1px; }
        .payin-loader .loader-brand span { color: #f59e0b; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen font-sans">
    <!-- Global Loading Overlay -->
    <div id="payin-global-loader" class="payin-loader">
        <div class="loader-brand">Pay<span>In</span></div>
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
