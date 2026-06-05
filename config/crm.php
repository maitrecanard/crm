<?php

return [
    /*
    | Identité du vendeur (toi) utilisée dans les scénarios de prospection.
    | Modifiable via le .env (CRM_VENDEUR_*).
    */
    'vendeur' => [
        'societe' => env('CRM_VENDEUR_SOCIETE', 'TechCare Solutions'),
        'prenom'  => env('CRM_VENDEUR_PRENOM', 'Mathieu'),
        'contact' => env('CRM_VENDEUR_CONTACT', ''),
    ],
];
