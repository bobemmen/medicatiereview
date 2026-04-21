# Medicatiebeoordeling Webapp — Projectplan

## 1. Doel

Een publiek toegankelijke webapplicatie waarmee apothekers en andere zorgprofessionals een gestructureerde medicatiebeoordeling (MBO) kunnen uitvoeren op basis van een geanonimiseerd patiëntendossier. De applicatie begeleidt de gebruiker door de stappen van de KNMP-richtlijn voor medicatiebeoordeling, ondersteund door AI-analyse via de Claude API.

**Primaire gebruikers:** Apothekers (openbaar en ziekenhuisapothekers), apothekersassistenten onder supervisie, huisartsen die een MBO initiëren.

**Doelgroep patiënten:** Kwetsbare ouderen (≥ 65 jaar) met polyfarmacie (≥ 5 chronische geneesmiddelen), conform KNMP-richtlijn.

---

## 2. KNMP-stappenplan als leidraad

De applicatie volgt de vijf fasen van de KNMP-richtlijn Medicatiebeoordeling:

| Stap | Fase | Wat de app doet |
|------|------|-----------------|
| 1 | **Farmacotherapeutische anamnese** | Genereert gerichte vragen voor het patiëntgesprek (STRIP-methode) |
| 2 | **Farmacotherapeutische analyse** | Analyseert medicatie op DRP's, STOPP/START-NL, interacties, nierfunctie, adherentie-risico's |
| 3 | **Farmacotherapeutisch behandelplan** | Stelt concept-aanbevelingen op per DRP |
| 4 | **Vaststelling behandelplan** | Structureert de uitkomsten voor het overleg met huisarts en patiënt |
| 5 | **Follow-up en monitoring** | Geeft monitoringpunten en follow-up termijnen per aanpassing |

---

## 3. Functionaliteiten

### 3.1 Upload en extractie

- Gebruiker uploadt een geanonimiseerd tekstfragment of PDF van het dossier
- Ondersteunde secties: voorgeschiedenis, actieve episodes, recente labuitslagen (nierfunctie, elektrolyten, INR etc.), actuele medicatielijst
- Claude extraheert gestructureerde data uit de vrije tekst

### 3.2 Farmacotherapeutische anamnese-ondersteuning (STRIP stap 1)

- Genereert een gepersonaliseerde vragenlijst voor het patiëntgesprek op basis van de medicatielijst
- Dekt: therapietrouw (Morisky-vragen), bijwerkingen, zelfmedicatie, problemen bij gebruik (inslikken, tijdstip, herinneringen)
- Output: printbare vragenlijst en digitale invoerruimte voor antwoorden

### 3.3 Farmacotherapeutische analyse (STRIP stap 2)

Claude voert een volledige analyse uit op:

- **STOPP/START-NL criteria** — opsporen van potentieel ongeschikte medicatie (STOPP) en mogelijk gemiste indicaties (START)
- **Geneesmiddelinteracties** — klinisch relevante interacties gesignaleerd en gecategoriseerd op ernst
- **Contra-indicaties** — koppeling van voorgeschreven middelen aan de vermelde diagnoses/voorgeschiedenis
- **Nierfunctie-aanpassing** — vlaggen van middelen die dosisaanpassing vereisen bij verminderde nierfunctie (eGFR)
- **Dubbelmedicatie** — signalering van therapeutische duplicaties
- **Farmacotherapeutische problemen (FTP's)** — classificatie per type (onnodig middel, verkeerde keuze, te lage/hoge dosis, bijwerking, interactie, niet-ingenomen)

### 3.4 Behandelplan (STRIP stap 3 & 4)

- Per gesignaleerd FTP: concreet voorstel (staken, dosisaanpassing, wisselen, toevoegen, monitoren)
- Prioritering op klinische urgentie
- Exporteerbaar als gestructureerd verslag (PDF of Markdown) voor overleg met huisarts
- Ruimte voor apotheker om aanbevelingen aan te passen voor het definitieve verslag

### 3.5 Follow-up en monitoring

- Per voorgestelde aanpassing: monitoringpunten, parameters en termijnen
- Optionele samenvatting voor de patiënt in begrijpelijke taal

---

## 4. Technische architectuur

```
┌────────────────────────────────────────────────────────┐
│                     Browser (HTTPS)                    │
│                     Next.js frontend                   │
│  Upload → Anamnese → Analyse → Behandelplan → Export   │
└───────────────────────┬────────────────────────────────┘
                        │ REST API (HTTPS)
┌───────────────────────▼────────────────────────────────┐
│                  Backend (FastAPI / Python)             │
│  - Dossierparsing                                      │
│  - Promptconstructie per KNMP-stap                     │
│  - Claude API aanroepen (claude-opus-4-7)              │
│  - PDF-generatie (verslag)                             │
│  - Rate limiting & authenticatie                       │
└───────────────────────┬────────────────────────────────┘
                        │
┌───────────────────────▼────────────────────────────────┐
│              Claude API (Anthropic)                    │
│  Extractie / Anamnese / STOPP-START / Analyse /        │
│  Behandelplan / Follow-up                              │
└────────────────────────────────────────────────────────┘
```

**Geen database.** Dossierinhoud wordt uitsluitend in geheugen verwerkt voor de duur van de sessie en nooit opgeslagen.

### 4.1 Tech stack

| Laag | Keuze | Motivatie |
|------|-------|-----------|
| Frontend | Next.js (React) | SSR, goede PDF/export-ondersteuning, actief ecosysteem |
| Backend | FastAPI (Python) | Snelle async API, native typing, eenvoudige Anthropic SDK integratie |
| LLM | Claude API (`claude-opus-4-7`) | Beste redeneerkapaciteit voor klinische analyse |
| PDF-export | WeasyPrint of ReportLab | Server-side PDF zonder client afhankelijkheid |
| Hosting | VPS of cloud (bv. Hetzner, Fly.io) | GDPR-vriendelijke locatie (EU) |
| TLS | Let's Encrypt (verplicht) | Alle verkeer versleuteld |

---

## 5. Privacy en gegevensverwerking

### 5.1 Privacy-by-design principes

- **Geen opslag:** Dossierdata wordt niet naar schijf geschreven en niet gelogd. Sessiedata verdwijnt na afsluiten.
- **Minimale verwerking:** Alleen de inhoud die de gebruiker aanlevert wordt verwerkt; er wordt niets bijgevoegd of aangevuld vanuit externe patiëntensystemen.
- **Anonimisatie ligt bij de gebruiker:** De webapp verwerkt geanonimiseerde gegevens. Instructies voor het anonimiseren worden prominent getoond vóór upload.
- **HTTPS verplicht:** Alle communicatie via TLS 1.2+.
- **Geen tracking of analytics** die dossierinhoud raken.

### 5.2 AVG-overwegingen

Hoewel de tool werkt met geanonimiseerde gegevens, zijn gezondheidsgegevens bijzondere persoonsgegevens (AVG art. 9). Relevante maatregelen:

- Duidelijke gebruikersverklaring: de gebruiker bevestigt dat data volledig geanonimiseerd is
- Verwerkersovereenkomst met Anthropic (Claude API) — controleren of Anthropic een DPA aanbiedt (dit is het geval via hun Enterprise-overeenkomst)
- Privacyverklaring op de website
- Geen verwerking van directe identificatoren (naam, BSN, geboortedatum) — validatie/waarschuwing bij detectie

### 5.3 Gegevensoverdracht buiten EU

Claude API-servers staan mogelijk buiten de EU. Aandachtspunten:

- Gebruik Anthropic's Enterprise API met DPA voor AVG-compliance
- Alternatief overwegen: self-hosted open-source LLM (bv. Llama) voor volledig EU-gebaseerde verwerking — dit gaat ten koste van analysekwaliteit

---

## 6. Regelgeving en juridisch kader

### 6.1 Software as a Medical Device (SaMD) — EU MDR

Dit is het meest kritische regelgevingsvraagstuk. Een applicatie die:
- Medische informatie analyseert
- Aanbevelingen doet die een zorgprofessional kan gebruiken bij behandelbeslissingen

...valt mogelijk onder de **EU Medical Device Regulation (MDR 2017/745)** als Software as a Medical Device.

**Risicoklasse-inschatting:**
- Klasse I (laag risico): als de tool uitsluitend dient als informatieondersteuning zonder directe invloed op behandelbeslissingen — dan is zelfverklaring mogelijk
- Klasse IIa (middelmatig risico): als aanbevelingen direct leiden tot medicatiewijzigingen — dan is een Notified Body vereist

**Aanbeveling:** Juridisch/regulatory advies inwinnen vóór publieke lancering. In de eerste versie duidelijk positioneren als *beslissingsondersteuning* (niet beslissingsautomatisering) en prominente disclaimers tonen.

### 6.2 Disclaimer

Op elke pagina zichtbaar:

> *Deze applicatie is een hulpmiddel voor BIG-geregistreerde zorgprofessionals en vervangt niet het klinisch oordeel van de apotheker of arts. Aanbevelingen dienen altijd getoetst te worden door een bevoegde zorgprofessional. De makers aanvaarden geen aansprakelijkheid voor beslissingen genomen op basis van de output.*

### 6.3 Toegangsbeheer

- Overweeg een registratie-/loginmechanisme te vereisen (bv. BIG-registratienummer als verificatie), zodat de tool aantoonbaar alleen door professionals wordt gebruikt
- Dit versterkt ook de positie bij MDR-beoordeling

---

## 7. UX-flow

```
[Start]
    │
    ▼
[Disclaimer accepteren + bevestiging anonimisatie]
    │
    ▼
[Upload dossier (tekst plakken of PDF)]
    │
    ▼
[Claude extraheert: medicatielijst, diagnoses, labs]
[Gebruiker controleert/corrigeert extractie]
    │
    ▼
[STAP 1: Anamnese-vragen gegenereerd]
[Apotheker voert antwoorden in na patiëntgesprek]
    │
    ▼
[STAP 2: Farmacotherapeutische analyse]
[Overzicht FTP's, STOPP/START, interacties, contra-indicaties]
    │
    ▼
[STAP 3 & 4: Conceptbehandelplan]
[Apotheker past aan, voegt toe, verwijdert]
    │
    ▼
[STAP 5: Follow-up & monitoringpunten]
    │
    ▼
[Export: PDF-verslag voor huisarts + patiëntsamenvatting]
    │
    ▼
[Sessie beëindigen — alle data gewist]
```

---

## 8. Claude-prompting strategie

Elke KNMP-stap krijgt een eigen systeemprompt met:
- Rol: "Je bent een klinisch farmacoloog gespecialiseerd in ouderengeneeskunde..."
- Expliciete STOPP/START-NL criteria als referentiekader
- Instructie om FTP's te classificeren per type (PCNE-classificatie)
- Instructie om evidence-niveau te vermelden bij aanbevelingen
- Strikte outputstructuur (JSON of Markdown met vaste secties) voor consistente verwerking

**Prompt-chaining:** De output van stap 1 (extractie) wordt doorgegeven als context aan stap 2 (analyse), enzovoort — zodat de apotheker tussentijds kan corrigeren.

---

## 9. Ontwikkelfases

### Fase 1 — MVP (2-3 maanden)
- Tekstinvoer geanonimiseerd dossier
- Claude-extractie medicatielijst en diagnoses
- STOPP/START-NL analyse
- Basis behandelplan output (Markdown)
- Minimale UI, geen login

### Fase 2 — Uitbreiding (1-2 maanden)
- PDF-upload ondersteuning
- Anamnese-vragengenerator + invoer patiëntantwoorden
- PDF-export verslag
- Verbeterde interactie-analyse (met nuancering ernst)

### Fase 3 — Compliance en productie (2-3 maanden)
- Login/registratie (BIG-nummerverificatie overwegen)
- Formele MDR-beoordeling en juridisch advies
- DPA met Anthropic
- Security audit
- Gebruikersonderzoek met apothekers

### Fase 4 — Groei
- Meertaligheid (NL/EN)
- Integratie met EPD/HIS via FHIR-standaard (optioneel)
- Feedbackmechanisme voor kwaliteitsverbetering prompts

---

## 10. Risico's

| Risico | Impact | Mitigatie |
|--------|--------|-----------|
| Onjuiste AI-analyse leidt tot schadelijk advies | Hoog | Prominente disclaimer, validatie door professional verplicht |
| MDR-classificatie als medisch hulpmiddel | Hoog | Vroeg regulatory advies, conservatieve positionering |
| Re-identificatie van "geanonimiseerde" data | Middel | Gebruiker verantwoordelijk, instructies anonimisering, geen opslag |
| API-downtime Anthropic | Laag-middel | Foutafhandeling, duidelijke melding bij gebruiker |
| Datalekkage via API-verbinding | Middel | TLS, geen logging van inhoud, DPA |
| Misbruik door niet-professionals | Middel | Overweeg toegangsbeperking (login), disclaimers |
| Verouderde STOPP/START criteria in prompts | Laag-middel | Versioned prompts, regelmatig updaten bij nieuwe richtlijnen |

---

## 11. Openstaande beslissingen

1. **Toegangsbeheer:** Open voor iedereen vs. registratie (BIG-nummer)? — Registratie versterkt MDR-positie maar verlaagt adoptie.
2. **Hosting EU vs. buiten EU:** Invloed op AVG en Anthropic DPA-vereisten.
3. **Taal Claude API:** Alleen Nederlands of ook Engels? Claude presteert goed in het Nederlands.
4. **Financiering:** Gratis tool, abonnementsmodel of subsidie (ZonMw, KNMP-fonds)?
5. **Samenwerking KNMP/LHV:** Afstemming met beroepsorganisaties verhoogt geloofwaardigheid en vermindert MDR-risico.
