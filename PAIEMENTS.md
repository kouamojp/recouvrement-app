# Paiement en ligne des dettes

Permet au débiteur de régler tout ou partie d'une créance depuis son espace, par
carte bancaire, Orange Money, MTN Mobile Money ou PayPal. L'onglet correspondant
vit dans le tableau de bord Angular (`arc_dsahboard`, `/debiteur/paiements`).

## Routes

```http
GET    /api/debiteur/me/paiements               → Paiement[]
POST   /api/debiteur/me/paiements               → Paiement (201)
GET    /api/debiteur/me/paiements/{id}          → Paiement
POST   /api/debiteur/me/paiements/{id}/annuler  → Paiement
POST   /api/paiements/webhook/{passerelle}      → { "recu": true }
```

Les quatre premières passent par `jwt.profil:debiteur`, comme le reste de
l'espace débiteur : un token de partenaire ou d'agent reçoit un 403 portant le
code `profil_non_autorise`. Le périmètre vient du token, aucun identifiant de
débiteur ne circule.

Le webhook est exclu de ce middleware — le prestataire n'a pas de token. La
charge utile qu'il envoie n'est jamais crue sur parole : elle sert seulement à
repérer la transaction, dont le statut est ensuite relu à la source. La route
répond toujours 200, sans quoi le prestataire rejouerait l'appel en boucle.

## Déroulé

1. `POST /paiements` valide la demande, refuse un montant supérieur au solde ou
   une dette déjà soldée (422), puis ouvre la transaction chez le prestataire.
2. Carte et PayPal renvoient une `url_redirection` ; le mobile money renvoie une
   `instruction` et le statut `en_attente`. Le prestataire ramène le débiteur sur
   `url_retour` complétée de `?paiement={id}`.
3. Chaque `GET /paiements/{id}` relit le statut auprès du prestataire — c'est ce
   qui fait basculer l'écran d'attente du tableau de bord. Le webhook fait la
   même chose sans navigateur ouvert.
4. Au passage à `reussi` : la dette est créditée (`montant_verse`,
   `dernier_versement`, `solde` recalculé par le modèle), un `Recu` est émis, puis
   l'administration, le partenaire créancier et l'agent en charge sont notifiés
   par courriel.

L'imputation est **idempotente** : un webhook arrivant après la relecture du
tableau de bord ne crédite pas la dette deux fois. Un paiement resté en attente
au-delà de `PAIEMENT_DELAI_EXPIRATION` minutes bascule en `echoue`.

## Passerelles

| Moyen | Passerelle par défaut |
|---|---|
| Carte bancaire, Orange Money, MTN MoMo | CinetPay |
| PayPal | PayPal Orders v2 |

Le choix se règle moyen par moyen dans `config/paiements.php`.

**Sans identifiants marchands**, la fabrique se replie hors production sur la
passerelle `factice`, qui déroule tout le tunnel — attente, confirmation, mise à
jour de la dette, notifications — sans prestataire réel. En production, des
identifiants manquants lèvent une erreur : c'est un défaut de déploiement.

Deux contraintes de prestataire méritent d'être connues :

- **CinetPay** règle en XAF par multiples de 5. Le montant transmis est arrondi
  vers le bas ; le reliquat de quelques francs reste au solde de la dette.
- **PayPal ne connaît pas le franc CFA.** Le montant est converti à l'initiation
  au taux `PAYPAL_TAUX_DEPUIS_FCFA` (655,957 par défaut, parité fixe XAF/EUR).
  Le montant en FCFA reste la seule référence comptable.

## Mise en service

```dotenv
PAIEMENT_EMAIL_ADMIN=comptabilite@exemple.test

CINETPAY_API_KEY=…
CINETPAY_SITE_ID=…

PAYPAL_URL=https://api-m.paypal.com   # sandbox par défaut
PAYPAL_CLIENT_ID=…
PAYPAL_SECRET=…
```

Puis déclarer chez chaque prestataire l'URL de notification :
`https://<domaine>/api/paiements/webhook/cinetpay` et `…/webhook/paypal`.

Sans `PAIEMENT_EMAIL_ADMIN`, l'administration n'est pas notifiée — le partenaire
et l'agent le restent.

## Tests

```bash
vendor/bin/phpunit --testsuite Unit
```

Couvrent la référence, le cycle de vie, l'expiration et la passerelle factice.
L'imputation elle-même se vérifie de bout en bout avec la passerelle factice,
sur un jeu de données jetable.
