# Arcréances — application de recouvrement

Application Laravel 7 + Backpack CRUD 4.1, **stockage MongoDB** (pas de SQL).

---

## Prérequis

| Composant | Version | Remarque |
|---|---|---|
| PHP | **7.4 ou 8.0** | voir l'avertissement ci-dessous |
| Extension `mongodb` | **≥ 1.18** | requise par `mongodb/mongodb ^1.6` |
| Serveur MongoDB | 4.x ou plus | écoute par défaut sur `127.0.0.1:27017` |
| Composer | 2.x | |

### ⚠️ PHP 8.1 et supérieur ne fonctionnent pas

`composer.json` déclare `"php": "^7.2.5|^8.0"`, ce qui laisse croire que tout PHP 8.x
convient. **C'est faux en pratique.** Laravel 7 est antérieur aux changements de
signature de PHP 8.1 et casse au chargement du framework :

```
FatalError: During inheritance of ArrayAccess: Return type of
Illuminate\Support\Collection::offsetExists($key) should either be compatible
with ArrayAccess::offsetExists(mixed $offset): bool, or the
#[\ReturnTypeWillChange] attribute should be used...
```

Cette erreur survient avant l'initialisation du gestionnaire de logs, donc **elle
n'apparaît pas dans `storage/logs/`**. Elle peut aussi se manifester sous des
formes trompeuses — notamment un `Database connection [mysql] not configured.`
émis pendant que le gestionnaire d'erreurs tente de rendre sa page.

Le plancher réel est PHP **7.4** (imposé par `mongodb/mongodb`, qui exige
`^7.4 || ^8.0`), le plafond réel est PHP **8.0**.

### Vérifier son environnement

```bash
php -v                              # doit afficher 7.4.x ou 8.0.x
php -m | grep mongodb               # doit afficher : mongodb
php -r "echo phpversion('mongodb');" # doit afficher >= 1.18
```

Si `mongodb` est absent, activez `extension=mongodb` dans le `php.ini` **du binaire
utilisé** (`php --ini` indique lequel) et vérifiez que `extension_dir` pointe bien
vers le dossier `ext/`. Un `extension_dir` relatif est résolu depuis le répertoire
courant et échoue dès qu'on lance `artisan` depuis ailleurs : préférez un chemin
absolu.

---

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

`migrate` s'exécute **contre MongoDB** : l'historique est stocké dans la collection
`migrations` de la base `arc`, pas dans une table SQL.

## Configuration

La connexion Mongo utilise ses propres variables, **volontairement distinctes** des
`DB_*` :

```dotenv
DB_CONNECTION=mongodb

MONGO_DB_HOST=127.0.0.1
MONGO_DB_PORT=27017
MONGO_DB_DATABASE=arc
MONGO_DB_USERNAME=
MONGO_DB_PASSWORD=
```

> **Ne réintroduisez pas de variables `DB_HOST` / `DB_PORT`** pour la connexion
> Mongo. Historiquement le bloc `mongodb` de `config/database.php` lisait
> `env('DB_PORT', 27017)` ; le défaut `27017` ne s'appliquant que si la variable est
> absente, un `DB_PORT=3306` destiné à MySQL envoyait le driver Mongo sur le serveur
> MySQL, avec pour symptôme :
>
> ```
> No suitable servers found: [Invalid reply from server. calling hello on '127.0.0.1:3306']
> ```

## Mise en production

Voir [DEPLOIEMENT.md](DEPLOIEMENT.md) — procédure pour un hébergement cPanel avec
MongoDB Atlas, prérequis à vérifier auprès de l'hébergeur et pièges connus.

## Lancer l'application

```bash
php artisan serve
```

→ http://localhost:8000/admin/login

Le serveur intégré est la méthode recommandée : il utilise le PHP du `PATH`, donc
celui dont vous venez de vérifier la version et les extensions.

**Servir le projet via Apache/WAMP demande deux prérequis supplémentaires** : que le
module PHP chargé par Apache (`httpd.conf`, directive `LoadModule php_module`) soit
en 7.4 ou 8.0, et que **ce** PHP-là dispose de `php_mongodb.dll` — les distributions
WAMP ne l'incluent pas par défaut. Sur une installation WAMP, changer la version PHP
d'Apache affecte tous les vhosts de la machine.

---

## Architecture — points non évidents

**Tous les modèles sont sur MongoDB.** `App\User` et les modèles de `app/Models/`
étendent `Jenssegers\Mongodb\Eloquent\Model` et déclarent
`protected $connection = 'mongodb'`. Il n'y a aucune connexion SQL configurée.

**Deux service providers remplacent ceux de Laravel** dans `config/app.php` :

- `App\Providers\MongoValidationServiceProvider` — sans lui, les règles `unique` et
  `exists` interrogent la connexion par défaut avec une syntaxe SQL. L'opérateur
  `regex` de Mongo étant invalide pour le query builder SQL, Laravel le rétrograde
  silencieusement en `where('colonne', '=', 'regex')` : la validation ne remonte
  alors **aucun doublon**, sans lever la moindre erreur.

  C'est une sous-classe et non le provider du package, pour deux raisons. D'abord
  `App\Validation\MongoPresenceVerifier` ancre le motif (`/^…$/i`) là où le package
  ne le fait pas — sinon `a@b.com` est refusé comme doublon de `aa@b.com`. Ensuite
  le provider est `DeferrableProvider` : il s'enregistre à la résolution du service
  et écraserait tout binding posé depuis `AppServiceProvider`.

- `Jenssegers\Mongodb\Auth\PasswordResetServiceProvider` — remplace celui
  d'Illuminate. Mongo stocke `created_at` en `UTCDateTime`, que le repository
  standard ne sait pas comparer ; sans ce provider la réinitialisation de mot de
  passe échoue à la vérification d'expiration du jeton.

Ces deux providers ne sont **pas** auto-découverts : `mongodb/laravel-mongodb`
n'expose que `MongodbServiceProvider` et `MongodbQueueServiceProvider` dans son
`extra.laravel.providers`. Ils doivent rester déclarés explicitement.

---

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400"></a></p>

<p align="center">
<a href="https://travis-ci.org/laravel/framework"><img src="https://travis-ci.org/laravel/framework.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/license.svg" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains over 1500 video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the Laravel [Patreon page](https://patreon.com/taylorotwell).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Cubet Techno Labs](https://cubettech.com)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[Many](https://www.many.co.uk)**
- **[Webdock, Fast VPS Hosting](https://www.webdock.io/en)**
- **[DevSquad](https://devsquad.com)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
