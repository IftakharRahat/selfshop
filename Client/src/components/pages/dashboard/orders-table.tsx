/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";
import { Spin } from "antd";
import { Package } from "lucide-react";
import Image from "next/image";
import { useEffect, useState } from "react";
import { getImageUrl } from "@/lib/utils";
import { useOrdersByStatusQuery } from "@/redux/features/orderApi";

export default function OrdersTable({ status = "Pending" }: { status?: string }) {
	const [page, setPage] = useState(1);

	// Reset to page 1 when status changes
	useEffect(() => {
		setPage(1);
	}, [status]);

	const { data, isLoading } = useOrdersByStatusQuery({ status, page });

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
			{/* Mobile Card Layout */}
			<div className="md:hidden space-y-3">
				{orders.map((order: any) => (
					<div
						key={order.id}
						className="bg-gray-50/60 border border-gray-100 rounded-xl p-3"
					>
						<div className="flex items-center gap-3 mb-2.5">
							{order.products?.[0]?.ViewProductImage ? (
								<Image
									src={getImageUrl(order.products[0].ViewProductImage)}
									alt="Product"
									width={40}
									height={40}
									className="w-10 h-10 rounded-lg object-cover border border-gray-100"
								/>
							) : (
								<div className="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center">
									<Package className="w-4 h-4 text-gray-400" />
								</div>
							)}
							<div className="flex-1 min-w-0">
								<p className="text-sm font-semibold text-gray-900 truncate">
									{order.invoiceID}
								</p>
								<p className="text-xs text-gray-500 truncate">
									{order.customers?.customerName ?? "—"}
								</p>
							</div>
							<span className="bg-amber-50 text-amber-700 border border-amber-200 px-2 py-0.5 rounded-full text-[10px] font-medium flex-shrink-0">
								{order.status}
							</span>
						</div>

						<div className="grid grid-cols-2 gap-x-4 gap-y-1 text-xs mb-2.5">
							<div>
								<span className="text-gray-400">Phone</span>
								<p className="text-gray-700 truncate">{order.customers?.customerPhone ?? "—"}</p>
							</div>
							<div>
								<span className="text-gray-400">Date</span>
								<p className="text-gray-700">{order.orderDate}</p>
							</div>
							<div className="col-span-2">
								<span className="text-gray-400">Address</span>
								<p className="text-gray-700 truncate">{order.customers?.customerAddress ?? "—"}</p>
							</div>
						</div>

						<button className="w-full text-xs font-medium py-2 text-[#E5005F] hover:bg-[#E5005F]/5 border border-[#E5005F]/20 rounded-lg transition-colors cursor-pointer">
							View Order
						</button>
					</div>
				))}

				{orders.length === 0 && (
					<div className="py-10 text-center text-gray-400 text-sm">
						No pending orders found.
					</div>
				)}
			</div>

			{/* Desktop Table Layout */}
			<div className="hidden md:block overflow-x-auto">
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
				<div className="flex justify-center items-center gap-2 sm:gap-3 py-3 sm:py-4 border-t border-gray-100 mt-2">
					<button
						disabled={!pagination.prev_page_url}
						onClick={() => setPage((p) => Math.max(p - 1, 1))}
						className="px-3 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm font-medium bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors cursor-pointer"
					>
						Previous
					</button>

					<span className="text-xs sm:text-sm text-gray-500">
						Page {pagination.current_page} of {pagination.last_page}
					</span>

					<button
						disabled={!pagination.next_page_url}
						onClick={() => setPage((p) => p + 1)}
						className="px-3 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm font-medium bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors cursor-pointer"
					>
						Next
					</button>
				</div>
			)}
		</div>
	);
}
