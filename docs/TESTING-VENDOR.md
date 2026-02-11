# How to Test Vendor (Wholesale) Module

## 1. Backend (Laravel) – API

### 1.1 Start Laravel
```bash
cd c:\Users\Rahat\OneDrive\Documents\backup\backend
php artisan serve
```
API base: `http://127.0.0.1:8000/api`

### 1.2 Test without auth (public product – should work)
```bash
curl -s "http://127.0.0.1:8000/api/product-details/YOUR_PRODUCT_SLUG" | head -c 500
```
Replace `YOUR_PRODUCT_SLUG` with a real product slug from your DB (e.g. from `products.ProductSlug`).

### 1.3 Get a login token
```bash
curl -s -X POST "http://127.0.0.1:8000/api/login" ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"YOUR_EMAIL_OR_PHONE\",\"password\":\"YOUR_PASSWORD\"}"
```
Copy the `access_token` from the JSON response.

### 1.4 Mark your user as verified wholesaler (required for vendor API)
In MySQL or Laravel Tinker:
```bash
cd c:\Users\Rahat\OneDrive\Documents\backup\backend
php artisan tinker
```
Then:
```php
\User::where('email', 'YOUR_EMAIL')->update(['is_verified_wholesaler' => true]);
```
Or in SQL: `UPDATE users SET is_verified_wholesaler = 1 WHERE email = 'YOUR_EMAIL';`

### 1.5 Test vendor product details (with token)
```bash
curl -s "http://127.0.0.1:8000/api/vendor/product-details/YOUR_PRODUCT_SLUG" ^
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```
- **200**: You should see `product_details` and `price_tiers`.
- **403**: User is not verified wholesaler (do step 1.4).
- **401**: Invalid or missing token (do step 1.3).

### 1.6 Test bulk add to cart (with token)
```bash
curl -s -X POST "http://127.0.0.1:8000/api/vendor/bulk-add-to-cart" ^
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" ^
  -H "Content-Type: application/json" ^
  -d "{\"product_id\":1,\"items\":[{\"variant_label\":\"Red / S\",\"qty\":2,\"unit_price\":350,\"size\":\"S\"}]}"
```
- **201**: Cart rows created.
- **422**: Check body (product_id, items array required).

---

## 2. Frontend (Next.js) – UI

### 2.1 Start Next.js
```bash
cd Client
pnpm dev
```
Open: `http://localhost:3000`

### 2.2 Test vendor area (no subdomain)
- Go to: **http://localhost:3000/vendor**  
  You should see the Vendor (Wholesale) dashboard.
- Go to a product page, click **“Bulk Order (mixed sizes · tier discount)”**  
  You should land on `/vendor/product/{slug}` with the **Stepper Matrix** (variant list with [-] qty [+]) and **sticky footer** (Total Qty, Unit price, Total BDT).

### 2.3 Test with login
1. Log in on the main site (so Redux has `access_token`).
2. Ensure your user is **verified wholesaler** (step 1.4).
3. Open **http://localhost:3000/vendor/product/YOUR_PRODUCT_SLUG**.
4. Change quantities with the steppers; footer should update (tier discount if total qty ≥ 50 or 100).
5. Click **“Add to cart · X pcs”**.
   - If 201: you should be redirected and cart should have the new lines.
   - If 401/403: check token and `is_verified_wholesaler`.

### 2.4 Test with vendor subdomain (optional)
- Add to `C:\Windows\System32\drivers\etc\hosts`:  
  `127.0.0.1 vendor.localhost`
- Open **http://vendor.localhost:3000**  
  Same app, but middleware rewrites to the vendor UI (e.g. `/vendor`).

---

## 3. Quick checklist

| Step | What to check |
|------|----------------|
| Laravel running | `http://127.0.0.1:8000` responds |
| Public product API | `GET /api/product-details/{slug}` returns JSON |
| User verified | `users.is_verified_wholesaler = 1` for your user |
| Vendor product API | `GET /api/vendor/product-details/{slug}` with Bearer token returns 200 + `price_tiers` |
| Bulk cart API | `POST /api/vendor/bulk-add-to-cart` with token returns 201 |
| Next.js running | `http://localhost:3000/vendor` loads |
| Bulk Order button | On product page, link goes to `/vendor/product/{slug}` |
| Matrix + footer | Quantities and totals update when steppers are used |
| Add to cart (UI) | After “Add to cart”, no error and cart has new items (or redirect works) |

If any step fails, use the error message (401/403/422 or UI error) and the section above to fix it.

---

## 4. Testing Category-Wise Discount & Product Reviews

These flows require a **vendor user**: someone who has registered via **Vendor Registration** (`/vendor/register`) or has a `vendors` row linked to their `users` account. The vendor portal uses the same `auth:sanctum` token as the main site.

### 4.1 Prerequisites

1. **Backend running:** `cd backend && php artisan serve` (e.g. `http://127.0.0.1:8000`).
2. **Frontend running:** `cd Client && pnpm dev` (e.g. `http://localhost:3000`).
3. **Vendor user:** Log in as a user that has an associated vendor record (e.g. registered at `/vendor/register` and approved, or manually linked in DB: `vendors.user_id = users.id`).
4. **Migrations run:** `reviews` and `vendor_category_discounts` tables exist (migrations from the category discount & reviews feature).

### 4.2 Category-Wise Discount – UI flow

1. Log in as a vendor user (via `/vendor/login` or main login then go to `/vendor`).
2. In the left sidebar, under **Products**, click **Category-Wise Discount**.
3. You should see a table of all active categories with:
   - Icon, Name, Discount (%), Discount Date Range (start/end date), Action (Set).
4. **Set a discount:**
   - In a row, enter a **discount %** (e.g. `10`).
   - Optionally set **start date** and **end date**.
   - Click **Set** for that row.
5. **Expected:** Toast “Discount updated”, and on refresh the same row still shows your values.
6. **Search:** Use “Type name & Enter” to filter categories by name; only matching rows should show.

### 4.3 Category-Wise Discount – API (optional)

```bash
# 1. Get token (use your vendor user email/password)
curl -s -X POST "http://127.0.0.1:8000/api/login" -H "Content-Type: application/json" -d "{\"email\":\"vendor@example.com\",\"password\":\"password\"}"
# Copy access_token from response.

# 2. List categories with discount data
curl -s "http://127.0.0.1:8000/api/vendor/category-discounts" -H "Authorization: Bearer YOUR_ACCESS_TOKEN"

# 3. Set discount for category ID 1 (use an id from step 2)
curl -s -X POST "http://127.0.0.1:8000/api/vendor/category-discounts/1" ^
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" ^
  -H "Content-Type: application/json" ^
  -d "{\"discount_percent\":15,\"start_date\":\"2025-02-01\",\"end_date\":\"2025-02-28\"}"
```

- **200:** Category discount created/updated.
- **403:** User has no vendor; **401:** Invalid/missing token.

### 4.4 Product Reviews – Getting reviews into the system

Reviews are created by **customers** (logged-in users) on the main site, not from the vendor panel. One way to create test data:

**Option A – Main site UI**  
If your storefront has a product review form (e.g. on product detail page), log in as a normal customer, open a product that belongs to your vendor, submit a review (rating + message).

**Option B – API (customer token)**  
Use a **customer** user token (not vendor):

```bash
# Get customer token
curl -s -X POST "http://127.0.0.1:8000/api/login" -H "Content-Type: application/json" -d "{\"email\":\"customer@example.com\",\"password\":\"password\"}"

# Submit review for a product (use a product_id that has vendor_id = your vendor)
curl -s -X POST "http://127.0.0.1:8000/api/review/store" ^
  -H "Authorization: Bearer CUSTOMER_ACCESS_TOKEN" ^
  -H "Content-Type: application/json" ^
  -d "{\"product_id\":344,\"rating\":5,\"messages\":\"Great product!\"}"
```

**Option C – Database**  
Insert directly into `reviews`:

```sql
INSERT INTO reviews (user_id, product_id, messages, rating, status, created_at, updated_at)
VALUES (1, 344, 'Test review text', 5, 'Active', NOW(), NOW());
```

Use a `product_id` that exists and has `vendor_id` set to your vendor’s id.

### 4.5 Product Reviews – Vendor UI flow

1. Log in as the **vendor** (same user whose products have reviews).
2. In the sidebar, under **Products**, click **Product Reviews**.
3. **List page:** You should see a table of **your products that have at least one review**, with:
   - Product name (and thumbnail), Rating (stars + average), Reviews count, “new” badge if any, **View Reviews** button.
4. Use **Filter by Rating** (e.g. 5 Stars) and **Type Product Name** search to narrow the list.
5. Click **View Reviews** for one product.
6. **Detail page:** You should see:
   - Product name and image, average rating, total review count.
   - Each review: user name/email, star rating, message, optional image, date, status.

### 4.6 Product Reviews – API (optional)

```bash
# List products with reviews (vendor token)
curl -s "http://127.0.0.1:8000/api/vendor/reviews" -H "Authorization: Bearer VENDOR_ACCESS_TOKEN"
# Optional: ?search=ProductName&rating=5

# Get all reviews for a specific product (vendor token)
curl -s "http://127.0.0.1:8000/api/vendor/reviews/344" -H "Authorization: Bearer VENDOR_ACCESS_TOKEN"
```

- **200:** JSON with `product`, `avg_rating`, `review_count`, `reviews` array.
- **403:** Not your product; **404:** Product not found or not vendor’s.

### 4.7 Quick checklist – Category discount & Reviews

| Step | What to check |
|------|----------------|
| Vendor logged in | Can open `/vendor` and see Products sidebar |
| Category discount page | `/vendor/category-discount` loads, table of categories visible |
| Set discount | Enter % and dates, click Set → success toast, data persists on refresh |
| Reviews exist | At least one review in `reviews` for a product with your `vendor_id` |
| Reviews list | `/vendor/reviews` shows that product with rating and count |
| View Reviews | Click View Reviews → detail page shows each review with user, rating, message |
| Filter/search | Rating filter and product name search work on reviews list |

---

## 5. Phase 2 – Product Management (Variants, Bulk Upload, Wholesale Tiers & Dropship)

Use a **vendor user** (logged in with a user that has an associated `vendors` record). Replace `VENDOR_ACCESS_TOKEN` and product `ID` with real values.

### 5.1 Variants & MOQ (V-2.2)

**Test case 1 – Add variant and list**
1. Open a vendor product edit page: `/vendor/products/{id}/edit` (use a product ID you own).
2. In the **Variants** section, enter Title (e.g. `Red / S`), Qty `10`, Price `200`. Click **Add variant**.
3. **Expected:** Success toast; the new variant appears in the table (Title, Qty, Price). Refreshing the page still shows the variant.
4. **API check:** `GET /api/vendor/products/{id}/variants` with Bearer token returns `data.variants` array including the new row.

**Test case 2 – Remove variant**
1. On the same edit page, ensure at least one variant exists. Click **Remove** for one variant.
2. **Expected:** Success toast; the row disappears. Refreshing the page confirms the variant is gone.
3. **API check:** `DELETE /api/vendor/products/{id}/variants/{variantId}` with Bearer token returns 200 and the variant is no longer returned by `GET .../variants`.

### 5.2 Bulk product upload (V-2.3)

**Test case 1 – Valid CSV creates products**
1. Go to **Products** → **Bulk upload** (`/vendor/products/bulk-upload`). Click **Download CSV template** and open the file.
2. Fill at least one data row with valid values: `ProductName`, `category_id`, `subcategory_id`, `brand_id` (use existing IDs from your DB, e.g. 1, 1, 1), `ProductResellerPrice`, `ProductRegularPrice`, `qty`, `minimum_qty`, etc. Save as CSV.
3. Upload the file and click **Upload**.
4. **Expected:** Success message; **Result** shows **Created: 1** (or more). Products list shows the new product(s) with Pending approval.

**Test case 2 – Invalid CSV returns errors**
1. Upload a CSV with an invalid row (e.g. empty `ProductName`, or non‑existent `category_id` such as 99999).
2. **Expected:** Response shows **Created: 0** (or partial count) and **Result** lists row-level errors (e.g. "Row 2: ProductName required" or "category_id invalid"). No product is created for invalid rows.

### 5.3 Wholesale price tiers & allow_dropship (V-2.4)

**Test case 1 – Add price tier and allow_dropship**
1. On product edit `/vendor/products/{id}/edit`, check **Allow dropship**. In **Wholesale price tiers**, add Min qty `50`, Unit price `180`, Label `Tier 2`. Click **Add tier**.
2. **Expected:** Success toasts; the new tier appears in the table. **Allow dropship** remains checked after saving the main form.
3. **API check:** `GET /api/vendor/products/{id}/price-tiers` returns `data.price_tiers` with the new tier. Product resource includes `allow_dropship: true` after save.

**Test case 2 – Remove price tier**
1. On the same edit page, click **Remove** for one price tier.
2. **Expected:** Success toast; the tier row disappears. `GET .../price-tiers` no longer returns that tier.
