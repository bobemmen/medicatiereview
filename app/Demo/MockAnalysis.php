<?php

namespace App\Demo;

/**
 * Realistische mock-data voor de demo-pagina.
 *
 * Dezelfde structuur als wat ClaudeService::streamAnalyseDossier teruggeeft,
 * zodat het review- en rapport-scherm zonder echte API-call gerenderd kunnen
 * worden. Dekt alle statussen (ok/aandacht/drp), variatie in DRP-types voor
 * sortering, en bronnen die de STOP-NL v2 deep-link en fallbacks aantonen.
 */
class MockAnalysis
{
    public static function data(): array
    {
        return [
            'patient' => [
                'initialen_of_geanonimiseerde_naam' => 'Mevr. M.K.',
                'leeftijd' => 78,
                'geslacht' => 'vrouw',
                'gewicht_kg' => '62 kg',
                'nierfunctie' => 'eGFR 38 ml/min/1,73m² (CKD G3b)',
                'huisarts' => 'Dr. A. Vermeer',
                'allergieen' => ['Penicilline (huiduitslag)'],
                'voorgeschiedenis' => [
                    'Hartfalen NYHA II (LVEF 40%, 2022)',
                    'Atriumfibrilleren (paroxismaal, sinds 2020)',
                    'Hypertensie',
                    'Diabetes mellitus type 2 (sinds 2014)',
                    'Osteoporose',
                    'Valincident zonder fractuur (6 maanden geleden)',
                ],
            ],

            'medicatie' => [
                [
                    'naam' => 'Diltiazem',
                    'atc_code' => 'C08DB01',
                    'sterkte' => '90 mg',
                    'vorm' => 'tablet mga',
                    'frequentie' => '2× daags 1 tablet',
                    'indicatie' => 'Hypertensie',
                    'status' => 'drp',
                    'aandachtspunt' => 'Non-dihydropyridine calciumantagonist bij hartfalen met verminderde ejectiefractie — negatief inotroop effect kan hartfalen verergeren.',
                    'notitie' => 'Bij volgende cardiologie-controle bespreken: vervangen door amlodipine of intensiveren ACE-remmer/bètablokker.',
                    'drp_typen' => ['Indicatieprobleem', 'Bijwerking (vermoed)'],
                    'mechanisme' => 'Diltiazem onderdrukt de contractiliteit van het myocard.',
                    'klinisch_gevolg' => 'Toename hartfalen-symptomen, verminderde inspanningstolerantie.',
                    'actie' => 'Diltiazem staken; overweeg amlodipine 5 mg 1×/dag.',
                    'bronnen' => [
                        ['type' => 'STOPP-NL', 'titel' => 'STOPP-NL E1 — non-dihydropyridine calciumantagonist bij hartfalen met verminderde ejectiefractie'],
                        ['type' => 'NHG-standaard', 'titel' => 'NHG-Standaard Hartfalen', 'url' => 'https://richtlijnen.nhg.org/standaarden/hartfalen'],
                        ['type' => 'Farmacotherapeutisch Kompas', 'titel' => 'Diltiazem — preparaattekst', 'url' => 'https://www.farmacotherapeutischkompas.nl/bladeren/preparaatteksten/d/diltiazem'],
                    ],
                ],

                [
                    'naam' => 'Lormetazepam',
                    'atc_code' => 'N05CD06',
                    'sterkte' => '1 mg',
                    'vorm' => 'tablet',
                    'frequentie' => "1× daags 1 tablet ('s avonds)",
                    'indicatie' => 'Slapeloosheid',
                    'status' => 'drp',
                    'aandachtspunt' => 'Langdurig gebruik benzodiazepine bij kwetsbare oudere met valincident in voorgeschiedenis — verhoogt valrisico, cognitieve achteruitgang, afhankelijkheid.',
                    'notitie' => 'Afbouwen volgens NHG-schema (per 2 weken 25% verlagen). Slaapadviezen meegeven; bij hardnekkig insomnia CGT-i overwegen.',
                    'drp_typen' => ['Bijwerking (vermoed)', 'Onnodig geneesmiddel', 'Indicatieprobleem'],
                    'mechanisme' => 'GABA-A potentiëring veroorzaakt sedatie, spierrelaxatie en cognitieve effecten.',
                    'klinisch_gevolg' => 'Toegenomen valrisico, mogelijk cognitieve achteruitgang, tolerantie en afhankelijkheid.',
                    'actie' => 'Afbouwschema: per 2 weken 25% verlagen. Slaapadviezen en CGT-i overwegen.',
                    'bronnen' => [
                        ['type' => 'STOPP-NL', 'titel' => 'STOPP-NL B3 — langwerkende benzodiazepine bij ouderen (valrisico)'],
                        ['type' => 'NHG-standaard', 'titel' => 'NHG-Standaard Slaapproblemen en slaapmiddelen', 'url' => 'https://richtlijnen.nhg.org/standaarden/slaapproblemen-en-slaapmiddelen'],
                    ],
                ],

                [
                    'naam' => 'Apixaban',
                    'atc_code' => 'B01AF02',
                    'sterkte' => '5 mg',
                    'vorm' => 'tablet',
                    'frequentie' => '2× daags 1 tablet',
                    'indicatie' => 'Atriumfibrilleren (CHA₂DS₂-VASc 5)',
                    'status' => 'drp',
                    'aandachtspunt' => 'Dosering 2×5 mg ondanks ≥2 reductiecriteria (leeftijd ≥80 jr-grens, gewicht <60 kg, creatinine ≥133 µmol/L) — controleer op dosisreductie naar 2×2,5 mg.',
                    'notitie' => 'Verifiëren of 2 van 3 reductiecriteria gelden; indien ja: dosering verlagen naar 2×2,5 mg.',
                    'drp_typen' => ['Doseringsafwijking'],
                    'mechanisme' => 'Bij dosisreductiecriteria leidt 2×5 mg tot supratherapeutische plasmaspiegels en bloedingsrisico.',
                    'klinisch_gevolg' => 'Verhoogd bloedingsrisico (intracraniaal, gastro-intestinaal).',
                    'actie' => 'Verifiëren of 2 van 3 reductiecriteria gelden; indien ja: dosis verlagen naar 2×2,5 mg.',
                    'bronnen' => [
                        ['type' => 'SmPC', 'titel' => 'Eliquis (apixaban) — SmPC paragraaf 4.2', 'url' => 'https://www.geneesmiddeleninformatiebank.nl'],
                        ['type' => 'NHG-standaard', 'titel' => 'NHG-Standaard Atriumfibrilleren', 'url' => 'https://richtlijnen.nhg.org/standaarden/atriumfibrilleren'],
                    ],
                ],

                [
                    'naam' => 'Furosemide',
                    'atc_code' => 'C03CA01',
                    'sterkte' => '40 mg',
                    'vorm' => 'tablet',
                    'frequentie' => '1× daags 1 tablet (ochtend)',
                    'indicatie' => 'Hartfalen',
                    'status' => 'aandacht',
                    'aandachtspunt' => 'Lisdiureticum bij oudere met valincident — risico op orthostase, dehydratie, elektrolytstoornissen. Recente eGFR-daling: dosis evalueren.',
                    'notitie' => 'K+/Na+/creatinine controleren bij volgende lab. Patiënte instrueren over signalen van dehydratie en orthostase.',
                    'drp_typen' => ['Bijwerking (vermoed)'],
                    'mechanisme' => 'Volume-depletie en hypotensie verhogen valrisico.',
                    'klinisch_gevolg' => 'Vallen, syncope, AKI bij intercurrente ziekte.',
                    'actie' => 'K+/Na+/creatinine controleren bij volgende lab. Patiënt instrueren over signs of dehydratie.',
                    'bronnen' => [
                        ['type' => 'NHG-standaard', 'titel' => 'NHG-Standaard Hartfalen', 'url' => 'https://richtlijnen.nhg.org/standaarden/hartfalen'],
                    ],
                ],

                [
                    'naam' => 'Metformine',
                    'atc_code' => 'A10BA02',
                    'sterkte' => '1000 mg',
                    'vorm' => 'tablet',
                    'frequentie' => '2× daags 1 tablet',
                    'indicatie' => 'Diabetes mellitus type 2',
                    'status' => 'aandacht',
                    'aandachtspunt' => 'eGFR 38 ml/min — bij eGFR 30-45 maximale dosering 2×500 mg; staken bij eGFR <30.',
                    'notitie' => 'Dosis halveren naar 2×500 mg. eGFR 3-maandelijks vervolgen; bij eGFR <30 staken.',
                    'drp_typen' => ['Doseringsafwijking'],
                    'mechanisme' => 'Verminderde renale klaring → accumulatie → risico lactaatacidose.',
                    'klinisch_gevolg' => 'Lactaatacidose bij intercurrente ziekte, dehydratie, contrastonderzoek.',
                    'actie' => 'Dosis halveren naar 2×500 mg. eGFR 3-maandelijks vervolgen.',
                    'bronnen' => [
                        ['type' => 'NHG-standaard', 'titel' => 'NHG-Standaard Diabetes mellitus type 2', 'url' => 'https://richtlijnen.nhg.org/standaarden/diabetes-mellitus-type-2'],
                        ['type' => 'Farmacotherapeutisch Kompas', 'titel' => 'Metformine — dosering bij verminderde nierfunctie', 'url' => 'https://www.farmacotherapeutischkompas.nl/bladeren/preparaatteksten/m/metformine'],
                    ],
                ],

                [
                    'naam' => 'Pantoprazol',
                    'atc_code' => 'A02BC02',
                    'sterkte' => '40 mg',
                    'vorm' => 'tablet mga',
                    'frequentie' => '1× daags 1 tablet',
                    'indicatie' => 'Maagprotectie bij anticoagulantia',
                    'status' => 'aandacht',
                    'aandachtspunt' => 'Langdurig PPI-gebruik (>1 jaar) — herevalueer indicatie. Bij DOAC zonder andere risicofactoren is maagprotectie meestal niet nodig.',
                    'notitie' => 'Indicatie heroverwegen; bij geen risicofactoren afbouwen (om de dag → staken).',
                    'drp_typen' => ['Onnodig geneesmiddel'],
                    'mechanisme' => 'Lange-termijn PPI: B12-deficiëntie, hypomagnesiëmie, fractuurrisico, C. difficile.',
                    'klinisch_gevolg' => 'Cumulatieve bijwerkingen bij ontbrekende indicatie.',
                    'actie' => 'Indicatie heroverwegen; eventueel afbouwschema (om de dag → staken).',
                    'bronnen' => [
                        ['type' => 'NHG-standaard', 'titel' => 'NHG-Standaard Maagklachten', 'url' => 'https://richtlijnen.nhg.org/standaarden/maagklachten'],
                    ],
                ],

                [
                    'naam' => 'Metoprolol',
                    'atc_code' => 'C07AB02',
                    'sterkte' => '50 mg',
                    'vorm' => 'tablet mga',
                    'frequentie' => '2× daags 1 tablet',
                    'indicatie' => 'Hartfalen / atriumfibrilleren (rate control)',
                    'status' => 'ok',
                    'aandachtspunt' => '',
                    'notitie' => '',
                    'drp_typen' => [],
                    'mechanisme' => '',
                    'klinisch_gevolg' => '',
                    'actie' => '',
                    'bronnen' => [],
                ],

                [
                    'naam' => 'Atorvastatine',
                    'atc_code' => 'C10AA05',
                    'sterkte' => '40 mg',
                    'vorm' => 'tablet',
                    'frequentie' => "1× daags 1 tablet ('s avonds)",
                    'indicatie' => 'Secundaire CV-preventie',
                    'status' => 'ok',
                    'aandachtspunt' => '',
                    'notitie' => '',
                    'drp_typen' => [],
                    'mechanisme' => '',
                    'klinisch_gevolg' => '',
                    'actie' => '',
                    'bronnen' => [],
                ],
            ],

            'samenvatting' => 'Kwetsbare 78-jarige met hartfalen, AF en CKD G3b. Drie DRP\'s: diltiazem ongeschikt bij HFrEF (E1), langdurig lormetazepam met valrisico, en mogelijk supratherapeutische apixaban-dosering. Daarnaast aandacht voor metformine-dosering bij verminderde nierfunctie en herevaluatie PPI/lisdiureticum.',

            'anamnese_vragen' => [
                'Bent u de afgelopen 6 maanden gevallen of bijna gevallen?',
                'Hoe slaapt u zonder de slaaptablet? Hoe lang gebruikt u lormetazepam al en heeft u al eerder geprobeerd het af te bouwen?',
                'Ervaart u duizeligheid bij het opstaan of na het innemen van uw bloeddruk- of plasmiddelen?',
                'Heeft u last van kortademigheid, vocht in de benen of een onverwachte gewichtstoename van meer dan 2 kg in een week?',
                'Hoe zijn uw bloedsuikerwaarden de laatste weken en heeft u hypoglykemische klachten (trillen, zweten, honger)?',
                'Heeft u de laatste tijd last van ongewone bloedingen, langdurig nabloeden bij een wondje of onverwachte blauwe plekken?',
                'Gebruikt u ook vrij verkrijgbare pijnstillers zoals ibuprofen of naproxen?',
                'Hoe is uw eetlust en gewicht de laatste maanden en drinkt u voldoende?',
                'Neemt u al uw medicijnen dagelijks in? Vergeet u wel eens een dosis, of slaat u een tablet over?',
                'Heeft u last van maagklachten, zuurbranden of een vol gevoel na het eten?',
            ],
        ];
    }
}
