# Medicatiebeoordeling — AI-ondersteunde MBO (demo)

Publiek toegankelijke webapp die apothekers ondersteunt bij een gestructureerde medicatiebeoordeling (MBO) volgens de KNMP-richtlijn. Een geanonimiseerd patiëntendossier wordt geüpload (PDF, DOCX of tekst), waarna Claude een volledige analyse uitvoert: anamnese-vragen (STRIP), farmacotherapeutische analyse (PCNE, STOPP/START-NL, interacties, nierfunctie), een voorstel voor behandelplan en follow-up.

> **Dit is een demo voor beslissingsondersteuning.** De output moet altijd getoetst worden door een BIG-geregistreerde zorgprofessional. Zie `PLAN.md` voor het achterliggende plan, inclusief AVG- en MDR-overwegingen.

## Stack

- **Laravel 13** + **Livewire 4** — single-file component in `resources/views/components/⚡medication-review.blade.php`
- **Tailwind CSS v4** via Vite
- **Anthropic Claude API** (`claude-opus-4-7`) — server-side, key nooit in de browser
- **smalot/pdfparser** + **phpoffice/phpword** voor PDF/DOCX-extractie
- Geen database, geen persistente opslag — session- en cache-driver op `cookie`/`file`

## Lokale setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
# vul in .env je ANTHROPIC_API_KEY in
npm run build
composer dev   # start server + vite
```

App draait dan op [http://127.0.0.1:8000](http://127.0.0.1:8000).

## Environment variables

| Variabele | Vereist | Default | Doel |
|-----------|---------|---------|------|
| `ANTHROPIC_API_KEY` | ja | — | API-key voor Claude |
| `ANTHROPIC_MODEL` | nee | `claude-opus-4-7` | Claude-model |
| `APP_KEY` | ja | — | Wordt gegenereerd via `php artisan key:generate` |
| `APP_URL` | nee | — | Publieke URL voor de deployment |

## Deployen op Laravel Cloud

1. Log in op [cloud.laravel.com](https://cloud.laravel.com) en maak een nieuwe app aan.
2. Koppel deze Git-repository en selecteer de branch (`claude/patient-record-webapp-plan-ASxVm` of de branch waarop je samenvoegt).
3. Zet bij **Environment**:
   - `ANTHROPIC_API_KEY` = *(jouw Claude API-key)*
   - `APP_ENV` = `production`
   - `APP_DEBUG` = `false`
   - `APP_URL` = *(de publieke URL die Laravel Cloud toekent)*
4. Laravel Cloud detecteert automatisch dat dit een Laravel-app is en draait:
   - `composer install --no-dev --optimize-autoloader`
   - `npm ci && npm run build`
   - `php artisan config:cache && php artisan route:cache && php artisan view:cache`
5. Er is **geen database** nodig — sessions en cache draaien op `cookie`/`file`.
6. Push je branch — Laravel Cloud deployt automatisch.

### Let op
- De Laravel Cloud-disk is ephemeral: dat past prima bij dit project omdat er niets wordt opgeslagen.
- Zorg dat `ANTHROPIC_API_KEY` alleen via environment variables wordt gezet, nooit in de repo.

## Structuur

```
app/
  Services/
    ClaudeService.php       → prompt + API-aanroep naar Claude
    DossierExtractor.php    → PDF/DOCX/TXT → platte tekst
resources/
  views/
    layouts/app.blade.php                   → hoofdlayout
    components/⚡medication-review.blade.php → Livewire single-file component
    partials/results.blade.php              → resultaatweergave
routes/web.php              → Route::livewire('/', 'medication-review')
config/services.php         → Anthropic-config
PLAN.md                     → volledig projectplan
```

## Demo-flow

1. **Disclaimer** — gebruiker moet bevestigen
2. **Dossier invoeren** — upload PDF/DOCX/TXT óf plak tekst óf gebruik het meegeleverde voorbeeld-dossier (82-jarige vrouw met polyfarmacie)
3. **Analyse** — Claude doet extractie + volledige MBO-analyse (~30-90 sec)
4. **Resultaten** — overzicht per KNMP-stap, met prioriteit, ernst en onderbouwing
5. **Export** — download het verslag als Markdown voor overleg met huisarts

## Disclaimer

Deze tool is een demo. Er is geen klinische validatie uitgevoerd en geen CE-markering (MDR). Gebruik uitsluitend geanonimiseerde data en toets elke aanbeveling als BIG-geregistreerde zorgprofessional. Zie `PLAN.md` §6 voor de volledige juridische en regulatoire context.
