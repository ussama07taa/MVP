# PROJECT_STATE.md - Rapport d'Architecture & Sécurité

Ce document récapitule l'état technique actuel du projet **SaaS_Menuiserie_ERP**. Il est destiné à être partagé pour des revues de sécurité ou des déploiements.

## 1. Architecture & Tech Stack

*   **Backend** : Laravel 10.10 (PHP 8.1+)
*   **Frontend** : Vue.js 3.5.33 (Composition API)
*   **Framework CSS** : Tailwind CSS v4.2.4
*   **Outil de Build** : Vite 5.0.0
*   **Modèle d'Intégration** : Hybride SPA (Single Page Application). Laravel sert de point d'entrée unique via `app.blade.php`, et Vue Router prend le relais pour la navigation interne. L'authentification et les données sont gérées via une API standalone protégée par Laravel Sanctum.

## 2. Schéma de la Base de Données (Modèles & Relations)

Le système utilise un pattern **Multi-tenant** où chaque ressource est isolée par un `tenant_id`.

### Modèles Principaux & Colonnes :
*   **User** : `id`, `name`, `email`, `password`, `role` (admin/cashier), `tenant_id`, `timestamps`.
*   **Order** : `id`, `tenant_id`, `client_id`, `user_id`, `total_sell_price`, `total_cost_price`, `amount_paid`, `status`, `timestamps`.
*   **InventoryAdjustment** : `id`, `tenant_id`, `item_type` (Morph), `item_id`, `user_id`, `quantity`, `reason` (kosor/chute/erreur), `notes`, `timestamps`.
*   **Setting** : `id`, `company_name`, `company_address`, `company_phone`, `company_email`, `company_ice`, `company_rc`, `company_logo`, `invoice_footer_text`, `timestamps`.
*   **StockPanel (MDF/LATI)** : `id`, `type`, `color_code`, `finish_type`, `quantity`, `cost_price`, `base_price_sell`, `tenant_id`.
*   **StockCanto (Bandchant)** : `id`, `name`, `color_code`, `total_length_remaining`, `cost_price_per_m`, `base_price_sell_per_m`, `tenant_id`.

### Relations Clés :
*   `Order` **belongsTo** `User` (Cashier)
*   `Order` **hasMany** `OrderLine`
*   `InventoryAdjustment` **morphTo** `StockPanel` ou `StockCanto`
*   Toutes les ressources **belongTo** `Tenant`.

## 3. Routes & API Endpoints

### Routes Web (`web.php`) :
*   `/login` [GET/POST] : Géré par `AuthController`.
*   `/logout` [POST] : Déconnexion sécurisée avec jeton CSRF.
*   `/{any}` [GET] : Catch-all route redirigeant vers `app.blade.php` pour laisser Vue Router gérer l'interface.

### Routes API (`api.php`) - Middleware `auth:sanctum` :
*   `GET /api/user` : Retourne l'utilisateur actuel.
*   `POST /api/orders/checkout` : Validation d'une vente (Transaction atomique).
*   `GET /api/panels` & `GET /api/cantos` : Récupération du stock.
*   `POST /api/stock/adjust` : Déclaration de casse (Kosor).
*   **Middleware Admin** : Protège les routes de statistiques financières, configuration des employés et paramètres globaux.

## 4. Structure Frontend (Vue Router)

### Routes Vue (`app.js`) :
*   `/` -> Redirige vers `/dashboard`.
*   `/login` -> Placeholder pour la page Blade.
*   `/pos` -> Interface de caisse (`PosApp.vue`).
*   `/admin` -> Layout d'administration (`AdminLayout.vue`) contenant les sous-routes :
    *   `/admin/stock-mdf`
    *   `/admin/stock-canto`
    *   `/admin/statistiques`
    *   `/admin/settings`

### Arborescence des Composants :
`app.blade.php` -> `#app` -> `router-view` -> (`AdminLayout` ou `PosApp`) -> (Pages spécifiques comme `StockMdfPage.vue`).

## 5. Design Patterns & Gestion d'État

*   **Gestion d'État** : Utilisation de **Vue 3 Composition API (refs/reactive)**. L'état global (utilisateur connecté, paramètres entreprise) est injecté depuis le Blade vers l'objet `window` au chargement initial.
*   **Authentification** : Gestion par **Cookies HTTP-Only (Sanctum Stateful)**. Le frontend envoie les credentials via Axios (`withCredentials: true`), et Laravel gère la session de manière sécurisée.
*   **CORS** : Configuré strictement pour autoriser les requêtes authentifiées depuis le domaine local/production.

## 6. Snippets de Code Critiques

### Configuration Vite (`vite.config.js`) :
```javascript
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue(),
    ],
    build: {
        chunkSizeWarningLimit: 1000,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        return id.toString().split('node_modules/')[1].split('/')[0].toString();
                    }
                }
            }
        }
    }
});
```

---
**Rapport généré le :** 01 Mai 2026
**Statut :** Prêt pour revue de sécurité et déploiement.
