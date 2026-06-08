# Déploiement sur CloudPanel

Guide pour déployer **Menuiserie ERP** (Laravel 10 + Vue/Inertia) sur votre serveur CloudPanel.

**Serveur :** Ubuntu 24.04 — `13.140.140.149` — 4 CPU / 8 GB RAM

---

## Étape 1 — Créer le site dans CloudPanel

1. Ouvrez CloudPanel : `https://13.140.140.149:8443`
2. Cliquez **Sites** → **+ Add Site** → **Create a PHP Site**
3. Remplissez :
   - **Domain Name** : votre domaine (ex: `erp.votredomaine.com`) ou l'IP `13.140.140.149`
   - **Application** : **Laravel** (ou **Generic** si Laravel n'est pas listé)
   - **PHP Version** : **8.2** ou **8.3** (minimum 8.1)
   - **Site User** : ex. `menuiserie`
   - **Site User Password** : mot de passe fort
4. Cliquez **Create**

> Si vous avez choisi **Generic**, allez dans **Settings** du site et changez le **Root Directory** en ajoutant `/public` à la fin.
> Exemple : `erp.votredomaine.com/public`

---

## Étape 2 — Créer la base de données MySQL

1. Dans CloudPanel → **Databases** → **+ Add Database**
2. Remplissez :
   - **Database Name** : `menuiserie_erp`
   - **Database User** : `menuiserie_user`
   - **Password** : mot de passe fort
3. Notez ces 3 valeurs — vous en aurez besoin pour le `.env`

---

## Étape 3 — Se connecter en SSH

Depuis votre PC (PowerShell ou terminal) :

```bash
ssh menuiserie@13.140.140.149
```

Remplacez `menuiserie` par le **Site User** créé à l'étape 1.

---

## Étape 4 — Cloner le projet

```bash
cd ~/htdocs
rm -rf erp.votredomaine.com          # supprime le dossier vide créé par CloudPanel
git clone https://github.com/ussama07taa/MVP.git erp.votredomaine.com
cd erp.votredomaine.com
```

Remplacez `erp.votredomaine.com` par votre nom de domaine.

---

## Étape 5 — Configurer le `.env`

```bash
cp .env.production.example .env
nano .env
```

Modifiez ces lignes :

```env
APP_NAME="Menuiserie ERP"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://erp.votredomaine.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=menuiserie_erp
DB_USERNAME=menuiserie_user
DB_PASSWORD=votre_mot_de_passe_db
```

Générez la clé d'application :

```bash
php artisan key:generate
```

---

## Étape 6 — Installer les dépendances et builder

```bash
# PHP
php /usr/local/bin/composer install --no-dev --optimize-autoloader

# Frontend (Node.js doit être installé sur le serveur)
npm install
npm run build
```

> Si Node.js n'est pas installé sur le serveur, buildez en local sur votre PC puis uploadez le dossier `public/build/` via SFTP.

---

## Étape 7 — Base de données et permissions

```bash
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link

# Permissions Laravel
chmod -R 775 storage bootstrap/cache

# Cache production
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Étape 8 — SSL (HTTPS)

1. CloudPanel → votre site → **SSL/TLS**
2. Activez **Let's Encrypt** (gratuit) si vous avez un nom de domaine pointant vers `13.140.140.149`
3. Sans domaine, vous pouvez utiliser l'IP en HTTP (non recommandé en production)

---

## Connexion initiale

| Champ | Valeur |
|-------|--------|
| **URL** | `https://erp.votredomaine.com` |
| **Email** | `admin@taaouati.com` |
| **Mot de passe** | `password` |

> Changez le mot de passe admin immédiatement après la première connexion !

---

## Déploiement automatique (script)

Un script `deploy.sh` est inclus. Après la première configuration manuelle :

```bash
cd ~/htdocs/erp.votredomaine.com
chmod +x deploy.sh
DOMAIN=erp.votredomaine.com ./deploy.sh
```

Pour les mises à jour futures :

```bash
git pull origin main
php /usr/local/bin/composer install --no-dev --optimize-autoloader
npm run build
php artisan migrate --force
php artisan optimize:clear && php artisan config:cache && php artisan route:cache
```

---

## Dépannage

| Problème | Solution |
|----------|----------|
| Page blanche | `tail -f storage/logs/laravel.log` |
| Vite manifest not found | `npm run build` |
| 500 sur storage/uploads | `php artisan storage:link` + `chmod -R 775 storage` |
| Erreur DB | Vérifiez `.env` DB_* et que MySQL tourne |
| 403 Forbidden | Vérifiez Root Directory = `domaine/public` |

---

## Checklist rapide

- [ ] Site PHP créé (Laravel template)
- [ ] Root Directory = `/public`
- [ ] Base MySQL créée
- [ ] Projet cloné dans `~/htdocs/domaine/`
- [ ] `.env` configuré (APP_URL, DB, APP_DEBUG=false)
- [ ] `composer install --no-dev`
- [ ] `npm run build`
- [ ] `php artisan migrate --seed`
- [ ] `php artisan storage:link`
- [ ] Permissions `storage/` et `bootstrap/cache/`
- [ ] SSL activé
- [ ] Mot de passe admin changé
