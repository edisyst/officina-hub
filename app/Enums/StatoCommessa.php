<?php

namespace App\Enums;

enum StatoCommessa: string
{
    case Bozza = 'bozza';
    case Accettata = 'accettata';
    case InLavorazione = 'in_lavorazione';
    case Sospesa = 'sospesa';
    case Completata = 'completata';
    case Consegnata = 'consegnata';
    case Fatturata = 'fatturata';

    public function label(): string
    {
        return match($this) {
            self::Bozza => 'Bozza',
            self::Accettata => 'Accettata',
            self::InLavorazione => 'In Lavorazione',
            self::Sospesa => 'Sospesa',
            self::Completata => 'Completata',
            self::Consegnata => 'Consegnata',
            self::Fatturata => 'Fatturata',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Bozza => 'badge-secondary',
            self::Accettata => 'badge-info',
            self::InLavorazione => 'badge-primary',
            self::Sospesa => 'badge-warning',
            self::Completata => 'badge-success',
            self::Consegnata => 'badge-dark',
            self::Fatturata => 'badge-light',
        };
    }

    /** Restituisce le transizioni ammesse per lo stato corrente */
    public function transizioniAmmesse(): array
    {
        return match($this) {
            self::Bozza => [self::Accettata],
            self::Accettata => [self::InLavorazione],
            self::InLavorazione => [self::Sospesa, self::Completata],
            self::Sospesa => [self::InLavorazione],
            self::Completata => [self::Consegnata],
            self::Consegnata => [self::Fatturata],
            self::Fatturata => [],
        };
    }

    public function puoTransire(self $nuovoStato): bool
    {
        return in_array($nuovoStato, $this->transizioniAmmesse());
    }
}
