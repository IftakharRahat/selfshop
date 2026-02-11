# Admin Panel: Products, Category Discount & Product Reviews

This document describes how **vendor products**, **category-wise discount**, and **product reviews** work in the **admin panel** (Laravel Blade, `admin/*` routes).

---

## 1. Products in Admin Panel

### Current behavior

- **Products list** (`admin/products`)  
  - Data is loaded via `admin/product/get/data` (ProductController `productdata()`).  
  - **Filter:** Products are filtered by `shop_id = current admin's id`. So this list shows products linked to the logged-in admin’s “shop”, not all products in the system.
- **Vendor-created products**  
  - Stored in the same `products` table with `vendor_id` set.  
  - They **do not** appear in the main Products list unless they also have `shop_id` set to that admin (e.g. your app sets `shop_id` when a vendor product is approved).
- **Product edit** (`admin/products/{id}/edit`)  
  - Admin can open any product by ID (e.g. from a direct link or from “Shops Products” if visible).  
  - The edit form includes **Discount %** (`Discount`). Admin can change product-level discount here for any product they can edit.
- **Shops Products** (`admin/shop/products`)  
  - Lists products where `shop_id != current admin's id` (other shops’ products).  
  - Does not specifically filter by `vendor_id`.

### How “product” works in admin (summary)

| Action | Where | How it works |
|--------|--------|----------------|
| List products | **Products** menu | Shows products with `shop_id` = logged-in admin. Vendor products appear only if they have that `shop_id`. |
| Edit product (name, price, **discount**, status, etc.) | **Products** → Edit | Edit form has **Discount %**. Saving updates `products.Discount`. |
| List other shops’ products | **Shops Products** | Products with different `shop_id`. |
| See vendor-owned products | **Vendor Requests** → View vendor | On vendor detail you see vendor info; product count or list can be added there. For a single list of all vendor products, use **Vendor Products** (see below). |

### Optional: “Vendor Products” in admin

To see all products created by vendors in one place:

- Add a menu item **Users → Vendor Products** (or under **Vendor Requests**).
- Backend: new route + method that lists `Product::whereNotNull('vendor_id')->with('vendor')` (and optional filters).
- View: table with product name, SKU, vendor name, category, status, link to edit (reuse `admin/products/{id}/edit`).

Then admin can open **Products** → Edit for any product (by ID) and change **Discount %** or other fields.

---

## 2. Category-wise discount in Admin Panel

### Current behavior (vendor-only)

- **Category-wise discount** is set by **vendors** in the vendor portal (`/vendor/category-discount`).
- Each record is in `vendor_category_discounts` (per vendor, per category: `discount_percent`, `start_date`, `end_date`).
- **Admin panel:** There is no dedicated admin page for this yet. Admins cannot see or edit vendor category discounts from the UI.

### How “discount” works in admin (summary)

| Level | Who sets it | Where (vendor) | Where (admin) |
|-------|-------------|----------------|----------------|
| **Product-level discount** | Vendor (vendor portal) or Admin | Vendor product create/edit | **Products** → Edit product → **Discount %** field |
| **Category-wise discount** (vendor) | Vendor only | **Category-Wise Discount** page | **Vendor Category Discounts** (see below) – optional admin list/edit |

### Vendor product verification (admin approve & edit)

When a **vendor adds a product**:

1. The product is saved with **vendor_approval_status = pending** and **status = Inactive**, so it does **not** appear on the storefront until admin approves.
2. Admin sees it under **Users** → **Vendor Products** (`admin/vendor-products`). The list shows all products with `vendor_id` set, with columns: Image, Product name, Vendor, Category, SKU, **Approval status** (Pending/Approved/Rejected), Added date.
3. Admin can **Approve** (sets `vendor_approval_status = approved`, `status = Active` — product then appears on the storefront) or **Reject** (sets `vendor_approval_status = rejected`, `status = Inactive`).
4. Admin has **full edit access**: click **Edit** to open the normal product edit form (`admin/products/{id}/edit`). There they can change name, prices, **Discount %**, and for vendor products a **Vendor approval status** dropdown (Pending/Approved/Rejected). For vendor products, **vendor_id** and **ProductSku** are preserved on save (not overwritten).

### Optional: Admin view for vendor category discounts

To let admin see and optionally edit vendor category discounts:

- Add menu: e.g. **Users** dropdown → **Vendor Category Discounts** (or under a “Vendor” section).
- **List page:** Table of all `vendor_category_discounts` with columns: Vendor name, Category name, Discount %, Start date, End date, Actions (Edit).
- **Edit (optional):** Form or modal to change discount % and dates for a selected vendor+category.  
- Backend: `AdminVendorCategoryDiscountController@index` (and optionally `@update`). Use `VendorCategoryDiscount::with(['vendor','category'])->get()`.

Once implemented, admin can open that page to see how each vendor’s category discount is set and change it if needed.

---

## 3. Product reviews in Admin Panel

### Current behavior

- **Reviews** are submitted by **customers** on the frontend (e.g. `POST /api/review/store`). Stored in `reviews` (product_id, user_id, rating, messages, status, etc.).
- **Vendor portal:** Vendors see their product reviews at **Product Reviews** and **View Reviews** per product (read-only).
- **Admin panel:** There is no admin page for reviews yet. Admins cannot list, approve, or reply to reviews from the UI.

### How “reviews” could work in admin (summary)

| Action | Vendor portal | Admin panel (if implemented) |
|--------|----------------|-------------------------------|
| List reviews | Per product (vendor’s products only) | All reviews, with filters (product, vendor, rating, status) |
| View review | Message, rating, user, date | Same + optional “Reply” or “Approve/Reject” |
| Moderate | – | Approve / Reject (update `reviews.status`) |
| Reply | – | Store reply (e.g. in `review_replies` or in a note field) |

### Optional: Admin page for product reviews

To let admin manage reviews:

- Add menu: e.g. **Users** (or **Products**) → **Product Reviews**.
- **List page:** Table of reviews with: Product name (and link), Vendor (if product has vendor_id), Customer, Rating, Message snippet, Status, Date, Actions (View, Approve/Reject).
- **Detail/Modal:** Full message, user info, optional status change and reply.
- Backend: `AdminReviewController@index` (and optionally `@update` for status). Use `Review::with(['product','user'])->...` and filter by product/vendor/rating/status.

Then “product” (which product the review is for) and “discount” (product/category discount) are both visible and manageable from the admin panel as described above.

---

## 4. Quick reference

- **Product-level discount:** Admin can edit it in **Products** → Edit product → **Discount %**.
- **Category-wise discount (per vendor):** Set by vendors in the vendor portal; admin can see/edit if you add **Vendor Category Discounts** page and backend.
- **Product reviews:** Created by customers; vendors see them in the vendor portal; admin can see/moderate if you add **Product Reviews** page and backend.

---

## 5. Implemented admin pages

The following admin pages are implemented and available under **Users** dropdown:

| Page | Route | Description |
|------|--------|-------------|
| **Product Reviews** | `admin/reviews` | List all reviews with product name, vendor, customer, rating, message, status. Filter by product name, rating, status. **Activate/Deactivate** each review (updates `reviews.status`). |
| **Vendor Category Discounts** | `admin/vendor-category-discounts` | List all vendor-set category discounts. Columns: Vendor, Category, Discount %, Start/End date, Updated. Filter by vendor ID or category ID. Read-only (vendors edit from their portal). |
| **Vendor Products** | `admin/vendor-products` | List all products added by vendors. Columns: Image, Product, Vendor, Category, SKU, Approval status, Added, Action (Edit, Approve, Reject). Approve makes the product visible on the storefront; Edit opens full product edit form with Vendor approval status dropdown. |

- **Sidebar:** **Users** → **Vendor Requests**, **Vendor Products**, **Product Reviews**, **Vendor Category Discounts**.
- **Product-level discount** continues to be edited in **Products** → Edit product → **Discount %**.
