import type { NextRequest } from "next/server";
import { NextResponse } from "next/server";

const VENDOR_HOSTS = [
	"vendor.selfshop.com",
	"vendor.selfshop.com.bd",
	"supplier.selfshop.com.bd",
	"vendor.localhost",
];
const VENDOR_PREFIX = "/vendor";

function getRequestHost(request: NextRequest) {
	const forwardedHost = request.headers.get("x-forwarded-host");
	const hostFromHeader = request.headers.get("host");
	const rawHost = forwardedHost ?? hostFromHeader ?? request.nextUrl.host ?? "";

	// x-forwarded-host can contain a comma-separated chain.
	return rawHost.split(",")[0]?.trim().toLowerCase();
}

function isVendorHost(host: string) {
	if (!host) return false;
	const hostWithoutPort = host.split(":")[0];
	return VENDOR_HOSTS.some((allowed) => hostWithoutPort === allowed);
}

export function proxy(request: NextRequest) {
	const host = getRequestHost(request);
	const isVendorSubdomain = isVendorHost(host);

	if (isVendorSubdomain) {
		const pathname = request.nextUrl.pathname;
		const newPath = pathname.startsWith(VENDOR_PREFIX)
			? pathname
			: `${VENDOR_PREFIX}${pathname === "/" ? "" : pathname}`;
		const url = request.nextUrl.clone();
		url.pathname = newPath;
		return NextResponse.rewrite(url);
	}

	return NextResponse.next();
}

export const config = {
	matcher: [
		/*
		 * Match all paths except static files and api
		 */
		"/((?!_next/static|_next/image|favicon.ico|api).*)",
	],
};
