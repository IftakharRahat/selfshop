/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";
import { Spin } from "antd";
import { Package } from "lucide-react";
import Image from "next/image";
import { useState } from "react";
import { getImageUrl } from "@/lib/utils";
import { usePendingOrderDataQuery } from "@/redux/features/orderApi";

export default function OrdersTable() {
	const [page, setPage] = useState(1);

	const { data, isLoading } = usePendingOrderDataQuery(page);

	const orders = data?.data?.data || [];
	const pagination = data?.data;

	if (isLoading) {
		return (
			<div className="flex justify-center py-10">
				<Spin size="large" />
			</div>
		);
	}

	return (
		<div>
			<div className="overflow-x-auto">
				<table className="w-full">
					<thead>
						<tr className="bg-gray-50/80">
							<th className="p-4 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">
								Product
							</th>
							<th className="p-4 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">
								Order ID
							</th>
							<th className="p-4 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">
								Customer
							</th>
							<th className="p-4 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">
								Address
							</th>
							<th className="p-4 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">
								Phone
							</th>
							<th className="p-4 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">
								Date
							</th>
							<th className="p-4 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">
								Status
							</th>
							<th className="p-4 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">
								Action
							</th>
						</tr>
					</thead>

					<tbody>
						{orders.map((order: any) => (
							<tr
								key={order.id}
								className="border-b border-gray-50 hover:bg-gray-50/50 transition-colors"
							>
								<td className="p-4">
									{order.products?.[0]?.ViewProductImage ? (
										<Image
											src={getImageUrl(order.products[0].ViewProductImage)}
											alt="Product"
											width={36}
											height={36}
											className="w-9 h-9 rounded-lg object-cover border border-gray-100"
										/>
									) : (
										<div className="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center">
											<Package className="w-4 h-4 text-gray-400" />
										</div>
									)}
								</td>

								<td className="p-4 text-sm font-medium text-gray-900">{order.invoiceID}</td>

								<td className="p-4 text-sm text-gray-700">
									{order.customers?.customerName ?? "—"}
								</td>

								<td className="p-4 text-sm text-gray-500">
									{order.customers?.customerAddress ?? "—"}
								</td>

								<td className="p-4 text-sm text-gray-500">
									{order.customers?.customerPhone ?? "—"}
								</td>

								<td className="p-4 text-sm text-gray-500">{order.orderDate}</td>

								<td className="p-4">
									<span className="bg-amber-50 text-amber-700 border border-amber-200 px-2.5 py-1 rounded-full text-xs font-medium">
										{order.status}
									</span>
								</td>

								<td className="p-4">
									<button className="text-xs font-medium px-3 py-1.5 text-[#E5005F] hover:bg-[#E5005F]/5 border border-[#E5005F]/20 rounded-lg transition-colors cursor-pointer">
										View
									</button>
								</td>
							</tr>
						))}

						{orders.length === 0 && (
							<tr>
								<td colSpan={8} className="py-12 text-center text-gray-400 text-sm">
									No pending orders found.
								</td>
							</tr>
						)}
					</tbody>
				</table>
			</div>

			{/* Pagination */}
			{pagination && (
				<div className="flex justify-center items-center gap-3 py-4 border-t border-gray-100 mt-2">
					<button
						disabled={!pagination.prev_page_url}
						onClick={() => setPage((p) => Math.max(p - 1, 1))}
						className="px-4 py-2 text-sm font-medium bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors cursor-pointer"
					>
						Previous
					</button>

					<span className="text-sm text-gray-500">
						Page {pagination.current_page} of {pagination.last_page}
					</span>

					<button
						disabled={!pagination.next_page_url}
						onClick={() => setPage((p) => p + 1)}
						className="px-4 py-2 text-sm font-medium bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors cursor-pointer"
					>
						Next
					</button>
				</div>
			)}
		</div>
	);
}
