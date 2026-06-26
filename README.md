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

## Module Partenaires
Gestion des **apporteurs d'affaires / sous-traitants** : on leur rattache des
projets, et ils peuvent **transmettre des tâches à réaliser** depuis leur propre
espace. Onglet **Partenaires** (réservé aux comptes admin).

- **Créer un partenaire** : nom, contact, e-mail, téléphone, notes. À la création,
  un **compte de connexion** dédié est généré (rôle `partenaire`) et un **e-mail
  d'activation** part automatiquement à son adresse.
- **Activation du compte** : le partenaire reçoit un **lien signé** (valable 7 j) ;
  il y **définit son mot de passe** (≥12 car., maj/min/chiffre/symbole), ce qui
  active et connecte son compte. Bouton **« Renvoyer l'invitation »** si le lien
  a expiré. Les comptes partenaire sont **dispensés de 2FA** (accès externe limité).
- **Rattacher des projets** : depuis la fiche partenaire (rattacher / détacher un
  projet existant) ou à la **création d'un projet** (sélecteur « Partenaire »).
- **Portail partenaire** (`/portail`) : le partenaire connecté voit **ses projets**
  et **transmet une tâche** (projet, intitulé, détails, échéance). La tâche arrive
  dans le projet côté admin, marquée **source = partenaire**, et suit le cycle
  habituel (à faire → en cours → fait).
- **Rappels quotidiens** : tant qu'il reste des tâches partenaire non terminées,
  un rappel part **par e-mail + notification in-app** (cloche dans la barre de nav).
  Cadence : **7h en semaine, 10h le week-end**.
- **Suivi côté partenaire** : quand tu **prends en charge** une tâche transmise
  (statut → *en cours*) ou que tu la **termines** (statut → *fait*), le partenaire
  est **prévenu automatiquement** (e-mail + notification in-app sur son portail).

```bash
php artisan crm:rappels-partenaires            # rappel des tâches partenaire en attente
php artisan crm:rappels-partenaires --dry-run  # simulation (n'envoie rien)
```

> Cloisonnement : un partenaire connecté est limité à son portail (toute URL du
> back-office le **redirige** vers `/portail`) et ne peut transmettre une tâche que
> sur **ses propres** projets ; un admin n'a pas accès au portail (403).

## Module Assistance (API client)
Permet à un **client** de déclarer un incident **depuis son propre site**, sans
aucun accès au CRM. Tout passe par une **API cloisonnée par token** (150 car.)
qui identifie le client. Les tickets réutilisent le système de suivi existant
(références `TIC-…`, statuts, e-mails de notification client).

- **Token par client** : fiche client → carte **🛟 Assistance** → *Générer le
  token*. Le token (150 car.) est à coller sur le site du client ; un bouton
  *Régénérer* l'invalide et en émet un nouveau.
- **Sites autorisés** : seuls les **sites déclarés dans les projets** du client
  (`url_prod` / `url_preprod`) peuvent appeler l'API. Le site appelant (`Origin`,
  `Referer`, ou en-tête `X-Support-Site` côté serveur) doit correspondre — il
  détermine aussi **à quel projet** le ticket est rattaché.
- **Déclaration d'incident** : `motif`, `titre`, `description` et jusqu'à
  **4 images** (base64). Images stockées en **privé**, visibles uniquement par
  l'admin (vignettes sur la fiche projet, onglet *Production*). Le client reçoit
  un **accusé de réception** par e-mail.
- **Suivi côté client** : l'API expose les **motifs**, la **liste des tickets** et
  le **statut** de chacun (avec les messages publics) — le tout limité à ses
  propres tickets.

### API (consommée par le site du client)
```
GET  /api/support/motifs                 # motifs proposés
POST /api/support/tickets                # déclarer un incident
GET  /api/support/tickets                # ses tickets + statut
GET  /api/support/tickets/{reference}    # statut détaillé d'un ticket
```
En-têtes requis : `X-Support-Token: <token 150 car.>` et un site autorisé
(`Origin`/`Referer` en navigateur, ou `X-Support-Site` côté serveur).

SDK Python (`crm_sdk.py`), pour une intégration serveur :
```python
from crm_sdk import SupportClient
sup = SupportClient.from_env()                  # CRM_URL + CRM_SUPPORT_TOKEN + CRM_SUPPORT_SITE
sup.create_ticket("panne", "Site KO", "Erreur 500", images=["capture.png"])
sup.tickets()
```

> Cloisonnement : le client n'a **aucun compte CRM**. Le token l'identifie, et un
> client ne voit/crée que **ses** tickets, uniquement depuis un **site déclaré**
> dans ses projets.

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

## Rapport — Fonctionnalité « Partenaires » (juin 2026)

Fonctionnalité demandée : *créer un partenaire pour lui lier des projets, lui
permettre de transmettre des tâches à réaliser, et recevoir des rappels quotidiens
(7h en semaine, 10h le week-end)* ; le partenaire **valide son compte par e-mail**.

### Livré
1. **Partenaire + compte** : modèle `Partenaire`, CRUD admin, création d'un compte
   `User` (rôle `partenaire`) et **e-mail d'activation** via lien signé (7 j) →
   définition du mot de passe → connexion. Renvoi d'invitation possible.
2. **Rattachement de projets** : `projects.partenaire_id` ; attache/détache depuis
   la fiche partenaire + sélecteur à la création de projet.
3. **Transmission de tâches** : portail partenaire (`/portail`) cloisonné ; tâches
   `source=partenaire` rattachées au projet et au partenaire émetteur.
4. **Rappels** : commande `crm:rappels-partenaires` (e-mail **+** notification
   in-app), planifiée **7h en semaine / 10h le week-end**.
5. **Suivi du partenaire** : à la **prise en charge** (en cours) et à la **fin**
   (fait) d'une tâche transmise, le partenaire est notifié (e-mail + in-app).
6. **Contrôle d'accès** : middlewares `EnsureAdmin` / `EnsurePartenaire`, partenaires
   dispensés de 2FA, badge de notifications dans la barre de navigation (admin + partenaire).

### Cycle qualité
- **Développement** : 5 migrations, modèles, 5 contrôleurs, 1 commande, 1 mailable,
  1 notification, 2 middlewares, 5 pages React + nav/cloche.
- **Vérification** : `php -l` sur tous les fichiers PHP, `npm run build` (assets OK).
- **Tests unitaires/fonctionnels** : `tests/Feature/PartenairesTest.php` — **15 tests,
  40 assertions** (création+invitation, activation signée, transmission de tâche,
  cloisonnement portail/back-office, suppression en cascade du compte, rappels,
  notification du partenaire à la prise en charge et à la fin d'une tâche).
- **Exécution** : migrations appliquées, `route:list` et `schedule:list` vérifiés
  (`0 7 * * 1-5` et `0 10 * * 6,0`), commande lancée en `--dry-run`.
- **Correction** : envoi d'e-mail rendu résilient (le partenaire est créé même si
  le SMTP échoue → « Renvoyer l'invitation »).
- **Non-régression** : suite complète **47 passés / 7 échecs pré-existants**
  (tests d'auth/profil obsolètes : mots de passe faibles, inscription désactivée,
  PATCH→PUT — sans rapport avec cette fonctionnalité).

## Rapport — Module « Assistance » (API client, juin 2026)

Fonctionnalité demandée : *les clients déclarent un incident depuis leur site
(titre, description, 4 images max) ; motifs, tickets et statut récupérés via le
SDK ; le tout cloisonné par token (150 car.) qui identifie le client. Le client
n'a **aucun accès au CRM**, et seuls les **sites présents dans les projets**
peuvent appeler l'API.*

### Livré
1. **Token client (150 car.)** : `prospects.support_token`, généré/régénéré depuis
   la fiche client (carte Assistance + snippet d'intégration).
2. **API cloisonnée** (`/api/support/*`) : middleware `ResolveSupportClient` qui
   identifie le client par token **et** vérifie que le site appelant correspond à
   l'URL d'un projet (`url_prod`/`url_preprod`) — ce site détermine le projet cible.
3. **Déclaration d'incident** : réutilise le modèle `Bug` (`source=client_site`,
   `motif`), accusé de réception e-mail, jusqu'à **4 images** base64 stockées en
   **privé** (`bug_images`) et servies via une route admin authentifiée.
4. **Lecture** : endpoints motifs / liste des tickets / statut, limités aux tickets
   du client (rien d'interne exposé).
5. **SDK** : classe `SupportClient` (Python) — `motifs()`, `create_ticket()`,
   `tickets()`, `ticket()` ; images encodées en base64 depuis des fichiers.
6. **Admin** : token + intégration sur la fiche client, vignettes des images sur la
   fiche projet (onglet Production), badge « 🛟 Site client » sur les tickets.

### Cycle qualité
- **Développement** : 3 migrations, modèle `BugImage` (+ `Bug`/`Prospect` étendus),
  1 middleware, 1 contrôleur API, 1 service images, 1 contrôleur d'images privées,
  4 endpoints, SDK `SupportClient`, UI fiche client + fiche projet.
- **Vérification** : `php -l` OK, `python3 -c "import crm_sdk"` OK, `npm run build` OK.
- **Tests fonctionnels** : `tests/Feature/SupportApiTest.php` — **12 tests,
  36 assertions** (auth token, motifs, création, images base64 stockées en privé,
  limite de 4 images, base64 invalide / non-image sans orphelin, cloisonnement par
  client, restriction par site → projet, messages publics uniquement).
- **Exécution** : migrations appliquées, `route:list` vérifié.
- **Correction** : restriction « site déclaré dans un projet » ajoutée (Origin /
  Referer / `X-Support-Site`), nettoyage du ticket si une image échoue (pas d'orphelin).
- **Non-régression** : suite complète **59 passés / 7 échecs pré-existants**.
