/**
 * Format a number using Bangladesh/Indian numbering system.
 *
 * Examples:
 *   formatBDT(124356)    → "1,24,356.00"
 *   formatBDT(1000000)   → "10,00,000.00"
 *   formatBDT(12345678)  → "1,23,45,678.00"
 *   formatBDT(45, 0)     → "45"
 *   formatBDT(0)         → "0.00"
 *
 * Uses `en-IN` locale which applies the South-Asian grouping
 * (lakh / crore) that matches the Bangladesh standard.
 */
export function formatBDT(value: number | string, decimals = 2): string {
	const num = typeof value === "string" ? parseFloat(value) || 0 : value;
	return new Intl.NumberFormat("en-IN", {
		minimumFractionDigits: decimals,
		maximumFractionDigits: decimals,
	}).format(num);
}

/**
 * Format a number using Bangladesh/Indian numbering system,
 * but without forcing trailing zeros for decimal values.
 *
 * Examples:
 *   formatNumber(124356)    → "1,24,356"
 *   formatNumber(92.5)      → "92.5"
 *   formatNumber(5357.33)   → "5,357.33"
 */
export function formatNumber(value: number | string, maxDecimals = 2): string {
	const num = typeof value === "string" ? parseFloat(value) || 0 : value;
	return new Intl.NumberFormat("en-IN", {
		maximumFractionDigits: maxDecimals,
	}).format(num);
}
