<?php

namespace App\Services\Recommendations;

use App\Models\MaintenanceRule;
use App\Models\Scadenza;
use App\Models\VehicleRecommendation;
use App\Models\Veicolo;
use Carbon\Carbon;

class RecommendationEngineService
{
    public function refreshFor(Veicolo $vehicle): void
    {
        $this->refreshDeadlines($vehicle);
        $this->refreshMileage($vehicle);
    }

    private function refreshDeadlines(Veicolo $vehicle): void
    {
        // Feature check: scadenzario table may not exist in all deployments
        if (! \Illuminate\Support\Facades\Schema::hasTable('scadenze')) {
            return;
        }

        $horizon = now()->addDays(config('recommendations.deadline_horizon_days', 60));

        Scadenza::where('veicolo_id', $vehicle->id)
            ->where('data_scadenza', '<=', $horizon)
            ->whereNull('deleted_at')
            ->get()
            ->each(function (Scadenza $scadenza) use ($vehicle) {
                $title = $scadenza->descrizione ?? $scadenza->tipo->label();

                $this->upsertRecommendation($vehicle, 'deadline', $title, [
                    'description' => null,
                    'due_date'    => $scadenza->data_scadenza,
                    'due_km'      => null,
                ]);
            });
    }

    private function refreshMileage(Veicolo $vehicle): void
    {
        $kmAttuali = (int) $vehicle->km_attuali;

        MaintenanceRule::active()->get()->each(function (MaintenanceRule $rule) use ($vehicle, $kmAttuali) {
            // Find last execution: FK match on recommendations, else fuzzy title match on righe
            $lastKm   = null;
            $lastDate = null;

            // Check resolved recommendations for this rule + vehicle
            $lastResolved = VehicleRecommendation::where('vehicle_id', $vehicle->id)
                ->where('title', $rule->name)
                ->where('status', 'accepted')
                ->whereNotNull('resolved_work_order_id')
                ->latest()
                ->first();

            if ($lastResolved) {
                $lastKm   = $lastResolved->due_km;
                $lastDate = $lastResolved->updated_at;
            } else {
                // Fuzzy title match on commessa_righe for this vehicle's work orders
                $lastRiga = \Illuminate\Support\Facades\DB::table('commessa_righe')
                    ->join('commesse', 'commesse.id', '=', 'commessa_righe.commessa_id')
                    ->where('commesse.veicolo_id', $vehicle->id)
                    ->whereNull('commesse.deleted_at')
                    ->whereRaw('LOWER(commessa_righe.descrizione) = ?', [strtolower($rule->name)])
                    ->where('commessa_righe.outcome', '!=', 'declined')
                    ->orderByDesc('commesse.data_ingresso')
                    ->select(['commesse.km_ingresso', 'commesse.data_ingresso'])
                    ->first();

                if ($lastRiga) {
                    $lastKm   = $lastRiga->km_ingresso;
                    $lastDate = Carbon::parse($lastRiga->data_ingresso);
                }
            }

            $needsByKm = $rule->every_km !== null
                && $lastKm !== null
                && ($kmAttuali - (int) $lastKm) >= $rule->every_km;

            $needsByKmNoHistory = $rule->every_km !== null && $lastKm === null;

            $needsByMonths = $rule->every_months !== null
                && $lastDate !== null
                && Carbon::parse($lastDate)->addMonths($rule->every_months)->isPast();

            $needsByMonthsNoHistory = $rule->every_months !== null && $lastDate === null;

            if (! ($needsByKm || $needsByKmNoHistory || $needsByMonths || $needsByMonthsNoHistory)) {
                return;
            }

            $this->upsertRecommendation($vehicle, 'mileage', $rule->name, [
                'description' => null,
                'due_date'    => $rule->every_months ? now()->addMonths($rule->every_months)->toDateString() : null,
                'due_km'      => $rule->every_km ? ($lastKm ?? $kmAttuali) + $rule->every_km : null,
            ]);
        });
    }

    private function upsertRecommendation(Veicolo $vehicle, string $source, string $title, array $extra): void
    {
        $exists = VehicleRecommendation::where('vehicle_id', $vehicle->id)
            ->where('source', $source)
            ->where('title', $title)
            ->where('status', 'pending')
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) {
            return;
        }

        VehicleRecommendation::create(array_merge([
            'vehicle_id' => $vehicle->id,
            'source'     => $source,
            'title'      => $title,
            'status'     => 'pending',
        ], $extra));
    }
}
