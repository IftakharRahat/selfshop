"use client";

import { useState } from "react";
import Link from "next/link";
import WithVendorAuth from "../WithVendorAuth";
import { useGetVendorOrdersQuery } from "@/redux/api/vendorApi";

const statusOptions = [
	{ value: "", label: "All statuses" },
	{ value: "Pending", label: "Pending" },
	{ value: "Processing", label: "Processing" },
	{ value: "Shipped", label: "Shipped" },
	{ value: "Delivered", label: "Delivered" },
	{ value: "Cancelled", label: "Cancelled" },
	{ value: "Returned", label: "Returned" },
];

export default function VendorOrdersPage() {
	const [search, setSearch] = useState("");
	const [status, setStatus] = useState("");
	const [payment, setPayment] = useState("");
	const [page, setPage] = useState(1);

	const { data, isLoading, error } = useGetVendorOrdersQuery({
		search: search || undefined,
		status: status || undefined,
		payment: payment || undefined,
		page,
		per_page: 15,
	});

	const orders = data?.data?.orders ?? [];
	const pagination = data?.data?.pagination;

	return (
		<WithVendorAuth>
			<div className="space-y-6">
				<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100 flex items-center justify-between gap-4">
					<div>
						<h1 className="text-2xl font-bold text-gray-900 mb-1">All orders</h1>
						<p className="text-sm text-gray-600">Orders containing your products.</p>
					</div>
				</div>

				<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
					<div className="flex flex-wrap items-center gap-3 mb-4">
						<input
							type="text"
							placeholder="Search orders..."
							value={search}
							onChange={(e) => setSearch(e.target.value)}
							className="rounded-md border border-gray-300 px-3 py-2 text-sm w-56 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-pink-500"
						/>
						<select
							value={status}
							onChange={(e) => setStatus(e.target.value)}
							className="rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500"
						>
							{statusOptions.map((o) => (
								<option key={o.value} value={o.value}>{o.label}</option>
							))}
						</select>
						<select
							value={payment}
							onChange={(e) => setPayment(e.target.value)}
							className="rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500"
						>
							<option value="">All payments</option>
							<option value="Cash on Delivery">Cash on Delivery</option>
							<option value="Online">Online</option>
						</select>
					</div>

					{isLoading ? (
						<p className="text-sm text-gray-500 py-8">Loading orders...</p>
					) : error ? (
						<p className="text-sm text-red-600 py-8">Failed to load orders.</p>
					) : orders.length === 0 ? (
						<p className="text-sm text-gray-500 py-8">No orders found.</p>
					) : (
						<div className="overflow-x-auto">
							<table className="min-w-full text-sm">
								<thead className="bg-gray-50 text-gray-600">
									<tr>
										<th className="px-3 py-2 text-left font-medium">Order code</th>
										<th className="px-3 py-2 text-center font-medium">Products</th>
										<th className="px-3 py-2 text-left font-medium">Customer</th>
										<th className="px-3 py-2 text-right font-medium">Amount</th>
										<th className="px-3 py-2 text-center font-medium">Delivery status</th>
										<th className="px-3 py-2 text-center font-medium">Payment</th>
										<th className="px-3 py-2 text-center font-medium">Options</th>
									</tr>
								</thead>
								<tbody className="divide-y divide-gray-100">
									{orders.map((o) => (
										<tr key={o.id} className="hover:bg-gray-50">
											<td className="px-3 py-2">
												<Link href={`/vendor/orders/${o.id}`} className="text-blue-600 hover:underline font-mono">
													{o.invoiceID}
												</Link>
											</td>
											<td className="px-3 py-2 text-center">{o.vendor_item_count}</td>
											<td className="px-3 py-2">{o.customer_name ?? "—"} {o.customer_phone ? ` · ${o.customer_phone}` : ""}</td>
											<td className="px-3 py-2 text-right font-medium">৳{o.vendor_subtotal.toLocaleString()}</td>
											<td className="px-3 py-2 text-center">
												<span className={`inline-flex px-2 py-0.5 rounded text-xs font-medium ${
													o.status === "Delivered" ? "bg-green-100 text-green-800" :
													o.status === "Cancelled" || o.status === "Returned" ? "bg-red-100 text-red-800" :
													o.status === "Processing" || o.status === "Shipped" ? "bg-blue-100 text-blue-800" :
													"bg-amber-100 text-amber-800"
												}`}>
													{o.status}
												</span>
											</td>
											<td className="px-3 py-2 text-center text-gray-600">{o.Payment ?? "—"}</td>
											<td className="px-3 py-2 text-center">
												<Link href={`/vendor/orders/${o.id}`} className="text-xs font-medium text-pink-600 hover:underline">View</Link>
											</td>
										</tr>
									))}
								</tbody>
							</table>
						</div>
					)}

					{pagination && pagination.last_page > 1 && (
						<div className="flex items-center justify-between mt-4 pt-4 border-t border-gray-200">
							<p className="text-xs text-gray-500">
								Page {pagination.current_page} of {pagination.last_page} ({pagination.total} orders)
							</p>
							<div className="flex gap-2">
								<button
									type="button"
									disabled={pagination.current_page <= 1}
									onClick={() => setPage((p) => Math.max(1, p - 1))}
									className="rounded-md border border-gray-300 px-3 py-1.5 text-sm disabled:opacity-50"
								>
									Previous
								</button>
								<button
									type="button"
									disabled={pagination.current_page >= pagination.last_page}
									onClick={() => setPage((p) => p + 1)}
									className="rounded-md border border-gray-300 px-3 py-1.5 text-sm disabled:opacity-50"
								>
									Next
								</button>
							</div>
						</div>
					)}
				</div>
			</div>
		</WithVendorAuth>
	);
}
