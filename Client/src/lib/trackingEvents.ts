/**
 * Tracking Events Utility
 * 
 * Fires Facebook Pixel events and pushes to GTM Data Layer
 * for the subscription funnel tracking.
 * 
 * Events tracked:
 * 1. Lead — user registers/opens account
 * 2. InitiateCheckout — user views pricing or starts payment
 * 3. Purchase — payment completes, account activates
 */

// Safely access fbq (Facebook Pixel)
function fbq(...args: any[]) {
	if (typeof window !== "undefined" && (window as any).fbq) {
		(window as any).fbq(...args);
	}
}

// Safely push to GTM dataLayer
function pushDataLayer(event: Record<string, any>) {
	if (typeof window !== "undefined") {
		(window as any).dataLayer = (window as any).dataLayer || [];
		(window as any).dataLayer.push(event);
	}
}

/**
 * Fire when a user successfully registers (creates an account)
 */
export function trackLead(data?: { method?: string }) {
	// Facebook Pixel
	fbq("track", "Lead", {
		content_category: "Subscription",
		...(data?.method && { content_name: data.method }),
	});

	// GTM Data Layer
	pushDataLayer({
		event: "sign_up",
		method: data?.method || "email",
	});
}

/**
 * Fire when a user views pricing page or starts the payment process
 */
export function trackInitiateCheckout(data?: {
	value?: number;
	currency?: string;
	packageName?: string;
}) {
	// Facebook Pixel
	fbq("track", "InitiateCheckout", {
		value: data?.value || 0,
		currency: data?.currency || "BDT",
		content_category: "Subscription",
		content_name: data?.packageName || "Account Package",
	});

	// GTM Data Layer
	pushDataLayer({
		event: "begin_checkout",
		ecommerce: {
			value: data?.value || 0,
			currency: data?.currency || "BDT",
			items: [
				{
					item_name: data?.packageName || "Account Package",
					item_category: "Subscription",
					price: data?.value || 0,
					quantity: 1,
				},
			],
		},
	});
}

/**
 * Fire when payment is completed and account is activated
 */
export function trackPurchase(data?: {
	value?: number;
	currency?: string;
	transactionId?: string;
	packageName?: string;
}) {
	// Facebook Pixel
	fbq("track", "Purchase", {
		value: data?.value || 0,
		currency: data?.currency || "BDT",
		content_category: "Subscription",
		content_name: data?.packageName || "Account Package",
	});

	// GTM Data Layer
	pushDataLayer({
		event: "purchase",
		ecommerce: {
			transaction_id: data?.transactionId || "",
			value: data?.value || 0,
			currency: data?.currency || "BDT",
			items: [
				{
					item_name: data?.packageName || "Account Package",
					item_category: "Subscription",
					price: data?.value || 0,
					quantity: 1,
				},
			],
		},
	});
}
