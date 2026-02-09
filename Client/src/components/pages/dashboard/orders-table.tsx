/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";
import { Spin } from "antd";
import { useState } from "react";
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
		<div className="bg-white">
			<div className="p-0">
				<div className="overflow-x-auto">
					<table className="w-full">
						<thead>
							<tr>
								<th className="p-4 text-sm font-medium text-gray-600 text-left">
									Order ID
								</th>
								<th className="p-4 text-sm font-medium text-gray-600 text-left">
									Customer name
								</th>
								<th className="p-4 text-sm font-medium text-gray-600 text-left">
									Address
								</th>
								<th className="p-4 text-sm font-medium text-gray-600 text-left">
									Number
								</th>
								<th className="p-4 text-sm font-medium text-gray-600 text-left">
									Time
								</th>
								<th className="p-4 text-sm font-medium text-gray-600 text-left">
									Status
								</th>
								<th className="p-4 text-sm font-medium text-gray-600 text-left">
									View
								</th>
							</tr>
						</thead>

						<tbody>
							{orders.map((order: any) => (
								<tr
									key={order.id}
									className="border-b border-gray-100 hover:bg-gray-50"
								>
									<td className="p-4">{order.invoiceID}</td>

									<td className="p-4">
										{order.customers?.customerName ?? "—"}
									</td>

									<td className="p-4 text-gray-600">
										{order.customers?.customerAddress ?? "—"}
									</td>

									<td className="p-4 text-gray-600">
										{order.customers?.customerPhone ?? "—"}
									</td>

									<td className="p-4 text-gray-600">{order.orderDate}</td>

									<td className="p-4">
										<span className="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-xs">
											{order.status}
										</span>
									</td>

									<td className="p-4">
										<button className="text-xs px-3 py-1 bg-gray-100 rounded-md">
											View
										</button>
									</td>
								</tr>
							))}

							{orders.length === 0 && (
								<tr>
									<td colSpan={8} className="py-6 text-center text-gray-500">
										No orders found.
									</td>
								</tr>
							)}
						</tbody>
					</table>
				</div>

				{/* Pagination */}
				{pagination && (
					<div className="flex justify-center items-center gap-4 py-4">
						<button
							disabled={!pagination.prev_page_url}
							onClick={() => setPage((p) => Math.max(p - 1, 1))}
							className="px-4 py-2 bg-gray-100 rounded disabled:opacity-50"
						>
							Previous
						</button>

						<span>
							Page {pagination.current_page} of {pagination.last_page}
						</span>

						<button
							disabled={!pagination.next_page_url}
							onClick={() => setPage((p) => p + 1)}
							className="px-4 py-2 bg-gray-100 rounded disabled:opacity-50"
						>
							Next
						</button>
					</div>
				)}
			</div>
		</div>
	);
}
