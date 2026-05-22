/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { Spin } from "antd";
import { Package, Star } from "lucide-react";
import Image from "next/image";
import { useEffect, useState } from "react";
import Link from "next/link";
import { getImageUrl } from "@/lib/utils";
import { useOrderDataByStatusQuery } from "@/redux/features/orderApi";
import { useGetReviewableProductsQuery } from "@/redux/features/dashboardApi";
import ReviewModal from "./ReviewModal";

const statusColors: Record<string, string> = {
	Pending: "bg-amber-50 text-amber-700 border-amber-200",
	Accepted: "bg-emerald-50 text-emerald-700 border-emerald-200",
	Rejected: "bg-red-50 text-red-700 border-red-200",
	Confirmed: "bg-blue-50 text-blue-700 border-blue-200",
	Processing: "bg-indigo-50 text-indigo-700 border-indigo-200",
	"Shipped to warehouse": "bg-blue-50 text-blue-700 border-blue-200",
	"On the way": "bg-cyan-50 text-cyan-700 border-cyan-200",
	Packageing: "bg-purple-50 text-purple-700 border-purple-200",
	Ontheway: "bg-cyan-50 text-cyan-700 border-cyan-200",
	Delivered: "bg-green-50 text-green-700 border-green-200",
	Canceled: "bg-red-50 text-red-700 border-red-200",
	Return: "bg-gray-50 text-gray-700 border-gray-200",
	"Payment Pending": "bg-amber-50 text-amber-700 border-amber-200",
	"Payment Failed": "bg-red-50 text-red-700 border-red-200",
	"Payment Canceled": "bg-rose-50 text-rose-700 border-rose-200",
};

interface OrdersTableProps {
	status?: string;
}

function invoiceViewHref(invoiceId: string | null | undefined, orderId?: number | string): string {
	const value = String(invoiceId ?? "").trim();
	const normalized = value.replace(/^[^A-Za-z0-9]+/, "");
	const params = new URLSearchParams();
	params.set('invoiceID', normalized);
	if (orderId) params.set('id', String(orderId));
	return `/dashboard/orders/view?${params.toString()}`;
}

export default function OrdersTable({ status = "all" }: OrdersTableProps) {
	const [page, setPage] = useState(1);
	const [reviewModal, setReviewModal] = useState<{
		open: boolean;
		productId: number;
		productName: string;
	}>({ open: false, productId: 0, productName: "" });

	useEffect(() => {
		setPage(1);
	}, [status]);

	const { data, isLoading } = useOrderDataByStatusQuery({ status, page });
	const { data: reviewableData } = useGetReviewableProductsQuery();
	const orders = data?.data?.data || [];
	const pagination = data?.data;

	// Build a set of product IDs that are reviewable (delivered + not yet reviewed)
	const reviewableProductIds = new Set(
		(reviewableData?.data ?? []).map((r) => r.product_id),
	);

	const getFirstProductId = (order: any): number | null => {
		const products = order.products || order.orderproducts || [];
		if (products.length > 0) {
			return products[0].product_id ?? products[0].id ?? null;
		}
		return null;
	};

	const getFirstProductName = (order: any): string => {
		const products = order.products || order.orderproducts || [];
		if (products.length > 0) {
			return products[0].productName || products[0].ProductName || "Product";
		}
		return "Product";
	};

	const getCustomerMeta = (order: any) => {
		const rawData = order?.data;
		let parsed: any = {};
		try {
			parsed = typeof rawData === "string" ? JSON.parse(rawData) : (rawData || {});
		} catch {
			parsed = {};
		}

		return {
			name: order?.customers?.customerName ?? parsed?.customer_name ?? parsed?.customerName ?? "-",
			phone: order?.customers?.customerPhone ?? parsed?.customer_phone ?? parsed?.customerPhone ?? "-",
			address: order?.customers?.customerAddress ?? parsed?.customer_address ?? parsed?.customerAddress ?? "-",
		};
	};

	const toDisplayStatus = (order: any): string => {
		const raw = String(order?.status ?? "");
		if (raw === "Pending Payment") return "Payment Pending";
		if (raw === "Failed") return "Payment Failed";
		if (raw === "Canceled" || raw === "Cancelled") return "Payment Canceled";

		return order?.customer_status ?? order?.display_status ?? raw;
	};

	if (isLoading) {
		return (
			<div className="flex justify-center py-10">
				<Spin size="large" />
			</div>
		);
	}

	return (
		<div>
			{/* Review Modal */}
			<ReviewModal
				isOpen={reviewModal.open}
				onClose={() =>
					setReviewModal({ open: false, productId: 0, productName: "" })
				}
				productId={reviewModal.productId}
				productName={reviewModal.productName}
			/>

			{/* Mobile view */}
			<div className="md:hidden space-y-3">
				{orders.map((order: any) => {
					const displayStatus = toDisplayStatus(order);
					const customer = getCustomerMeta(order);
					const isDelivered = displayStatus === "Delivered";
					const firstProductId = getFirstProductId(order);
					const canReview =
						isDelivered &&
						firstProductId &&
						reviewableProductIds.has(firstProductId);
					const hasReviewed =
						isDelivered &&
						firstProductId &&
						!reviewableProductIds.has(firstProductId);

					return (
						<div
							key={order.id}
							className="bg-gray-50/60 border border-gray-100 rounded-xl p-3"
						>
							<div className="flex items-center gap-3 mb-2.5">
								{(() => {
									const img = order.orderproducts?.[0]?.product?.ViewProductImage
										|| order.products?.[0]?.ViewProductImage;
									return img ? (
										<Image
											src={getImageUrl(img)}
											alt="Product"
											width={40}
											height={40}
											className="w-10 h-10 rounded-lg object-cover border border-gray-100"
										/>
									) : (
										<div className="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center">
											<Package className="w-4 h-4 text-gray-400" />
										</div>
									);
								})()}
								<div className="flex-1 min-w-0">
									<p className="text-sm font-semibold text-gray-900 truncate">
										{order.invoiceID}
									</p>
									<p className="text-xs text-gray-500 truncate">
										{customer.name}
									</p>
								</div>
								<span
									className={`border px-2 py-0.5 rounded-full text-[10px] font-medium flex-shrink-0 ${statusColors[displayStatus] || "bg-amber-50 text-amber-700 border-amber-200"}`}
								>
									{displayStatus}
								</span>
							</div>

							<div className="grid grid-cols-2 gap-x-4 gap-y-1 text-xs mb-2.5">
								<div>
									<span className="text-gray-400">Phone</span>
									<p className="text-gray-700 truncate">
										{customer.phone}
									</p>
								</div>
								<div>
									<span className="text-gray-400">Date</span>
									<p className="text-gray-700">{order.orderDate}</p>
								</div>
								<div className="col-span-2">
									<span className="text-gray-400">Address</span>
									<p className="text-gray-700 truncate">
										{customer.address}
									</p>
								</div>
							</div>

							<div className="flex gap-2">
								<Link
									href={invoiceViewHref(order.invoiceID, order.id)}
									className="flex-1 text-center text-xs font-medium py-2 text-[#E5005F] hover:bg-[#E5005F]/5 border border-[#E5005F]/20 rounded-lg transition-colors cursor-pointer"
								>
									View Order
								</Link>
								{canReview && (
									<button
										onClick={() =>
											setReviewModal({
												open: true,
												productId: firstProductId!,
												productName: getFirstProductName(order),
											})
										}
										className="flex items-center justify-center gap-1 px-3 py-2 text-xs font-medium text-amber-600 hover:bg-amber-50 border border-amber-200 rounded-lg transition-colors cursor-pointer"
									>
										<Star className="w-3.5 h-3.5" />
										Review
									</button>
								)}
								{hasReviewed && (
									<span className="flex items-center justify-center gap-1 px-3 py-2 text-xs font-medium text-green-600 bg-green-50 border border-green-200 rounded-lg">
										<Star className="w-3.5 h-3.5 fill-green-500" />
										Reviewed
									</span>
								)}
							</div>
						</div>
					);
				})}

				{orders.length === 0 && (
					<div className="py-10 text-center text-gray-400 text-sm">
						No orders found.
					</div>
				)}
			</div>

			{/* Desktop view */}
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
						{orders.map((order: any) => {
							const displayStatus = toDisplayStatus(order);
							const customer = getCustomerMeta(order);
							const isDelivered = displayStatus === "Delivered";
							const firstProductId = getFirstProductId(order);
							const canReview =
								isDelivered &&
								firstProductId &&
								reviewableProductIds.has(firstProductId);
							const hasReviewed =
								isDelivered &&
								firstProductId &&
								!reviewableProductIds.has(firstProductId);

							return (
								<tr
									key={order.id}
									className="border-b border-gray-50 hover:bg-gray-50/50 transition-colors"
								>
									<td className="p-4">
										{(() => {
										const img = order.orderproducts?.[0]?.product?.ViewProductImage
											|| order.products?.[0]?.ViewProductImage;
										return img ? (
											<Image
												src={getImageUrl(img)}
												alt="Product"
												width={36}
												height={36}
												className="w-9 h-9 rounded-lg object-cover border border-gray-100"
											/>
										) : (
											<div className="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center">
												<Package className="w-4 h-4 text-gray-400" />
											</div>
										);
									})()}
									</td>

									<td className="p-4 text-sm font-medium text-gray-900">
										{order.invoiceID}
									</td>

									<td className="p-4 text-sm text-gray-700">
										{customer.name}
									</td>

									<td className="p-4 text-sm text-gray-500">
										{customer.address}
									</td>

									<td className="p-4 text-sm text-gray-500">
										{customer.phone}
									</td>

									<td className="p-4 text-sm text-gray-500">
										{order.orderDate}
									</td>

									<td className="p-4">
										<span
											className={`border px-2.5 py-1 rounded-full text-xs font-medium ${statusColors[displayStatus] || "bg-gray-50 text-gray-700 border-gray-200"}`}
										>
											{displayStatus}
										</span>
									</td>

									<td className="p-4">
										<div className="flex items-center gap-2">
											<Link
												href={invoiceViewHref(order.invoiceID, order.id)}
												className="inline-block text-xs font-medium px-3 py-1.5 text-[#E5005F] hover:bg-[#E5005F]/5 border border-[#E5005F]/20 rounded-lg transition-colors cursor-pointer"
											>
												View
											</Link>
											{canReview && (
												<button
													onClick={() =>
														setReviewModal({
															open: true,
															productId: firstProductId!,
															productName:
																getFirstProductName(order),
														})
													}
													className="inline-flex items-center gap-1 text-xs font-medium px-3 py-1.5 text-amber-600 hover:bg-amber-50 border border-amber-200 rounded-lg transition-colors cursor-pointer"
												>
													<Star className="w-3 h-3" />
													Review
												</button>
											)}
											{hasReviewed && (
												<span className="inline-flex items-center gap-1 text-xs font-medium px-3 py-1.5 text-green-600 bg-green-50 border border-green-200 rounded-lg">
													<Star className="w-3 h-3 fill-green-500" />
													Reviewed
												</span>
											)}
										</div>
									</td>
								</tr>
							);
						})}

						{orders.length === 0 && (
							<tr>
								<td
									colSpan={8}
									className="py-12 text-center text-gray-400 text-sm"
								>
									No orders found.
								</td>
							</tr>
						)}
					</tbody>
				</table>
			</div>

			{/* Pagination */}
			{pagination && pagination.last_page > 1 && (
				<div className="flex justify-center items-center gap-3 py-4 border-t border-gray-100 mt-2">
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
