# App mobile NativePHP — guide de mise en place

> ⚠️ **NativePHP Mobile** requiert **PHP 8.3**, une **licence** (nativephp.com) et
> un **build sur ta machine** (Xcode pour iOS, Android Studio pour Android). Ce
> guide te donne tout le code nécessaire ; la compilation se fait chez toi.

## Architecture
Le CRM (serveur, PHP 8.2) **n'est pas** embarqué dans le mobile. L'app mobile est
un **projet séparé** (Laravel + NativePHP, PHP 8.3) qui consomme l'**API du CRM**
(token Sanctum) — exactement l'API déjà en place :

```
 App mobile (NativePHP, on-device)  ──HTTPS + token──►  CRM serveur (/api/*)
   login, liste prospects, fiches,                       Sanctum
   maj statut, AO…
```

### Endpoints disponibles (déjà testés)
| Méthode | Endpoint | Usage |
|---|---|---|
| POST | `/api/login` | email+password → `{ token }` |
| GET  | `/api/me` | profil courant |
| GET  | `/api/stats` | KPIs pipeline |
| GET  | `/api/prospects?q=&statut=&source_fichier=` | liste paginée |
| GET  | `/api/prospects/{id}` | fiche + interactions + AO |
| PATCH| `/api/prospects/{id}` | `{statut, notes, prochaine_relance}` |
| POST | `/api/prospects/{id}/interactions` | `{type, note}` |
| GET  | `/api/tenders` | appels d'offres |
| POST | `/api/logout` | révoque le token |

## 1. Pré-requis
- **PHP 8.3** (uniquement pour CE projet mobile), Composer, Node.
- **Licence NativePHP Mobile** + `NATIVEPHP_LICENSE_KEY`.
- **iOS** : macOS + Xcode. **Android** : Android Studio + SDK.

## 2. Créer le projet mobile
```bash
laravel new crm-mobile && cd crm-mobile
composer require nativephp/mobile
php artisan native:install        # configure le projet (app id, nom…)
```
Dans `.env` du projet mobile :
```dotenv
NATIVEPHP_APP_ID=fr.techcare.crm
NATIVEPHP_APP_NAME="CRM TechCare"
NATIVEPHP_LICENSE_KEY=xxxxx
CRM_API_URL=https://crm.tonserveur.fr
```

## 3. Client API (à créer : `app/Support/CrmApi.php`)
```php
<?php
namespace App\Support;

use Illuminate\Support\Facades\Http;
use Native\Mobile\Facades\SecureStorage;   // stockage chiffré du device

class CrmApi
{
    private function base(): string { return rtrim(config('crm.api_url'), '/').'/api'; }
    private function token(): ?string { return SecureStorage::get('crm_token'); }

    public function login(string $email, string $password): bool
    {
        $r = Http::acceptJson()->post($this->base().'/login', [
            'email' => $email, 'password' => $password, 'device' => 'mobile',
        ]);
        if ($r->successful()) { SecureStorage::set('crm_token', $r->json('token')); return true; }
        return false;
    }

    private function http() { return Http::acceptJson()->withToken($this->token()); }

    public function stats(): array          { return $this->http()->get($this->base().'/stats')->json(); }
    public function prospects(array $q = []) { return $this->http()->get($this->base().'/prospects', $q)->json(); }
    public function prospect(int $id): array { return $this->http()->get($this->base()."/prospects/$id")->json(); }
    public function setStatut(int $id, string $s) { return $this->http()->patch($this->base()."/prospects/$id", ['statut' => $s])->json(); }
    public function tenders(): array        { return $this->http()->get($this->base().'/tenders')->json(); }
}
```
Ajoute `config/crm.php` : `return ['api_url' => env('CRM_API_URL')];`.

## 4. Écrans (Livewire — recommandé par NativePHP)
```bash
composer require livewire/livewire
php artisan make:livewire Auth/Login
php artisan make:livewire Prospects/Index
```
Exemple `Prospects/Index` (logique) :
```php
public array $prospects = [];
public string $statut = '';
public function mount(\App\Support\CrmApi $crm) {
    $this->prospects = $crm->prospects(['statut' => $this->statut])['data'] ?? [];
}
```
Vue : une liste cliquable → écran fiche (`prospect($id)`), boutons de statut (`setStatut`).

## 5. Lancer / builder
```bash
php artisan native:run ios          # ou : android  (émulateur/appareil)
php artisan native:build ios        # binaire de distribution
```

## 6. Notes
- **2FA** : l'app utilise un **token** (login email+password) qui **contourne le
  2FA web**. Protège-le (stockage sécurisé du device) ; tu peux le révoquer côté
  CRM (`personal_access_tokens`) ou via `POST /api/logout`.
- **Hors-ligne** : NativePHP permet une base locale ; pour la v1, vise du
  **online-only** (les données vivent sur le serveur).
- En attendant le build natif, la **PWA** (déjà en place) rend le CRM
  installable sur mobile sans licence — voir le README.
```
