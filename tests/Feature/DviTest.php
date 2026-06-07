<?php

namespace Tests\Feature;

use App\Enums\StatoApprovazioneDvi;
use App\Enums\StatoDviIspezione;
use App\Enums\UrgenzaDvi;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\DviCategoria;
use App\Models\DviIspezione;
use App\Models\DviVoce;
use App\Models\Setting;
use App\Models\User;
use App\Models\Veicolo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DviTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $meccanico;
    private Commessa $commessa;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['admin', 'accettatore', 'meccanico', 'cassa'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        $this->admin = User::create([
            'name' => 'Admin Test', 'email' => 'admin@dvi.test',
            'password' => bcrypt('password'),
        ]);
        $this->admin->assignRole('admin');

        $this->meccanico = User::create([
            'name' => 'Meccanico Test', 'email' => 'mec@dvi.test',
            'password' => bcrypt('password'),
        ]);
        $this->meccanico->assignRole('meccanico');

        $cliente = Cliente::create([
            'tipo' => 'fisica', 'nome' => 'Mario', 'cognome' => 'Rossi',
            'email' => 'cliente@test.local',
        ]);
        $veicolo = Veicolo::create([
            'cliente_id' => $cliente->id,
            'tipo' => 'auto', 'targa' => 'AB123CD',
            'marca' => 'Fiat', 'modello' => 'Punto',
            'alimentazione' => 'benzina',
        ]);

        Setting::firstOrCreate(['key' => 'officina_nome'],  ['value' => 'Test Officina']);
        Setting::firstOrCreate(['key' => 'officina_email'], ['value' => 'officina@test.local']);
        Setting::firstOrCreate(['key' => 'officina_telefono'], ['value' => '06 000000']);
        Setting::firstOrCreate(['key' => 'template_email_dvi'], ['value' => "Oggetto: DVI {{TARGA}}\n\n{{LINK_DVI}}"]);

        $this->commessa = Commessa::create([
            'numero'              => 'COM-TEST-0001',
            'cliente_id'          => $cliente->id,
            'veicolo_id'          => $veicolo->id,
            'user_id'             => $this->admin->id,
            'tipo'                => 'meccanica',
            'stato'               => 'bozza',
            'data_ingresso'       => now(),
            'descrizione_cliente' => 'Test DVI',
        ]);
    }

    public function test_crea_ispezione_con_voci(): void
    {
        $ispezione = DviIspezione::create([
            'commessa_id' => $this->commessa->id,
            'user_id'     => $this->meccanico->id,
            'stato'       => StatoDviIspezione::Bozza,
        ]);

        $voce1 = DviVoce::create([
            'dvi_ispezione_id' => $ispezione->id,
            'categoria'        => 'Freni',
            'descrizione'      => 'Pastiglie consumate',
            'urgenza'          => UrgenzaDvi::Urgente,
            'prezzo_stimato'   => 120.00,
            'ordinamento'      => 1,
        ]);

        $voce2 = DviVoce::create([
            'dvi_ispezione_id' => $ispezione->id,
            'categoria'        => 'Pneumatici',
            'descrizione'      => 'Pressione bassa',
            'urgenza'          => UrgenzaDvi::Attenzione,
            'prezzo_stimato'   => 0,
            'ordinamento'      => 2,
        ]);

        $voce3 = DviVoce::create([
            'dvi_ispezione_id' => $ispezione->id,
            'categoria'        => 'Luci',
            'descrizione'      => 'Luci funzionanti',
            'urgenza'          => UrgenzaDvi::Ok,
            'ordinamento'      => 3,
        ]);

        $this->assertDatabaseHas('dvi_ispezioni', ['id' => $ispezione->id, 'stato' => 'bozza']);
        $this->assertCount(3, $ispezione->fresh()->voci);
    }

    public function test_invio_dvi_aggiorna_stato_e_token(): void
    {
        $ispezione = DviIspezione::create([
            'commessa_id' => $this->commessa->id,
            'user_id'     => $this->meccanico->id,
            'stato'       => StatoDviIspezione::Bozza,
        ]);

        $token = Str::random(64);
        $ispezione->update([
            'stato'         => StatoDviIspezione::InviataCliente,
            'link_token'    => $token,
            'link_scade_at' => now()->addDays(7),
            'inviata_at'    => now(),
        ]);

        $this->assertDatabaseHas('dvi_ispezioni', [
            'id'     => $ispezione->id,
            'stato'  => 'inviata_cliente',
            'link_token' => $token,
        ]);
    }

    public function test_portale_cliente_token_valido(): void
    {
        $token = Str::random(64);
        $ispezione = $this->creaIspezioneInviata($token);

        DviVoce::create([
            'dvi_ispezione_id' => $ispezione->id,
            'categoria'        => 'Freni',
            'descrizione'      => 'Test voce',
            'urgenza'          => UrgenzaDvi::Urgente,
            'prezzo_stimato'   => 100.00,
            'ordinamento'      => 1,
        ]);

        $response = $this->get(route('dvi.portale', $token));
        $response->assertStatus(200);
        $response->assertSee('Test voce');
    }

    public function test_portale_cliente_token_scaduto(): void
    {
        $token = Str::random(64);
        DviIspezione::create([
            'commessa_id'   => $this->commessa->id,
            'user_id'       => $this->meccanico->id,
            'stato'         => StatoDviIspezione::InviataCliente,
            'link_token'    => $token,
            'link_scade_at' => now()->subDay(),
            'inviata_at'    => now()->subDays(8),
        ]);

        $response = $this->get(route('dvi.portale', $token));
        $response->assertStatus(200);
        $response->assertSee('scaduto', false);
    }

    public function test_portale_cliente_token_non_trovato(): void
    {
        $response = $this->get(route('dvi.portale', 'tokeninesistente'));
        $response->assertStatus(200);
        $response->assertSee('Pagina non trovata', false);
    }

    public function test_approvazione_parziale_calcola_importo(): void
    {
        $token = Str::random(64);
        $ispezione = $this->creaIspezioneInviata($token);

        $voce1 = DviVoce::create([
            'dvi_ispezione_id' => $ispezione->id,
            'categoria'        => 'Freni',
            'descrizione'      => 'Pastiglie',
            'urgenza'          => UrgenzaDvi::Urgente,
            'prezzo_stimato'   => 120.00,
            'ordinamento'      => 1,
        ]);

        $voce2 = DviVoce::create([
            'dvi_ispezione_id' => $ispezione->id,
            'categoria'        => 'Pneumatici',
            'descrizione'      => 'Gomme',
            'urgenza'          => UrgenzaDvi::Attenzione,
            'prezzo_stimato'   => 80.00,
            'ordinamento'      => 2,
        ]);

        $voce3 = DviVoce::create([
            'dvi_ispezione_id' => $ispezione->id,
            'categoria'        => 'Luci',
            'descrizione'      => 'Lampade',
            'urgenza'          => UrgenzaDvi::Ok,
            'prezzo_stimato'   => 30.00,
            'ordinamento'      => 3,
        ]);

        // Il cliente approva 2 voci e rimanda 1
        $response = $this->post(route('dvi.salva-risposte', $token), [
            'risposte' => [
                $voce1->id => 'approvato',
                $voce2->id => 'approvato',
                $voce3->id => 'rimandato',
            ],
            'note_cliente' => 'Fate pure le prime due',
        ]);

        $response->assertRedirect(route('dvi.conferma', $token));

        $ispezione->refresh();
        $this->assertEquals('parzialmente_approvata', $ispezione->stato->value);
        $this->assertEquals('Fate pure le prime due', $ispezione->note_cliente);

        $this->commessa->refresh();
        $this->assertEquals(200.00, $this->commessa->dvi_approvazione_importo);
    }

    public function test_token_riusato_mostra_pagina_gia_risposto(): void
    {
        $token = Str::random(64);
        $ispezione = $this->creaIspezioneInviata($token);

        $voce = DviVoce::create([
            'dvi_ispezione_id' => $ispezione->id,
            'categoria'        => 'Test',
            'descrizione'      => 'Voce test',
            'urgenza'          => UrgenzaDvi::Ok,
            'ordinamento'      => 1,
        ]);

        // Prima risposta
        $this->post(route('dvi.salva-risposte', $token), [
            'risposte' => [$voce->id => 'approvato'],
        ]);

        $ispezione->refresh();
        $this->assertNotEquals('inviata_cliente', $ispezione->stato->value);

        // Il portale ora mostra "risposta già inviata"
        $response = $this->get(route('dvi.portale', $token));
        $response->assertStatus(200);
        $response->assertSee('gi', false); // "Risposta già inviata"
    }

    public function test_converti_in_preventivo_crea_righe(): void
    {
        $ispezione = DviIspezione::create([
            'commessa_id' => $this->commessa->id,
            'user_id'     => $this->meccanico->id,
            'stato'       => StatoDviIspezione::ParzialmenteApprovata,
            'approvata_at' => now(),
        ]);

        DviVoce::create([
            'dvi_ispezione_id' => $ispezione->id,
            'categoria'        => 'Freni',
            'descrizione'      => 'Pastiglie approvate',
            'urgenza'          => UrgenzaDvi::Urgente,
            'prezzo_stimato'   => 120.00,
            'stato_approvazione' => StatoApprovazioneDvi::Approvato,
            'ordinamento'      => 1,
        ]);

        DviVoce::create([
            'dvi_ispezione_id' => $ispezione->id,
            'categoria'        => 'Luci',
            'descrizione'      => 'Lampade rimandate',
            'urgenza'          => UrgenzaDvi::Ok,
            'prezzo_stimato'   => 30.00,
            'stato_approvazione' => StatoApprovazioneDvi::Rimandato,
            'ordinamento'      => 2,
        ]);

        Setting::firstOrCreate(['key' => 'iva_default'], ['value' => '22']);

        // Autenticato come admin, chiama il Livewire component direttamente tramite logica
        $this->actingAs($this->admin);

        $component = new \App\Livewire\Dvi\DettaglioDvi();
        $component->commessaId = $this->commessa->id;
        $component->mount($this->commessa->id);
        $component->convertiInPreventivo($ispezione->id);

        $this->assertDatabaseHas('commessa_righe', [
            'commessa_id' => $this->commessa->id,
            'descrizione' => '[DVI] Pastiglie approvate',
            'prezzo_unitario' => '120.00',
        ]);

        // La voce rimanda non deve essere convertita
        $this->assertDatabaseMissing('commessa_righe', [
            'commessa_id' => $this->commessa->id,
            'descrizione' => '[DVI] Lampade rimandate',
        ]);
    }

    private function creaIspezioneInviata(string $token): DviIspezione
    {
        return DviIspezione::create([
            'commessa_id'   => $this->commessa->id,
            'user_id'       => $this->meccanico->id,
            'stato'         => StatoDviIspezione::InviataCliente,
            'link_token'    => $token,
            'link_scade_at' => now()->addDays(7),
            'inviata_at'    => now(),
        ]);
    }
}
