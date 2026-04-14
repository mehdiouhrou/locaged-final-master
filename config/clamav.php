<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Antivirus (ClamAV) — scan des fichiers uploadés
    |--------------------------------------------------------------------------
    |
    | Si enabled=true, chaque fichier temporaire Livewire est analysé après sélection.
    | Nécessite l’exécutable `clamscan` (paquet clamav sur Debian/Ubmac) ou adapter
    | ClamAvScanner pour clamdscan / socket INSTREAM.
    |
    */

    'enabled' => env('CLAMAV_ENABLED', false),

    /** Binaire clamscan (chemin absolu si besoin) */
    'binary' => env('CLAMAV_BINARY', 'clamscan'),

];
