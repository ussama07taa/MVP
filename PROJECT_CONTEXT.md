# Project Context: SaaS Menuiserie ERP

This document serves as the authoritative knowledge base for the **SaaS Menuiserie ERP** project. It outlines the business logic, technical architecture, and critical workflows to ensure consistency for all AI assistants and developers working on this codebase.

---

## 1. Project Overview & Business Logic

*   **Name:** SaaS Menuiserie ERP
*   **Purpose:** A multi-tenant ERP system specifically designed for woodworking workshops (Menuiseries) in Morocco. It addresses local industry needs such as board cutting (Tefsil), edge banding (Canto/Bandchant), and specific material types (MDF, Lati, etc.).
*   **Core Features:**
    *   **Point of Sale (Caisse):** Optimized for fast checkout of materials and labor services.
    *   **Inventory Management:** Real-time tracking of MDF boards, edge banding rolls, and consumables.
    *   **Supplier Procurement (Achats):** Integrated flow for restocking and managing supplier debt.
    *   **Financial Tracking:** Profit calculation per order, client credit management, and expense tracking (including employee wages).
    *   **Multi-Tenancy:** Each workshop operates in its own isolated environment.

---

## 2. Tech Stack

*   **Backend:** Laravel 11 (PHP 8.2+), MySQL.
*   **Frontend:** 
    *   Vue 3 (Composition API) with Vite.
    *   Vue Router for a Single Page Application (SPA) experience in the Admin panel.
    *   Tailwind CSS for styling.
    *   Headless UI & Lucide Icons for interactive components.
*   **Architecture:**
    *   **Multi-tenancy:** Single database approach using a `tenant_id` scope.
    *   **Trait-based Scoping:** The `BelongsToTenant` trait and `TenantScope` ensure data isolation by automatically applying `tenant_id` to queries.
    *   **Pessimistic Locking:** Uses `lockForUpdate()` during financial and stock transactions (e.g., in `OrderController`) to prevent race conditions.

---

## 3. Database Structure & Key Models

### Core System
*   **`tenants`:** Workshop accounts.
*   **`users`:** Staff accounts belonging to a tenant (Admin/Cashier).
*   **`clients`:** Customers with tracking for `total_credit` (unpaid debt).

### Sales & Finance
*   **`orders`:** Main sale record containing totals, status, and profit data.
*   **`order_lines`:** Individual items in an order. Uses a polymorphic `item` relationship (`item_type`, `item_id`).
*   **`payments`:** Tracks payments (advances/settlements) from clients.

### Inventory & Stock
*   **`stock_panels`:** MDF/LATI boards. Tracked by `type`, `size_x`, `size_y`, `thickness`, and `color_code`.
*   **`stock_cantos`:** Bandchant (Edge banding). Tracked by `color_code`, `width_mm`, and `total_length_remaining` (meters).
*   **`consumables`:** Glue, screws, and hardware. Tracked by `quantity_in_stock`.
*   **`services`:** Labor items like "Découpe" (Cutting) or "Pose" (Installation).

### Suppliers & Procurement
*   **`suppliers`:** Vendors with `total_debt` tracking.
*   **`purchases`:** Bulk reception of stock items.
*   **`purchase_items`:** Line items for purchases (MDF, Canto, Consumables).
*   **`supplier_payments`:** Tracking payments made to reduce `total_debt`.

---

## 4. Critical Workflows

### 📥 Procurement Flow (Achats)
1.  Admin records a mixed purchase (e.g., 50 MDF boards, 5 Canto rolls, 10kg Glue) from a `Supplier`.
2.  The system increments the relevant `quantity` or `total_length_remaining` in the stock tables.
3.  The total cost of the purchase is added to the `Supplier`'s `total_debt`.
4.  Initial advances paid at reception are recorded as `SupplierPayment` and deducted from the debt.

### 📤 Sales/POS Flow (Caisse)
1.  **Selection:** Cashier selects MDF panels or Canto rolls.
2.  **Smart Service Linking:** When a `StockCanto` is added, the UI allows toggling a "Pose" service. This merges the material and labor into a single line for the invoice but correctly decrements the meter length from `stock_cantos`.
3.  **Checkout:** `OrderController` uses `DB::transaction` and `lockForUpdate` to:
    *   Decrement stock levels.
    *   Create the `Order` and `OrderLine` records.
    *   Record any "Avance" (Advance) paid.
    *   Add any remaining balance to the `Client`'s `total_credit`.

### 📄 Quotation Flow (Devis)
*   *Implementation Note:* Quotes (Devis) are represented as `orders` with a specific status (e.g., `draft` or `quote`). Unlike confirmed orders, they do **not** affect stock levels or client credit until converted to a full invoice.

---

## 5. UI/UX Principles

*   **POS Layout:** Features a fixed right-side cart for rapid, tablet-friendly checkout. It supports on-the-fly client creation.
*   **SPA Navigation:** The Admin dashboard uses Vue Router to switch between views (Stock, Clients, History) without page reloads, maintaining a premium, app-like feel.
*   **Quick Data Entry:** Uses Headless UI Modals to allow adding Suppliers or Clients without leaving the current procurement or sales screen.
*   **Wood Texture Picker:** A visual component used in Stock and Procurement pages to select finishes by color swatch (e.g., Halifax Oak, Asur 112) instead of typing complex codes.
*   **Responsiveness:** Designed first for Desktop/Tablet use in a workshop environment, using a sleek dark-themed sidebar and clean, card-based layouts.
