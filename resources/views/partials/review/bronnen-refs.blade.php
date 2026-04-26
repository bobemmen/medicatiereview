@props(['bronnen' => []])

@if (!empty($bronnen))
    @php
        $stoppV2Base = 'https://www.nhg.org/thema/farmacotherapie/stop-nl-v2/';

        // STOP-NL v2 hoofdstuk-ankers per criteriumcode-letter.
        // Bevestigd: E = cardiovasculaire-belasting (per gebruikersopgave).
        // TODO: vul de overige letters aan met de exacte slug uit de inhoudsopgave op
        //       https://www.nhg.org/thema/farmacotherapie/stop-nl-v2/
        // Bekende v2-domeinen (slug onbekend): vallen, cognitieve achteruitgang /
        // anticholinerge belasting, mictie- en defecatieproblemen, bloedingsrisico,
        // beperkte levensverwachting < 1 jaar.
        $stoppV2Anchors = [
            'A' => 'a-stoppen-algemeen',
            'B' => 'b-vallen',
            'C' => 'c-verminderde-cognitieve-functies',
            'D' => 'd-mictie-en-defecatieproblemen',
            'E' => 'e-cardiovasculaire-belasting',
            'F' => 'f-bloedingsrisico',
            'G' => 'g-gering-geschatte-resterende-levensverwachting-1-jaar',
        ];

        $fallbackUrls = [
            'STOPP-NL'                    => $stoppV2Base,
            'START-NL'                    => $stoppV2Base,
            'NHG-standaard'               => 'https://richtlijnen.nhg.org/standaarden',
            'KNMP Kennisbank'             => 'https://www.farmacotherapeutischkompas.nl',
            'G-Standaard'                 => 'https://www.farmacotherapeutischkompas.nl',
            'SmPC'                        => 'https://www.geneesmiddeleninformatiebank.nl',
            'Farmacotherapeutisch Kompas' => 'https://www.farmacotherapeutischkompas.nl',
        ];
        $allowedHosts = [
            'STOPP-NL'                    => ['www.nhg.org', 'nhg.org', 'richtlijnen.nhg.org'],
            'START-NL'                    => ['www.nhg.org', 'nhg.org', 'richtlijnen.nhg.org'],
            'NHG-standaard'               => ['www.nhg.org', 'nhg.org', 'richtlijnen.nhg.org'],
            'KNMP Kennisbank'             => ['kennisbank.knmp.nl', 'www.knmp.nl', 'knmp.nl', 'www.farmacotherapeutischkompas.nl', 'farmacotherapeutischkompas.nl'],
            'G-Standaard'                 => ['www.z-index.nl', 'z-index.nl', 'www.farmacotherapeutischkompas.nl', 'farmacotherapeutischkompas.nl'],
            'SmPC'                        => ['www.geneesmiddeleninformatiebank.nl', 'geneesmiddeleninformatiebank.nl', 'www.cbg-meb.nl', 'cbg-meb.nl', 'www.ema.europa.eu', 'ema.europa.eu'],
            'Farmacotherapeutisch Kompas' => ['www.farmacotherapeutischkompas.nl', 'farmacotherapeutischkompas.nl'],
        ];
    @endphp
    <span class="inline-flex flex-wrap items-baseline gap-x-0.5 ml-0.5 align-baseline">
        @foreach ($bronnen as $bron)
            @php
                $type    = $bron['type']  ?? 'Overig';
                $titel   = $bron['titel'] ?? '';
                $rawUrl  = trim((string) ($bron['url'] ?? ''));

                // Voor STOPP-NL/START-NL bouwen we de URL zelf op basis van de
                // criteriumcode-letter in de titel. Dat is betrouwbaarder dan
                // wat het LLM verzint, en geeft een directe deep-link naar het
                // juiste hoofdstuk wanneer we de slug kennen.
                $stoppDeepLink = null;
                if (in_array($type, ['STOPP-NL', 'START-NL'], true)) {
                    if (preg_match('/\b([A-Z])\d+/u', $titel, $m)) {
                        $letter = $m[1];
                        if (isset($stoppV2Anchors[$letter])) {
                            $stoppDeepLink = $stoppV2Base . '#' . $stoppV2Anchors[$letter];
                        }
                    }
                }

                $deepLink = $stoppDeepLink;
                if ($deepLink === null && $rawUrl !== '') {
                    $scheme = parse_url($rawUrl, PHP_URL_SCHEME);
                    $host   = strtolower((string) parse_url($rawUrl, PHP_URL_HOST));
                    $allowed = $allowedHosts[$type] ?? null;
                    if ($scheme === 'https' && $host !== '') {
                        if ($allowed === null || in_array($host, $allowed, true)) {
                            $deepLink = $rawUrl;
                        }
                    }
                }

                $url       = $deepLink ?? ($fallbackUrls[$type] ?? null);
                $isDeepLink = $deepLink !== null;
            @endphp
            <span x-data="{ open: false }"
                @mouseenter="open = true"
                @mouseleave="open = false"
                @keydown.escape.window="open = false"
                class="relative inline-block">
                <button type="button"
                    @click.stop="open = !open"
                    class="text-[10px] font-semibold text-[#1A4F82] hover:text-[#1D5FA0] hover:underline cursor-pointer align-baseline focus:outline-none focus:ring-1 focus:ring-[#1A4F82] rounded-sm px-0.5">({{ $loop->iteration }})</button>
                <div x-show="open"
                    x-transition.opacity.duration.100ms
                    x-cloak
                    @click.stop
                    class="absolute z-30 left-0 mt-1 w-64 bg-white border border-[#E2E8F0] rounded-md shadow-lg p-2.5 text-left normal-case">
                    <div class="text-[9px] font-semibold text-[#1A4F82] uppercase tracking-wider">{{ $type }}</div>
                    <div class="text-xs font-semibold text-[#0F172A] mt-0.5 leading-snug">{{ $titel }}</div>
                    @if ($url)
                        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
                            class="inline-flex items-center gap-1 mt-2 text-[11px] text-[#1A4F82] hover:text-[#1D5FA0] hover:underline">
                            {{ $isDeepLink ? 'Open pagina' : 'Open bron-website' }}
                            <svg width="10" height="10" viewBox="0 0 10 10" fill="none">
                                <path d="M1.5 8.5l7-7M4 1.5h4.5v4.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    @endif
                </div>
            </span>
        @endforeach
    </span>
@endif
