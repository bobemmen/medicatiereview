<div class="flex-1 flex items-center justify-center p-6">
    <div class="max-w-xl w-full bg-white rounded-lg shadow-sm border border-[#E2E8F0] p-10 text-center"
         x-data="{
            steps: [
                { pct:  6, text: 'Patiëntgegevens inlezen…' },
                { pct: 13, text: 'Medicatielijst structureren…' },
                { pct: 21, text: 'ATC-codes opzoeken…' },
                { pct: 29, text: 'Indicaties en doseringen controleren…' },
                { pct: 37, text: 'STOPP-criteria toepassen…' },
                { pct: 45, text: 'START-criteria controleren…' },
                { pct: 52, text: 'Geneesmiddelinteracties analyseren…' },
                { pct: 60, text: 'Nierfunctie-aanpassingen beoordelen…' },
                { pct: 67, text: 'Drug-related problems classificeren…' },
                { pct: 74, text: 'Klinische notities formuleren…' },
                { pct: 81, text: 'Aanbevelingen opstellen…' },
                { pct: 88, text: 'Samenvatting samenstellen…' },
                { pct: 94, text: 'Analyse afronden…' },
            ],
            i: 0,
            visible: true,
            get pct()  { return this.steps[this.i]?.pct  ?? 94 },
            get text() { return this.steps[this.i]?.text ?? '' },
            advance() {
                if (this.i >= this.steps.length - 1) return;
                this.visible = false;
                setTimeout(() => {
                    this.i++;
                    this.visible = true;
                    const delay = this.i < 4 ? 2800 : this.i < 9 ? 3800 : 4500;
                    setTimeout(() => this.advance(), delay);
                }, 350);
            },
            startStream() {
                const token = document.querySelector('meta[name=csrf-token]')?.content;
                const dossier = this.$wire.dossierText;
                const url = '{{ route('analyze-stream') }}';
                const wire = this.$wire;

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'text/event-stream',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ dossier }),
                }).then(async (response) => {
                    if (!response.ok) {
                        const text = await response.text();
                        throw new Error('HTTP ' + response.status + ': ' + text.slice(0, 200));
                    }
                    const reader = response.body.getReader();
                    const decoder = new TextDecoder();
                    let buffer = '';
                    let finalResult = null;
                    let gotError = null;
                    let sawStart = false;
                    let heartbeats = 0;
                    while (true) {
                        const { value, done } = await reader.read();
                        if (done) break;
                        buffer += decoder.decode(value, { stream: true });
                        let sep;
                        while ((sep = buffer.indexOf('\n\n')) !== -1) {
                            const block = buffer.slice(0, sep);
                            buffer = buffer.slice(sep + 2);
                            let event = 'message';
                            let data = '';
                            for (const line of block.split('\n')) {
                                if (line.startsWith('event: ')) event = line.slice(7);
                                else if (line.startsWith('data: ')) data += (data ? '\n' : '') + line.slice(6);
                            }
                            if (!data) continue;
                            let parsed;
                            try { parsed = JSON.parse(data); } catch (e) { continue; }
                            if (event === 'result') finalResult = parsed;
                            else if (event === 'error') gotError = parsed?.message || 'Onbekende streamfout';
                            else if (event === 'start') sawStart = true;
                            else if (event === 'heartbeat') heartbeats++;
                        }
                    }
                    if (gotError) await wire.call('setAnalysisError', gotError);
                    else if (finalResult) await wire.call('setAnalysisResult', finalResult);
                    else {
                        // Stream sloot zonder result én zonder error. Geef de gebruiker
                        // context over hoe ver we kwamen, zodat helder is of het nooit
                        // begon of mid-flight is afgekapt.
                        const detail = !sawStart
                            ? 'De analyse is niet gestart. Controleer je internetverbinding en probeer het opnieuw.'
                            : heartbeats === 0
                                ? 'De analyse is direct na de start afgebroken. Probeer het opnieuw.'
                                : `De analyse is vroegtijdig afgebroken na ${heartbeats} voortgangs-events. Probeer het opnieuw — vaak helpt een nieuwe poging of een korter dossier.`;
                        await wire.call('setAnalysisError', detail);
                    }
                }).catch(async (err) => {
                    await wire.call('setAnalysisError', 'Netwerkfout: ' + (err?.message || err));
                });
            }
         }"
         x-init="setTimeout(() => advance(), 2800); startStream();">

        <div class="inline-block animate-spin rounded-full h-10 w-10 border-4 border-[#E2E8F0] border-t-[#1A4F82] mb-6"></div>
        <h2 class="text-lg font-semibold text-[#0F172A] mb-3">Claude analyseert het dossier</h2>

        <div class="relative h-10 overflow-hidden mb-4">
            <p class="text-sm text-[#1A4F82] font-medium transition-all duration-300"
               :style="visible ? 'opacity:1;transform:translateY(0)' : 'opacity:0;transform:translateY(-8px)'"
               x-text="text"
               aria-live="polite">
            </p>
        </div>

        <div class="w-full bg-[#E2E8F0] rounded-full h-1 mb-4 overflow-hidden">
            <div class="h-1 rounded-full bg-[#1A4F82] transition-all duration-[1200ms] ease-in-out"
                 :style="'width:' + pct + '%'">
            </div>
        </div>

        <p class="text-xs text-[#94A3B8]">Dit duurt meestal 30–60 seconden</p>
    </div>
</div>
