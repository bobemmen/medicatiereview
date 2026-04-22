<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicatiereview — {{ config('services.apotheek.naam') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-[#F7F9FC] text-[#0F172A] font-sans antialiased">
    {{ $slot }}
    @livewireScripts
</body>
</html>
