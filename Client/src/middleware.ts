import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";

const VENDOR_HOSTS = ["vendor.selfshop.com", "vendor.localhost"];
const VENDOR_PREFIX = "/vendor";

export function middleware(request: NextRequest) {
  const host = request.headers.get("host") ?? "";
  const isVendorSubdomain = VENDOR_HOSTS.some((h) => host.startsWith(h));

  if (isVendorSubdomain) {
    const pathname = request.nextUrl.pathname;
    const newPath = pathname.startsWith(VENDOR_PREFIX) ? pathname : `${VENDOR_PREFIX}${pathname === "/" ? "" : pathname}`;
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
