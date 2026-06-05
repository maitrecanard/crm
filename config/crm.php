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
    | Expéditeur des e-mails de SUPPORT (suivi des bugs côté client) — distinct
    | de l'identité CRM. Le from utilise une adresse valide de ton domaine.
    */
    'support' => [
        'email' => env('CRM_SUPPORT_EMAIL', env('MAIL_FROM_ADDRESS', 'support@techcaresolutions.fr')),
        'name'  => env('CRM_SUPPORT_NAME', 'Support TechCare Solutions'),
        // Copie cachée (BCC) reçue à chaque e-mail de ticket. Vide = pas de copie.
        'copy'  => env('CRM_SUPPORT_COPY', env('CRM_SUPPORT_EMAIL', env('MAIL_FROM_ADDRESS'))),
    ],

    /*
    | Sécurité
    | - allow_registration : inscription publique (/register). FAUX par défaut :
    |   un CRM privé ne doit pas laisser n'importe qui créer un compte.
    | - allow_remote_migrate : endpoint SDK POST /api/maintenance/migrate.
    */
    'allow_registration'   => (bool) env('CRM_ALLOW_REGISTRATION', false),
    'allow_remote_migrate' => (bool) env('CRM_ALLOW_REMOTE_MIGRATE', true),
    'require_2fa'          => (bool) env('CRM_REQUIRE_2FA', true),
];
