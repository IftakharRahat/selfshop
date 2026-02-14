"use client";

import { useState } from "react";
import Link from "next/link";
import WithVendorAuth from "../WithVendorAuth";
import { useGetVendorEarningsSummaryQuery, useGetVendorEarningsQuery } from "@/redux/api/vendorApi";

export default function VendorEarningsPage() {
	const [page, setPage] = useState(1);
	const [statusFilter, setStatusFilter] = useState<string>("");

	const { data: summaryData, isLoading: summaryLoading } = useGetVendorEarningsSummaryQuery(undefined);
	// Defer earnings list until summary is done so the page feels faster and we don't double-hit sync
	const { data: earningsData, isLoading: earningsLoading } = useGetVendorEarningsQuery(
		{
			page,
			per_page: 15,
			status: statusFilter || undefined,
		},
		{ skip: summaryLoading }
	);

	const summary = summaryData?.data;
	const earnings = earningsData?.data?.earnings ?? [];
	const pagination = earningsData?.data?.pagination;

	return (
		<WithVendorAuth>
			<div className="space-y-6">
				<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100 flex items-center justify-between gap-4">
					<div>
						<h1 className="text-2xl font-bold text-gray-900 mb-1">Earnings & balance</h1>
						<p className="text-sm text-gray-600">
							Total sales, commission, and available balance.
						</p>
					</div>
					<Link
						href="/vendor/payouts"
						className="inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50"
					>
						Payouts
					</Link>
				</div>

				{summaryLoading ? (
					<div className="grid grid-cols-2 md:grid-cols-4 gap-4">
						{[1, 2, 3, 4, 5, 6].map((i) => (
							<div key={i} className="rounded-xl border p-4 bg-gray-100 animate-pulse h-24" />
						))}
					</div>
				) : summary && (
					<div className="grid grid-cols-2 md:grid-cols-4 gap-4">
						<div className="rounded-xl border p-4 bg-blue-50 border-blue-200">
							<p className="text-sm font-medium text-blue-600">Total sales</p>
							<p className="text-2xl font-bold text-blue-800 mt-1">৳{Number(summary.total_sales).toLocaleString()}</p>
						</div>
						<div className="rounded-xl border p-4 bg-amber-50 border-amber-200">
							<p className="text-sm font-medium text-amber-600">Commission</p>
							<p className="text-2xl font-bold text-amber-800 mt-1">৳{Number(summary.total_commission).toLocaleString()}</p>
						</div>
						<div className="rounded-xl border p-4 bg-emerald-50 border-emerald-200">
							<p className="text-sm font-medium text-emerald-600">Net earnings</p>
							<p className="text-2xl font-bold text-emerald-800 mt-1">৳{Number(summary.net_earnings).toLocaleString()}</p>
						</div>
						<div className="rounded-xl border p-4 bg-gray-50 border-gray-200">
							<p className="text-sm font-medium text-gray-600">Pending (orders)</p>
							<p className="text-2xl font-bold text-gray-800 mt-1">৳{Number(summary.pending_balance).toLocaleString()}</p>
						</div>
						<div className="rounded-xl border p-4 bg-green-50 border-green-200">
							<p className="text-sm font-medium text-green-600">Available balance</p>
							<p className="text-2xl font-bold text-green-800 mt-1">৳{Number(summary.available_balance).toLocaleString()}</p>
						</div>
						<div className="rounded-xl border p-4 bg-indigo-50 border-indigo-200">
							<p className="text-sm font-medium text-indigo-600">Paid out</p>
							<p className="text-2xl font-bold text-indigo-800 mt-1">৳{Number(summary.paid_total).toLocaleString()}</p>
						</div>
					</div>
				)}

				<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
					<h2 className="text-sm font-semibold text-gray-800 mb-3">Earnings breakdown</h2>
					<div className="flex gap-2 mb-4">
						<button
							type="button"
							onClick={() => { setStatusFilter(""); setPage(1); }}
							className={`px-3 py-1.5 text-xs font-medium rounded-md ${statusFilter === "" ? "bg-gray-900 text-white" : "bg-gray-100 text-gray-700"}`}
						>
							All
						</button>
						<button
							type="button"
							onClick={() => { setStatusFilter("pending"); setPage(1); }}
							className={`px-3 py-1.5 text-xs font-medium rounded-md ${statusFilter === "pending" ? "bg-gray-900 text-white" : "bg-gray-100 text-gray-700"}`}
						>
							Pending
						</button>
						<button
							type="button"
							onClick={() => { setStatusFilter("available"); setPage(1); }}
							className={`px-3 py-1.5 text-xs font-medium rounded-md ${statusFilter === "available" ? "bg-gray-900 text-white" : "bg-gray-100 text-gray-700"}`}
						>
							Available
						</button>
						<button
							type="button"
							onClick={() => { setStatusFilter("paid"); setPage(1); }}
							className={`px-3 py-1.5 text-xs font-medium rounded-md ${statusFilter === "paid" ? "bg-gray-900 text-white" : "bg-gray-100 text-gray-700"}`}
						>
							Paid
						</button>
					</div>
					{earningsLoading ? (
						<p className="text-sm text-gray-500 py-8 text-center">Loading...</p>
					) : earnings.length === 0 ? (
						<p className="text-sm text-gray-500 py-8 text-center">No earnings yet. Sales will appear here.</p>
					) : (
						<>
							<div className="overflow-x-auto">
								<table className="min-w-full text-sm">
									<thead className="bg-gray-50 text-gray-600">
										<tr>
											<th className="px-3 py-2 text-left font-medium">Order</th>
											<th className="px-3 py-2 text-left font-medium">Product</th>
											<th className="px-3 py-2 text-right font-medium">Line total</th>
											<th className="px-3 py-2 text-right font-medium">Commission</th>
											<th className="px-3 py-2 text-right font-medium">Net</th>
											<th className="px-3 py-2 text-center font-medium">Status</th>
										</tr>
									</thead>
									<tbody className="divide-y divide-gray-100">
										{earnings.map((e: { id: number; order: { invoiceID: string } | null; product_name: string; line_total: number; commission_amount: number; net_amount: number; status: string }) => (
											<tr key={e.id} className="hover:bg-gray-50">
												<td className="px-3 py-2 text-gray-600">{e.order?.invoiceID ?? "—"}</td>
												<td className="px-3 py-2">{e.product_name}</td>
												<td className="px-3 py-2 text-right">৳{Number(e.line_total).toLocaleString()}</td>
												<td className="px-3 py-2 text-right text-amber-600">-৳{Number(e.commission_amount).toLocaleString()}</td>
												<td className="px-3 py-2 text-right font-medium">৳{Number(e.net_amount).toLocaleString()}</td>
												<td className="px-3 py-2 text-center">
													<span className={`inline-flex px-2 py-0.5 rounded text-xs font-medium ${
														e.status === "paid" ? "bg-green-100 text-green-700" :
														e.status === "available" ? "bg-blue-100 text-blue-700" :
														"bg-gray-100 text-gray-700"
													}`}>
														{e.status}
													</span>
												</td>
											</tr>
										))}
									</tbody>
								</table>
							</div>
							{pagination && pagination.last_page > 1 && (
								<div className="flex justify-between items-center mt-4 pt-3 border-t border-gray-100">
									<p className="text-xs text-gray-500">Page {pagination.current_page} of {pagination.last_page}</p>
									<div className="flex gap-1">
										<button
											disabled={page <= 1}
											onClick={() => setPage((p) => p - 1)}
											className="px-3 py-1 text-xs border rounded disabled:opacity-40 hover:bg-gray-50"
										>
											Prev
										</button>
										<button
											disabled={page >= pagination.last_page}
											onClick={() => setPage((p) => p + 1)}
											className="px-3 py-1 text-xs border rounded disabled:opacity-40 hover:bg-gray-50"
										>
											Next
										</button>
									</div>
								</div>
							)}
						</>
					)}
				</div>
			</div>
		</WithVendorAuth>
	);
}
