/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import Link from "next/link";

const statusStyles: Record<string, string> = {
	Shipped: "bg-blue-50 text-blue-700 border-blue-200",
	Delivered: "bg-green-50 text-green-700 border-green-200",
	Processing: "bg-amber-50 text-amber-700 border-amber-200",
	Pending: "bg-amber-50 text-amber-700 border-amber-200",
	Cancelled: "bg-red-50 text-red-700 border-red-200",
	Returned: "bg-gray-50 text-gray-700 border-gray-200",
};

function formatDate(dateStr: string | null | undefined) {
	if (!dateStr) return "—";
	try {
		return new Date(dateStr).toLocaleString(undefined, { dateStyle: "medium", timeStyle: "short" });
	} catch {
		return dateStr;
	}
}

interface OrderDetailCardProps {
	orderData: any;
	showBackLink?: boolean;
	backHref?: string;
	backLabel?: string;
}

export default function OrderDetailCard({
	orderData,
	showBackLink = false,
	backHref = "/dashboard/orders",
	backLabel = "← Back to orders",
}: OrderDetailCardProps) {
	return (
		<div className="bg-gray-50/60 border border-gray-100 rounded-xl p-4 sm:p-5">
			{showBackLink && (
				<div className="mb-3">
					<Link href={backHref} className="text-sm font-medium text-[#E5005F] hover:underline">
						{backLabel}
					</Link>
				</div>
			)}

			{/* Header: Invoice + Status */}
			<div className="flex items-center justify-between mb-3">
				<div>
					<p className="text-sm font-semibold text-gray-900">Order: {orderData.invoiceID}</p>
					<p className="text-xs text-gray-400 mt-0.5">{orderData.orderDate}</p>
				</div>
				<span className={`px-2.5 py-1 rounded-full text-xs font-medium border ${statusStyles[orderData.status] ?? "bg-amber-50 text-amber-700 border-amber-200"}`}>
					{orderData.status}
				</span>
			</div>

			{/* Tracking & shipment */}
			{(orderData.tracking_number || orderData.shipped_at) && (
				<div className="py-3 border-t border-gray-200">
					<p className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Tracking & shipment</p>
					<div className="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
						{orderData.tracking_number && (
							<div>
								<p className="text-xs text-gray-400">Tracking number</p>
								<p className="font-medium text-gray-900 font-mono">{orderData.tracking_number}</p>
							</div>
						)}
						{orderData.shipped_at && (
							<div>
								<p className="text-xs text-gray-400">Shipped on</p>
								<p className="font-medium text-gray-900">{formatDate(orderData.shipped_at)}</p>
							</div>
						)}
					</div>
				</div>
			)}

			{/* Info Grid */}
			<div className="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-2 text-sm py-3 border-t border-gray-200">
				<div>
					<p className="text-xs text-gray-400">Customer</p>
					<p className="font-medium text-gray-900">{orderData.customers?.customerName}</p>
				</div>
				<div>
					<p className="text-xs text-gray-400">Phone</p>
					<p className="font-medium text-gray-900">{orderData.customers?.customerPhone}</p>
				</div>
				<div>
					<p className="text-xs text-gray-400">Delivery Charge</p>
					<p className="font-medium text-gray-900">৳ {orderData.deliveryCharge}</p>
				</div>
				{orderData.customers?.customerAddress && (
					<div className="col-span-2 sm:col-span-3">
						<p className="text-xs text-gray-400">Address</p>
						<p className="font-medium text-gray-900">{orderData.customers?.customerAddress}</p>
					</div>
				)}
				{orderData.couriers?.courierName && (
					<div>
						<p className="text-xs text-gray-400">Courier</p>
						<p className="font-medium text-gray-900">{orderData.couriers?.courierName}</p>
					</div>
				)}
			</div>

			{/* Products */}
			<div className="pt-3 border-t border-gray-200">
				<p className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Products</p>
				{orderData.orderproducts?.map((item: any) => (
					<div
						key={item.id}
						className="flex flex-wrap items-center justify-between gap-y-1 text-sm py-2 border-b border-gray-100 last:border-0"
					>
						<div className="flex-1 min-w-0">
							<span className="text-gray-700">{item.productName} <span className="text-gray-400">×{item.quantity}</span></span>
							{(item.tracking_number || item.fulfillment_status) && (
								<div className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs">
									{item.fulfillment_status && item.fulfillment_status !== "pending" && (
										<span className={`px-1.5 py-0.5 rounded font-medium ${item.fulfillment_status === "shipped" ? "bg-blue-100 text-blue-700" : item.fulfillment_status === "delivered" ? "bg-green-100 text-green-700" : "bg-gray-100 text-gray-600"}`}>
											{String(item.fulfillment_status).replace(/^\w/, (c: string) => c.toUpperCase())}
										</span>
									)}
									{item.tracking_number && (
										<span className="text-gray-500 font-mono">Track: {item.tracking_number}</span>
									)}
								</div>
							)}
						</div>
						<span className="font-medium text-gray-900">৳ {item.productPrice}</span>
					</div>
				))}
			</div>
		</div>
	);
}
