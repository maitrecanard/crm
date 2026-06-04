# Déploiement du CRM sur un autre serveur

## 1. Pré-requis serveur
- PHP 8.2+, Composer, Node 20+, un serveur web (Nginx/Apache) + **HTTPS**.
- Base de données **MySQL/MariaDB** (recommandé en prod) ou SQLite.

## 2. Installation
```bash
git clone <repo-crm> && cd crm        # ou copie du dossier crm/
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
npm ci && npm run build
```

## 3. Configuration `.env`
```dotenv
APP_NAME="CRM TechCare"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://crm.exemple.fr        # URL publique, en HTTPS

# Base de données MySQL
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=crm
DB_USERNAME=crm
DB_PASSWORD=secret

# Sessions / cookies sécurisés
SESSION_SECURE_COOKIE=true
```

## 4. Migrations + compte admin
```bash
php artisan migrate --force
php artisan tinker --execute="App\Models\User::create(['name'=>'Mathieu','email'=>'mathieusiaudeau@gmail.com','password'=>bcrypt('CHANGE_MOI')]);"
```

## 5. 2FA (authentification à deux facteurs)
- Connexion → **Profil** → section « Authentification à deux facteurs » →
  **Activer**, scanner le QR (Google Authenticator / Authy / 1Password),
  **conserver les codes de récupération**, puis saisir un code pour **confirmer**.
- À la prochaine connexion, un **challenge** est demandé après le mot de passe.
- Codes de récupération utilisables si le téléphone est perdu (à usage unique).

## 5 bis. Connexion « Se connecter avec Google » (optionnel)
1. **Google Cloud Console** → *APIs & Services* → *Credentials* → *Create
   OAuth client ID* → type **Web application**.
2. **Authorized redirect URI** : `https://crm.exemple.fr/auth/google/callback`
   (doit correspondre **exactement** à `APP_URL` + `/auth/google/callback`).
3. Récupère **Client ID** et **Client secret**, puis dans `.env` :
   ```dotenv
   GOOGLE_CLIENT_ID=xxxx.apps.googleusercontent.com
   GOOGLE_CLIENT_SECRET=xxxx
   GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
   ```
4. Le bouton **« Se connecter avec Google »** apparaît alors sur la page de login.

> 🔒 Sécurité : la connexion Google ne marche **que pour un compte CRM existant**
> (liaison par email — pas de création de compte sauvage). Le `google_id` est
> **chiffré** en base avec `APP_KEY`. Un utilisateur connecté via Google n'a pas
> besoin du 2FA (Google est le fournisseur d'identité).

## 5 ter. E-mail (réinitialisation de mot de passe)
Le CRM envoie un **lien sécurisé** (token expirant 60 min, à usage unique) par
e-mail. En local, `MAIL_MAILER=log` écrit l'e-mail dans `storage/logs/laravel.log`.
**En production**, configure un vrai SMTP dans `.env` :
```dotenv
MAIL_MAILER=smtp
MAIL_HOST=mail.techcaresolutions.fr   # SMTP de ton hébergeur (ex. o2switch)
MAIL_PORT=465
MAIL_USERNAME=no-reply@techcaresolutions.fr
MAIL_PASSWORD=********
MAIL_SCHEME=ssl                       # ssl (465) ou tls (587)
MAIL_FROM_ADDRESS="no-reply@techcaresolutions.fr"
MAIL_FROM_NAME="CRM TechCare"
```
Parcours : **« Mot de passe oublié ? »** → email → lien de réinitialisation →
nouveau mot de passe. L'e-mail est en français.

## 6. API + SDK (réception des prospects depuis le moteur)
Le moteur de prospection (autre serveur) pousse ses données via l'API du CRM.

**Côté CRM — générer un token :**
```bash
php artisan crm:api-token --email=mathieusiaudeau@gmail.com
# -> copie le token (non récupérable ensuite)
```

**Côté moteur (repo `search`) — configurer puis pousser :**
```bash
export CRM_URL=https://crm.exemple.fr
export CRM_TOKEN=<le token>
python3 push_to_crm.py            # prospects (result_*.json) + appels d'offres
```

Endpoints API (auth Bearer Sanctum) :
- `POST /api/prospects` — un prospect
- `POST /api/prospects/bulk` — `{ "prospects": [...], "source": "clients_tech" }`
- `POST /api/tenders/bulk` — `{ "tenders": [...] }`

L'upsert **ne réécrit jamais** le suivi CRM (statut, notes, relances) ; il ajoute
les nouveaux et rafraîchit les données. Idempotent (dédup par URL/idweb).

**Automatiser** (cron sur le serveur moteur, après le refresh hebdo) :
```cron
30 6 * * 1 cd /chemin/search && CRM_URL=https://crm.exemple.fr CRM_TOKEN=xxx python3 push_to_crm.py >> push_crm.log 2>&1
```

## 7. Optimisations prod
```bash
php artisan config:cache && php artisan route:cache && php artisan view:cache
```
Pointer le webroot sur `crm/public`, et donner les droits d'écriture à
`storage/` et `bootstrap/cache/`.
