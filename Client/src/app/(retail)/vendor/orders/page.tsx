"use client";

import { useState, useEffect } from "react";
import Link from "next/link";
import WithVendorAuth from "../WithVendorAuth";
import { useGetVendorOrdersQuery } from "@/redux/api/vendorApi";
import { formatBDT } from "@/lib/format-currency";

const statusOptions = [
	{ value: "", label: "All statuses" },
	{ value: "Pending", label: "Pending" },
	{ value: "Confirmed", label: "Accepted" },
	{ value: "Canceled", label: "Rejected" },
	{ value: "Ontheway", label: "Shipped to warehouse / On the way" },
	{ value: "Processing", label: "Processing" },
	{ value: "Delivered", label: "Delivered" },
	{ value: "Returned", label: "Returned" },
];

const statusBadgeClass = (status: string) => {
	const s = status.toLowerCase();
	if (s.includes("deliver")) return "bg-green-100 text-green-800";
	if (s.includes("reject") || s.includes("cancel") || s.includes("return")) return "bg-red-100 text-red-800";
	if (s.includes("accept") || s.includes("confirm")) return "bg-emerald-100 text-emerald-800";
	if (s.includes("ship") || s.includes("way") || s.includes("transit")) return "bg-blue-100 text-blue-800";
	return "bg-amber-100 text-amber-800";
};

export default function VendorOrdersPage() {
	const [search, setSearch] = useState("");
	const [debouncedSearch, setDebouncedSearch] = useState("");
	const [status, setStatus] = useState("");
	const [payment, setPayment] = useState("");
	const [page, setPage] = useState(1);

	useEffect(() => {
		const handler = setTimeout(() => {
			setDebouncedSearch(search);
			setPage(1); // Reset to page 1 on new search
		}, 400);

		return () => clearTimeout(handler);
	}, [search]);

	const { data, isLoading, error } = useGetVendorOrdersQuery({
		search: debouncedSearch || undefined,
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
				<div className="rounded-xl bg-white p-4 sm:p-6 shadow-sm border border-gray-100 flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
					<div>
						<h1 className="text-2xl font-bold text-gray-900 mb-1">All orders</h1>
						<p className="text-sm text-gray-600">Orders containing your products.</p>
					</div>
				</div>

				<div className="rounded-xl bg-white p-4 sm:p-6 shadow-sm border border-gray-100">
					<div className="mb-4 flex flex-wrap items-stretch gap-3 sm:items-center">
						<input
							type="text"
							placeholder="Search orders..."
							value={search}
							onChange={(e) => setSearch(e.target.value)}
							className="rounded-md border border-gray-300 px-3 py-2 text-sm w-full sm:w-56 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
						/>
						<select
							value={status}
							onChange={(e) => setStatus(e.target.value)}
							className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:w-auto"
						>
							{statusOptions.map((o) => (
								<option key={o.value} value={o.value}>{o.label}</option>
							))}
						</select>
						<select
							value={payment}
							onChange={(e) => setPayment(e.target.value)}
							className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:w-auto"
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
											<td className="px-3 py-2 text-right font-medium">৳{formatBDT(o.vendor_subtotal)}</td>
											<td className="px-3 py-2 text-center">
												<span className={`inline-flex px-2 py-0.5 rounded text-xs font-medium ${statusBadgeClass(o.display_status ?? o.status)}`}>
													{o.display_status ?? o.status}
												</span>
											</td>
											<td className="px-3 py-2 text-center text-gray-600">{o.Payment ?? "—"}</td>
											<td className="px-3 py-2 text-center">
												<Link href={`/vendor/orders/${o.id}`} className="text-xs font-medium text-[#2d2a5d] hover:underline">View</Link>
											</td>
										</tr>
									))}
								</tbody>
							</table>
						</div>
					)}

					{pagination && pagination.last_page > 1 && (
						<div className="mt-4 flex flex-col gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
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

