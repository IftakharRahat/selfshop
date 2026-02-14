"use client";

import { useRef } from "react";
import Link from "next/link";
import Image from "next/image";
import WithVendorAuth from "./WithVendorAuth";
import { useGetVendorDashboardQuery } from "@/redux/api/vendorApi";
import { getImageUrl } from "@/lib/utils";
import {
	Package,
	Star,
	ClipboardList,
	TrendingUp,
	ShoppingBag,
	XCircle,
	Truck,
	CheckCircle2,
	Wallet,
	Settings,
	CreditCard,
	ChevronLeft,
	ChevronRight,
	Eye,
	Award,
} from "lucide-react";
import { BarChart, Bar, XAxis, YAxis, ResponsiveContainer, Tooltip } from "recharts";

function formatMoney(value: number) {
	return new Intl.NumberFormat("en-US", {
		style: "currency",
		currency: "USD",
		minimumFractionDigits: 2,
	}).format(value);
}

export default function VendorHomePage() {
	const carouselRef = useRef<HTMLDivElement>(null);
	const { data, isLoading, isError } = useGetVendorDashboardQuery();
	const dashboard = data?.data;

	const scrollCarousel = (dir: "left" | "right") => {
		if (!carouselRef.current) return;
		const step = 280;
		carouselRef.current.scrollBy({ left: dir === "left" ? -step : step, behavior: "smooth" });
	};

	if (isError) {
		return (
			<WithVendorAuth>
				<div className="rounded-xl bg-white p-6 border border-red-100 text-red-700">
					Failed to load dashboard. Please refresh.
				</div>
			</WithVendorAuth>
		);
	}

	return (
		<WithVendorAuth>
			<div className="space-y-5">
				<div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
						{/* Products */}
						<div className="rounded-xl bg-[#2d2a5d] p-5 text-white">
							<div className="flex items-start justify-between">
								<div>
									<p className="text-sm">Products</p>
									<p className="text-2xl font-bold mt-1">
										{isLoading ? "—" : dashboard?.product_count ?? 0}
									</p>
									<Link
										href="/vendor/products/new"
										className="text-sm text-gray-200 hover:text-white mt-3 inline-block"
									>
										+ Add New Product
									</Link>
								</div>
								<Package className="w-12 h-12 text-white/85" />
							</div>
						</div>

						{/* Rating */}
						<div className="rounded-xl bg-[#2d2a5d] p-5 text-white">
							<div className="flex items-start justify-between">
								<div>
									<p className="text-sm">Rating</p>
									<p className="text-2xl font-bold mt-1">5</p>
									<p className="text-xs text-gray-200 mt-3">Followers 0</p>
								</div>
								<Star className="w-12 h-12 text-white/85" />
							</div>
						</div>

						{/* Total Order */}
						<div className="rounded-xl bg-[#2d2a5d] p-5 text-white">
							<div className="flex items-start justify-between">
								<div>
									<p className="text-sm">Total Order</p>
									<p className="text-2xl font-bold mt-1">
										{isLoading ? "—" : dashboard?.total_orders ?? 0}
									</p>
									<Link
										href="/vendor/orders"
										className="text-sm text-gray-200 hover:text-white mt-3 inline-flex items-center gap-1"
									>
										<Eye className="w-3.5 h-3.5" /> View All Order
									</Link>
								</div>
								<ClipboardList className="w-12 h-12 text-white/85" />
							</div>
						</div>

						{/* Total Sales */}
						<div className="rounded-xl bg-[#2d2a5d] p-5 text-white">
							<div className="flex items-start justify-between">
								<div>
									<p className="text-sm">Total Sales</p>
									<p className="text-2xl font-bold mt-1">
										{isLoading ? "—" : formatMoney(dashboard?.total_sales ?? 0)}
									</p>
									<p className="text-xs text-gray-200 mt-3">
										Last Month: {isLoading ? "—" : formatMoney(dashboard?.last_month_sales ?? 0)}
									</p>
								</div>
								<TrendingUp className="w-12 h-12 text-white/85" />
							</div>
						</div>
					</div>

				<div className="grid grid-cols-1 lg:grid-cols-4 gap-4">
					<div className="rounded-xl bg-white p-5 border border-gray-100">
						<h2 className="text-[28px] leading-none font-semibold text-gray-900 mb-3">Sales Stat</h2>
								{isLoading ? (
									<div className="h-48 flex items-center justify-center text-gray-400">Loading...</div>
								) : dashboard?.sales_chart?.length ? (
									<div className="h-48">
										<ResponsiveContainer width="100%" height="100%">
											<BarChart
												data={dashboard.sales_chart.map((d) => ({
													name: d.month,
													Sales: d.total,
												}))}
											>
												<XAxis dataKey="name" tick={{ fontSize: 11 }} />
												<YAxis tick={{ fontSize: 11 }} tickFormatter={(v) => `$${v}`} />
												<Tooltip formatter={(v: number) => [formatMoney(v), "Sales"]} />
												<Bar dataKey="Sales" fill="#4f46e5" radius={[4, 4, 0, 0]} />
											</BarChart>
										</ResponsiveContainer>
									</div>
								) : (
									<div className="h-48 flex items-center justify-center text-gray-400 text-sm">
										No sales data yet
									</div>
								)}
					</div>
					<div className="rounded-xl bg-white p-5 border border-gray-100">
						<h2 className="text-[28px] leading-none font-semibold text-gray-900 mb-4">Category wise product count</h2>
									{isLoading ? (
										<p className="text-sm text-gray-500">Loading...</p>
									) : dashboard?.category_wise_product_count?.length ? (
										<ul className="space-y-2 text-sm">
											{dashboard.category_wise_product_count.map((c) => (
												<li key={c.category_name} className="flex justify-between">
													<span className="text-gray-700">{c.category_name}</span>
													<span className="font-medium">{c.product_count}</span>
												</li>
											))}
										</ul>
									) : (
										<p className="text-sm text-gray-500">No categories yet</p>
									)}
					</div>
					<div className="rounded-xl bg-white p-5 border border-gray-100">
						<h2 className="text-[28px] leading-none font-semibold text-gray-900 mb-1">Orders</h2>
						<p className="text-sm text-gray-500 mb-4">This Month</p>
									<ul className="space-y-2 text-sm">
										{[
											{ key: "new", label: "New Order", icon: ShoppingBag, statuses: ["Pending", "New"] },
											{ key: "cancelled", label: "Cancelled", icon: XCircle, statuses: ["Cancelled"] },
											{ key: "ondelivery", label: "On delivery", icon: Truck, statuses: ["Ontheway", "OnDelivery"] },
											{ key: "delivered", label: "Delivered", icon: CheckCircle2, statuses: ["Delivered", "Complete"] },
										].map(({ key, label, icon: Icon, statuses }) => {
											const total = statuses.reduce(
												(sum, s) => sum + (dashboard?.orders_this_month_by_status?.[s] ?? 0),
												0
											);
											return (
												<li key={key} className="flex items-center justify-between py-1">
													<span className="flex items-center gap-3 text-gray-700">
														<Icon className="w-5 h-5 text-gray-500" />
														{label}
													</span>
													<span className="font-medium">{isLoading ? "—" : total}</span>
												</li>
											);
										})}
									</ul>
					</div>
					<div className="rounded-xl bg-white p-5 border border-gray-100">
						<h2 className="text-[28px] leading-none font-semibold text-gray-900 mb-4">Purchased Package</h2>
						<div className="space-y-2 text-sm text-gray-600">
							<p className="flex items-center gap-2 text-gray-800 font-semibold">
								<Award className="w-5 h-5 text-yellow-500" />
								Current Package: Platinum
							</p>
							<p>Product Upload Limit: 500 Times</p>
							<p>Preorder Product Upload Limit: 0 Times</p>
							<p>Package Expires at: 2026-12-08</p>
							<button
								type="button"
								className="mt-3 w-full rounded-lg bg-gray-100 py-2.5 text-[#2d2a5d] font-semibold hover:bg-gray-200"
							>
								Upgrade Package
							</button>
						</div>
					</div>
				</div>

				<div className="grid grid-cols-1 md:grid-cols-4 gap-4">
					<div className="rounded-xl bg-white p-5 border border-gray-100">
						<h2 className="text-xl font-semibold text-gray-900 mb-2">Commission Type &amp; Rate</h2>
						<p className="text-sm text-gray-600">Currently No Commission System is Set by Admin</p>
					</div>
					<div className="rounded-xl bg-[#f2f2fa] p-5 border border-gray-100 text-center">
						<h2 className="text-xl font-semibold text-gray-900 mb-3">Money Withdraw</h2>
						<Wallet className="w-10 h-10 text-[#2d2a5d] mx-auto mb-3" />
						<Link
							href="/vendor/payouts"
							className="inline-flex w-full items-center justify-center rounded-lg bg-[#2d2a5d] py-2.5 text-white font-medium hover:bg-[#252947]"
						>
							Go to payout
						</Link>
					</div>
					<div className="rounded-xl bg-[#f2f2fa] p-5 border border-gray-100 text-center">
						<h2 className="text-xl font-semibold text-gray-900 mb-3">Shop Settings</h2>
						<Settings className="w-10 h-10 text-[#2d2a5d] mx-auto mb-3" />
						<Link
							href="/vendor/profile"
							className="inline-flex w-full items-center justify-center rounded-lg bg-[#2d2a5d] py-2.5 text-white font-medium hover:bg-[#252947]"
						>
							Go to setting
						</Link>
					</div>
					<div className="rounded-xl bg-[#f2f2fa] p-5 border border-gray-100 text-center">
						<h2 className="text-xl font-semibold text-gray-900 mb-3">Payment Settings</h2>
						<CreditCard className="w-10 h-10 text-[#2d2a5d] mx-auto mb-3" />
						<Link
							href="/vendor/payout-accounts"
							className="inline-flex w-full items-center justify-center rounded-lg bg-[#2d2a5d] py-2.5 text-white font-medium hover:bg-[#252947]"
						>
							Configure Now
						</Link>
					</div>
				</div>

				<div className="rounded-xl bg-white p-5 border border-gray-100">
					<h2 className="text-sm font-semibold mb-1">Sold Amount</h2>
					<p className="text-xs text-gray-500 mb-2">Your sold amount (current month)</p>
					<p className="text-4xl font-bold text-[#2d2a5d]">
						{isLoading ? "—" : formatMoney(dashboard?.this_month_sales ?? 0)}
					</p>
					<p className="text-xs text-gray-500 mt-1">
						Last Month: {isLoading ? "—" : formatMoney(dashboard?.last_month_sales ?? 0)}
					</p>
				</div>

					{/* Top 12 Products carousel */}
					<div className="rounded-xl bg-white text-gray-900 p-5 border border-gray-100">
						<h2 className="text-2xl font-semibold mb-4">Top 12 Products</h2>
						{isLoading ? (
							<div className="flex gap-4 overflow-hidden">
								{[1, 2, 3, 4].map((i) => (
									<div
										key={i}
										className="w-44 flex-shrink-0 h-56 rounded-lg bg-gray-100 animate-pulse"
									/>
								))}
							</div>
						) : dashboard?.top_products?.length ? (
							<div className="relative">
								<button
									type="button"
									onClick={() => scrollCarousel("left")}
									className="absolute left-0 top-1/2 -translate-y-1/2 z-10 w-8 h-8 rounded-full bg-gray-200 hover:bg-gray-300 flex items-center justify-center text-gray-700 shadow"
								>
									<ChevronLeft className="w-5 h-5" />
								</button>
								<button
									type="button"
									onClick={() => scrollCarousel("right")}
									className="absolute right-0 top-1/2 -translate-y-1/2 z-10 w-8 h-8 rounded-full bg-gray-200 hover:bg-gray-300 flex items-center justify-center text-gray-700 shadow"
								>
									<ChevronRight className="w-5 h-5" />
								</button>
								<div
									ref={carouselRef}
									className="flex gap-4 overflow-x-auto scroll-smooth pb-2 scrollbar-hide"
									style={{ scrollbarWidth: "none" }}
								>
									{dashboard.top_products.map((p) => (
										<Link
											key={p.id}
											href={`/vendor/products/${p.id}/edit`}
											className="w-44 flex-shrink-0 group rounded-lg border border-gray-100 p-2"
										>
											<div className="aspect-square rounded-lg bg-gray-100 overflow-hidden">
												{p.image ? (
													<Image
														src={getImageUrl(p.image)}
														alt={p.name}
														width={176}
														height={176}
														className="w-full h-full object-cover group-hover:scale-105 transition"
													/>
												) : (
													<div className="w-full h-full flex items-center justify-center text-gray-400">
														<Package className="w-10 h-10" />
													</div>
												)}
											</div>
											<p className="mt-2 text-sm font-medium text-gray-900 truncate">{p.name}</p>
											<p className="text-sm font-semibold text-[#2d2a5d]">
												{formatMoney(p.price)}
											</p>
											<div className="flex items-center gap-1 mt-1 text-amber-500">
												<Star className="w-3.5 h-3.5 fill-current" />
												<Star className="w-3.5 h-3.5 fill-current" />
												<Star className="w-3.5 h-3.5 fill-current" />
												<Star className="w-3.5 h-3.5 fill-current" />
												<Star className="w-3.5 h-3.5 fill-current" />
											</div>
											<p className="text-[11px] text-gray-500 mt-1">
												Sales: {formatMoney(p.total_sales)} · Qty: {p.total_quantity}
											</p>
										</Link>
									))}
								</div>
							</div>
						) : (
							<p className="text-sm text-gray-500 py-4">No product sales yet</p>
						)}
					</div>
			</div>
		</WithVendorAuth>
	);
}
