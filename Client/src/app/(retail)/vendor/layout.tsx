"use client";

import Link from "next/link";
import type { ReactNode } from "react";

/**
 * Shared layout shell for the vendor area (header + container).
 * Auth is handled per-page so that /vendor/login stays accessible.
 */
export default function VendorLayout({ children }: { children: ReactNode }) {
	return (
		<div className="min-h-screen bg-gray-50">
			<header className="sticky top-0 z-10 bg-white border-b border-gray-200 px-4 py-3 shadow-sm">
				<div className="container mx-auto flex items-center justify-between">
					<Link href="/vendor" className="text-xl font-bold text-[#E5005F]">
						SelfShop Vendor
					</Link>
					<nav className="flex items-center gap-4 text-sm text-gray-600">
						<Link href="/vendor/profile" className="hover:text-gray-900">
							Profile &amp; KYC
						</Link>
						<Link href="/vendor" className="hover:text-gray-900">
							Wholesale
						</Link>
					</nav>
				</div>
			</header>
			<main className="container mx-auto px-4 py-6 pb-24">{children}</main>
		</div>
	);
}
