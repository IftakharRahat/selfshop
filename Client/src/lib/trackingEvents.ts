/**
 * Tracking Events Utility — GTM DataLayer Only
 *
 * Pushes structured ecommerce events to window.dataLayer
 * for the reseller subscription funnel.
 * GTM handles all tag firing (Facebook Pixel, GA4, etc.)
 *
 * Events tracked:
 * 1. sign_up          — user registers (creates an account)
 * 2. view_pricing     — user views the package selection page
 * 3. begin_checkout   — user clicks "Proceed To Payment"
 * 4. initiate_payment — user clicks "Pay Now" on the invoice page
 * 5. purchase         — payment succeeds, account activates
 *
 * User data is SHA-256 hashed before being pushed.
 */

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/** SHA-256 hash a string (returns hex). Returns empty string on failure. */
async function sha256(value: string): Promise<string> {
	if (!value || typeof window === "undefined") return "";
	try {
		const encoder = new TextEncoder();
		const data = encoder.encode(value.trim().toLowerCase());
		const hashBuffer = await crypto.subtle.digest("SHA-256", data);
		const hashArray = Array.from(new Uint8Array(hashBuffer));
		return hashArray.map((b) => b.toString(16).padStart(2, "0")).join("");
	} catch {
		return "";
	}
}

/** Safely push to GTM dataLayer */
function pushDataLayer(event: Record<string, unknown>) {
	if (typeof window !== "undefined") {
		(window as any).dataLayer = (window as any).dataLayer || [];
		(window as any).dataLayer.push(event);
	}
}

/** Build hashed user_data object for the dataLayer */
async function buildUserData(data?: {
	phone?: string;
	name?: string;
}): Promise<Record<string, string>> {
	const result: Record<string, string> = {};
	if (data?.phone) result.hashed_phone = await sha256(data.phone);
	if (data?.name) result.hashed_name = await sha256(data.name);
	return result;
}

// ---------------------------------------------------------------------------
// 0. view_registration — fired when registration form is displayed
// ---------------------------------------------------------------------------
export function trackViewRegistration() {
	pushDataLayer({
		event: "view_registration",
	});
}

// ---------------------------------------------------------------------------
// 1. sign_up — fired when a user successfully registers
// ---------------------------------------------------------------------------
export async function trackLead(data?: {
	method?: string;
	phone?: string;
	name?: string;
	campaignCode?: string;
}) {
	const user_data = await buildUserData({
		phone: data?.phone,
		name: data?.name,
	});

	pushDataLayer({
		event: "sign_up",
		method: data?.method || "phone",
		...(data?.campaignCode && { campaign_code: data.campaignCode }),
		...(Object.keys(user_data).length > 0 && { user_data }),
	});
}

// ---------------------------------------------------------------------------
// 2. view_pricing — fired when the package selection page renders
// ---------------------------------------------------------------------------
export async function trackViewPricing(data?: {
	phone?: string;
}) {
	const user_data = await buildUserData({ phone: data?.phone });

	pushDataLayer({
		event: "view_pricing",
		...(Object.keys(user_data).length > 0 && { user_data }),
	});
}

// ---------------------------------------------------------------------------
// 3. begin_checkout — fired when user clicks "Proceed To Payment"
// ---------------------------------------------------------------------------
export async function trackInitiateCheckout(data?: {
	value?: number;
	currency?: string;
	packageName?: string;
	packageId?: number;
	phone?: string;
}) {
	const user_data = await buildUserData({ phone: data?.phone });

	pushDataLayer({
		event: "begin_checkout",
		ecommerce: {
			currency: data?.currency || "BDT",
			value: data?.value || 0,
			items: [
				{
					item_id: data?.packageId ? `pkg_${data.packageId}` : undefined,
					item_name: data?.packageName || "Account Package",
					item_category: "Subscription",
					price: data?.value || 0,
					quantity: 1,
				},
			],
		},
		...(Object.keys(user_data).length > 0 && { user_data }),
	});
}

// ---------------------------------------------------------------------------
// 4. initiate_payment — fired when user clicks "Pay Now" on invoice page
// ---------------------------------------------------------------------------
export async function trackInitiatePayment(data?: {
	value?: number;
	currency?: string;
	packageName?: string;
	packageId?: number;
	invoiceId?: string;
	phone?: string;
}) {
	const user_data = await buildUserData({ phone: data?.phone });

	pushDataLayer({
		event: "initiate_payment",
		invoice_id: data?.invoiceId || "",
		ecommerce: {
			currency: data?.currency || "BDT",
			value: data?.value || 0,
			items: [
				{
					item_id: data?.packageId ? `pkg_${data.packageId}` : undefined,
					item_name: data?.packageName || "Account Package",
					item_category: "Subscription",
					price: data?.value || 0,
					quantity: 1,
				},
			],
		},
		...(Object.keys(user_data).length > 0 && { user_data }),
	});
}

// ---------------------------------------------------------------------------
// 5. purchase — fired when payment is completed and account is activated
// ---------------------------------------------------------------------------
export async function trackPurchase(data?: {
	value?: number;
	currency?: string;
	transactionId?: string;
	packageName?: string;
	packageId?: number;
	phone?: string;
	name?: string;
}) {
	const user_data = await buildUserData({
		phone: data?.phone,
		name: data?.name,
	});

	pushDataLayer({
		event: "purchase",
		ecommerce: {
			transaction_id: data?.transactionId || "",
			currency: data?.currency || "BDT",
			value: data?.value || 0,
			items: [
				{
					item_id: data?.packageId ? `pkg_${data.packageId}` : undefined,
					item_name: data?.packageName || "Account Package",
					item_category: "Subscription",
					price: data?.value || 0,
					quantity: 1,
				},
			],
		},
		...(Object.keys(user_data).length > 0 && { user_data }),
	});
}
