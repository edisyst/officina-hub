<div class="board-columns" wire:poll.15s>

    {{-- ════════════════════════════════════════════════════════
         COLONNA 1: In lavorazione
    ════════════════════════════════════════════════════════ --}}
    <div class="board-col">
        <div class="col-title">
            <i class="fas fa-wrench" style="color: var(--green); margin-right:.3em"></i>
            In lavorazione
            <span class="count">{{ $this->lavorazioni->count() }}</span>
        </div>

        <div class="col-scroll"
             x-data="{
                 scroll: 0,
                 step: 0.5,
                 init() {
                     setInterval(() => {
                         const el = this.$el;
                         const inner = el.querySelector('.col-inner');
                         if (!inner) return;
                         if (inner.scrollHeight <= el.clientHeight) { this.scroll = 0; return; }
                         this.scroll += this.step;
                         if (this.scroll >= inner.scrollHeight - el.clientHeight) this.scroll = 0;
                         inner.style.transform = 'translateY(-' + this.scroll + 'px)';
                     }, 40);
                 }
             }">
            <div class="col-inner">
                @forelse($this->lavorazioni as $lav)
                    @php
                        $ts = $lav['started_at_ts'];
                        $minPrev = $lav['minuti_preventivati'];
                    @endphp
                    <div class="card"
                         x-data="{
                             ts: {{ $ts ?? 0 }},
                             minPrev: {{ $minPrev ?? 0 }},
                             elapsed: '',
                             pct: 0,
                             colorClass: 'ok',
                             tick() {
                                 if (!this.ts) return;
                                 const secs = Math.floor(Date.now() / 1000) - this.ts;
                                 const h = Math.floor(secs / 3600);
                                 const m = Math.floor((secs % 3600) / 60);
                                 const s = secs % 60;
                                 this.elapsed = String(h).padStart(2,'0') + ':' + String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
                                 if (this.minPrev > 0) {
                                     const minEff = secs / 60;
                                     this.pct = Math.min((minEff / this.minPrev) * 100, 100);
                                     this.colorClass = minEff > this.minPrev ? 'over' : (minEff > this.minPrev * 0.8 ? 'warn' : 'ok');
                                 }
                             }
                         }"
                         x-init="tick(); setInterval(() => tick(), 1000)">

                        <div class="card-targa">{{ $lav['targa'] }}</div>
                        <div class="card-modello">{{ $lav['modello'] }}</div>
                        <div class="card-meccanico">
                            <i class="fas fa-user" style="font-size:.8em; color:var(--muted); margin-right:.3em"></i>
                            {{ $lav['meccanico_nome'] }}
                        </div>
                        <div class="card-desc">{{ $lav['descrizione'] }}</div>
                        <div class="card-timer" x-text="elapsed"></div>

                        @if($this->hasEstimated)
                            <div class="card-progress" x-show="minPrev > 0">
                                <div class="progress-label">
                                    <span>Trascorso</span>
                                    <span>Preventivato: {{ $minPrev ? gmdate('H:i', $minPrev * 60) : '—' }}</span>
                                </div>
                                <div class="progress-bar-track">
                                    <div class="progress-bar-fill"
                                         :class="colorClass"
                                         :style="'width:' + pct + '%'"></div>
                                </div>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="empty-state">Nessuna lavorazione attiva</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════
         COLONNA 2: In attesa ricambi (sospese)
    ════════════════════════════════════════════════════════ --}}
    <div class="board-col">
        <div class="col-title">
            <i class="fas fa-box-open" style="color: var(--orange); margin-right:.3em"></i>
            In attesa ricambi
            <span class="count">{{ $this->sospese->count() }}</span>
        </div>

        <div class="col-scroll"
             x-data="{
                 scroll: 0,
                 step: 0.5,
                 init() {
                     setInterval(() => {
                         const el = this.$el;
                         const inner = el.querySelector('.col-inner');
                         if (!inner) return;
                         if (inner.scrollHeight <= el.clientHeight) { this.scroll = 0; return; }
                         this.scroll += this.step;
                         if (this.scroll >= inner.scrollHeight - el.clientHeight) this.scroll = 0;
                         inner.style.transform = 'translateY(-' + this.scroll + 'px)';
                     }, 40);
                 }
             }">
            <div class="col-inner">
                @forelse($this->sospese as $sos)
                    @php
                        $ricambi = $sos['ricambi'];
                        $extra   = count($ricambi) > 3 ? count($ricambi) - 3 : 0;
                        $shown   = array_slice($ricambi, 0, 3);
                    @endphp
                    <div class="card">
                        <div class="card-attesa-header">
                            <div class="card-targa">{{ $sos['targa'] }}</div>
                            <div class="card-giorni">
                                {{ $sos['giorni_attesa'] }} {{ $sos['giorni_attesa'] === 1 ? 'giorno' : 'giorni' }}
                            </div>
                        </div>
                        <div class="card-cognome">{{ $sos['cognome'] }}</div>
                        @if(count($ricambi) > 0)
                            <div class="card-ricambi">
                                @foreach($shown as $r)
                                    <span class="chip" title="{{ $r }}">{{ $r }}</span>
                                @endforeach
                                @if($extra > 0)
                                    <span class="chip chip-more">+{{ $extra }}</span>
                                @endif
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="empty-state">Nessun veicolo in attesa</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════
         COLONNA 3: Prossimi ingressi
    ════════════════════════════════════════════════════════ --}}
    <div class="board-col">
        <div class="col-title">
            <i class="fas fa-calendar-check" style="color: var(--blue); margin-right:.3em"></i>
            Prossimi ingressi
            <span class="count">{{ $this->appuntamenti->count() }}</span>
        </div>

        <div class="col-scroll"
             x-data="{
                 scroll: 0,
                 step: 0.5,
                 init() {
                     setInterval(() => {
                         const el = this.$el;
                         const inner = el.querySelector('.col-inner');
                         if (!inner) return;
                         if (inner.scrollHeight <= el.clientHeight) { this.scroll = 0; return; }
                         this.scroll += this.step;
                         if (this.scroll >= inner.scrollHeight - el.clientHeight) this.scroll = 0;
                         inner.style.transform = 'translateY(-' + this.scroll + 'px)';
                     }, 40);
                 }
             }">
            <div class="col-inner">
                @forelse($this->appuntamenti as $apt)
                    <div class="apt-row">
                        <div class="apt-time">{{ $apt['ora'] }}</div>
                        <div>
                            <span class="apt-giorno-badge {{ $apt['giorno'] === 'Oggi' ? 'badge-oggi' : 'badge-domani' }}">
                                {{ $apt['giorno'] }}
                            </span>
                        </div>
                        <div class="apt-info">
                            <div class="apt-targa">
                                {{ $apt['targa'] }}
                                @if($apt['cognome'])
                                    <span style="font-size:.8em; color:var(--muted); font-weight:400"> — {{ $apt['cognome'] }}</span>
                                @endif
                            </div>
                            <div class="apt-desc">{{ $apt['titolo'] }}</div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">Nessun appuntamento nelle prossime 48h</div>
                @endforelse
            </div>
        </div>
    </div>

</div>
