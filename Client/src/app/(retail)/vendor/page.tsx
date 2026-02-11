"use client";

import Link from "next/link";
import WithVendorAuth from "./WithVendorAuth";

/**
 * Vendor (Wholesale) home – for verified wholesalers at vendor.selfshop.com
 */
export default function VendorHomePage() {
	return (
		<WithVendorAuth>
			<div className="space-y-6">
				<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
					<h1 className="text-2xl font-bold text-gray-900 mb-2">
						Wholesale Dashboard
					</h1>
					<p className="text-gray-600">
						Use bulk ordering to buy mixed sizes/variants with automatic tier
						discounts. Tap a product and use <strong>Bulk Order</strong> for
						the matrix.
					</p>
				</div>
				<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
					<h2 className="font-semibold text-gray-900 mb-3">Quick actions</h2>
					<div className="flex flex-wrap gap-3">
						<Link
							href="/product-filter"
							className="inline-flex items-center px-4 py-2 rounded-lg bg-[#E5005F] text-white text-sm font-medium hover:bg-pink-700"
						>
							Browse products
						</Link>
						<Link
							href="/dashboard/orders"
							className="inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50"
						>
							My orders
						</Link>
					</div>
				</div>
			</div>
		</WithVendorAuth>
	);
}
