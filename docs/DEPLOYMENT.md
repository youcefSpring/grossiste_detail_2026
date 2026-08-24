# Grossiste — Installation & Exploitation

Système de gestion vente en gros / détail. Laravel 13 · PHP 8.4 · MySQL 8 · Blade + jQuery.

---

## 1. Prérequis serveur

| Composant | Version | Notes |
|---|---|---|
| PHP | 8.3+ | extensions : `pdo_mysql`, `mbstring`, `intl`, `gd`, `zip`, `bcmath` |
| MySQL / MariaDB | 8.0+ / 10.6+ | `utf8mb4_unicode_ci` obligatoire (arabe) |
| Node.js | 20+ | uniquement pour compiler les assets |
| Nginx ou Apache | — | racine web sur `public/` **uniquement** |

Un VPS 2 vCPU / 4 Go suffit pour un magasin. Prévoir plus de RAM si le catalogue dépasse
50 000 références.

---

## 2. Installation

```bash
git clone <votre-depot> grossiste && cd grossiste

composer install --no-dev --optimize-autoloader
npm ci && npm run build

cp .env.example .env
php artisan key:generate
```

Créer la base et l'utilisateur :

```sql
CREATE DATABASE grossiste CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'grossiste'@'localhost' IDENTIFIED BY 'un-mot-de-passe-solide';
GRANT ALL ON grossiste.* TO 'grossiste'@'localhost';
FLUSH PRIVILEGES;
```

Renseigner `.env` :

```dotenv
APP_ENV=production
APP_DEBUG=false                 # jamais true en production
APP_URL=https://votre-domaine.dz
APP_TIMEZONE=Africa/Algiers
APP_LOCALE=ar

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=grossiste
DB_USERNAME=grossiste
DB_PASSWORD=un-mot-de-passe-solide

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true      # avec HTTPS
CACHE_STORE=database            # ou redis si disponible
```

Puis :

```bash
php artisan migrate --force
php artisan db:seed --force        # rôles, permissions, catégories, comptes de test
php artisan storage:link
```

### Comptes créés

`db:seed` crée un compte par rôle, tous avec le mot de passe `password` :

| Email | Rôle | Accès |
|---|---|---|
| `admin@grossiste.dz` | Propriétaire | Tout, y compris utilisateurs et paramètres |
| `manager@grossiste.dz` | Gérant | Opérations et rapports, sans les paramètres |
| `vendeur@grossiste.dz` | Vendeur | Caisse, clients, retours |
| `achat@grossiste.dz` | Acheteur | Achats, fournisseurs, produits |
| `stock@grossiste.dz` | Magasinier | Stock et inventaire |
| `compta@grossiste.dz` | Comptable | Règlements, dépenses, rapports financiers |

Utile pour vérifier ce que voit chaque employé avant la mise en service.

> **En production : supprimer ou désactiver tous ces comptes sauf le propriétaire,
> et changer immédiatement son mot de passe** (Utilisateurs → Modifier).
> Pour ne créer que le propriétaire : `php artisan db:seed --class=RolesSeeder`
> puis créer le compte depuis l'interface.

---

## 3. Mise en cache pour la production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

À relancer après **chaque** déploiement. Pour revenir en arrière : `php artisan optimize:clear`.

---

## 4. Permissions fichiers

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
```

Le reste du projet doit rester en lecture seule pour le serveur web.

---

## 5. Nginx

```nginx
server {
    listen 443 ssl http2;
    server_name votre-domaine.dz;

    root /var/www/grossiste/public;   # jamais la racine du projet
    index index.php;

    client_max_body_size 8M;          # photos produits et justificatifs

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known) { deny all; }   # bloque .env, .git
}
```

---

## 6. Tâches planifiées

Une seule ligne de cron suffit :

```cron
* * * * * cd /var/www/grossiste && php artisan schedule:run >> /dev/null 2>&1
```

---

## 7. Sauvegardes

La base contient toute la comptabilité du magasin. Sauvegarde quotidienne minimum :

```bash
#!/bin/bash
# /usr/local/bin/sauvegarde-grossiste.sh
DATE=$(date +%F)
mysqldump --single-transaction --default-character-set=utf8mb4 \
    -u grossiste -p'...' grossiste | gzip > /var/backups/grossiste-$DATE.sql.gz
tar czf /var/backups/grossiste-storage-$DATE.tar.gz -C /var/www/grossiste storage/app/public
find /var/backups -name 'grossiste-*' -mtime +30 -delete
```

```cron
30 2 * * * /usr/local/bin/sauvegarde-grossiste.sh
```

**Tester la restauration au moins une fois.** Une sauvegarde jamais restaurée n'est pas une sauvegarde.

---

## 8. Vérifications d'exploitation

```bash
# Le stock affiché correspond-il au journal des mouvements ?
php artisan app:recompute-stock --check

# Combien de requêtes par écran ? (diagnostic de lenteur)
php artisan app:probe-queries
```

`app:recompute-stock` sans `--check` recalcule les totaux à partir du journal.
À lancer après une restauration de sauvegarde.

---

## 9. Sécurité — état des lieux

Déjà en place :

- Mots de passe hachés (bcrypt), minimum 8 caractères, confirmation obligatoire
- Protection CSRF sur tous les formulaires
- Échappement automatique Blade (aucun `{!! !!}` dans le projet)
- Requêtes préparées partout ; aucun `whereRaw` ne reçoit de saisie utilisateur
- `$fillable` sur tous les modèles ; le stock et les soldes ne sont **jamais** assignables par formulaire
- Autorisation serveur sur chaque route ; l'interface masque, le serveur refuse
- Limitation à 10 tentatives de connexion par minute
- Uploads restreints par type MIME et taille, stockés hors du code exécutable
- Journal d'audit automatique, sans mots de passe
- Comptes désactivés, jamais supprimés — les ventes restent attribuables

À faire côté serveur :

- HTTPS obligatoire (`SESSION_SECURE_COOKIE=true`)
- `APP_DEBUG=false`
- Racine web sur `public/` seulement
- Accès MySQL limité à `localhost`
- Mises à jour système régulières

---

## 10. Mise à jour

```bash
php artisan down                      # page de maintenance
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
```

Sauvegarder la base **avant** toute migration.

---

## 11. Données de démonstration

Pour une formation ou une démonstration, sur une base séparée :

```bash
php artisan migrate:fresh --seed
php artisan db:seed --class=DemoSeeder
```

Crée fournisseurs, clients, 16 produits, achats et ventes répartis sur un mois.
**Ne jamais lancer sur la base de production.**
