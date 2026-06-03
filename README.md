# CRM Prospects — TechCare Solutions

CRM personnel pour piloter les prospects issus du moteur de prospection
(`../result_*.json`). Stack : **Laravel 12 + Inertia + React + Tailwind**,
base **SQLite** (zéro config).

## Démarrer

```bash
cd crm
php artisan serve              # back-end sur http://127.0.0.1:8000
# dans un autre terminal (dev avec hot-reload) :
npm run dev
# …ou en prod local : npm run build  (assets déjà compilés)
```

Puis ouvre **http://127.0.0.1:8000**.

### Connexion
- **Email** : `mathieusiaudeau@gmail.com`
- **Mot de passe** : `techcare2026`  *(à changer dans « Profile »)*

## Tableau de bord (page d'accueil)
- **KPIs cliquables** : prospects, à contacter, en cours, RDV, gagnés, AO ouverts.
- **Pipeline prospects** en barres (clic → filtre la liste par statut).
- **Relances à venir (7 j)** : prospects à relancer, retards en rouge.
- **Appels d'offres urgents** : AO ouverts triés par deadline (J-x).

## Fonctionnalités (v1)
- **772 prospects** importés des 4 sources (clients tech, grands comptes, appels d'offres, PME).
- **Pipeline** : à contacter → contacté → relancé → RDV → gagné / perdu (compteurs en haut).
- **Filtres** : recherche plein-texte, par statut, par source, par secteur.
- **Fiche prospect** : infos + signal d'alerte + lien source ; édition du **statut**,
  **notes**, **prochaine relance** ; **historique d'interactions** (appel, email,
  LinkedIn, RDV, note) avec avancement automatique du pipeline.

## Module Appels d'offres
- Onglet **Appels d'offres** : les MAPA web/logiciel **encore ouverts** (BOAMP),
  triés par **date limite** avec code couleur d'urgence (J-3 rouge, J-7 orange).
- **Pipeline candidature** : à étudier → go → dossier en cours → déposé →
  gagné / perdu / abandonné (+ « expiré » auto quand la deadline passe).
- Fiche AO : objet, acheteur, deadline (J-x), **lien vers l'avis BOAMP** (DCE +
  contact), rappel de la commande de génération du dossier de réponse.
- **Lien acheteur ↔ prospect** : chaque AO est relié à son **prospect acheteur**
  (créé automatiquement s'il n'existe pas). Fiche AO → fiche du prospect, et
  fiche prospect → ses appels d'offres.
- Bouton **« ↻ Rafraîchir (BOAMP) »** ou en CLI :

```bash
php artisan crm:import-ao      # réimporte les MAPA ouverts, marque les expirés
```

## (Ré)importer les prospects

```bash
php artisan crm:import                 # lit ../result_*.json
php artisan crm:import --path=/chemin  # autre dossier
```

L'import **ne réécrit jamais** ton suivi (statut, notes, relances) : il ne fait
qu'ajouter les nouveaux prospects et rafraîchir les données sources. Pratique
après le refresh hebdo des appels d'offres.

## Sécurité & déploiement
- **2FA (TOTP)** : Profil → « Authentification à deux facteurs » → Activer →
  scanner le QR (Google Authenticator/Authy/1Password) → confirmer. Un challenge
  est demandé à chaque connexion ; codes de récupération fournis.
- **API + SDK** : le moteur de prospection (autre serveur) pousse ses données
  via l'API token (Sanctum). Token : `php artisan crm:api-token`. Envoi côté
  moteur : `python3 push_to_crm.py` (avec `CRM_URL` + `CRM_TOKEN`).
- 👉 Guide complet : [`DEPLOIEMENT.md`](DEPLOIEMENT.md).

## Notes
- Base : `database/database.sqlite` (locale). Pour passer à MySQL/MariaDB :
  ajuster `DB_*` dans `.env` puis `php artisan migrate:fresh && php artisan crm:import`.
- Données perso : ce dossier `crm/` est **gitignoré** par le repo `search`.
