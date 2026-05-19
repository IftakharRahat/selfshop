type SearchParamValue = string | string[] | undefined;
type SearchParamMap = Record<string, SearchParamValue>;

function firstParam(value: SearchParamValue): string {
	const raw = Array.isArray(value) ? value[0] : value;
	return raw?.trim() ?? "";
}

export function getReferralCodeFromSearchParams(searchParams: SearchParamMap = {}) {
	return (
		firstParam(searchParams.campaign) ||
		firstParam(searchParams.refer) ||
		firstParam(searchParams.ref) ||
		firstParam(searchParams.refer_by) ||
		firstParam(searchParams.code)
	);
}
