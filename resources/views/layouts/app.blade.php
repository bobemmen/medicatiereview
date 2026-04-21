<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicatiebeoordeling — AI Ondersteuning</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">

    <header class="bg-white border-b border-slate-200 shadow-sm">
        <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Medicatiebeoordeling</h1>
                <p class="text-xs text-slate-500 mt-0.5">AI-ondersteunde MBO — KNMP-richtlijn</p>
            </div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700 ring-1 ring-amber-200">
                Uitsluitend voor zorgprofessionals
            </span>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-8">
        {{ $slot }}
    </main>

    <footer class="border-t border-slate-200 bg-white mt-12">
        <div class="max-w-4xl mx-auto px-4 py-4 text-center text-xs text-slate-400">
            Hulpmiddel voor BIG-geregistreerde zorgprofessionals. Geen medisch advies.
            Aanbevelingen dienen te worden getoetst door een bevoegde zorgprofessional.
        </div>
    </footer>

    @livewireScripts
</body>
</html>
