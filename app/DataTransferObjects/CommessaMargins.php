<?php

namespace App\DataTransferObjects;

class CommessaMargins
{
    public function __construct(
        // Ricambi
        public readonly float $ricavoRicambi,
        public readonly float $costoRicambi,
        public readonly float $margineRicambi,
        public readonly float $margineRicambiPerc,

        // Manodopera
        public readonly float $ricavoManodopera,
        public readonly ?float $costoManodopera, // null se costo non configurato

        // Ore
        public readonly float $orePreventivate,
        public readonly float $oreEffettive,
        public readonly float $deltaOre,

        // Totali
        public readonly float $ricavoTotale,
        public readonly float $costoTotale,
        public readonly float $margineTotale,
        public readonly float $margineTotalePerc,

        // Trasparenza su dati mancanti
        public readonly int $righeRicambiSenzaCosto,
        public readonly int $righeManodoperaSenzaStima,
    ) {}

    public function progressoOrePerc(): float
    {
        if ($this->orePreventivate <= 0) {
            return 0.0;
        }
        return min(round($this->oreEffettive / $this->orePreventivate * 100, 1), 999.9);
    }

    public function oreInBudget(): bool
    {
        return $this->orePreventivate <= 0 || $this->oreEffettive <= $this->orePreventivate;
    }
}
