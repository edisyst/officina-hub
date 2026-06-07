<?php

namespace App\Enums;

enum StatoCarrozzeria: string
{
    case Accettazione              = 'accettazione';
    case Smontaggio                = 'smontaggio';
    case RiscontroDanni            = 'riscontro_danni';
    case InLavorazioneLamiera      = 'in_lavorazione_lamiera';
    case InLavorazioneVerniciatura = 'in_lavorazione_verniciatura';
    case Rimontaggio               = 'rimontaggio';
    case ControlloQualita          = 'controllo_qualita';
    case Consegna                  = 'consegna';

    public function label(): string
    {
        return match($this) {
            self::Accettazione              => 'Accettazione',
            self::Smontaggio                => 'Smontaggio',
            self::RiscontroDanni            => 'Riscontro Danni',
            self::InLavorazioneLamiera      => 'Lav. Lamiera',
            self::InLavorazioneVerniciatura => 'Verniciatura',
            self::Rimontaggio               => 'Rimontaggio',
            self::ControlloQualita          => 'Controllo Qualità',
            self::Consegna                  => 'Consegna',
        };
    }

    public function ordine(): int
    {
        return match($this) {
            self::Accettazione              => 1,
            self::Smontaggio                => 2,
            self::RiscontroDanni            => 3,
            self::InLavorazioneLamiera      => 4,
            self::InLavorazioneVerniciatura => 5,
            self::Rimontaggio               => 6,
            self::ControlloQualita          => 7,
            self::Consegna                  => 8,
        };
    }

    public function successiva(): ?self
    {
        return match($this) {
            self::Accettazione              => self::Smontaggio,
            self::Smontaggio                => self::RiscontroDanni,
            self::RiscontroDanni            => self::InLavorazioneLamiera,
            self::InLavorazioneLamiera      => self::InLavorazioneVerniciatura,
            self::InLavorazioneVerniciatura => self::Rimontaggio,
            self::Rimontaggio               => self::ControlloQualita,
            self::ControlloQualita          => self::Consegna,
            self::Consegna                  => null,
        };
    }

    /** @return self[] */
    public static function inOrdine(): array
    {
        return [
            self::Accettazione,
            self::Smontaggio,
            self::RiscontroDanni,
            self::InLavorazioneLamiera,
            self::InLavorazioneVerniciatura,
            self::Rimontaggio,
            self::ControlloQualita,
            self::Consegna,
        ];
    }
}
