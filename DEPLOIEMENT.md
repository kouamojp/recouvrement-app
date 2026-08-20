# Déploiement en production sur cPanel

Cible : API Laravel + back-office Backpack servis par un hébergement cPanel, base
sur un cluster **MongoDB Atlas**. Le tableau de bord Angular (`arc_dsahboard`) est
un projet distinct, déployé sur son propre sous-domaine.

---

## 0. Vérifier d'abord que l'hébergement peut faire tourner l'application

Trois prérequis ne sont **pas** acquis sur un cPanel mutualisé. Les vérifier avant
tout le reste évite de découvrir l'obstacle une fois le code en ligne.

### PHP 7.4 ou 8.0

`MultiPHP Manager` → sélectionner **ea-php74** (ou 80) pour le domaine.

PHP 8.1 et supérieur **cassent** Laravel 7 au chargement du framework, avant même
l'initialisation des logs : l'erreur n'apparaît donc pas dans `storage/logs/` et
se manifeste parfois sous une forme trompeuse (`Database connection [mysql] not
configured.`). Voir le README pour le détail.

### L'extension `mongodb` ≥ 1.18

`Select PHP Version` → onglet `Extensions` → cocher **`mongodb`**.

**Si `mongodb` n'apparaît pas dans la liste, l'application ne peut pas tourner sur
cet hébergement en l'état.** Il n'y a pas de contournement applicatif : tous les
modèles étendent le driver Mongo, il n'existe aucune connexion SQL de repli. Les
deux issues sont de demander à l'hébergeur d'installer l'extension (`pecl install
mongodb`, opération root/WHM), ou de changer d'offre pour un VPS.

Demandez la confirmation par écrit avant de payer l'hébergement : beaucoup
d'offres mutualisées ne proposent que MySQL et refusent l'ajout d'extensions PECL.

### Les appels sortants

L'application doit joindre depuis le serveur :

| Destination | Usage |
|---|---|
| `*.mongodb.net` (TLS, 27017) | la base de données |
| `api-checkout.cinetpay.com` | initiation et relecture des paiements |
| `api-m.paypal.com` | idem |
| le SMTP retenu | notifications de paiement |

Certains mutualisés bloquent les connexions sortantes non HTTP. Test rapide, à
lancer depuis le Terminal cPanel (voir §2) :

```bash
php -r 'var_dump(extension_loaded("mongodb"), phpversion("mongodb"), PHP_VERSION);'
php -r '$c=curl_init("https://api-m.paypal.com");curl_setopt($c,CURLOPT_NOBODY,1);curl_exec($c);var_dump(curl_error($c));'
```

> N'utilisez pas un `phpinfo.php` déposé dans `public_html` pour ces vérifications :
> il expose publiquement chemins, versions et variables du serveur. Si vous n'avez
> pas d'accès Terminal, déposez-le, lisez-le, **supprimez-le immédiatement**.

---

## 1. Arborescence sur le serveur

Le dossier `public/` doit être la racine web, et **le reste du projet doit rester
hors de `public_html`** — sans quoi `.env`, qui contient les clés de paiement,
`APP_KEY` et `JWT_SECRET`, est téléchargeable par n'importe qui.

Structure recommandée :

```
/home/UTILISATEUR/
├── arc/                     ← le projet, hors zone web
│   ├── app/  config/  routes/  storage/  vendor/
│   ├── .env
│   └── public/              ← racine web du sous-domaine API
└── public_html/
    └── app/                 ← build Angular (projet séparé)
```

Dans cPanel → `Domaines` → créer le sous-domaine `api.votredomaine.com` et fixer
sa **Racine du document** à `/home/UTILISATEUR/arc/public`.

Le front Angular part sur `app.votredomaine.com` (ou un sous-dossier de
`public_html`), avec sa propre racine.

<details>
<summary>Si l'hébergeur interdit une racine hors <code>public_html</code></summary>

Placez le projet dans `/home/UTILISATEUR/arc`, copiez le contenu de
`arc/public/` dans `public_html/`, puis corrigez les deux chemins de
`public_html/index.php` :

```php
require __DIR__.'/../arc/vendor/autoload.php';
$app = require_once __DIR__.'/../arc/bootstrap/app.php';
```

C'est une configuration à éviter quand on peut : chaque mise à jour impose de
recopier `public/`.
</details>

---

## 2. Envoyer le code

`vendor/` est ignoré par git : un simple clone ne suffit pas.

### Avec accès SSH ou Terminal cPanel (recommandé)

```bash
cd ~
git clone git@github.com:kouamojp/recouvrement-app.git arc
cd arc
/usr/local/bin/ea-php74 /opt/cpanel/composer/bin/composer install --no-dev --optimize-autoloader
```

Appelez toujours le binaire PHP **explicitement** (`ea-php74`) : le `php` du PATH
d'un compte cPanel est souvent une autre version que celle servie par le domaine.

### Sans accès shell

Construisez `vendor/` en local **avec un PHP 7.4 ou 8.0** (les dépendances
figées dépendent de la version qui les installe), puis téléversez :

```bash
composer install --no-dev --optimize-autoloader
```

Zippez le projet **sans** `node_modules`, `.git`, `tests`, `storage/logs/*`,
puis `Gestionnaire de fichiers` → téléverser dans `/home/UTILISATEUR/` →
`Extraire`.

`npm` n'est pas nécessaire : `resources/js/app.js` et `resources/sass/app.scss`
sont vides, et Backpack sert ses propres assets depuis `public/packages`.

---

## 3. La base : MongoDB Atlas

cPanel ne fournit pas de serveur MongoDB. Sur Atlas :

1. Créer un cluster (le palier gratuit M0 suffit pour démarrer, pas pour durer).
2. `Database Access` → créer un utilisateur avec le rôle `readWrite` sur `arc`.
3. `Network Access` → autoriser l'IP sortante du serveur cPanel. Elle est visible
   dans cPanel (`Informations générales` → `Adresse IP partagée`). Sur un
   mutualisé cette IP peut changer : si l'hébergeur ne garantit pas d'IP fixe,
   `0.0.0.0/0` est le seul recours praticable — l'accès reste protégé par les
   identifiants et le TLS, mais c'est un affaiblissement à assumer.
4. Copier l'URI de connexion `mongodb+srv://…`.

Cette URI se renseigne dans `MONGO_DB_DSN` (support ajouté à
`config/database.php`) : elle prend le pas sur `MONGO_DB_HOST` / `MONGO_DB_PORT`,
qui ne savent pas exprimer un jeu de nœuds répliqués ni le TLS.

---

## 4. Le fichier `.env` de production

```dotenv
APP_NAME=Acreances
APP_ENV=production
APP_KEY=                     # rempli par artisan key:generate
APP_DEBUG=false
APP_URL=https://api.votredomaine.com

SESSION_SECURE_COOKIE=true

# Origine exacte du front Angular : le schéma et le domaine, sans / final.
# Plusieurs valeurs séparées par des virgules si préproduction.
FRONTEND_URLS=https://app.votredomaine.com

JWT_SECRET=                  # rempli par artisan jwt:secret

LOG_CHANNEL=stack

DB_CONNECTION=mongodb
MONGO_DB_DSN=mongodb+srv://arc:MOTDEPASSE@cluster0.xxxxx.mongodb.net/arc?retryWrites=true&w=majority
MONGO_DB_DATABASE=arc

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

MAIL_MAILER=smtp
MAIL_HOST=mail.votredomaine.com
MAIL_PORT=465
MAIL_USERNAME=no-reply@votredomaine.com
MAIL_PASSWORD=…
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=no-reply@votredomaine.com
MAIL_FROM_NAME="${APP_NAME}"

# --- Paiement en ligne ---
PAIEMENT_EMAIL_ADMIN=comptabilite@votredomaine.com
PAIEMENT_DELAI_EXPIRATION=30

CINETPAY_API_KEY=…
CINETPAY_SITE_ID=…

PAYPAL_URL=https://api-m.paypal.com
PAYPAL_CLIENT_ID=…
PAYPAL_SECRET=…
```

Quatre valeurs dont l'oubli ne se voit pas tout de suite :

- **`APP_DEBUG=false`** — sinon chaque erreur affiche la trace complète, les
  chemins du serveur et le contenu des variables au visiteur.
- **`PAYPAL_URL`** — le défaut du code est la *sandbox* : les paiements
  paraissent fonctionner sans qu'aucun argent ne circule.
- **`FRONTEND_URLS`** — le front reçoit des erreurs CORS opaques si l'origine ne
  correspond pas au caractère près.
- **`MAIL_*`** — le `.env.example` pointe sur Mailtrap : aucune notification ne
  part réellement tant qu'on ne l'a pas changé.

Ne réintroduisez **jamais** de `DB_HOST` / `DB_PORT` : historiquement le bloc
Mongo lisait `env('DB_PORT', 27017)`, et un `DB_PORT=3306` résiduel envoyait le
driver Mongo sur le serveur MySQL (`No suitable servers found`).

---

## 5. Initialiser l'application

```bash
cd ~/arc
/usr/local/bin/ea-php74 artisan key:generate
/usr/local/bin/ea-php74 artisan jwt:secret
/usr/local/bin/ea-php74 artisan migrate --force
/usr/local/bin/ea-php74 artisan config:cache
/usr/local/bin/ea-php74 artisan route:cache
/usr/local/bin/ea-php74 artisan view:cache
```

`migrate` s'exécute contre MongoDB : l'historique va dans la collection
`migrations` de la base `arc`.

> **Ne lancez pas `db:seed` en production.** Les seeders (`DebiteurSeeder`,
> `DetteSeeder`, `RecuSeeder`…) créent le jeu de démonstration : de fausses
> dettes mêlées aux vraies, impossibles à distinguer après coup.

<details>
<summary>Sans accès Terminal : passer par une tâche cron jetable</summary>

`cPanel` → `Tâches cron` → ajouter une tâche « une fois par minute » avec la
commande voulue, attendre l'exécution, **puis supprimer la tâche** :

```
/usr/local/bin/ea-php74 /home/UTILISATEUR/arc/artisan migrate --force >> /home/UTILISATEUR/arc/storage/logs/cron.log 2>&1
```

Répéter pour `key:generate`, `jwt:secret` et les trois `*:cache`. Vérifier
`cron.log` après chaque étape.
</details>

### Permissions

```bash
find ~/arc -type d -exec chmod 755 {} \;
find ~/arc -type f -exec chmod 644 {} \;
chmod -R 775 ~/arc/storage ~/arc/bootstrap/cache
chmod 600 ~/arc/.env
```

Sur cPanel, PHP tourne sous votre propre utilisateur : `775` suffit, `777` n'est
jamais nécessaire et ouvre le dossier à tous les comptes de la machine.

---

## 6. HTTPS

`cPanel` → `SSL/TLS Status` → **Run AutoSSL** sur le sous-domaine API. Les
webhooks des prestataires refusent souvent un certificat invalide, et les jetons
JWT circulent en clair sans TLS.

Forcer la redirection en tête de `public/.htaccess`, juste après
`RewriteEngine On` :

```apache
RewriteCond %{HTTPS} off
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

Si le domaine passe par Cloudflare ou un autre proxy, renseignez en plus la
propriété `$proxies` de `app/Http/Middleware/TrustProxies.php` — sinon Laravel
voit l'IP du proxy et génère des URL en `http://`.

---

## 7. Mettre les paiements en service

Déclarer chez chaque prestataire l'URL de notification :

```
https://api.votredomaine.com/api/paiements/webhook/cinetpay
https://api.votredomaine.com/api/paiements/webhook/paypal
```

En production, des identifiants marchands manquants **lèvent une erreur** : le
repli sur la passerelle factice ne vaut qu'hors production, précisément pour
qu'un déploiement incomplet ne simule pas des encaissements.

Deux contraintes de prestataire à connaître (détail dans `PAIEMENTS.md`) :
CinetPay règle en XAF par multiples de 5, le reliquat restant au solde de la
dette ; PayPal ne connaît pas le franc CFA et convertit à l'initiation au taux
`PAYPAL_TAUX_DEPUIS_FCFA`, le montant en FCFA restant la référence comptable.

---

## 8. Recette après mise en ligne

- [ ] `https://api.votredomaine.com/admin/login` s'affiche et la connexion passe
- [ ] Une erreur volontaire (URL inexistante) affiche une page d'erreur **sans**
      trace ni chemin de fichier — sinon `APP_DEBUG` est resté à `true`
- [ ] Le front Angular s'authentifie et charge les données (pas d'erreur CORS)
- [ ] Une dette créée dans le back-office apparaît côté débiteur
- [ ] **Un règlement réel de faible montant**, de bout en bout : le statut passe
      à `reussi`, la dette est créditée, le reçu est émis, les courriels partent.
      Le webhook relit le statut à la source : seul un vrai passage chez le
      prestataire valide la chaîne complète
- [ ] `.env` n'est pas accessible : `https://api.votredomaine.com/.env` doit
      répondre 404

---

## 9. Exploitation

**Aucune tâche planifiée n'est nécessaire.** L'expiration des paiements en
attente est calculée à la lecture (`Paiement::estExpire()`), pas par un cron.
Inutile d'installer `schedule:run`.

**Les courriels partent en synchrone** (`QUEUE_CONNECTION=sync`) : la
notification de paiement confirmé est envoyée pendant la requête du webhook. Un
SMTP lent ou en panne ralentit cette requête, voire la fait échouer. La
notification `PaiementConfirme` porte déjà le trait `Queueable` : passer en file
d'attente est possible sans réécriture le jour où le volume le demande, mais un
mutualisé cPanel ne permet pas de superviser un worker durablement — prévoyez un
VPS à ce moment-là.

**Journaux** : `storage/logs/laravel.log`, à purger périodiquement (le driver
`stack` n'applique pas de rotation par défaut sur ce fichier).

**Sauvegardes** : les snapshots Atlas ne couvrent pas les paliers gratuits.
Prévoir un `mongodump --uri="…"` régulier, et **conserver une copie du `.env`
hors du serveur** : perdre `APP_KEY` et `JWT_SECRET` invalide tous les jetons en
circulation.

### Déployer une mise à jour

```bash
cd ~/arc
git pull
/usr/local/bin/ea-php74 /opt/cpanel/composer/bin/composer install --no-dev --optimize-autoloader
/usr/local/bin/ea-php74 artisan migrate --force
/usr/local/bin/ea-php74 artisan config:cache
/usr/local/bin/ea-php74 artisan route:cache
/usr/local/bin/ea-php74 artisan view:cache
```

Les trois `*:cache` sont à rejouer **à chaque** déploiement : tant qu'ils ne le
sont pas, un `.env` modifié reste sans effet, ce qui est la première cause de
« j'ai changé la variable et rien n'a bougé ».

---

## 10. Récapitulatif des pièges

| Symptôme | Cause |
|---|---|
| Erreur fatale sur `ArrayAccess`, ou `Database connection [mysql] not configured` | PHP 8.1+ — repasser en 7.4 ou 8.0 |
| `Class 'MongoDB\Client' not found` | extension `mongodb` absente du PHP servi par Apache |
| `No suitable servers found … '127.0.0.1:3306'` | un `DB_PORT` traîne dans le `.env` |
| `Unable to prepare route … Uses Closure` au `route:cache` | une route en closure a été rajoutée dans `routes/` |
| Le paiement « réussit » mais aucun argent n'arrive | `PAYPAL_URL` resté sur la sandbox |
| Le front reçoit des erreurs CORS | `FRONTEND_URLS` ne correspond pas exactement à l'origine |
| Aucun courriel ne part | `MAIL_*` resté sur Mailtrap |
| Une modification du `.env` reste sans effet | `config:cache` non rejoué |
