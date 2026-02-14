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
	const navItemClass = (active: boolean) =>
		`flex items-center justify-between rounded-md px-2.5 py-1.5 ${
			active
				? "bg-[#2d2a5d] text-white"
				: "text-gray-700 hover:bg-indigo-50 hover:text-[#2d2a5d]"
		}`;

	return (
		<div className="min-h-screen bg-gray-50 flex">
			<aside className="hidden md:flex md:flex-col w-64 bg-white border-r border-gray-200">
				<div className="h-14 flex items-center px-5 border-b border-gray-200">
					<Link
						href="/vendor"
						className="text-lg font-semibold tracking-tight text-[#2d2a5d]"
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
									className={navItemClass(pathname === "/vendor")}
								>
									<span>Dashboard</span>
								</Link>
							</li>
							<li>
								<Link
									href="/vendor/reports"
									className={navItemClass(isActive("/vendor/reports"))}
								>
									<span>Reports</span>
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
									className={navItemClass(isActive("/vendor/orders"))}
								>
									<span>All orders</span>
								</Link>
							</li>
							<li>
								<Link
									href="/vendor/shipping"
									className={navItemClass(isActive("/vendor/shipping"))}
								>
									<span>Shipping methods</span>
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
									className={navItemClass(isActive("/vendor/products"))}
								>
									<span>Products</span>
								</Link>
							</li>
							<li>
								<Link
									href="/vendor/products/new"
									className={navItemClass(isActive("/vendor/products/new"))}
								>
									<span>Add new product</span>
								</Link>
							</li>
							<li>
								<Link
									href="/vendor/category-discount"
									className={navItemClass(isActive("/vendor/category-discount"))}
								>
									<span>Category-Wise Discount</span>
								</Link>
							</li>
							<li>
								<Link
									href="/vendor/reviews"
									className={navItemClass(isActive("/vendor/reviews"))}
								>
									<span>Product Reviews</span>
								</Link>
							</li>
						</ul>
					</div>

					{/* Earnings & Payouts */}
					<div>
						<p className="px-2 mb-1 text-[11px] font-semibold uppercase tracking-wide text-gray-500">
							Earnings &amp; Payouts
						</p>
						<ul className="space-y-1">
							<li>
								<Link
									href="/vendor/earnings"
									className={navItemClass(isActive("/vendor/earnings"))}
								>
									<span>Earnings</span>
								</Link>
							</li>
							<li>
								<Link
									href="/vendor/payouts"
									className={navItemClass(isActive("/vendor/payouts"))}
								>
									<span>Payouts</span>
								</Link>
							</li>
							<li>
								<Link
									href="/vendor/payout-accounts"
									className={navItemClass(isActive("/vendor/payout-accounts"))}
								>
									<span>Payout accounts</span>
								</Link>
							</li>
						</ul>
					</div>

					{/* Inventory */}
					<div>
						<p className="px-2 mb-1 text-[11px] font-semibold uppercase tracking-wide text-gray-500">
							Inventory
						</p>
						<ul className="space-y-1">
							<li>
								<Link
									href="/vendor/inventory"
									className={navItemClass(isActive("/vendor/inventory"))}
								>
									<span>Inventory</span>
								</Link>
							</li>
							<li>
								<Link
									href="/vendor/warehouses"
									className={navItemClass(isActive("/vendor/warehouses"))}
								>
									<span>Warehouses</span>
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
									className={navItemClass(isActive("/vendor/profile"))}
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
							className="text-lg font-semibold tracking-tight text-[#2d2a5d]"
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
