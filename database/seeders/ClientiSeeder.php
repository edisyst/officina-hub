<?php

namespace Database\Seeders;

use App\Models\Cliente;
use Illuminate\Database\Seeder;

class ClientiSeeder extends Seeder
{
    public function run(): void
    {
        $clienti = [
            [
                'tipo'           => 'fisica',
                'nome'           => 'Giovanni',
                'cognome'        => 'Rossi',
                'telefono'       => '333 1234567',
                'email'          => 'giovanni.rossi@email.it',
                'indirizzo'      => 'Via Garibaldi 12',
                'citta'          => 'Milano',
                'cap'            => '20100',
                'provincia'      => 'MI',
                'codice_fiscale' => 'RSSGNN80A01F205X',
            ],
            [
                'tipo'      => 'fisica',
                'nome'      => 'Anna',
                'cognome'   => 'Verdi',
                'telefono'  => '347 9876543',
                'email'     => 'anna.verdi@email.it',
                'indirizzo' => 'Corso Italia 45',
                'citta'     => 'Roma',
                'cap'       => '00100',
                'provincia' => 'RM',
            ],
            [
                'tipo'      => 'fisica',
                'nome'      => 'Carlo',
                'cognome'   => 'Esposito',
                'telefono'  => '320 5551234',
                'email'     => 'carlo.esposito@email.it',
                'indirizzo' => 'Via Napoli 8',
                'citta'     => 'Napoli',
                'cap'       => '80100',
                'provincia' => 'NA',
            ],
            [
                'tipo'            => 'giuridica',
                'ragione_sociale' => 'Trasporti Sud S.r.l.',
                'partita_iva'     => '12345678901',
                'telefono'        => '081 4567890',
                'email'           => 'info@trasportisud.it',
                'indirizzo'       => 'Via Industriale 33',
                'citta'           => 'Salerno',
                'cap'             => '84100',
                'provincia'       => 'SA',
            ],
            [
                'tipo'            => 'giuridica',
                'ragione_sociale' => 'Ediltech S.p.A.',
                'partita_iva'     => '98765432101',
                'telefono'        => '02 9988776',
                'email'           => 'amministrazione@ediltech.it',
                'indirizzo'       => 'Viale Europa 100',
                'citta'           => 'Milano',
                'cap'             => '20100',
                'provincia'       => 'MI',
            ],
            [
                'tipo'      => 'fisica',
                'nome'      => 'Marta',
                'cognome'   => 'Lombardi',
                'telefono'  => '389 1112233',
                'email'     => 'marta.lombardi@email.it',
                'indirizzo' => 'Via Manzoni 7',
                'citta'     => 'Torino',
                'cap'       => '10100',
                'provincia' => 'TO',
            ],
        ];

        foreach ($clienti as $data) {
            Cliente::firstOrCreate(['email' => $data['email']], $data);
        }
    }
}
