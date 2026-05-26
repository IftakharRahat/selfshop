export interface SubscriptionInvoice {
  id?: number | string | null;
  invoice_id?: number | string | null;
  invoiceID?: string | null;
  invoice_code?: string | null;
  package_id?: number | string | null;
  package_name?: string | null;
  amount?: number | string | null;
  payable_amount?: number | string | null;
}

export interface SubscriptionState {
  is_active?: boolean;
  is_expired?: boolean;
  status?: string | null;
  membership_status?: string | null;
  expire_date?: string | null;
  requires_payment?: boolean;
  latest_invoice?: SubscriptionInvoice | null;
}

export type SubscriptionRoute =
  | string
  | {
      pathname: string;
      params?: Record<string, string>;
    };

function payloadFromProfileResponse(profileResponse: any) {
  return profileResponse?.data ?? profileResponse ?? {};
}

function isExpired(expireDate?: string | null): boolean {
  if (!expireDate) return false;
  const parsed = new Date(expireDate);
  if (Number.isNaN(parsed.getTime())) return false;
  parsed.setHours(23, 59, 59, 999);
  return parsed.getTime() < Date.now();
}

export function getSubscriptionFromProfile(profileResponse: any): SubscriptionState | null {
  const payload = payloadFromProfileResponse(profileResponse);
  return payload?.subscription ?? payload?.profile?.subscription ?? null;
}

export function getProfileFromProfileResponse(profileResponse: any) {
  const payload = payloadFromProfileResponse(profileResponse);
  return payload?.profile ?? payload?.user ?? payload;
}

export function getPendingInvoiceFromProfile(profileResponse: any): SubscriptionInvoice | null {
  const payload = payloadFromProfileResponse(profileResponse);
  const subscription = getSubscriptionFromProfile(payload);
  return subscription?.latest_invoice ?? payload?.invoice ?? payload?.latest_invoice ?? null;
}

export function isSubscriptionActive(profileResponse: any): boolean {
  const subscription = getSubscriptionFromProfile(profileResponse);

  if (typeof subscription?.is_active === "boolean") {
    return subscription.is_active;
  }

  const profile = getProfileFromProfileResponse(profileResponse);
  const membershipStatus = String(profile?.membership_status ?? "").toLowerCase();
  const accountStatus = String(profile?.status ?? "").toLowerCase();
  return membershipStatus === "paid" && accountStatus === "active" && !isExpired(profile?.expire_date);
}

export function isSubscriptionExpired(profileResponse: any): boolean {
  const subscription = getSubscriptionFromProfile(profileResponse);
  if (typeof subscription?.is_expired === "boolean") return subscription.is_expired;
  return isExpired(getProfileFromProfileResponse(profileResponse)?.expire_date);
}

export function invoiceRoute(invoice?: SubscriptionInvoice | null): SubscriptionRoute | null {
  if (!invoice) return null;

  const invoiceId = invoice.id ?? invoice.invoice_id;
  const invoiceCode = invoice.invoiceID ?? invoice.invoice_code;

  if (!invoiceId && !invoiceCode) return null;

  return {
    pathname: "/invoice",
    params: {
      invoice_id: String(invoiceId ?? ""),
      invoiceID: String(invoiceCode ?? ""),
      package_id: String(invoice.package_id ?? ""),
      package_name: String(invoice.package_name ?? ""),
      amount: String(invoice.payable_amount ?? invoice.amount ?? ""),
    },
  };
}

export function subscriptionDestinationFromProfile(
  profileResponse: any,
  activeDestination: SubscriptionRoute = "/(tabs)/dashboard",
): SubscriptionRoute {
  if (isSubscriptionActive(profileResponse)) return activeDestination;
  return invoiceRoute(getPendingInvoiceFromProfile(profileResponse)) ?? "/pricing";
}
