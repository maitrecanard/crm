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

    /*
    | Sécurité
    | - allow_registration : inscription publique (/register). FAUX par défaut :
    |   un CRM privé ne doit pas laisser n'importe qui créer un compte.
    | - allow_remote_migrate : endpoint SDK POST /api/maintenance/migrate.
    */
    'allow_registration'   => (bool) env('CRM_ALLOW_REGISTRATION', false),
    'allow_remote_migrate' => (bool) env('CRM_ALLOW_REMOTE_MIGRATE', true),
];
