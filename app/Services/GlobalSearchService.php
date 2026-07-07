<?php

namespace App\Services;

use App\Models\Articolo;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\Veicolo;
use Illuminate\Support\Facades\DB;

class GlobalSearchService
{
    public function search(string $query, int $limitPerGroup = 5): array
    {
        $query = trim($query);

        if (mb_strlen($query) < 2) {
            return [];
        }

        return array_filter([
            $this->searchClienti($query, $limitPerGroup),
            $this->searchVeicoli($query, $limitPerGroup),
            $this->searchCommesse($query, $limitPerGroup),
            $this->searchArticoli($query, $limitPerGroup),
        ]);
    }

    private function searchClienti(string $query, int $limit): ?array
    {
        $isPhone = $this->looksLikePhone($query);
        $isFreeText = !$isPhone;

        $q = Cliente::query()->limit($limit);

        if ($isPhone) {
            $normalized = $this->normalizePhone($query);
            $q->where(DB::raw("REPLACE(REPLACE(REPLACE(telefono, ' ', ''), '+39', ''), '-', '')"), 'LIKE', "%{$normalized}%");
        } elseif ($this->isDriverlessDB()) {
            $q->where(function ($sub) use ($query) {
                $sub->where('nome', 'LIKE', "%{$query}%")
                    ->orWhere('cognome', 'LIKE', "%{$query}%")
                    ->orWhere('ragione_sociale', 'LIKE', "%{$query}%")
                    ->orWhere('email', 'LIKE', "%{$query}%");
            });
        } else {
            $term = $this->booleanTerm($query);
            $q->whereRaw('MATCH(nome, cognome, ragione_sociale, email) AGAINST(? IN BOOLEAN MODE)', [$term]);
        }

        $results = $q->get();

        if ($results->isEmpty()) {
            return null;
        }

        return [
            'tipo' => 'clienti',
            'label' => 'Clienti',
            'icon' => 'fas fa-user',
            'items' => $results->map(fn (Cliente $c) => [
                'label' => trim("{$c->nome} {$c->cognome}") ?: ($c->ragione_sociale ?? ''),
                'secondary' => $c->email ?? $c->telefono ?? '',
                'url' => route('clienti.show', $c),
                'quick_actions' => [
                    ['label' => 'Dettaglio cliente', 'url' => route('clienti.show', $c), 'icon' => 'fas fa-user'],
                    ['label' => 'Elenco OdL', 'url' => route('commesse.index') . "?cliente_id={$c->id}", 'icon' => 'fas fa-clipboard-list'],
                ],
            ])->toArray(),
        ];
    }

    private function searchVeicoli(string $query, int $limit): ?array
    {
        $isCode = $this->looksLikeCode($query);

        $q = Veicolo::with('clientePrincipale')->limit($limit);

        if ($isCode) {
            $q->where(function ($sub) use ($query) {
                $sub->where('targa', 'LIKE', "{$query}%")
                    ->orWhere('vin', 'LIKE', "{$query}%");
            });
        } elseif ($this->isDriverlessDB()) {
            $q->where(function ($sub) use ($query) {
                $sub->where('targa', 'LIKE', "%{$query}%")
                    ->orWhere('marca', 'LIKE', "%{$query}%")
                    ->orWhere('modello', 'LIKE', "%{$query}%");
            });
        } else {
            $term = $this->booleanTerm($query);
            $q->where(function ($sub) use ($query, $term) {
                $sub->where('targa', 'LIKE', "{$query}%")
                    ->orWhere('vin', 'LIKE', "{$query}%")
                    ->orWhereRaw('MATCH(marca, modello) AGAINST(? IN BOOLEAN MODE)', [$term]);
            });
        }

        $results = $q->get();

        if ($results->isEmpty()) {
            return null;
        }

        return [
            'tipo' => 'veicoli',
            'label' => 'Veicoli',
            'icon' => 'fas fa-car',
            'items' => $results->map(fn (Veicolo $v) => [
                'label' => "{$v->marca} {$v->modello}",
                'secondary' => $v->targa . ($v->clientePrincipale ? ' — ' . trim("{$v->clientePrincipale->nome} {$v->clientePrincipale->cognome}") : ''),
                'url' => route('veicoli.show', $v),
                'quick_actions' => [
                    ['label' => 'Nuovo OdL', 'url' => route('commesse.create') . "?veicolo_id={$v->id}", 'icon' => 'fas fa-plus'],
                    ['label' => 'Storico interventi', 'url' => route('commesse.index') . "?veicolo_id={$v->id}", 'icon' => 'fas fa-history'],
                ],
            ])->toArray(),
        ];
    }

    private function searchCommesse(string $query, int $limit): ?array
    {
        $isCode = $this->looksLikeCode($query);

        $q = Commessa::with('cliente')->limit($limit);

        if ($isCode) {
            $q->where('numero', 'LIKE', "{$query}%");
        } elseif ($this->isDriverlessDB()) {
            $q->where(function ($sub) use ($query) {
                $sub->where('numero', 'LIKE', "%{$query}%")
                    ->orWhere('descrizione_cliente', 'LIKE', "%{$query}%")
                    ->orWhere('diagnosi_tecnica', 'LIKE', "%{$query}%")
                    ->orWhere('note_interne', 'LIKE', "%{$query}%");
            });
        } else {
            $term = $this->booleanTerm($query);
            $q->where(function ($sub) use ($query, $term) {
                $sub->where('numero', 'LIKE', "{$query}%")
                    ->orWhereRaw('MATCH(descrizione_cliente, diagnosi_tecnica, note_interne) AGAINST(? IN BOOLEAN MODE)', [$term]);
            });
        }

        $results = $q->get();

        if ($results->isEmpty()) {
            return null;
        }

        return [
            'tipo' => 'commesse',
            'label' => 'Ordini di lavoro',
            'icon' => 'fas fa-clipboard',
            'items' => $results->map(fn (Commessa $c) => [
                'label' => "OdL #{$c->numero}",
                'secondary' => ($c->cliente ? trim("{$c->cliente->nome} {$c->cliente->cognome}") . ' — ' : '') . ($c->descrizione_cliente ?? ''),
                'url' => route('commesse.show', $c),
                'quick_actions' => [
                    ['label' => 'Apri', 'url' => route('commesse.show', $c), 'icon' => 'fas fa-external-link-alt'],
                    ['label' => 'Stampa scheda', 'url' => route('commesse.show', $c) . '#stampa', 'icon' => 'fas fa-print'],
                ],
            ])->toArray(),
        ];
    }

    private function searchArticoli(string $query, int $limit): ?array
    {
        $isCode = $this->looksLikeCode($query);

        $q = Articolo::query()->limit($limit);

        if ($isCode) {
            $q->where('codice', 'LIKE', "{$query}%");
        } elseif ($this->isDriverlessDB()) {
            $q->where(function ($sub) use ($query) {
                $sub->where('codice', 'LIKE', "%{$query}%")
                    ->orWhere('descrizione', 'LIKE', "%{$query}%");
            });
        } else {
            $term = $this->booleanTerm($query);
            $q->where(function ($sub) use ($query, $term) {
                $sub->where('codice', 'LIKE', "{$query}%")
                    ->orWhereRaw('MATCH(descrizione) AGAINST(? IN BOOLEAN MODE)', [$term]);
            });
        }

        $results = $q->get();

        if ($results->isEmpty()) {
            return null;
        }

        return [
            'tipo' => 'articoli',
            'label' => 'Ricambi',
            'icon' => 'fas fa-cogs',
            'items' => $results->map(fn (Articolo $a) => [
                'label' => $a->descrizione,
                'secondary' => $a->codice . ($a->giacenza_attuale !== null ? " — Giacenza: {$a->giacenza_attuale}" : ''),
                'url' => route('magazzino.articoli.show', $a),
                'quick_actions' => [
                    ['label' => 'Giacenza e movimenti', 'url' => route('magazzino.articoli.show', $a), 'icon' => 'fas fa-boxes'],
                    ['label' => 'Movimento magazzino', 'url' => route('magazzino.movimenti') . "?articolo_id={$a->id}", 'icon' => 'fas fa-exchange-alt'],
                ],
            ])->toArray(),
        ];
    }

    private function booleanTerm(string $query): string
    {
        $words = array_filter(explode(' ', trim($query)));
        return implode(' ', array_map(fn ($w) => '+' . $w . '*', $words));
    }

    private function looksLikeCode(string $query): bool
    {
        return (bool) preg_match('/^[A-Z0-9\-\/]{2,}$/i', $query) && preg_match('/\d/', $query);
    }

    private function looksLikePhone(string $query): bool
    {
        return (bool) preg_match('/^[\d\s\+\-]{6,}$/', $query);
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/[\s\+\-]/', '', str_replace('+39', '', $phone));
    }

    private function isDriverlessDB(): bool
    {
        return config('database.default') === 'sqlite';
    }
}
