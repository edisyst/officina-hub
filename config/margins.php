<?php

return [
    /*
     * Costo orario interno manodopera (€/h).
     * Se null, il costo manodopera non viene calcolato e i margini mostrano
     * solo il ricavo. Sovrascrivibile via env LABOR_COST_PER_HOUR.
     * Il valore per-meccanico (users.costo_orario) ha priorità su questo.
     */
    'labor_cost_per_hour' => env('LABOR_COST_PER_HOUR') !== null
        ? (float) env('LABOR_COST_PER_HOUR')
        : null,
];
