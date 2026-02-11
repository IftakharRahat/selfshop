## Vendor / Supplier – Implementation Plan

This document breaks down the full vendor feature set into phases and concrete tasks. Use it for planning, assigning work, and tracking progress.

---

### Phase 0 – Foundations (in progress)

- **V-0.1 – Data model & roles**
  - Define `Vendor`, `VendorWarehouse`, `VendorPayoutAccount`, `VendorKycDocument` models + migrations.
  - Link `User` ↔ `Vendor` (one-to-one) and ensure `is_verified_wholesaler` / role flags are consistent.
  - Seed example vendor accounts for testing.
- **V-0.2 – Admin visibility**
  - Add `Type` column on admin `Users` list (Customer / Vendor / Wholesaler).
  - Create `Vendor Requests` list in admin (menu: **Users → Vendor Requests**).
  - Show vendor company, contact info, status, and created date.

---

### Phase 1 – Vendor Account & Profile

**Goal:** A vendor can register, submit KYC and profile details; admin can approve/reject.

- **V-1.1 – Vendor registration & auth**
  - Frontend pages: `vendor/register`, `vendor/login`, `vendor/profile`.
  - API endpoints for registration, login, logout, password reset.
  - Create `User` + linked `Vendor` record with status `pending` on registration.
- **V-1.2 – KYC & bank info**
  - KYC upload form: NID / Trade License, document type, file upload, status.
  - Bank / mobile banking info stored in `VendorPayoutAccount`.
  - Validation + secure storage of document paths.
- **V-1.3 – Shop/profile management**
  - Vendor profile form for:
    - Store name, logo, banner.
    - Address, pickup location, country/city/postcode.
    - Contact & support info (email, phone, WhatsApp, support hours).
  - Multiple warehouse/location support via `VendorWarehouse` model and UI.
- **V-1.4 – Admin approval workflow**
  - Admin actions on `Vendor Requests`: Approve / Reject with optional reason.
  - On approve: set `vendor.status = approved`, `user.status = Active`, `is_verified_wholesaler = true`.
  - On reject: set `vendor.status = rejected`, store `notes`, notify vendor.
  - Vendor portal banner showing current status (Pending / Approved / Rejected).

---

### Phase 2 – Product Management

**Goal:** Vendors can manage their own products with wholesale-friendly pricing.

- **V-2.1 – Basic product CRUD**
  - Vendor-facing product list and create/edit forms.
  - Restrict access so vendors only see/manage their own products.
  - Fields: title, description, images, base price, category, brand, stock.
- **V-2.2 – Variants & MOQ**
  - Support product variants (size, color, etc.).
  - MOQ (minimum order quantity) per product/variant.
- **V-2.3 – Bulk product upload**
  - CSV/Excel template for bulk product import.
  - Upload endpoint with validation and error reporting.
- **V-2.4 – Wholesale pricing & dropshipping**
  - Wholesale pricing tiers and MOQ-based price slabs.
  - `allow_dropship` flag per product with special pricing.
- **V-2.5 – Product approval flow**
  - Product status: Pending / Approved / Rejected.
  - Admin UI to review vendor products and approve/reject.
  - Vendor view of product approval status + rejection reason.

---

### Phase 3 – Inventory & Stock Management

**Goal:** Accurate stock per vendor, per location.

- **V-3.1 – Core stock tracking**
  - Stock fields and movement logs per product/variant.
  - Real-time stock update on order placement / cancellation / return.
- **V-3.2 – Alerts & tools**
  - Low stock alerts (email/notification + dashboard widget).
  - SKU management and search.
- **V-3.3 – Multiple locations**
  - Stock per warehouse (`VendorWarehouse`).
  - UI for vendor to allocate stock per location.
- **V-3.4 – Dropshipping sync (later)**
  - Hooks/API for auto stock sync for dropship vendors.

---

### Phase 4 – Order Management

**Goal:** Vendors can manage their orders end‑to‑end.

- **V-4.1 – Order list & details**
  - Vendor order list filtered to orders containing their products.
  - Statuses: Pending, Processing, Shipped, Delivered, Cancelled, Returned.
  - Order detail page with line items and customer shipping info (respecting privacy rules).
- **V-4.2 – Status updates & notifications**
  - Vendor can update order line/item statuses (within allowed transitions).
  - Notifications to buyers on key status changes.
- **V-4.3 – Bulk & wholesale orders**
  - Tools for bulk updates (e.g. change status for selected orders).
  - Handling large quantity wholesale orders (UX + performance).
- **V-4.4 – Documents**
  - Invoice & packing slip download/print.
  - Option to hide vendor branding for dropship orders.

---

### Phase 5 – Shipping & Fulfillment

**Goal:** Vendors can fulfil orders with consistent shipping rules.

- **V-5.1 – Shipping methods & rates**
  - Vendor-level shipping methods and rate configuration (flat, weight, zone-based).
  - Integration with existing courier setup where possible.
- **V-5.2 – Fulfilment workflow**
  - Tracking number upload and display to customer.
  - Partial shipment support for multi-item orders.
- **V-5.3 – Dropshipping flow**
  - Separate dropshipping fulfilment workflow and labelling.
  - Hide origin/vendor branding where configured.

---

### Phase 6 – Pricing, Commission & Payout

**Goal:** Clear money flow for vendors and platform.

- **V-6.1 – Commission model**
  - Global + per-category and per-vendor commission rates.
  - Calculation on each order line item.
- **V-6.2 – Earnings & balances**
  - Vendor earnings dashboard with:
    - Total sales, commissions, net earnings.
    - Pending balance, available balance, upcoming payouts.
- **V-6.3 – Payouts**
  - Bank / mobile banking setup UI (ties to `VendorPayoutAccount`).
  - Payout request flow (manual).
  - Admin payout processing + payout history for vendors.
- **V-6.4 – Automation (later)**
  - Scheduled/auto payouts via integration with payment provider.

---

### Phase 7 – Reports & Analytics

**Goal:** Give vendors and admins visibility into performance.

- **V-7.1 – Vendor reports**
  - Sales reports (daily / monthly).
  - Top selling products.
  - Wholesale vs dropship sales breakdown.
- **V-7.2 – Admin reports**
  - Profit & commission report by vendor.
  - Order & return analytics by vendor and product.

---

### Phase 8 – Reviews, Ratings & Communication

**Goal:** Build trust and support between buyers, vendors, and admin.

- **V-8.1 – Reviews & ratings**
  - Display product reviews in vendor portal.
  - Vendor rating calculation and display.
  - Vendor ability to reply to reviews (with moderation).
- **V-8.2 – Disputes & complaints**
  - Vendor view of disputes/complaints related to their orders.
  - Workflow for resolution with admin oversight.
- **V-8.3 – Messaging & notifications**
  - Buyer ↔ Vendor messaging per order.
  - Admin ↔ Vendor notifications (announcements, policy updates).

---

### Phase 9 – Settings, Security & Advanced

**Goal:** Make the vendor system robust, configurable, and scalable.

- **V-9.1 – Business settings**
  - Vendor-level business settings: Tax/VAT, return & refund policy.
  - Country-wise shipping rules and currency options (where relevant).
- **V-9.2 – Security & permissions**
  - Multi-user vendor accounts (staff logins) with roles (Manager, Staff).
  - Permissions matrix for vendor staff.
  - Login history and optional 2FA.
- **V-9.3 – Integrations (advanced)**
  - API access and webhooks for wholesale/ERP integrations.
  - White‑label dropshipping, auto price sync, and auto order routing.
  - Vendor performance score and subscription-based vendor plans.

---

### How to use this plan

- Treat each **V-x.y** item as a deliverable that can be turned into a ticket.
- Keep **`TESTING-VENDOR.md`** focused on QA scenarios; use this file for **scope & roadmap**.
- Update this file as decisions change (out of scope / done / moved to later phase).

