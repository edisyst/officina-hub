<?php

return [
    /*
     * Token segreto per l'accesso alla Tech Board senza login.
     * Generare con: php artisan tinker --execute="echo Str::random(64);"
     * Usare solo su rete locale dell'officina — la pagina espone dati operativi (targhe, nomi).
     */
    'token' => env('TECHBOARD_TOKEN', ''),
];
