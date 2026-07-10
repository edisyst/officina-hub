<?php

namespace App\Presenters;

use App\Models\Articolo;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\Veicolo;
use Illuminate\Database\Eloquent\Model;

class RecordablePresenter
{
    public static function label(Model $record): string
    {
        return match (true) {
            $record instanceof Commessa => "OdL #{$record->numero}",
            $record instanceof Cliente  => trim("{$record->nome} {$record->cognome}") ?: ($record->ragione_sociale ?? "Cliente #{$record->id}"),
            $record instanceof Veicolo  => trim("{$record->marca} {$record->modello}") . ($record->targa ? " — {$record->targa}" : ''),
            $record instanceof Articolo => $record->descrizione,
            default                     => class_basename($record) . " #{$record->id}",
        };
    }

    public static function url(Model $record): string
    {
        return match (true) {
            $record instanceof Commessa => route('commesse.show', $record),
            $record instanceof Cliente  => route('clienti.show', $record),
            $record instanceof Veicolo  => route('veicoli.show', $record),
            $record instanceof Articolo => route('magazzino.articoli.show', $record),
            default                     => '#',
        };
    }

    public static function icon(Model $record): string
    {
        return match (true) {
            $record instanceof Commessa => 'fas fa-clipboard-list',
            $record instanceof Cliente  => 'fas fa-user',
            $record instanceof Veicolo  => 'fas fa-car',
            $record instanceof Articolo => 'fas fa-cogs',
            default                     => 'fas fa-circle',
        };
    }

    public static function tipo(Model $record): string
    {
        return match (true) {
            $record instanceof Commessa => 'commessa',
            $record instanceof Cliente  => 'cliente',
            $record instanceof Veicolo  => 'veicolo',
            $record instanceof Articolo => 'articolo',
            default                     => 'record',
        };
    }
}
