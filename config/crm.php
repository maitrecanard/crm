<?php

return [
    /*
    | Identité du vendeur (toi) utilisée dans les scénarios de prospection.
    | Modifiable via le .env (CRM_VENDEUR_*).
    */
    'vendeur' => [
        'societe' => env('CRM_VENDEUR_SOCIETE', 'TechCare Solutions'),
        'prenom'  => env('CRM_VENDEUR_PRENOM', 'Mathieu Siaudeau'),
        'contact' => env('CRM_VENDEUR_CONTACT', '07.62.61.26.46'),
        // Expéditeur ET adresse de réponse des e-mails de prospection (depuis le CRM).
        'email'   => env('CRM_VENDEUR_EMAIL', 'mathieu.siaudeau@techcaresolutions.fr'),
        // Mentions légales (en-tête des contrats).
        'forme'   => env('CRM_VENDEUR_FORME', 'micro-entreprise'),
        'siret'   => env('CRM_VENDEUR_SIRET', '922 818 547 00015'),
        'adresse' => env('CRM_VENDEUR_ADRESSE', 'Valdivienne (86)'),
        'tva'     => env('CRM_VENDEUR_TVA', 'TVA non applicable, art. 293 B du CGI'),
    ],

    /*
    | Contrats : modèle de conditions par défaut. Modifiable en base via la page
    | Paramètres (clé `contrat_conditions`) — ce texte ne sert que de valeur
    | initiale. Chaque contrat copie le modèle courant et reste éditable.
    */
    'contrat' => [
        'conditions_defaut' => env('CRM_CONTRAT_CONDITIONS', "Article 1 — Objet\nLe présent contrat définit les conditions dans lesquelles le Prestataire réalise pour le Client les prestations décrites en objet ci-dessus.\n\nArticle 2 — Durée et exécution\nLes prestations sont réalisées en télétravail, selon le calendrier convenu entre les Parties. Le Client dispose de 10 jours pour valider les livrables ; à défaut de retour motivé, ils sont réputés acceptés.\n\nArticle 3 — Obligations du Prestataire\nLe Prestataire exécute les prestations avec diligence et professionnalisme (obligation de moyens), informe le Client de l'avancement et respecte la confidentialité.\n\nArticle 4 — Obligations du Client\nLe Client fournit en temps utile les informations, accès et validations nécessaires, désigne un interlocuteur décisionnaire et paie le prix aux échéances convenues.\n\nArticle 5 — Prix et paiement\nPrix indiqué en objet, exprimé en euros HT. TVA non applicable, art. 293 B du CGI. Acompte de 30 % à la commande, solde à la livraison. Paiement à 30 jours par virement. Tout retard donne lieu aux pénalités légales (3× le taux d'intérêt légal) et à une indemnité de 40 € (art. L441-10 du Code de commerce).\n\nArticle 6 — Propriété intellectuelle\nLes droits sur les livrables spécifiques sont cédés au Client après paiement intégral du prix.\n\nArticle 7 — Confidentialité\nChaque Partie s'engage à ne pas divulguer les informations confidentielles de l'autre, pendant la durée du contrat et 2 ans après son terme.\n\nArticle 8 — Résiliation\nEn cas de manquement grave non réparé sous 15 jours après mise en demeure, la Partie lésée peut résilier le contrat de plein droit. Les prestations réalisées restent dues.\n\nArticle 9 — Litiges\nLe présent contrat est régi par le droit français. À défaut d'accord amiable, compétence est attribuée aux tribunaux du ressort du Prestataire."),
    ],

    /*
    | Génération de mails de prospection par IA (API Anthropic). Clé requise
    | (ANTHROPIC_API_KEY). Sans clé, le bouton « Générer » renvoie une erreur claire.
    */
    'ai' => [
        'key'   => env('ANTHROPIC_API_KEY', ''),
        'model' => env('CRM_AI_MODEL', 'claude-sonnet-4-6'),
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
