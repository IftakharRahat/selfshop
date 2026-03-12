/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import Link from "next/link";

const statusStyles: Record<string, string> = {
	Shipped: "bg-blue-50 text-blue-700 border-blue-200",
	"Shipped to warehouse": "bg-blue-50 text-blue-700 border-blue-200",
	"On the way": "bg-cyan-50 text-cyan-700 border-cyan-200",
	Delivered: "bg-green-50 text-green-700 border-green-200",
	Processing: "bg-amber-50 text-amber-700 border-amber-200",
	Pending: "bg-amber-50 text-amber-700 border-amber-200",
	Accepted: "bg-emerald-50 text-emerald-700 border-emerald-200",
	Rejected: "bg-red-50 text-red-700 border-red-200",
	Cancelled: "bg-red-50 text-red-700 border-red-200",
	Canceled: "bg-red-50 text-red-700 border-red-200",
	Returned: "bg-gray-50 text-gray-700 border-gray-200",
};

function formatDate(dateStr: string | null | undefined) {
	if (!dateStr) return "-";
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
	backLabel = "<- Back to orders",
}: OrderDetailCardProps) {
	const customerStatus =
		orderData.customer_status ?? orderData.display_status ?? orderData.status;

	return (
		<div className="bg-gray-50/60 border border-gray-100 rounded-xl p-4 sm:p-5">
			{showBackLink && (
				<div className="mb-3">
					<Link href={backHref} className="text-sm font-medium text-[#E5005F] hover:underline">
						{backLabel}
					</Link>
				</div>
			)}

			<div className="flex items-center justify-between mb-3">
				<div>
					<p className="text-sm font-semibold text-gray-900">Order: {orderData.invoiceID}</p>
					<p className="text-xs text-gray-400 mt-0.5">{orderData.orderDate}</p>
				</div>
				<span className={`px-2.5 py-1 rounded-full text-xs font-medium border ${statusStyles[customerStatus] ?? "bg-amber-50 text-amber-700 border-amber-200"}`}>
					{customerStatus}
				</span>
			</div>

			{(orderData.tracking_number || orderData.shipped_at || orderData.carrybee_tracking_code) && (
				<div className="py-3 border-t border-gray-200">
					<p className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Tracking and shipment</p>
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
						{orderData.carrybee_tracking_code && (
							<div className="col-span-1 sm:col-span-2">
								<p className="text-xs text-gray-400 mb-1">Carry Bee Delivery</p>
								<div className="flex items-center gap-2">
									<span className="px-2 py-0.5 bg-yellow-50 text-yellow-700 border border-yellow-200 rounded text-xs font-medium">
										🚚 {orderData.carrybee_status ?? "Pending"}
									</span>
									<a
										href={orderData.trackingLink || `https://merchant.carrybee.com/order-track/${orderData.carrybee_tracking_code}`}
										target="_blank"
										rel="noreferrer"
										className="inline-flex items-center gap-1 px-3 py-1 bg-[#2d2a5d] text-white text-xs font-medium rounded-lg hover:bg-[#252947] transition-colors"
									>
										📦 Track Delivery
									</a>
								</div>
							</div>
						)}
					</div>
				</div>
			)}

			<div className="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-2 text-sm py-3 border-t border-gray-200">
				<div>
					<p className="text-xs text-gray-400">Customer</p>
					<p className="font-medium text-gray-900">{orderData.customers?.customerName}</p>
				</div>
				<div>
					<p className="text-xs text-gray-400">Phone</p>
					<p className="font-medium text-gray-900">{orderData.customers?.customerPhone}</p>
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

			<div className="pt-3 border-t border-gray-200">
				<p className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Products</p>
				{orderData.orderproducts?.map((item: any) => {
					const costPrice = parseFloat(item.productPrice) || 0;
					const qty = parseInt(item.quantity) || 1;
					const itemTotal = costPrice * qty;
					return (
						<div
							key={item.id}
							className="flex flex-wrap items-start justify-between gap-y-1 text-sm py-2 border-b border-gray-100 last:border-0"
						>
							<div className="flex-1 min-w-0">
								<span className="text-gray-700">{item.productName} <span className="text-gray-400">x{item.quantity}</span></span>
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
							<div className="text-right">
								<span className="font-medium text-gray-900">Tk {itemTotal.toLocaleString()}</span>
							</div>
						</div>
					);
				})}

				{/* Order Summary */}
				<div className="mt-3 pt-3 border-t border-gray-200 space-y-1.5 text-sm">
					<div className="flex justify-between">
						<span className="text-gray-500">Resell Price</span>
						<span className="font-medium text-gray-900">Tk {(parseFloat(orderData.subTotal) || 0).toLocaleString()}</span>
					</div>
					<div className="flex justify-between">
						<span className="text-gray-500">Seller Profit</span>
						<span className="font-medium text-green-600">Tk {(parseFloat(orderData.profit) || 0).toLocaleString()}</span>
					</div>
					<div className="flex justify-between pt-1.5 border-t border-gray-100">
						<span className="font-semibold text-gray-900">Total</span>
						<span className="font-semibold text-gray-900">Tk {((parseFloat(orderData.subTotal) || 0) + (parseFloat(orderData.profit) || 0)).toLocaleString()}</span>
					</div>
				</div>
			</div>
		</div>
	);
}
