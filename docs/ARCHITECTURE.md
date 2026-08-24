# Grossiste — Wholesale & Retail Management System
## Architecture & Database Design (v1 — for approval)

Stack: Laravel 13.17, PHP 8.4, MySQL 8, Blade + jQuery 3, Vite 8, Tailwind 4.
No SPA framework — server-rendered Blade pages, jQuery only for AJAX bits (POS search, live totals).

---

## 1. Requirements Analysis

### Functional (MVP scope, phase-1)
- Auth + roles/permissions (6 roles, granular abilities)
- Products (simple form, 10 fields) + optional variants
- Categories, units, optional brands
- Stock per warehouse + stock movement ledger
- POS sale (retail/wholesale pricing), invoices, printing
- Purchases + receiving, supplier returns
- Sales returns + exchanges
- Customers/suppliers with balances, payments, debts
- Expenses
- Dashboard (8 widgets), reports with filters + PDF/Excel export
- Audit log, notifications, settings
- 3 locales: ar (RTL), fr, en

### Non-functional
- 100k+ products/transactions → indexed, paginated, no N+1
- All multi-table money/stock ops inside DB transactions with row locks
- Server-side authorization on every write (Policies + Gates)
- Soft-cancel, never hard-delete financial records
- Mobile/tablet responsive; POS optimized desktop/tablet
- Employee usable without training (progressive disclosure)

### Assumed defaults (no blocking questions)
| Topic | Default |
|---|---|
| Currency | DZD, 2 decimals, integer minor units in DB (`bigint` centimes) |
| Tax/VAT | Disabled by default, toggle in settings |
| Variants | Disabled by default per product |
| Multi-warehouse | 1 default warehouse; multi hidden until enabled |
| Negative stock | Blocked, admin-overridable setting |
| Costing method | Weighted Average Cost (WAC) |
| Invoice numbering | `INV-{YYYY}-{00000}` per-year sequence, atomic |
| Timezone | Africa/Algiers |

### Edge cases handled by design
Partial returns, return-of-returned qty, exchange with negative difference (refund), payment > debt (credit balance), stock adjustment races, price change after sale (snapshot prices on line items), deleted product with history (soft delete), concurrent POS on same product (`SELECT … FOR UPDATE`).

---

## 2. Actors & Permission Matrix

Roles: `owner`, `manager`, `sales`, `purchasing`, `warehouse`, `accountant`.

| Ability | owner | manager | sales | purchasing | warehouse | accountant |
|---|---|---|---|---|---|---|
| sale.create / invoice.print | ✔ | ✔ | ✔ | – | – | – |
| sale.discount.limited (≤ setting %) | ✔ | ✔ | ✔ | – | – | – |
| sale.discount.unlimited | ✔ | ✔ | – | – | – | – |
| sale.void | ✔ | ✔ | – | – | – | – |
| sale.return / sale.exchange | ✔ | ✔ | ✔ | – | – | – |
| purchase.* / supplier.* | ✔ | ✔ | – | ✔ | – | – |
| purchase.return | ✔ | ✔ | – | ✔ | – | – |
| product.view | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |
| product.create/update | ✔ | ✔ | – | ✔ | – | – |
| product.cost.view | ✔ | ✔ | – | ✔ | – | ✔ |
| stock.view / movements.view | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |
| stock.receive / stock.transfer | ✔ | ✔ | – | ✔ | ✔ | – |
| stock.adjust | ✔ | ✔ | – | – | ✔* | – |
| customer.* | ✔ | ✔ | ✔ | – | – | ✔ |
| payment.record | ✔ | ✔ | ✔ | ✔ | – | ✔ |
| expense.* | ✔ | ✔ | – | – | – | ✔ |
| report.sales/inventory | ✔ | ✔ | own | ✔ | ✔ | ✔ |
| report.financial | ✔ | ✔ | – | – | – | ✔ |
| user.manage / settings.manage | ✔ | – | – | – | – | – |
| audit.view | ✔ | ✔ | – | – | – | – |

`*` warehouse adjust requires approval flag if `settings.stock.adjust_requires_approval`.

Implementation: `spatie/laravel-permission` (roles+permissions tables) + Laravel Policies per model. Frontend receives a flat `can: {...}` map; UI hides what user cannot do, backend still enforces.

---

## 3. Module List

1. **Auth & Users** — login, roles, employee profiles, session security
2. **Catalog** — products, variants (opt), categories, brands (opt), units, barcodes/labels
3. **Inventory** — stock per warehouse, movements ledger, adjustments, transfers, damaged stock, alerts
4. **Purchasing** — purchases, receiving, supplier returns, supplier payments
5. **Sales / POS** — POS screen, sales, invoices, pricing engine (retail/wholesale tiers), sale returns, exchanges, customer payments
6. **Partners** — customers (types, credit limits), suppliers, account statements
7. **Finance** — payments ledger, debts, expenses, cash summary
8. **Reports** — sales/purchase/inventory/financial + exports
9. **Dashboard** — KPIs and 8 widgets
10. **System** — settings, audit log, notifications, localization, backups

---

## 4. Business Workflows (text diagrams)

### 4.1 Sale (POS)
```
Scan/search → line added (price = PricingEngine(product, customer, qty, mode))
   → qty edit → [discount if allowed] → customer (default: Walk-in)
   → payment method (default Cash) + amount paid
   → CONFIRM
        BEGIN TX
          lock stock rows (FOR UPDATE)
          assert available >= qty  (unless allow_negative)
          create sale + sale_items (snapshot: name, unit_price, cost_at_sale)
          stock_movements: type=sale, qty=-n, ref=sale
          decrement inventory.quantity
          payment record if paid > 0
          customer.balance += (total - paid)
          invoice_number = nextSequence('INV')
          audit_log
        COMMIT
   → print / PDF
```

### 4.2 Purchase
```
Supplier → add lines (product, qty, unit_cost) → discount/tax → payment
 → CONFIRM
   BEGIN TX
     purchase + purchase_items
     stock_movements type=purchase +qty
     inventory.quantity += qty
     product.avg_cost = WAC recompute
     supplier.balance += (total - paid)
     payment record
   COMMIT
```

### 4.3 Sale return
```
Find invoice → pick lines → qty (≤ sold - already_returned) → reason → condition
 → CONFIRM
   BEGIN TX
     sale_return + items
     condition=resellable → inventory +qty (movement type=sale_return)
     condition=damaged    → damaged_quantity +qty (movement type=damaged)
     refund: cash out  OR  credit to customer.balance
     audit_log
   COMMIT
```

### 4.4 Exchange
```
Original invoice → returned lines → replacement lines
 → difference = new_total - returned_total
   diff > 0 → customer pays diff
   diff < 0 → refund or credit
 → CONFIRM (single TX: return legs + sale legs + payment leg + movements)
```

### 4.5 Purchase return
```
Purchase → lines → qty (≤ received - returned) → reason
 → TX: inventory -qty, movement type=purchase_return, supplier.balance -= amount
```

### 4.6 Stock transfer / adjustment
```
Transfer: from_wh -qty (movement transfer_out) + to_wh +qty (transfer_in), one TX, one ref id
Adjust:   counted_qty vs system_qty → delta movement type=adjustment, reason required
```

---

## 5. ERD (text)

```
users ──< sales >── customers ──< customer_payments
  │        │  └──< sale_items >── products/product_variants
  │        └──< sale_returns >── sale_return_items
  │        └──< exchanges >── exchange_items
  │
  └──< purchases >── suppliers ──< supplier_payments
           └──< purchase_items >── products
           └──< purchase_returns >── purchase_return_items

products ──< product_variants
products >── categories, brands(opt), units
products ──< inventory >── warehouses
products ──< stock_movements >── warehouses   (polymorphic ref → sale/purchase/return/adjustment/transfer)
products ──< price_tiers                       (wholesale qty thresholds)
customers >── customer_types ──< price_tiers   (type-level pricing)

payments (polymorphic payable: sale | purchase | customer | supplier)
expenses >── expense_categories
audit_logs (polymorphic auditable, user_id)
settings (key/value, json)
notifications (Laravel default)
sequences (name, year, current)
```

---

## 6. Table Design (key columns)

Money columns: `bigint` minor units. All tables `id bigint PK`, `created_at/updated_at`.

**users** — name, email(uniq), password, phone, warehouse_id(nullable), is_active, last_login_at, softDeletes
**roles / permissions / model_has_roles / role_has_permissions** — spatie standard

**categories** — name_json (translatable), parent_id(nullable), is_active
**brands** — name, is_active
**units** — code, name_json
**products** — name_json, sku(uniq,nullable,auto), barcode(uniq,nullable), reference, category_id, brand_id(null), unit_id, description, cost_price, avg_cost, retail_price, wholesale_price, min_price, tax_rate(default 0), min_stock, has_variants(bool), track_stock(bool default true), image_path, note, is_active, softDeletes
  idx: (barcode), (sku), (category_id,is_active), FULLTEXT/LIKE index on name
**product_variants** — product_id, name_json, sku(uniq), barcode(uniq,null), attributes json, cost_price, retail_price, wholesale_price, is_active
**price_tiers** — product_id(nullable = global), customer_type_id(nullable), min_qty, max_qty(nullable), price, priority
  idx: (product_id, customer_type_id, min_qty)

**warehouses** — name, code, address, is_default, is_active
**inventory** — warehouse_id, product_id, variant_id(null), quantity, reserved_quantity, damaged_quantity
  UNIQUE(warehouse_id, product_id, variant_id); idx(product_id)
**stock_movements** — warehouse_id, product_id, variant_id, type ENUM(purchase, sale, sale_return, purchase_return, adjustment, transfer_in, transfer_out, damaged, opening), quantity(signed), balance_after, unit_cost, reference_type, reference_id, user_id, reason, created_at
  idx: (product_id, created_at), (reference_type, reference_id), (type, created_at)

**customer_types** — name_json, default_discount_pct, is_wholesale
**customers** — name, phone, email, address, customer_type_id, credit_limit, balance, notes, is_active, softDeletes; idx(phone), idx(name)
**suppliers** — name, company, phone, email, address, tax_number, balance, notes, is_active, softDeletes

**sales** — invoice_number(uniq), customer_id(null=walk-in), user_id, warehouse_id, type ENUM(retail,wholesale), subtotal, discount_amount, tax_amount, total, paid_amount, due_amount, status ENUM(completed, voided, partially_returned, returned), note, sold_at, voided_by/voided_at/void_reason
  idx: (sold_at), (customer_id), (user_id, sold_at), (status)
**sale_items** — sale_id, product_id, variant_id, product_name(snapshot), quantity, returned_quantity, unit_price, unit_cost(snapshot), discount_amount, tax_amount, line_total
**sale_returns** — return_number, sale_id, customer_id, user_id, total_amount, refund_method ENUM(cash, credit, exchange), status, reason, returned_at
**sale_return_items** — sale_return_id, sale_item_id, product_id, quantity, unit_price, condition ENUM(resellable, damaged, defective), line_total
**exchanges** — exchange_number, sale_id, sale_return_id, new_sale_id, difference_amount, settlement ENUM(customer_paid, refunded, credited), user_id
**exchange_items** — exchange_id, direction ENUM(in,out), product_id, quantity, unit_price

**purchases** — reference(uniq), supplier_id, user_id, warehouse_id, subtotal, discount_amount, tax_amount, total, paid_amount, due_amount, status ENUM(draft, received, voided, partially_returned), note, purchased_at
**purchase_items** — purchase_id, product_id, variant_id, quantity, received_quantity, returned_quantity, unit_cost, discount_amount, line_total
**purchase_returns / purchase_return_items** — mirror of sale returns

**payments** — payment_number, direction ENUM(in,out), party_type(customer|supplier), party_id, payable_type(sale|purchase|null=on-account), payable_id, amount, method ENUM(cash, card, transfer, cheque, other), reference, user_id, paid_at, note
  idx: (party_type, party_id, paid_at), (payable_type, payable_id)

**expense_categories** — name_json
**expenses** — expense_category_id, amount, spent_at, method, user_id, description, attachment_path

**audit_logs** — user_id, action, auditable_type, auditable_id, old_values json, new_values json, ip, user_agent, created_at; idx(auditable_type, auditable_id), (user_id, created_at)
**settings** — key(uniq), value json, group
**sequences** — name, year, current  (row-locked for atomic invoice numbers)

### Indexing & transaction strategy
- Every FK indexed; composite indexes chosen for the listed report filters.
- Reports over date ranges hit `(sold_at)` / `(purchased_at)` covering indexes; heavy aggregates cached 5 min + optional nightly `daily_summaries` rollup table (phase 2).
- All write flows: `DB::transaction(fn () => …)` with `lockForUpdate()` on `inventory` rows, ordered by `inventory.id` to prevent deadlocks.
- Constraints: FK `restrict` on referenced masters, `cascade` only parent→line items. CHECK `quantity >= 0` on inventory unless negative allowed.

---

## 7. Application Architecture

```
app/
  Domain/
    Catalog/{Models,Services,Data}
    Inventory/{Models,Services: StockService, MovementRecorder}
    Sales/{Models, Services: SaleService, ReturnService, ExchangeService, PricingEngine}
    Purchasing/{...}
    Finance/{PaymentService, DebtService}
    Reporting/{Queries/*}
  Http/
    Controllers/Api/*        (thin, delegate to services)
    Requests/*               (validation)
    Resources/*              (JSON shape)
    Middleware/{SetLocale, EnsureActiveUser}
  Policies/*
  Support/{Money, Sequence, Settings}
```
- Controllers thin; business logic in Services; no Repository layer (Eloquent is the repository) except `Reporting/Queries` read models.
- Events: `SaleCompleted`, `StockLowDetected`, `PaymentRecorded` → Listeners for notifications, audit, cache bust. Queued jobs for PDF/Excel export and label printing.
- Routes: standard `web.php` session routes (forms + redirects). A few JSON endpoints under `/ajax/*` feed the jQuery widgets (product search, price lookup). No Sanctum, no token layer.
- Frontend: **Blade + jQuery 3 + Tailwind 4**. Pages are server-rendered; jQuery handles the interactive parts only (barcode input, cart lines, live totals, autocomplete) via `$.ajax` against small JSON endpoints. Translations come from `lang/{ar,fr,en}/*.php` — one source, no duplicated JS locale files.

---

## 8. UI/UX Architecture

Navigation (11 items, exactly as requested): Dashboard · Sales · Purchases · Products · Inventory · Customers · Suppliers · Expenses · Reports · Users · Settings.

- **Shell**: collapsible sidebar, top bar (global search `Ctrl+K`, language switcher, notifications bell, user menu).
- **POS** (`/sales/new`): single screen, 2 columns. Left = barcode input (auto-focus) + product grid/search. Right = cart, customer picker, discount, totals, payment, big green **Confirm (F9)**. Shortcuts: F2 search, F4 customer, F9 confirm, Esc clear.
- **Products list**: table desktop / cards mobile, columns Name | Stock | Retail | Wholesale | Status. Add form = 10 fields, `Advanced settings` collapsed (SKU, brand, variants, tax, unit, min price).
- **Inventory**: Product | Stock | Min | Status badge (Available/Low/Out). Filters only: search, category, status.
- **Returns/Exchange**: wizard-lite, one page, invoice lookup on top, lines below, sticky summary bar.
- Global patterns: toasts, confirm dialogs for destructive/void, skeleton loaders, empty states with a primary action, inline validation messages.
- Progressive disclosure driven by `settings` flags: `variants_enabled`, `multi_warehouse_enabled`, `tax_enabled`, `batches_enabled` — all OFF by default; the UI literally does not render those fields when off.

---

## 9. Multilingual / RTL Strategy

- Backend: `lang/{ar,fr,en}/*.php` for UI + validation; user-content translatables (`name_json`) stored as JSON columns via `spatie/laravel-translatable`. `SetLocale` middleware from user preference → session → `Accept-Language`.
- Frontend: `vue-i18n` with lazy-loaded locale chunks; single source of message keys shared with backend for validation echo.
- Direction: `<html :dir>` toggled by locale; Tailwind 4 logical properties only (`ms-*/me-*/ps-*/pe-*`, `text-start/end`) — no `left/right` utilities. Icons that imply direction get `rtl:-scale-x-100`.
- Numbers/dates via `Intl` with locale; Arabic uses Latin digits by default (setting to switch).
- Invoices: PDF rendered per document locale; Arabic PDF via `dompdf`+Amiri font or `mpdf` (mpdf recommended for reliable RTL shaping).
- Reports/exports carry the requested locale.

---

## 10. Development Roadmap

| Sprint | Deliverable |
|---|---|
| 0 | ✅ Done — Blade+jQuery scaffold, MySQL switch, spatie packages, base layout, auth, RTL shell, settings + roles seed |
| 1 | ✅ Done — Catalog: categories/units/products (simple form), barcode search, image upload, list/CRUD + tests |
| 2 | ✅ Done — Inventory: warehouses, inventory table, StockService + movements, adjustments, transfers, alerts |
| 3 | ✅ Done — Purchasing: suppliers, purchases → stock in, supplier balance, supplier payments. **Purchase returns moved to sprint 5** (same screen pattern as sales returns) |
| 4 | ✅ Done — Sales: PricingEngine, POS screen, sales, printable invoice, customer payments, debts, void |
| 5 | ✅ Done — Returns & exchanges: partial customer returns (resellable/damaged), cash or credit refund, exchange with price difference, supplier returns |
| 6 | ✅ Done — Expenses, live Dashboard, 7 reports, CSV export (Excel) + browser print. **PDF via browser print, not dompdf** — the browser shapes Arabic correctly |
| 7 | ✅ Done — Audit log (automatic, model-level), alerts bell, users + roles UI, settings UI with feature toggles |
| 8 | ✅ Done — Security audit (2 leaks fixed), performance audit (N+1 + scale to 50k products), demo seeder, deployment guide |

Each sprint ships: migrations → models → services → requests → policies → controllers → Vue views → tests → ar/fr/en strings.

---

## Decisions needing your OK
1. MySQL 8 for prod (dev can stay SQLite)? — recommended yes.
2. Currency DZD, no VAT by default? 
3. Vue 3 SPA (vs Blade+Livewire). Recommended: Vue 3 SPA per your spec.
4. Packages: `spatie/laravel-permission`, `spatie/laravel-translatable`, `spatie/laravel-activitylog` (audit), `mpdf` (RTL PDF), `maatwebsite/excel`, `milon/barcode`.
