"use client";

import Link from "next/link";
import type { ReactNode } from "react";
import { usePathname } from "next/navigation";

/**
 * Shared layout shell for the vendor area with left sidebar navigation,
 * similar to common seller dashboards.
 * Auth is handled per-page so that /vendor/login stays accessible.
 */
export default function VendorLayout({ children }: { children: ReactNode }) {
	const pathname = usePathname();

	const isActive = (href: string) =>
		pathname === href || pathname?.startsWith(`${href}/`);

	return (
		<div className="min-h-screen bg-gray-50 flex">
			<aside className="hidden md:flex md:flex-col w-64 bg-white border-r border-gray-200">
				<div className="h-14 flex items-center px-5 border-b border-gray-200">
					<Link
						href="/vendor"
						className="text-lg font-semibold tracking-tight text-[#E5005F]"
					>
						SelfShop Vendor
					</Link>
				</div>

				<nav className="flex-1 overflow-y-auto px-3 py-4 space-y-6 text-sm">
					{/* Main */}
					<div>
						<p className="px-2 mb-1 text-[11px] font-semibold uppercase tracking-wide text-gray-500">
							Main
						</p>
						<ul className="space-y-1">
							<li>
								<Link
									href="/vendor"
									className={`flex items-center justify-between rounded-md px-2.5 py-1.5 ${
										pathname === "/vendor"
											? "bg-gray-900 text-white"
											: "text-gray-700 hover:bg-gray-100"
									}`}
								>
									<span>Dashboard</span>
								</Link>
							</li>
						</ul>
					</div>

					{/* Orders */}
					<div>
						<p className="px-2 mb-1 text-[11px] font-semibold uppercase tracking-wide text-gray-500">
							Orders
						</p>
						<ul className="space-y-1">
							<li>
								<Link
									href="/vendor/orders"
									className={`flex items-center justify-between rounded-md px-2.5 py-1.5 ${
										isActive("/vendor/orders")
											? "bg-gray-900 text-white"
											: "text-gray-700 hover:bg-gray-100"
									}`}
								>
									<span>All orders</span>
								</Link>
							</li>
						</ul>
					</div>

					{/* Products */}
					<div>
						<p className="px-2 mb-1 text-[11px] font-semibold uppercase tracking-wide text-gray-500">
							Products
						</p>
						<ul className="space-y-1">
							<li>
								<Link
									href="/vendor/products"
									className={`flex items-center justify-between rounded-md px-2.5 py-1.5 ${
										isActive("/vendor/products")
											? "bg-gray-900 text-white"
											: "text-gray-700 hover:bg-gray-100"
									}`}
								>
									<span>Products</span>
								</Link>
							</li>
							<li>
								<Link
									href="/vendor/products/new"
									className={`flex items-center justify-between rounded-md px-2.5 py-1.5 ${
										isActive("/vendor/products/new")
											? "bg-gray-900 text-white"
											: "text-gray-700 hover:bg-gray-100"
									}`}
								>
									<span>Add new product</span>
								</Link>
							</li>
							<li>
								<Link
									href="/vendor/category-discount"
									className={`flex items-center justify-between rounded-md px-2.5 py-1.5 ${
										isActive("/vendor/category-discount")
											? "bg-gray-900 text-white"
											: "text-gray-700 hover:bg-gray-100"
									}`}
								>
									<span>Category-Wise Discount</span>
								</Link>
							</li>
							<li>
								<Link
									href="/vendor/reviews"
									className={`flex items-center justify-between rounded-md px-2.5 py-1.5 ${
										isActive("/vendor/reviews")
											? "bg-gray-900 text-white"
											: "text-gray-700 hover:bg-gray-100"
									}`}
								>
									<span>Product Reviews</span>
								</Link>
							</li>
						</ul>
					</div>

					{/* Account */}
					<div>
						<p className="px-2 mb-1 text-[11px] font-semibold uppercase tracking-wide text-gray-500">
							Account
						</p>
						<ul className="space-y-1">
							<li>
								<Link
									href="/vendor/profile"
									className={`flex items-center justify-between rounded-md px-2.5 py-1.5 ${
										isActive("/vendor/profile")
											? "bg-gray-900 text-white"
											: "text-gray-700 hover:bg-gray-100"
									}`}
								>
									<span>Profile &amp; KYC</span>
								</Link>
							</li>
						</ul>
					</div>
				</nav>
			</aside>

			<div className="flex-1 flex flex-col">
				<header className="md:hidden sticky top-0 z-10 bg-white border-b border-gray-200 px-4 py-3 shadow-sm">
					<div className="flex items-center justify-between">
						<Link
							href="/vendor"
							className="text-lg font-semibold tracking-tight text-[#E5005F]"
						>
							SelfShop Vendor
						</Link>
						<Link
							href="/vendor/profile"
							className="text-sm text-gray-600 hover:text-gray-900"
						>
							Profile &amp; KYC
						</Link>
					</div>
				</header>

				<main className="px-4 py-6 md:px-8 md:py-8">{children}</main>
			</div>
		</div>
	);
}
