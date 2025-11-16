# 🔒 Security Policy

## Supported Versions

Les versions suivantes de la B2B Wholesale Platform reçoivent des mises à jour de sécurité:

| Version | Supported          |
| ------- | ------------------ |
| 1.0.x   | :white_check_mark: |
| < 1.0   | :x:                |

## Reporting a Vulnerability

Nous prenons la sécurité très au sérieux. Si vous découvrez une vulnérabilité de sécurité, merci de suivre ces étapes:

### 1. NE PAS créer d'issue publique

Les vulnérabilités de sécurité doivent être rapportées de manière privée pour protéger les utilisateurs.

### 2. Contacter l'équipe de sécurité

Envoyez un email à: **security@votre-domaine.com**

Incluez:
- Description détaillée de la vulnérabilité
- Étapes pour reproduire le problème
- Impact potentiel
- Version affectée
- Suggestion de correction (si applicable)

### 3. Délai de réponse

- **Accusé de réception**: Sous 48 heures
- **Évaluation initiale**: Sous 1 semaine
- **Correction**: Selon la criticité (voir ci-dessous)

### 4. Niveaux de criticité

| Niveau | Délai de correction | Exemples |
|--------|---------------------|----------|
| **Critique** | 24-48 heures | Injection SQL, RCE, authentification bypass |
| **Élevé** | 1 semaine | XSS stocké, CSRF, escalade de privilèges |
| **Moyen** | 2-4 semaines | Divulgation d'informations, DoS |
| **Faible** | Prochaine release | Problèmes de configuration |

## Security Best Practices

### Configuration de production

#### 1. Variables d'environnement

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=<généré avec php artisan key:generate>
```

**⚠️ IMPORTANT**:
- Ne jamais exposer `APP_DEBUG=true` en production
- Utiliser une `APP_KEY` forte et unique
- Ne jamais commiter le fichier `.env`

#### 2. Base de données

```env
DB_PASSWORD=<mot de passe fort, minimum 16 caractères>
```

**Recommandations**:
- Utilisateur DB avec privilèges minimaux nécessaires
- Mot de passe aléatoire (lettres, chiffres, symboles)
- Connexion uniquement depuis localhost si possible
- Backups chiffrés

#### 3. Redis

```env
REDIS_PASSWORD=<mot de passe fort>
```

**Configuration Redis** (`/etc/redis/redis.conf`):
```
requirepass <STRONG_PASSWORD>
bind 127.0.0.1
```

#### 4. Sessions & Cookies

```env
SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

### Headers de sécurité

Le fichier `config/app.php` doit inclure:

```php
'headers' => [
    'X-Frame-Options' => 'SAMEORIGIN',
    'X-Content-Type-Options' => 'nosniff',
    'X-XSS-Protection' => '1; mode=block',
    'Referrer-Policy' => 'strict-origin-when-cross-origin',
    'Content-Security-Policy' => "default-src 'self'",
],
```

### Authentification

#### Laravel Sanctum

```php
// config/sanctum.php
'expiration' => 60, // minutes
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,127.0.0.1')),
```

#### Limitation de taux (Rate Limiting)

```php
// app/Http/Kernel.php
'api' => [
    'throttle:60,1', // 60 requêtes par minute
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

### Validation des entrées

**TOUJOURS** valider les entrées utilisateur:

```php
// ✅ Bon
$validated = $request->validate([
    'email' => 'required|email|max:255',
    'amount' => 'required|numeric|min:0|max:999999.999',
    'quantity' => 'required|integer|min:1|max:10000',
]);

// ❌ Mauvais
$email = $request->input('email'); // Non validé
```

### Protection CSRF

Laravel protège automatiquement contre CSRF. Assurez-vous:

```blade
<!-- Dans les formulaires Blade -->
<form method="POST">
    @csrf
    <!-- ... -->
</form>
```

```javascript
// Pour AJAX avec Axios
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
```

### Injection SQL

**TOUJOURS** utiliser Query Builder ou Eloquent:

```php
// ✅ Bon - Protégé contre injection SQL
User::where('email', $email)->first();
DB::table('users')->where('email', $email)->get();

// ❌ Mauvais - Vulnérable à injection SQL
DB::select("SELECT * FROM users WHERE email = '$email'");
```

### XSS (Cross-Site Scripting)

Blade échappe automatiquement:

```blade
<!-- ✅ Bon - Échappé automatiquement -->
{{ $user->name }}

<!-- ⚠️ Dangereux - Utiliser seulement si vous êtes sûr -->
{!! $trustedHtml !!}
```

### Mass Assignment

Protégez vos modèles:

```php
// app/Models/User.php
class User extends Authenticatable
{
    protected $fillable = ['name', 'email']; // Whitelist

    // OU

    protected $guarded = ['id', 'is_admin']; // Blacklist
}
```

### File Upload

```php
// Validation stricte
$request->validate([
    'file' => 'required|file|mimes:pdf,jpg,png|max:2048', // 2MB max
]);

// Stocker de manière sécurisée
$path = $request->file('file')->store('uploads', 'private');
```

### Permissions de fichiers

```bash
# Permissions recommandées
sudo chown -R www-data:www-data /var/www/b2b-platform
sudo chmod -R 755 /var/www/b2b-platform
sudo chmod -R 775 storage bootstrap/cache
```

### HTTPS/SSL

**TOUJOURS** utiliser HTTPS en production:

```nginx
# Nginx - Redirection HTTP vers HTTPS
server {
    listen 80;
    server_name votre-domaine.com;
    return 301 https://$server_name$request_uri;
}
```

### Logs de sécurité

Activer la journalisation:

```php
// Loguer les tentatives de login échouées
use Illuminate\Support\Facades\Log;

Log::warning('Failed login attempt', [
    'email' => $request->email,
    'ip' => $request->ip(),
    'user_agent' => $request->userAgent(),
]);
```

## Vulnérabilités connues

### Aucune vulnérabilité connue actuellement

Dernière vérification: 16 Janvier 2025

## Dépendances de sécurité

### Mise à jour régulière

```bash
# Vérifier les vulnérabilités dans les dépendances
composer audit

# Mettre à jour
composer update
```

### Dépendances critiques

- Laravel Framework: Toujours utiliser une version supportée
- Laravel Sanctum: Pour l'authentification API
- PHP: Version 8.2+ avec patches de sécurité

## Checklist de sécurité

### Avant déploiement

- [ ] `APP_DEBUG=false` en production
- [ ] `APP_KEY` généré et sécurisé
- [ ] Mots de passe de base de données forts
- [ ] Redis protégé par mot de passe
- [ ] HTTPS activé avec certificat SSL valide
- [ ] Firewall configuré (UFW)
- [ ] Fail2Ban installé et configuré
- [ ] Permissions de fichiers correctes
- [ ] Backups automatiques configurés
- [ ] Rate limiting activé
- [ ] Headers de sécurité configurés
- [ ] CORS configuré correctement
- [ ] Logs de sécurité activés
- [ ] Tests de pénétration effectués

### Maintenance régulière

- [ ] Mise à jour mensuelle des dépendances
- [ ] Review des logs de sécurité
- [ ] Audit des accès administrateurs
- [ ] Vérification des backups
- [ ] Scan de vulnérabilités
- [ ] Review des permissions utilisateurs

## Outils de sécurité recommandés

### Scan de vulnérabilités

```bash
# Local Security Checker
composer require --dev enlightn/security-checker
./vendor/bin/security-checker security:check

# OWASP Dependency Check
# https://owasp.org/www-project-dependency-check/
```

### Analyse statique

```bash
# PHPStan
composer require --dev phpstan/phpstan
./vendor/bin/phpstan analyse

# Psalm
composer require --dev vimeo/psalm
./vendor/bin/psalm
```

### Tests de pénétration

- OWASP ZAP: https://www.zaproxy.org/
- Burp Suite: https://portswigger.net/burp
- Nikto: https://github.com/sullo/nikto

## Ressources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security Best Practices](https://laravel.com/docs/security)
- [PHP Security Guide](https://www.php.net/manual/en/security.php)
- [CWE Top 25](https://cwe.mitre.org/top25/)

## Contact

**Email de sécurité**: security@votre-domaine.com

**PGP Key**: (À ajouter si applicable)

---

Merci de contribuer à la sécurité de la B2B Wholesale Platform! 🔒
