# Configuration PHP en production (cPanel + LiteSpeed)

Ce document décrit **l'environnement réellement constaté** sur l'hébergement de
production, et la procédure qui a permis d'y faire tourner l'application. Il
complète `DEPLOIEMENT.md`, dont la section 0 suppose un cPanel classique sur
Apache avec `MultiPHP Manager` — ce qui n'est pas la configuration ici.

Relevé du 20 août 2026.

---

## 1. L'environnement

| Élément | Valeur |
|---|---|
| Hôte | `mi3-ss55`, utilisateur `arcreanc` |
| Serveur web | **LiteSpeed** (pas Apache) |
| Pile PHP | CloudLinux (`/opt/alt/php*`) **et** EasyApache (`/opt/cpanel/ea-php*`) |
| Domaine | `arcreances.proditech-digital.com` |
| Racine du projet | `/home/arcreanc/arcreances.proditech-digital.com/recouvrement-app` |
| Racine du document | `…/recouvrement-app/public` |
| **Version PHP servie** | **7.4.33** |
| **Extension `mongodb`** | **1.20.1** (requis : `^1.18.0`) |

Le projet est rangé **dans** le dossier du sous-domaine, et non hors zone web
comme le recommande `DEPLOIEMENT.md` §1. Ce n'est acceptable **que** parce que la
racine du document pointe sur `public/` : seul ce sous-dossier est exposé, et
`.env`, `storage/` et `vendor/` restent hors d'atteinte du web.

> **À ne jamais faire ici** : remettre la racine du document sur
> `…/arcreances.proditech-digital.com`. `.env` (APP_KEY, JWT_SECRET, identifiants
> Atlas, clés CinetPay et PayPal) deviendrait téléchargeable publiquement.

`public/index.php` reste le fichier Laravel d'origine, non modifié :

```php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
```

Ces chemins sont corrects **parce que** la racine du document est `public/`.
Ils ne seraient à réécrire que si le contenu de `public/` était recopié ailleurs.

---

## 2. Fixer la version PHP : pourquoi pas MultiPHP Manager

`MultiPHP Manager` n'est pas proposé dans ce cPanel. C'est une fonctionnalité de
niveau WHM/root : **elle ne s'installe pas depuis un compte utilisateur**. Les
paquets PHP, eux, sont bien présents — de `php44` à `php85` côté CloudLinux, de
`ea-php54` à `ea-php83` côté EasyApache.

La version est donc déclarée **par un handler dans le `.htaccess` de la racine du
document**, ce que MultiPHP Manager aurait écrit automatiquement.

### Le handler

En tête de `public/.htaccess`, avant le bloc `<IfModule mod_rewrite.c>` :

```apache
# php -- BEGIN cPanel-generated handler, do not edit
AddHandler application/x-httpd-alt-php74___lsphp .php .php7 .phtml
# php -- END cPanel-generated handler, do not edit
```

Deux syntaxes existent selon la pile ; **une seule fonctionne** :

| Pile | Ligne |
|---|---|
| CloudLinux / lsphp (`/opt/alt/php74`) | `AddHandler application/x-httpd-alt-php74___lsphp .php .php7 .phtml` |
| EasyApache (`/opt/cpanel/ea-php74`) | `AddHandler application/x-httpd-ea-php74 .php .php7 .phtml` |

Pour lire celle qui est active : `head -3 public/.htaccess`.

LiteSpeed met sa configuration en cache. Après modification :
`touch public/.htaccess`, ou cPanel → *LiteSpeed Web Cache Manager* → *Flush All*.

### Protéger le handler des mises à jour

`public/.htaccess` est **suivi par git** (voir `git ls-files public/`). Un
`git pull` en production écrase le bloc handler, le domaine retombe sur le PHP
par défaut du serveur — une version 8.x sur laquelle Laravel 7 ne démarre pas.

Parade, à appliquer une fois par clone :

```bash
git update-index --skip-worktree public/.htaccess
```

À refaire après tout nouveau clone. Pour annuler : `--no-skip-worktree`.

### Alternative : Select PHP Version

L'icône `Select PHP Version` (PHP Selector CloudLinux) est disponible et fait le
même travail de façon plus durable, avec une limite : elle s'applique à **tout le
compte**, pas à un domaine. C'est en revanche **le seul endroit** où activer les
extensions PHP — voir §4.

---

## 3. Runbook : lire les erreurs de LiteSpeed

LiteSpeed ne se comporte pas comme Apache. Il ne sert jamais le code source d'un
`.php` qu'il ne sait pas exécuter : il refuse. D'où cette table de décision, qui
a servi à diagnostiquer la mise en ligne.

Test de référence — un fichier statique et un fichier PHP :

```bash
cd ~/arcreances.proditech-digital.com/recouvrement-app
curl -s -o /dev/null -w "statique:%{http_code}\n" https://arcreances.proditech-digital.com/favicon.ico
printf '<?php echo PHP_VERSION;' > public/version.php
curl -s https://arcreances.proditech-digital.com/version.php; echo
rm public/version.php
```

| Statique | PHP | Diagnostic |
|---|---|---|
| 403 | 403 | Racine du document mal placée, ou chaîne de permissions bloquante |
| 200 | 403 | **Handler PHP absent** — le cas rencontré. Voir §2 |
| 200 | 500 | PHP s'exécute, l'erreur est applicative : extension manquante, `.env`, `storage/` non inscriptible |
| 200 | `7.4.33` | Correct |

Un `403` sur `/` et un `500` sur une autre URL sont fréquemment **le même
problème** vu à deux points d'échec différents. Ne pas les traiter séparément.

### Permissions

Les dossiers en `750 arcreanc:nobody` créés par cPanel **ne posent pas de
problème** sous suEXEC : le statique est servi normalement. Ne pas y toucher.

En revanche, un fichier en `664` (inscriptible par le groupe) peut être refusé à
l'exécution. Normalisation :

```bash
find . -path ./vendor -prune -o -type f -exec chmod 644 {} \;
chmod -R 775 storage bootstrap/cache
chmod 600 .env
```

### Où sont les logs

`~/logs/` ne contient **pas** les journaux du domaine sur cet hôte (seulement
`app.log` et `roundcube/`). Chercher plutôt :

```bash
ls ~/access-logs/
ls /usr/local/apache/domlogs/arcreanc/
tail -40 storage/logs/laravel.log
```

Ou cPanel → *Metrics* → **Errors**.

Pour afficher une erreur en clair pendant un diagnostic : passer `APP_DEBUG=true`
dans `.env`, `php artisan config:clear`, **puis remettre `false` aussitôt** — la
stack trace expose les identifiants de connexion.

---

## 4. Extensions PHP

Relevé du 20 août 2026, côté **web** (seule mesure qui fasse foi) :

```
7.4.33 | mongodb: 1.20.1
```

`composer.lock` verrouille `mongodb/mongodb 1.19.1`, qui exige
`ext-mongodb: ^1.18.0` — soit tout `1.x` à partir de 1.18, et **rien en 2.x**.
La 1.20.1 installée est dans la plage. Contrainte à revérifier après toute
mise à jour de l'extension par l'hébergeur.

### Activer ou vérifier une extension

cPanel → `Select PHP Version` → menu **PHP version** sur **`7.4`** (jamais
`native`, sans quoi l'onglet suivant pilote une autre pile) → onglet
**`Extensions`** → cocher. La sauvegarde est automatique.

Extensions requises : `mongodb`, `openssl`, `mbstring`, `json`, `bcmath`,
`curl`, `pdo`.

`Select PHP Version` ne pilote que la pile CloudLinux (`/opt/alt/php74`). Si le
handler du `.htaccess` est en `ea-php74`, l'onglet Extensions **n'a aucun effet
sur le site** : les extensions EasyApache sont gérées au niveau root. Basculer
alors le handler sur la variante `alt-php74` (§2).

### Contrôle

L'interface peut confirmer un réglage qui ne s'applique pas au web. Mesurer :

```bash
cd ~/arcreances.proditech-digital.com/recouvrement-app
printf '<?php echo PHP_VERSION," | mongodb: ",(extension_loaded("mongodb")?phpversion("mongodb"):"ABSENT");' > public/version.php
curl -s https://arcreances.proditech-digital.com/version.php; echo
rm public/version.php
```

Sans l'extension `mongodb`, le boot échoue sur un 500 muet : tous les modèles
étendent le driver Mongo, il n'existe aucune connexion SQL de repli
(`DEPLOIEMENT.md` §0).

### `vendor/`

`vendor/` est ignoré par git ; le dépôt contient un `vendor.zip`. Vérifier :

```bash
ls -d vendor/autoload.php || /opt/alt/php74/usr/bin/php /opt/cpanel/composer/bin/composer install --no-dev --optimize-autoloader
```

---

## 5. PHP en ligne de commande

Le handler `.htaccess` ne concerne **que** le PHP web. Le `php` du PATH SSH reste
celui par défaut du serveur. Appeler toujours le binaire explicitement, dans les
commandes manuelles comme dans les crons :

```bash
/opt/alt/php74/usr/bin/php artisan migrate --force
/opt/alt/php74/usr/bin/php artisan config:cache
```

---

## 6. Incompatibilite ext-mongodb 1.20 / laravel-mongodb 3.7

### Symptome

Un 500 sur n'importe quelle page ecrivant ou filtrant une date :

```
MongoDB\BSON\UTCDateTime::__construct(): Creating a MongoDB\BSON\UTCDateTime
instance with a string is deprecated and will be removed in ext-mongodb 2.0
```

### Cause

`mongodb/laravel-mongodb v3.7.3` (verrouille par `composer.lock`) construit ses
dates a partir d'une chaine :

```php
// vendor/mongodb/laravel-mongodb/src/Jenssegers/Mongodb/Eloquent/Model.php:92
return new UTCDateTime($value->format('Uv'));   // 'Uv' renvoie une string
```

Sept appels sont concernes (`Eloquent\Model:92`, `:127`, `Query\Builder:933`,
`:938`, `:944`, `Auth\DatabaseTokenRepository:21`). `ext-mongodb 1.20` deprecie
ce constructeur.

Une depreciation ne devrait pas etre fatale — mais **Laravel 7 force
`error_reporting(-1)`, et `HandleExceptions::handleError` convertit toute notice
en `ErrorException`**. D'ou le 500. Laravel 8 a change ce comportement, pas la 7.

### Correctif en place

`app/Providers/AppServiceProvider::register()` — execute apres le bootstrap
`HandleExceptions`, donc au bon moment pour revenir sur son reglage :

```php
error_reporting(error_reporting() & ~E_DEPRECATED & ~E_USER_DEPRECATED);
```

Ni `vendor/` ni `APP_DEBUG` ne sont en cause : passer `APP_DEBUG=false` masque la
trace mais laisse le 500. Patcher `vendor/` serait ecrase au prochain
`composer install`.

### Portee

Ce reglage neutralise **toutes** les depreciations de l'application, pas
seulement celle-ci. C'est acceptable sur une pile figee (Laravel 7 + PHP 7.4),
mais a retirer lors de la montee de version : les depreciations redeviendront
alors le signal utile qu'elles doivent etre.

---

## 7. Dette technique

**PHP 7.4 n'est plus maintenu depuis novembre 2022** : aucun correctif de
sécurité n'y est plus appliqué. Le blocage n'est pas `composer.json`, qui accepte
déjà `^7.2.5|^8.0`, mais **Laravel 7**, qui ne va pas au-delà de PHP 8.0.

Deux étapes, dans cet ordre :

1. **Court terme** — basculer le handler sur `ea-php80` / `alt-php80`, après
   vérification que l'extension `mongodb` y est disponible. Même effort, une
   version moins ancienne, Laravel 7 y fonctionne.
2. **Moyen terme** — monter Laravel vers une version supportée, ce qui débloque
   PHP 8.2+. Chantier à planifier séparément.
