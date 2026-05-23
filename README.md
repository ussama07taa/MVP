# Menuiserie ERP — Système de Gestion d'Atelier

ERP complet pour gérer un atelier de menuiserie : **Point de Vente (POS)**, **Gestion de Stock**, **File d'Attente Atelier**, **Facturation**, **Paie**, et **Statistiques Financières**.

---

## Prérequis

Avant de commencer, vérifiez que vous avez installé :

| Outil | Version minimum | Vérifier avec |
|-------|----------------|---------------|
| **PHP** | 8.1+ | `php -v` |
| **Composer** | 2.x | `composer -V` |
| **Node.js** | 18+ | `node -v` |
| **npm** | 9+ | `npm -v` |
| **MySQL** | 8.0+ | `mysql --version` |

### Installation des prérequis (Windows)

1. **PHP** : Téléchargez [XAMPP](https://www.apachefriends.org/) ou [Laragon](https://laragon.org/) (recommandé — Laragon inclut PHP + MySQL + Composer)
2. **Composer** : [getcomposer.org](https://getcomposer.org/download/)
3. **Node.js** : [nodejs.org](https://nodejs.org/) (version LTS)
4. **MySQL** : Inclus dans XAMPP/Laragon, ou téléchargez [MySQL](https://dev.mysql.com/downloads/)

---

## Installation

### Étape 1 : Cloner le projet

```bash
git clone https://github.com/ussama07taa/MVP.git
cd MVP
```

### Étape 2 : Installer les dépendances PHP

```bash
composer install
```

### Étape 3 : Configurer l'environnement

```bash
cp .env.example .env
php artisan key:generate
```

Ouvrez le fichier `.env` et modifiez les paramètres de la base de données :

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=menuiserie_erp
DB_USERNAME=root
DB_PASSWORD=votre_mot_de_passe
```

### Étape 4 : Créer la base de données

Ouvrez MySQL (phpMyAdmin, Laragon, ou terminal) et créez la base :

```sql
CREATE DATABASE menuiserie_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Étape 5 : Lancer les migrations + Seed

```bash
php artisan migrate --seed
```

Cela va créer toutes les tables et un compte administrateur par défaut :
- **Email** : `admin@taaouati.com`
- **Mot de passe** : `password`

> ⚠️ **IMPORTANT** : Changez le mot de passe après la première connexion !

### Étape 6 : Lier le stockage

```bash
php artisan storage:link
```

Cela crée un lien symbolique pour que les logos et fichiers uploadés soient accessibles.

### Étape 7 : Installer les dépendances JavaScript + Build

```bash
npm install
npm run build
```

### Étape 8 : Lancer le serveur

```bash
php artisan serve
```

Ouvrez votre navigateur sur **http://localhost:8000**

---

## Configuration de l'entreprise

Après la première connexion :

1. Allez dans **Paramètres** (icône engrenage dans le sidebar)
2. Remplissez :
   - **Nom de l'Atelier** (ex: "Mon Atelier Menuiserie")
   - **Téléphone** (ex: "+212 6XX-XXXXXX")
   - **Email**
   - **Adresse**
   - **I.C.E** et **R.C** (pour les factures)
   - **Logo** (format JPG/PNG, max 2MB)
   - **Texte bas de facture** (optionnel)
3. Cliquez **Enregistrer**

Ces informations apparaîtront automatiquement sur toutes les factures, le POS, et le dashboard.

---

## Utilisateurs et Rôles

Le système a 3 rôles :

| Rôle | Accès |
|------|-------|
| **admin** | Tout (Dashboard, POS, Stock, Clients, Factures, Paie, Paramètres, Utilisateurs) |
| **cashier** | POS, Clients, Stock, Commandes, File d'Attente Atelier |
| **worker** | Atelier Mobile uniquement (Khedma Diyali) |

Pour créer de nouveaux utilisateurs : **Utilisateurs** → **Nouvel Utilisateur**

---

## Fonctionnalités principales

| Module | Description |
|--------|-------------|
| **Point de Vente (POS)** | Vente de panneaux MDF/LATI, bandchant, services (découpe, collage chant) |
| **Gestion de Stock** | Stock panneaux + bandchant avec CUMP (coût moyen pondéré) |
| **Achats (Procurement)** | Gestion des achats fournisseurs avec mise à jour automatique du stock |
| **File d'Attente Atelier** | Queue pour les travaux de l'atelier avec vue mobile pour les ouvriers |
| **Clients** | Gestion clients avec suivi de crédit et historique des commandes |
| **Facturation** | Factures et devis avec impression |
| **Paie** | Pointage, avances, et calcul automatique des salaires |
| **Statistiques** | Dashboard + rapports financiers (Revenue, COGS, Marge, OPEX, Profit Net) |
| **Dépenses** | Suivi des charges d'exploitation |

---

## Mode développement

Si vous voulez modifier le code frontend (Vue.js), utilisez le mode dev avec hot-reload :

```bash
# Terminal 1 : Backend
php artisan serve

# Terminal 2 : Frontend (hot-reload)
npm run dev
```

Les changements Vue.js seront visibles en temps réel sans avoir à faire `npm run build`.

---

## Résolution de problèmes

### Les images/logo ne s'affichent pas
```bash
php artisan storage:link
```

### Erreur "Vite manifest not found"
```bash
npm install
npm run build
```

### Erreur de connexion à la base de données
Vérifiez les paramètres dans `.env` :
- `DB_DATABASE` correspond au nom de votre base
- `DB_USERNAME` et `DB_PASSWORD` sont corrects
- MySQL est en cours d'exécution

### Page blanche après mise à jour
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
npm run build
```

---

## Mise à jour du projet

```bash
git pull origin main
composer install
php artisan migrate
npm install
npm run build
php artisan cache:clear
```

---

## Stack technique

- **Backend** : Laravel 10, PHP 8.1+
- **Frontend** : Vue.js 3, Inertia.js v2, Tailwind CSS 4
- **Base de données** : MySQL 8
- **Build** : Vite 8
- **Icons** : Lucide Vue Next
- **Charts** : Chart.js + vue-chartjs
