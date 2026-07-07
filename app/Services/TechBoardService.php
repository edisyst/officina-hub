<?php

namespace App\Services;

use App\Enums\StatoCommessa;
use App\Enums\TipoRiga;
use App\Models\Appuntamento;
use App\Models\Commessa;
use App\Models\Lavorazione;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class TechBoardService
{
    /** True se la colonna ore preventivate è presente (graceful degradation). */
    public function hasEstimatedMinutes(): bool
    {
        return Schema::hasColumn('lavorazioni', 'minuti_preventivati');
    }

    /** Meccanici con timer attivo — una riga per lavorazione. */
    public function lavorazioniAttive(): Collection
    {
        $withEstimated = $this->hasEstimatedMinutes();

        return Lavorazione::attive()
            ->with(['meccanico', 'commessa.veicolo', 'commessa.cliente'])
            ->get()
            ->map(fn(Lavorazione $l) => [
                'meccanico_nome'      => $l->meccanico?->name ?? '—',
                'targa'               => $l->commessa?->veicolo?->targa ?? '—',
                'modello'             => trim(($l->commessa?->veicolo?->marca ?? '') . ' ' . ($l->commessa?->veicolo?->modello ?? '')),
                'descrizione'         => $l->descrizione ?? '',
                'started_at_ts'       => $l->started_at?->timestamp,
                'minuti_preventivati' => $withEstimated ? ($l->minuti_preventivati ?: null) : null,
            ]);
    }

    /** OdL sospesi (in attesa ricambi). */
    public function commesseSospese(): Collection
    {
        return Commessa::where('stato', StatoCommessa::Sospesa)
            ->with(['veicolo', 'cliente', 'righe' => fn($q) => $q->where('tipo', TipoRiga::Articolo->value)])
            ->get()
            ->map(fn(Commessa $c) => [
                'targa'         => $c->veicolo?->targa ?? '—',
                'cognome'       => $c->cliente?->cognome ?? '—',
                'giorni_attesa' => (int) now()->diffInDays($c->data_ingresso),
                'ricambi'       => $c->righe->map(fn($r) => $r->descrizione)->values()->all(),
            ]);
    }

    /** Appuntamenti di oggi e domani, ordinati per ora. */
    public function prossimiAppuntamenti(): Collection
    {
        return Appuntamento::whereBetween('data_ora_inizio', [
            now()->startOfDay(),
            now()->addDay()->endOfDay(),
        ])
            ->with(['veicolo', 'cliente'])
            ->orderBy('data_ora_inizio')
            ->get()
            ->map(fn(Appuntamento $a) => [
                'ora'     => $a->data_ora_inizio->format('H:i'),
                'giorno'  => $a->data_ora_inizio->isToday() ? 'Oggi' : 'Domani',
                'targa'   => $a->veicolo?->targa ?? '—',
                'cognome' => $a->cliente?->cognome ?? '',
                'titolo'  => $a->titolo,
            ]);
    }
}
