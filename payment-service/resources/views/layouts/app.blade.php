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
    </style>
</head>
<body class="bg-gray-50 min-h-screen font-sans">
    @yield('content')
</body>
</html>
