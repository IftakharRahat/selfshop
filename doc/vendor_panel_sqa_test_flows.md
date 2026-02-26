# SelfShop Vendor Panel — SQA Test Feature Flows

> **App**: SelfShop Supplier Portal  
> **URL**: `/vendor/*`  
> **Date**: 2026-02-26  
> **Prepared for**: SQA Team

---

## Table of Contents

1. [Vendor Registration](#1-vendor-registration)
2. [Vendor Login](#2-vendor-login)
3. [Auth Guard & Routing](#3-auth-guard--routing)
4. [Dashboard](#4-dashboard)
5. [Products — List](#5-products--list)
6. [Products — Add New](#6-products--add-new)
7. [Products — Edit](#7-products--edit)
8. [Products — Bulk Upload](#8-products--bulk-upload)
9. [Orders — List](#9-orders--list)
10. [Orders — Detail & Actions](#10-orders--detail--actions)
11. [Campaign Events](#11-campaign-events)
12. [Category-wise Discount](#12-category-wise-discount)
13. [Earnings](#13-earnings)
14. [Payouts](#14-payouts)
15. [Payout Accounts](#15-payout-accounts)
16. [Profile & KYC](#16-profile--kyc)
17. [Product Reviews](#17-product-reviews)
18. [Shipping Methods](#18-shipping-methods)
19. [Warehouses](#19-warehouses)
20. [Notifications](#20-notifications)
21. [Sidebar & Navigation](#21-sidebar--navigation)

---

## 1. Vendor Registration

**Route**: `/vendor/register`

### Form Fields
| Field | Required | Type | Notes |
|-------|----------|------|-------|
| Your name | ✅ | text | — |
| Email or phone | ✅ | text | — |
| Password | ✅ | password | — |
| Company name | ✅ | text | — |
| Business type | ❌ | text | Placeholder: "Manufacturer, wholesaler, importer..." |
| Country | ❌ | text | — |
| City | ❌ | text | — |

### Test Cases

| # | Test Case | Steps | Expected Result |
|---|-----------|-------|-----------------|
| REG-01 | Successful registration | Fill all required fields → Click "Submit registration" | Toast: "Registration submitted. Admin will approve your vendor account." → Redirect to `/vendor/login` |
| REG-02 | Submit without name | Leave name empty → Submit | Browser HTML5 validation blocks submission |
| REG-03 | Submit without email | Leave email empty → Submit | Browser HTML5 validation blocks submission |
| REG-04 | Submit without password | Leave password empty → Submit | Browser HTML5 validation blocks submission |
| REG-05 | Submit without company name | Leave company name empty → Submit | Browser HTML5 validation blocks submission |
| REG-06 | Duplicate email | Register with an already-registered email | Toast: error message from API (e.g. "Email already exists") |
| REG-07 | Loading state | Click Submit → Observe button | Button shows "Submitting..." and is disabled |
| REG-08 | Navigate to login | Click "Back to vendor login" link | Navigates to `/vendor/login` |

---

## 2. Vendor Login

**Route**: `/vendor/login`

### Form Fields
| Field | Required | Type |
|-------|----------|------|
| Email or phone | ✅ | text |
| Password | ✅ | password |

### Test Cases

| # | Test Case | Steps | Expected Result |
|---|-----------|-------|-----------------|
| LGN-01 | Successful login | Enter valid vendor credentials → Click "Sign in" | Toast: "Logged in successfully" → Redirect to `/vendor` (dashboard) |
| LGN-02 | Wrong credentials | Enter invalid password → Sign in | Toast: "Login failed" or server message |
| LGN-03 | Empty email | Leave email empty → Submit | Browser validation blocks submission |
| LGN-04 | Empty password | Leave password empty → Submit | Browser validation blocks submission |
| LGN-05 | Loading state | Click Sign in → Observe button | Button shows "Signing in..." and is disabled |
| LGN-06 | Navigate to register | Click "Create supplier account" link | Navigates to `/vendor/register` |

---

## 3. Auth Guard & Routing

### Test Cases

| # | Test Case | Steps | Expected Result |
|---|-----------|-------|-----------------|
| AUTH-01 | Unauthenticated redirect | Visit `/vendor` without login | Redirect to `/vendor/login` |
| AUTH-02 | No vendor profile redirect | Login as user without a vendor profile → Visit `/vendor` | Redirect to `/vendor/profile` for setup |
| AUTH-03 | Auth pages accessible | Visit `/vendor/login` while not logged in | Page renders normally (no redirect loop) |
| AUTH-04 | Guard message display | Visit protected page without token | Shows "Redirecting to vendor login..." message |
| AUTH-05 | Vendor profile check | Login with token but no vendor profile → Visit `/vendor/orders` | Shows "Redirecting to vendor profile setup..." → Redirects to `/vendor/profile` |

---

## 4. Dashboard

**Route**: `/vendor`

### Widgets / Sections

| Widget | Data Displayed | Interactions |
|--------|---------------|--------------|
| Products card | Product count + "Add New Product" link | Link → `/vendor/products/new` |
| Rating card | Rating (5) + Followers count | — |
| Total Order card | Total orders + "View All Order" link | Link → `/vendor/orders` |
| Total Sales card | Total sales (BDT) + Last month sales | — |
| Sales Stat chart | Bar chart by month | Tooltip on hover shows formatted BDT |
| Category-wise product count | List of categories with product counts | — |
| Orders (This Month) | New, Accepted, Cancelled, On delivery, Delivered counts | — |
| Purchased Package | Current package name, limits, expiry | "Upgrade Package" button |
| Commission Type & Rate | Commission info from admin | — |
| Money Withdraw | Wallet icon + "Go to payout" button | Link → `/vendor/payouts` |
| Shop Settings | Settings icon + "Go to setting" button | Link → `/vendor/profile` |
| Payment Settings | Card icon + "Configure Now" button | Link → `/vendor/payout-accounts` |
| Sold Amount | Current month sales + Last month | — |
| Top 12 Products carousel | Scrollable product cards with image, name, price, rating, sales | Left/Right scroll buttons; Click card → `/vendor/products/{id}/edit` |

### Test Cases

| # | Test Case | Steps | Expected Result |
|---|-----------|-------|-----------------|
| DASH-01 | Page loads successfully | Navigate to `/vendor` | All cards render with data or "—" for loading |
| DASH-02 | Error state | API fails | Shows "Failed to load dashboard. Please refresh." |
| DASH-03 | Loading state | Page is loading | Stat cards show "—"; Sales chart shows "Loading..."; Carousel shows pulse skeletons |
| DASH-04 | Sales chart render | Dashboard has sales data | Bar chart renders with month labels and BDT formatted tooltips |
| DASH-05 | Empty sales chart | No sales data | Shows "No sales data yet" |
| DASH-06 | Top 12 carousel scroll | Click left/right arrows | Carousel scrolls smoothly in the corresponding direction |
| DASH-07 | Top products empty state | No products sold | Shows "No product sales yet" |
| DASH-08 | Quick links work | Click "Add New Product" / "View All Order" / "Go to payout" / etc. | Navigates to the correct page |

---

## 5. Products — List

**Route**: `/vendor/products`

### Test Cases

| # | Test Case | Steps | Expected Result |
|---|-----------|-------|-----------------|
| PLIST-01 | Products table displays | Navigate to Products page | Table shows: Name, Approval (badge), Current qty, Base price, Published (toggle), Stock (toggle), Options (Edit/Delete) |
| PLIST-02 | Search products | Type in search box | List filters based on search query |
| PLIST-03 | Empty state | No products exist | Shows "No products yet. Add your first product above." |
| PLIST-04 | Approval badges | Product pending/approved/rejected | Badge shows correct color: green=Approved, amber=Pending, red=Rejected |
| PLIST-05 | Toggle Published | Click publish toggle ON→OFF (Active→Inactive) | Toast: "Unpublished"; Toggle switches off |
| PLIST-06 | Toggle Published | Click publish toggle OFF→ON | Toast: "Published"; Toggle switches on (green) |
| PLIST-07 | Toggle Stock status | Click stock toggle ON→OFF | Toast: "Marked as Stock Out"; Toggle turns red; Label shows "STOCK OUT" |
| PLIST-08 | Toggle Stock status | Click stock toggle OFF→ON | Toast: "Marked as In Stock"; Toggle turns green; Label shows "IN STOCK" |
| PLIST-09 | Edit product | Click "Edit" link | Navigates to `/vendor/products/{id}/edit` |
| PLIST-10 | Delete product | Click "Delete" → Confirm dialog | Toast: "Product deleted"; Product removed from list |
| PLIST-11 | Cancel delete | Click "Delete" → Click Cancel in confirm | Product remains in list |
| PLIST-12 | Bulk Upload link | Click "Bulk upload" button | Navigates to `/vendor/products/bulk-upload` |
| PLIST-13 | Add product button | Click "Add new product" | Navigates to `/vendor/products/new` |
| PLIST-14 | API error state | API returns error | Shows "Failed to load products." |

---

## 6. Products — Add New

**Route**: `/vendor/products/new`

### Form Fields — Basic Information
| Field | Required | Type | Notes |
|-------|----------|------|-------|
| Product name | ✅ | text | — |
| Unit (e.g. Pc, Kg) | ❌ | text | Placeholder: "Pc" |
| Weight (kg) | ❌ | number | Default: 0 |
| Minimum purchase qty | ❌ | number | Default: 1 |
| Tags (comma separated) | ❌ | text | — |

### Form Fields — Category (Right Panel)
| Field | Required | Type | Notes |
|-------|----------|------|-------|
| Category | ✅ | dropdown | Populated from API |
| Subcategory | ✅ | dropdown | Populated based on selected category |
| Child category | Conditional | dropdown | Required if child categories exist for selected subcategory |
| Brand | ✅ | dropdown | Populated from API |

### Form Fields — Price & Stock (Dropshipping/Both modes only)
| Field | Required | Type |
|-------|----------|------|
| Base price (reseller) | ❌ | number |
| Regular price | ❌ | number |
| Quantity | ❌ | number |
| Low stock warning at | ❌ | number |
| SKU | ❌ | text |
| Discount | ❌ | number |
| Stock visibility | ❌ | radio (Show quantity / Show text / Hide) |

### Form Fields — Description
| Field | Required | Type |
|-------|----------|------|
| Short description | ❌ | textarea |
| Description | ❌ | textarea |

### Form Fields — Images
| Field | Required | Type |
|-------|----------|------|
| Gallery images | ❌ | file (multiple, images) |
| Thumbnail image | ❌ | file (single, image) |

### Selling Type
| Option | Description |
|--------|-------------|
| 🏭 Wholesale | Tier-based bulk pricing (shows Bulk Pricing table) |
| 🚀 Dropshipping | Single price + stock (shows Price & Stock section) |
| 🔄 Both | Wholesale + Dropshipping (shows both sections) |

### Bulk Pricing Table (Wholesale / Both)
| Column | Type | Notes |
|--------|------|-------|
| Variant (Optional) | text | e.g. "Red / S" |
| Color Name | text | e.g. "Red" |
| Color | color picker | — |
| Min Qty | number | — |
| Max Qty | number | — |
| Price | number | **Required to add row** |
| Delivery Charge | number | Optional |

### Test Cases

| # | Test Case | Steps | Expected Result |
|---|-----------|-------|-----------------|
| PADD-01 | Create product (wholesale) | Fill name, select category/subcategory/brand, selling type=Wholesale, add bulk pricing rows → Save | Toast: "Product created." → Redirect to `/vendor/products` |
| PADD-02 | Create product (dropshipping) | Fill name, category, brand, selling type=Dropshipping, enter base price, qty → Save | Product created with stock info |
| PADD-03 | Create product (both) | Fill all fields, selling type=Both, add bulk rows + fill price/stock → Save | Product created with both pricing types |
| PADD-04 | Missing required fields | Submit without product name | Browser validation blocks submission |
| PADD-05 | Missing category | Submit without selecting category | Browser validation blocks |
| PADD-06 | Category dropdown cascade | Select a category → Check subcategory options → Select subcategory → Check child categories | Subcategories populate; child categories populate; changing category resets subcategory |
| PADD-07 | Commission display | Select a category that has commission set | Shows "Selected category commission: X%" in the info box |
| PADD-08 | Add bulk pricing row | Fill Price field → Click "+ Add" | Row appears in table with variant, min/max qty, price, color |
| PADD-09 | Add bulk row no price | Leave Price empty → Click "+ Add" | Toast: "Price is required" |
| PADD-10 | Remove bulk row | Click "Remove" on an existing bulk row | Row is removed from table |
| PADD-11 | Auto-increment min qty | Add a row with Max Qty=10 → Add next row | Next row's Min Qty auto-fills to 11 |
| PADD-12 | Image upload | Select thumbnail + gallery images → Save | Product images are uploaded successfully |
| PADD-13 | Saving state | Click Save → Observe | Button shows "Saving..." and is disabled |
| PADD-14 | API validation error | Submit with server-side invalid data | Toast shows first API validation error message |
| PADD-15 | Selling type toggle | Switch between Wholesale ↔ Dropshipping ↔ Both | Correct sections show/hide (bulk pricing for Wholesale; price & stock for Dropshipping) |

---

## 7. Products — Edit

**Route**: `/vendor/products/{id}/edit`

### Test Cases

| # | Test Case | Steps | Expected Result |
|---|-----------|-------|-----------------|
| PEDT-01 | Edit page loads | Navigate to edit page for existing product | Form pre-fills with existing product data |
| PEDT-02 | Update product name | Change product name → Save | Toast: success; Product name updates |
| PEDT-03 | Update category | Change category/subcategory → Save | Product category updates; dropdown cascade works |
| PEDT-04 | Invalid product ID | Navigate to `/vendor/products/999999/edit` | Shows error or "Not found" message |

---

## 8. Products — Bulk Upload

**Route**: `/vendor/products/bulk-upload`

### Test Cases

| # | Test Case | Steps | Expected Result |
|---|-----------|-------|-----------------|
| BULK-01 | Page loads | Navigate to bulk upload page | Page renders correctly |
| BULK-02 | Upload valid file | Upload a correctly formatted file | Products are created/updated in bulk |
| BULK-03 | Upload invalid file | Upload an incorrectly formatted file | Error message displayed |

---

## 9. Orders — List

**Route**: `/vendor/orders`

### Filters
| Filter | Type | Options |
|--------|------|---------|
| Search | text | Free text search |
| Status | dropdown | All / Pending / Accepted / Rejected / Shipped to warehouse / Processing / Delivered / Returned |
| Payment | dropdown | All / Cash on Delivery / Online |

### Table Columns
Order code (link), Products count, Customer name & phone, Amount (৳), Delivery status (badge), Payment method, Options (View link)

### Test Cases

| # | Test Case | Steps | Expected Result |
|---|-----------|-------|-----------------|
| ORD-01 | Orders load | Navigate to `/vendor/orders` | Table displays orders with all columns |
| ORD-02 | Search filter | Type in search box | Results filter by order code / customer |
| ORD-03 | Status filter | Select "Pending" from dropdown | Only pending orders shown |
| ORD-04 | Payment filter | Select "Cash on Delivery" | Only COD orders shown |
| ORD-05 | Combined filters | Set status + payment + search | Results filtered by all criteria |
| ORD-06 | Pagination | Navigate to page 2 | Next page of orders loads; page info updates |
| ORD-07 | Previous/Next disabled | On first/last page | Previous/Next button disabled appropriately |
| ORD-08 | Click order code | Click invoice ID link | Navigates to `/vendor/orders/{id}` |
| ORD-09 | Click View | Click "View" link | Navigates to order detail |
| ORD-10 | Empty state | No orders | Shows "No orders found." |
| ORD-11 | Error state | API error | Shows "Failed to load orders." |
| ORD-12 | Status badge colors | Orders with different statuses | Delivered=green, Cancelled/Rejected=red, Accepted=emerald, Shipped=blue, Pending=amber |

---

## 10. Orders — Detail & Actions

**Route**: `/vendor/orders/{id}`

### Sections
- **Order Info**: Date, Delivery date, Status (badge), Courier live status, Warehouse sent at, Payment, Tracking number, Shipped at, Tracking link, Customer note
- **Shipping / Customer**: Customer name, phone, address
- **Your Items table**: Product (image + name + code), Price, Qty, Fulfillment status (badge), Tracking, Total
- **Vendor subtotal**: Sum total at bottom

### Action Buttons (conditionally shown)
| Button | Condition | Action |
|--------|-----------|--------|
| Accept | Status is Pending/Processing | Sets order to "Confirmed" |
| Reject | Not already rejected/cancelled/delivered | Sets order to "Canceled" with optional reason prompt |
| Send to warehouse | Accepted + not yet warehouse-sent + not rejected/delivered | Marks order as sent to warehouse |
| Add / update tracking | Not rejected/delivered | Opens tracking modal |

### Tracking Modal Fields
| Field | Type | Notes |
|-------|------|-------|
| Order tracking number | text | Single tracking for whole order |
| Per-item tracking | text × N | One input per line item |
| Dropship checkbox | checkbox | "Hide my branding on packing / label" |

### Test Cases

| # | Test Case | Steps | Expected Result |
|---|-----------|-------|-----------------|
| ODET-01 | Detail page loads | Click View on a valid order | All sections display order info, customer info, line items |
| ODET-02 | Accept order | Click "Accept" on a Pending order | Toast: "Order accepted"; Status badge updates to Accepted/Confirmed |
| ODET-03 | Reject order | Click "Reject" → Enter reason → OK | Toast: "Order rejected"; Status badge turns red |
| ODET-04 | Reject without reason | Click "Reject" → Leave reason empty → OK | Order still rejected (reason is optional) |
| ODET-05 | Send to warehouse | Click "Send to warehouse" on accepted order | Toast: "Order sent to warehouse"; Button disappears; "Warehouse sent at" timestamp appears |
| ODET-06 | Add order tracking | Click "Add / update tracking" → Enter tracking number → Save | Toast: "Tracking updated"; Tracking number shown in Order Info |
| ODET-07 | Add per-item tracking | Open tracking modal → Enter per-item tracking numbers → Save | Tracking numbers shown per line item in the table |
| ODET-08 | Dropship flag | Check "Dropship" checkbox → Save tracking | Fulfillment type set to "dropship" for line items |
| ODET-09 | Empty tracking submit | Open modal → Leave all empty → Save | Toast: "Enter at least one tracking number or mark as dropship." |
| ODET-10 | Cancel tracking modal | Open modal → Click "Cancel" | Modal closes without changes |
| ODET-11 | Invalid order ID | Navigate to `/vendor/orders/abc` | Shows "Invalid order ID." |
| ODET-12 | Order not found | Navigate to `/vendor/orders/999999` | Shows "Order not found." with "Back to orders" link |
| ODET-13 | Button visibility rules | Check accepted+shipped orders | Accept/Reject/Send to warehouse buttons hide appropriately per status |
| ODET-14 | Back to orders link | Click "Back to orders" | Navigates to `/vendor/orders` |

---

## 11. Campaign Events

### Campaigns List — `/vendor/campaigns`

| # | Test Case | Steps | Expected Result |
|---|-----------|-------|-----------------|
| CMP-01 | Campaigns load | Navigate to page | Campaign cards displayed in grid (banner, title, dates, countdown, stats) |
| CMP-02 | Countdown timer | Active campaign with future end date | Timer ticks every second showing "Xd Xh Xm Xs" |
| CMP-03 | Expired campaign | Campaign end date has passed | Badge shows "Ended" in red; CTA says "View Details" |
| CMP-04 | Registration closed | Registration deadline passed | Registration badge shows "Closed" in red |
| CMP-05 | Empty state | No campaigns | Shows "No active campaigns available" with icon |
| CMP-06 | Error state | API error | Shows "Failed to load campaigns." |
| CMP-07 | Campaign count badge | Campaigns loaded | Shows "X campaigns" pill in header |
| CMP-08 | Click campaign card | Click "Submit Deal" / "View Details" | Navigates to `/vendor/campaigns/{id}` |

### Campaign Detail — `/vendor/campaigns/{id}`

| # | Test Case | Steps | Expected Result |
|---|-----------|-------|-----------------|
| CMP-09 | Detail page loads | Navigate to campaign detail | Banner, info grid (Start, End, Time Left, Registration status), submitted products table |
| CMP-10 | Submit product | Click "Add Product" → Search → Select → Enter campaign price → Submit | Product appears in submitted products table with calculated discount % |
| CMP-11 | Submit without price | Select product but leave Campaign Price empty → Submit | Submit button remains disabled |
| CMP-12 | Already submitted product | Product already in campaign | Product shows "Already added" and is disabled in picker |
| CMP-13 | Remove submitted product | Click "Remove" on submitted product → Confirm | Product is removed from submitted list |
| CMP-14 | Cancel remove | Click "Remove" → Cancel confirm | Product remains |
| CMP-15 | Discount preview | Enter campaign price | Shows "Discount: X% OFF (from ৳Y)" text |
| CMP-16 | Search in picker | Type product name in picker search | Products filter by name |
| CMP-17 | Registration expired | Campaign registration deadline passed | "Add Product" button is hidden |
| CMP-18 | Campaign not found | Invalid campaign ID | Shows "Campaign not found" with back link |
| CMP-19 | Breadcrumb navigation | Click "Campaigns" in breadcrumb | Navigates back to `/vendor/campaigns` |

---

## 12. Category-wise Discount

**Route**: `/vendor/category-discount`

### Table Columns
| Column | Description |
|--------|-------------|
| # | Row index |
| Category | Icon + name |
| Discount | Number input (0–100) with "%" suffix |
| Discount Date Range | Start date + End date inputs |
| Action | "Set" button |

### Test Cases

| # | Test Case | Steps | Expected Result |
|---|-----------|-------|-----------------|
| CDISC-01 | Page loads | Navigate to page | Categories listed with current discount values |
| CDISC-02 | Set discount | Enter 10 in discount, set date range → Click "Set" | Toast: "Discount updated" |
| CDISC-03 | Set 0% discount | Enter 0 → Click "Set" | Toast: "Discount updated"; Effectively removes discount |
| CDISC-04 | Search categories | Type category name in search | Table filters matching categories |
| CDISC-05 | No categories | Empty list | Shows "No categories found." |
| CDISC-06 | Saving state | Click "Set" → Observe | Button shows "..." while saving |

---

## 13. Earnings

**Route**: `/vendor/earnings`

### Summary Cards
| Card | Color |
|------|-------|
| Total sales | Blue |
| Commission | Amber |
| Net earnings | Emerald |
| Pending (orders) | Gray |
| Available balance | Green |
| Paid out | Indigo |

### Earnings Table Columns
Order (invoice), Product, Line total, Commission, Net, Status (badge: paid/available/pending)

### Status Filters
All, Pending, Available, Paid

### Test Cases

| # | Test Case | Steps | Expected Result |
|---|-----------|-------|-----------------|
| EARN-01 | Page loads | Navigate to Earnings | Summary cards + earnings table display |
| EARN-02 | Summary values | Check card values | Values match API: total sales, commission, net, pending, available, paid |
| EARN-03 | Filter by status | Click "Pending" tab | Only pending earnings shown |
| EARN-04 | Filter by Available | Click "Available" tab | Only available earnings shown |
| EARN-05 | Filter by Paid | Click "Paid" tab | Only paid earnings shown |
| EARN-06 | Reset filter | Click "All" tab | All earnings shown |
| EARN-07 | Pagination | Navigate pages | Table paginates correctly |
| EARN-08 | Empty state | No earnings | Shows "No earnings yet. Sales will appear here." |
| EARN-09 | Payouts link | Click "Payouts" button in header | Navigates to `/vendor/payouts` |

---

## 14. Payouts

**Route**: `/vendor/payouts`

### Sections
1. **Balance & Request**: Available balance, pending request amount, amount input, payout account selector, "Request payout" button
2. **Payout Requests table**: Date, Amount, Account, Status (approved/rejected/pending)
3. **Payout History table**: Date, Amount, Reference, Status (paid/other)

### Test Cases

| # | Test Case | Steps | Expected Result |
|---|-----------|-------|-----------------|
| PAY-01 | Page loads | Navigate to Payouts | Balance shown, request form visible, tables load |
| PAY-02 | Request payout | Enter valid amount → Click "Request payout" | Toast: "Payout request submitted"; Request appears in requests table |
| PAY-03 | Amount exceeds balance | Enter amount > available balance → Request | Toast: "Amount cannot exceed available balance" |
| PAY-04 | Zero/negative amount | Enter 0 or -5 → Request | Toast: "Enter a valid amount" |
| PAY-05 | Pending request exists | Already have a pending request → Request again | Toast: "You already have a pending payout request" |
| PAY-06 | No payout accounts | No accounts configured | Shows "Add a payout account" link |
| PAY-07 | Select payout account | Multiple accounts exist → Select from dropdown | Selected account displayed in request |
| PAY-08 | Earnings link | Click "Earnings" button | Navigates to `/vendor/earnings` |
| PAY-09 | Request disabled states | Balance=0 OR pending request exists | Button disabled with reduced opacity |
| PAY-10 | Status badges | Requests with different statuses | Approved=green, Rejected=red, Pending=amber |
| PAY-11 | Pagination (requests) | Multiple pages of requests | Tables paginate correctly |
| PAY-12 | Pagination (history) | Multiple pages of history | Tables paginate correctly |

---

## 15. Payout Accounts

**Route**: `/vendor/payout-accounts`

### Account Card Info
Channel type (Bank/Mobile wallet/Other), Provider name, Account name, Account number, Routing number, DEFAULT badge, INACTIVE badge

### Modal Fields
| Field | Required | Type | Notes |
|-------|----------|------|-------|
| Type | ✅ | dropdown | Bank / Mobile wallet / Other |
| Provider / Bank name | ❌ | text | e.g. bKash |
| Account name | ✅ | text | — |
| Account number | ✅ | text | — |
| Routing number | ❌ | text | For banks |
| Use as default | ❌ | checkbox | — |

### Test Cases

| # | Test Case | Steps | Expected Result |
|---|-----------|-------|-----------------|
| PACC-01 | Page loads | Navigate to Payout Accounts | Account cards display |
| PACC-02 | Add account | Click "Add account" → Fill form → Click "Add" | Toast: "Payout account added"; New card appears |
| PACC-03 | Missing required fields | Submit without account name or number | Toast: "Account name and account number are required" |
| PACC-04 | Edit account | Click "Edit" → Change fields → Click "Update" | Toast: "Payout account updated" |
| PACC-05 | Delete account | Click "Remove" → Confirm | Toast: "Payout account removed"; Card disappears |
| PACC-06 | Cancel delete | Click "Remove" → Cancel | Account remains |
| PACC-07 | Cancel modal | Click "Cancel" in modal | Modal closes without saving |
| PACC-08 | Set as default | Check "Use as default" → Save | Account card shows "DEFAULT" badge |
| PACC-09 | Channel types | Select Bank / Mobile wallet / Other | Label shows correctly on card |
| PACC-10 | Empty state | No accounts | Shows "No payout accounts yet." with "Add your first payout account" link |
| PACC-11 | Payouts link | Click "Payouts" button | Navigates to `/vendor/payouts` |

---

## 16. Profile & KYC

**Route**: `/vendor/profile`

### Profile Form Fields
| Field | Required | Type |
|-------|----------|------|
| Company name | ✅ | text |
| Business type | ❌ | text |
| Contact person | ❌ | text |
| Contact email | ❌ | email |
| Contact phone | ❌ | text |
| Country | ❌ | text |
| City | ❌ | text |
| Address line | ❌ | text |

### KYC Document Form
| Field | Required | Type | Notes |
|-------|----------|------|-------|
| Document type | ✅ | text | e.g. "nid", "trade_license" |
| Document number | ❌ | text | — |
| Document file | ❌ | file | Image or PDF |

### Status Badges
- Account status: Approved (green) / Pending (amber) / Rejected (red)
- Verified badge: Enabled (sky blue) / Not granted (gray)

### Test Cases

| # | Test Case | Steps | Expected Result |
|---|-----------|-------|-----------------|
| PROF-01 | Profile loads | Navigate to `/vendor/profile` | Form pre-fills with existing vendor data |
| PROF-02 | First-time setup | New vendor (no profile) → Navigate | Form pre-fills with user info (name, email, phone) |
| PROF-03 | Save profile | Change company name → Click "Save profile" | Toast: "Vendor profile saved" |
| PROF-04 | Missing company name | Clear company name → Save | Browser validation blocks (required field) |
| PROF-05 | Submit KYC document | Fill document type → Upload file → Click "Submit KYC" | Toast: "KYC document submitted"; Document appears in submitted list |
| PROF-06 | KYC list display | Already submitted documents | Lists document type, number, date, status badge (approved/pending/rejected) |
| PROF-07 | KYC no documents | No documents submitted | Shows "No documents submitted yet." |
| PROF-08 | Account status display | Vendor is approved/pending/rejected | Correct colored badge displayed |
| PROF-09 | Verified badge display | Vendor has/hasn't verified badge | Shows "Verified badge enabled" or "Verified badge not granted" |
| PROF-10 | Saving state | Click Save → Observe | Button shows "Saving..." and is disabled |

---

## 17. Product Reviews

### Reviews List — `/vendor/reviews`

#### Filters
- Rating dropdown: 1–5 Stars
- Product name search

#### Table Columns
#, Product Name (with image), Rating (stars + value), Reviews count (+ "X new" badge), Options ("View Reviews" button)

### Test Cases

| # | Test Case | Steps | Expected Result |
|---|-----------|-------|-----------------|
| REV-01 | Page loads | Navigate to Reviews | Products with reviews listed in table |
| REV-02 | Filter by rating | Select "5 Stars" | Only 5-star rated products shown |
| REV-03 | Search by name | Type product name | Table filters by name |
| REV-04 | View Reviews | Click "View Reviews" on a product | Navigates to `/vendor/reviews/{id}` |
| REV-05 | New review badge | Product has new/unread reviews | Red badge shows "X new" |
| REV-06 | Empty state | No reviewed products | Shows "No product reviews found." |
| REV-07 | Star rendering | Products with different ratings | Stars filled correctly (e.g. 3.5 → 4 stars filled) |

### Review Detail — `/vendor/reviews/{id}`

| # | Test Case | Steps | Expected Result |
|---|-----------|-------|-----------------|
| REV-08 | Reviews display | Navigate to product review page | Individual reviews with customer info, rating, comment load |

---

## 18. Shipping Methods

**Route**: `/vendor/shipping`

### Table Columns
Name, Type (flat/weight/zone), Rate, Default (Yes/—), Active (Yes/No), Actions (Edit/Delete)

### Modal Fields
| Field | Required | Type | Notes |
|-------|----------|------|-------|
| Name | ✅ | text | e.g. "Standard delivery" |
| Type | ✅ | dropdown | Flat rate / By weight / By zone |
| Base rate (৳) | ✅ | number | — |
| Per kg rate (৳) | Conditional | number | Only visible when type = "weight" |
| Min order (৳) | ❌ | number | — |
| Max order (৳) | ❌ | number | — |
| Description | ❌ | text | — |
| Default method | ❌ | checkbox | — |
| Active | ❌ | checkbox | Default: checked |

### Test Cases

| # | Test Case | Steps | Expected Result |
|---|-----------|-------|-----------------|
| SHIP-01 | Page loads | Navigate to Shipping | Shipping methods table displays |
| SHIP-02 | Add flat rate method | Click "Add method" → Fill name + type=Flat + rate → Save | Toast: "Shipping method created"; Row appears |
| SHIP-03 | Add weight-based method | Type=By weight → Fill base rate + per kg rate → Save | Row shows rate + per kg suffix |
| SHIP-04 | Missing name | Submit without name | Toast: "Name is required" |
| SHIP-05 | Invalid rate | Enter negative rate → Save | Toast: "Enter a valid rate" |
| SHIP-06 | Edit method | Click "Edit" → Change name → Save | Toast: "Shipping method updated" |
| SHIP-07 | Delete method | Click "Delete" → Confirm | Toast: "Shipping method deleted"; Row removed |
| SHIP-08 | Cancel delete | Click "Delete" → Cancel confirm | Method remains |
| SHIP-09 | Cancel modal | Click "Cancel" in modal | Modal closes without saving |
| SHIP-10 | Per kg rate visibility | Switch type between Flat / Weight / Zone | Per kg rate field only visible for "weight" |
| SHIP-11 | Set as default | Check "Default method" → Save | Column shows "Yes" for default |
| SHIP-12 | Toggle active | Uncheck "Active" → Save | Column shows "No" (gray); method inactive |
| SHIP-13 | Empty state | No methods | Shows "No shipping methods yet." + "Add your first shipping method" link |

---

## 19. Warehouses

**Route**: `/vendor/warehouses`

### Warehouse Card Info
Name, Label, Default badge, Inactive badge, Address lines, City/State/Postcode, Country

### Modal Fields
| Field | Required | Type |
|-------|----------|------|
| Name | ✅ | text |
| Label | ❌ | text |
| Country | ❌ | text |
| State | ❌ | text |
| City | ❌ | text |
| Postcode | ❌ | text |
| Address Line 1 | ❌ | text |
| Address Line 2 | ❌ | text |
| Set as default warehouse | ❌ | checkbox |

### Test Cases

| # | Test Case | Steps | Expected Result |
|---|-----------|-------|-----------------|
| WH-01 | Page loads | Navigate to Warehouses | Warehouse cards display |
| WH-02 | Add warehouse | Click "Add Warehouse" → Fill name + address → Create | Toast: "Warehouse created"; Card appears |
| WH-03 | Missing name | Submit without name | Toast: "Warehouse name is required" |
| WH-04 | Edit warehouse | Click "Edit" → Change fields → Update | Toast: "Warehouse updated" |
| WH-05 | Delete warehouse | Click "Delete" → Confirm | Toast: "Warehouse deleted"; Card removed |
| WH-06 | Cancel delete | Click "Delete" → Cancel confirm | Warehouse remains |
| WH-07 | Set as default | Check "Set as default" → Save | Card shows "DEFAULT" badge |
| WH-08 | Inactive badge | Warehouse is inactive | Card shows "INACTIVE" badge |
| WH-09 | Address display | Warehouse with full address | All address fields render correctly |
| WH-10 | Cancel modal | Click "Cancel" | Modal closes without saving |
| WH-11 | Empty state | No warehouses | Shows "No warehouses yet." + "Create your first warehouse" link |
| WH-12 | Inventory link | Click "← Inventory" button | Navigates to `/vendor/inventory` |

---

## 20. Notifications

**Component**: `VendorNotificationCenter` (in layout header)

### Test Cases

| # | Test Case | Steps | Expected Result |
|---|-----------|-------|-----------------|
| NOTIF-01 | Notification bell visible | Logged in with vendor profile | Notification bell icon visible in header |
| NOTIF-02 | Bell hidden on auth pages | Visit `/vendor/login` | No notification bell |
| NOTIF-03 | Bell hidden without profile | Logged in but no vendor profile | Notification bell hidden/disabled |
| NOTIF-04 | Click notification bell | Click bell icon | Notification dropdown/panel opens |
| NOTIF-05 | Mobile notification | On mobile view | Notification bell appears in mobile header |

---

## 21. Sidebar & Navigation

### Navigation Sections

| Section | Items |
|---------|-------|
| **Main** | Dashboard, Reports |
| **Orders** | All orders, Shipping methods |
| **Campaigns** | Campaign Events |
| **Products** | Products, Add new product, Category-wise discount, Product reviews |
| **Earnings & Payouts** | Earnings, Payouts, Payout accounts |
| **Inventory** | Inventory, Warehouses |
| **Account** | Profile & KYC |

### Test Cases

| # | Test Case | Steps | Expected Result |
|---|-----------|-------|-----------------|
| NAV-01 | Sidebar renders | Logged in → View sidebar | All 7 sections with correct items displayed |
| NAV-02 | Active state | Navigate to a page | Corresponding nav item highlighted with dark bg + white text |
| NAV-03 | Navigation works | Click each nav item | Navigates to correct route |
| NAV-04 | Sidebar hidden on auth pages | Visit `/vendor/login` | Sidebar not visible |
| NAV-05 | Mobile hamburger menu | On mobile → Click hamburger icon | Sidebar slides in from left |
| NAV-06 | Mobile close menu | In mobile → Click X or overlay | Sidebar closes; body scroll restores |
| NAV-07 | Mobile nav route change | In mobile sidebar → Click a nav item | Sidebar closes; page navigates |
| NAV-08 | Brand link | Click "SelfShop Supplier" logo | Navigates to `/vendor` dashboard |
| NAV-09 | Mobile profile icon | On mobile → Click user profile icon | Navigates to `/vendor/profile` |

---

## Cross-Cutting Concerns

### Responsiveness
| # | Test Case | Expected Result |
|---|-----------|-----------------|
| RESP-01 | All pages render on mobile (≤640px) | No horizontal overflow; hamburger menu replaces sidebar |
| RESP-02 | All pages render on tablet (640–1024px) | Tables scroll horizontally if needed; layout stacks appropriately |
| RESP-03 | All pages render on desktop (≥1024px) | Sidebar visible; full table views; grid layouts |

### Error Handling
| # | Test Case | Expected Result |
|---|-----------|-----------------|
| ERR-01 | Network failure during API call | Toast error shown; page remains functional |
| ERR-02 | Session expired mid-action | Redirect to `/vendor/login` |
| ERR-03 | API returns 500 error | Error state shown on page (red text or error panel) |

### Loading States
| # | Test Case | Expected Result |
|---|-----------|-----------------|
| LOAD-01 | Initial page load | Skeleton/pulse animations or "Loading..." text shown |
| LOAD-02 | Button loading states | Buttons show "..." / "Saving..." / "Submitting..." text and are disabled |

---

> **Total test cases**: ~140+  
> **Modules covered**: 21 sections across 15+ feature areas
