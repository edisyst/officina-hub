<?php

namespace App\Providers;

use App\Models\Allegato;
use App\Models\Appuntamento;
use App\Models\Articolo;
use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\CompagniaAssicurativa;
use App\Models\Documento;
use App\Models\DviIspezione;
use App\Models\Fornitore;
use App\Models\Lavorazione;
use App\Models\Ponte;
use App\Models\Scadenza;
use App\Models\Sinistro;
use App\Models\Veicolo;
use App\Observers\CommessaObserver;
use App\Observers\DocumentoObserver;
use App\Policies\AllegatoPolicy;
use App\Policies\AppuntamentoPolicy;
use App\Policies\ArticoloPolicy;
use App\Policies\ClientePolicy;
use App\Policies\CommessaPolicy;
use App\Policies\CompagniaAssicurativaPolicy;
use App\Policies\DocumentoPolicy;
use App\Policies\DviIspezionePolicy;
use App\Policies\FornitorePolicy;
use App\Policies\LavorazionePolicy;
use App\Policies\PontePolicy;
use App\Policies\ScadenzaPolicy;
use App\Policies\SinistroPolicy;
use App\Policies\VeicoloPolicy;
use App\Services\MailConfigService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(Allegato::class, AllegatoPolicy::class);
        Gate::policy(Cliente::class, ClientePolicy::class);
        Gate::policy(Commessa::class, CommessaPolicy::class);
        Gate::policy(Veicolo::class, VeicoloPolicy::class);
        Gate::policy(Ponte::class, PontePolicy::class);
        Gate::policy(Appuntamento::class, AppuntamentoPolicy::class);
        Gate::policy(Lavorazione::class, LavorazionePolicy::class);
        Gate::policy(Articolo::class, ArticoloPolicy::class);
        Gate::policy(Fornitore::class, FornitorePolicy::class);
        Gate::policy(Documento::class, DocumentoPolicy::class);
        Gate::policy(CompagniaAssicurativa::class, CompagniaAssicurativaPolicy::class);
        Gate::policy(Scadenza::class, ScadenzaPolicy::class);
        Gate::policy(Sinistro::class, SinistroPolicy::class);
        Gate::policy(DviIspezione::class, DviIspezionePolicy::class);

        // Rate limiter per login: max 5 tentativi/minuto per IP
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Rate limiter per API interni: max 120 richieste/minuto per utente/IP
        RateLimiter::for('api-internal', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        // Gli admin bypassano tutti i gate
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('admin')) {
                return true;
            }
        });

        Commessa::observe(CommessaObserver::class);
        Documento::observe(DocumentoObserver::class);

        // Applica configurazione SMTP da DB (solo se non in console/test per evitare query)
        if (! $this->app->runningInConsole() || $this->app->environment('testing') === false) {
            try {
                app(MailConfigService::class)->applica();
            } catch (\Throwable) {
                // Ignora se il DB non è ancora pronto (es. prima migrate)
            }
        }
    }
}
