type PaymentMethodLike = {
  paymentTypeName?: string | null;
  paymenttype_name?: string | null;
  name?: string | null;
};

function titleCase(value: string): string {
  return value
    .replace(/[_-]+/g, " ")
    .replace(/\s+/g, " ")
    .trim()
    .toLowerCase()
    .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

export function rawPaymentMethodName(method?: PaymentMethodLike | null): string {
  return method?.paymentTypeName ?? method?.paymenttype_name ?? method?.name ?? "";
}

export function formatPaymentMethodName(method?: PaymentMethodLike | string | null): string {
  const raw = typeof method === "string" ? method : rawPaymentMethodName(method);
  const name = raw.trim();
  const key = name.toLowerCase();

  if (!key) return "Payment Method";
  if (key.includes("bkash") || key.includes("bikash") || key.includes("b-kash") || key.includes("b cash") || key.includes("বিকাশ")) return "bKash";
  if (key.includes("nagad") || key.includes("নগদ")) return "Nagad";
  if (key.includes("rocket") || key.includes("dbbl") || key.includes("রকেট")) return "Rocket";
  if (key.includes("upay") || key.includes("u-pay")) return "Upay";
  if (key.includes("bank")) return "Bank Transfer";
  if (key.includes("cash on delivery") || key === "cod") return "Cash on Delivery";
  if (key.includes("credit") || key.includes("debit") || key.includes("card")) return "Card";

  return titleCase(name);
}
