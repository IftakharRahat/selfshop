"use client";

import { useState } from "react";
import { useParams } from "next/navigation";
import Link from "next/link";
import WithVendorAuth from "../../WithVendorAuth";
import { useGetVendorOrderQuery, useAddVendorOrderTrackingMutation } from "@/redux/api/vendorApi";
import { getImageUrl } from "@/lib/utils";
import { toast } from "sonner";

export default function VendorOrderDetailPage() {
	const params = useParams();
	const id = Number(params?.id);
	const { data, isLoading, error } = useGetVendorOrderQuery(id, { skip: !id || isNaN(id) });
	const [addTracking, { isLoading: savingTracking }] = useAddVendorOrderTrackingMutation();
	const detail = data?.data;

	const [trackingModal, setTrackingModal] = useState(false);
	const [orderTracking, setOrderTracking] = useState("");
	const [lineTrackings, setLineTrackings] = useState<Record<number, string>>({});
	const [dropshipOrder, setDropshipOrder] = useState(false);

	if (!id || isNaN(id)) {
		return <WithVendorAuth><p className="text-red-600">Invalid order ID.</p></WithVendorAuth>;
	}
	if (isLoading) {
		return <WithVendorAuth><p className="text-gray-600 p-6">Loading order...</p></WithVendorAuth>;
	}
	if (error || !detail) {
		return (
			<WithVendorAuth>
				<p className="text-red-600">Order not found.</p>
				<Link href="/vendor/orders" className="text-blue-600 hover:underline mt-2 inline-block">Back to orders</Link>
			</WithVendorAuth>
		);
	}

	const { order, customer, line_items, vendor_subtotal } = detail;

	const canAddTracking = order.status !== "Cancelled" && order.status !== "Returned" && order.status !== "Delivered";

	const openTrackingModal = () => {
		setOrderTracking(order.tracking_number ?? "");
		setLineTrackings(
			Object.fromEntries(line_items.map((item) => [item.id, item.tracking_number ?? ""]))
		);
		setDropshipOrder(false);
		setTrackingModal(true);
	};

	const handleSubmitTracking = async () => {
		const hasOrder = orderTracking.trim() !== "";
		const hasLine = Object.values(lineTrackings).some((v) => v.trim() !== "");
		if (!hasOrder && !hasLine && !dropshipOrder) {
			toast.error("Enter at least one tracking number or mark as dropship.");
			return;
		}
		try {
			await addTracking({
				orderId: id,
				tracking_number: hasOrder ? orderTracking.trim() : undefined,
				line_items: hasLine || dropshipOrder
					? line_items.map((item) => ({
							order_product_id: item.id,
							tracking_number: (lineTrackings[item.id] ?? "").trim() || undefined,
							fulfillment_type: dropshipOrder ? "dropship" : undefined,
						}))
					: undefined,
			}).unwrap();
			toast.success("Tracking updated");
			setTrackingModal(false);
		} catch (err: unknown) {
			const e = err as { data?: { message?: string }; status?: number };
			const msg = e?.data?.message ?? (e?.status === 404 ? "Order not found." : "Failed to update tracking. Check the console or try again.");
			toast.error(msg);
		}
	};

	return (
		<WithVendorAuth>
			<div className="space-y-6">
				<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100 flex items-center justify-between gap-4">
					<div>
						<h1 className="text-2xl font-bold text-gray-900 mb-1">Order {order.invoiceID}</h1>
						<p className="text-sm text-gray-600">Your items in this order.</p>
					</div>
					<div className="flex items-center gap-2">
						{canAddTracking && (
							<button
								type="button"
								onClick={openTrackingModal}
								className="inline-flex items-center px-4 py-2 rounded-lg bg-[#2d2a5d] text-white text-sm font-medium hover:bg-[#252947]"
							>
								Add / update tracking
							</button>
						)}
						<Link href="/vendor/orders" className="text-sm font-medium text-gray-600 hover:text-gray-900">Back to orders</Link>
					</div>
				</div>

				<div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
					<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
						<h2 className="text-sm font-semibold text-gray-900 mb-3">Order info</h2>
						<dl className="space-y-2 text-sm">
							<dt className="text-gray-500">Date</dt>
							<dd className="font-medium">{order.orderDate ?? "—"}</dd>
							<dt className="text-gray-500">Delivery date</dt>
							<dd className="font-medium">{order.deliveryDate ?? "—"}</dd>
							<dt className="text-gray-500">Status</dt>
							<dd>
								<span className={`inline-flex px-2 py-0.5 rounded text-xs font-medium ${
									order.status === "Delivered" ? "bg-green-100 text-green-800" :
									order.status === "Cancelled" || order.status === "Returned" ? "bg-red-100 text-red-800" :
									order.status === "Shipped" ? "bg-blue-100 text-blue-800" :
									"bg-amber-100 text-amber-800"
								}`}>
									{order.status}
								</span>
							</dd>
							<dt className="text-gray-500">Payment</dt>
							<dd className="font-medium">{order.Payment ?? "—"}</dd>
							{(order.tracking_number || order.shipped_at) && (
								<>
									<dt className="text-gray-500">Tracking</dt>
									<dd className="font-medium font-mono">{order.tracking_number ?? "—"}</dd>
									<dt className="text-gray-500">Shipped at</dt>
									<dd className="font-medium">{order.shipped_at ? new Date(order.shipped_at).toLocaleString() : "—"}</dd>
								</>
							)}
							{order.customerNote && (
								<>
									<dt className="text-gray-500">Customer note</dt>
									<dd className="text-gray-700">{order.customerNote}</dd>
								</>
							)}
						</dl>
					</div>

					{customer && (
						<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
							<h2 className="text-sm font-semibold text-gray-900 mb-3">Shipping / Customer</h2>
							<dl className="space-y-2 text-sm">
								<dt className="text-gray-500">Name</dt>
								<dd className="font-medium">{customer.customerName}</dd>
								<dt className="text-gray-500">Phone</dt>
								<dd className="font-medium">{customer.customerPhone}</dd>
								<dt className="text-gray-500">Address</dt>
								<dd className="text-gray-700">{customer.customerAddress}</dd>
							</dl>
						</div>
					)}
				</div>

				<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
					<h2 className="text-sm font-semibold text-gray-900 mb-3">Your items</h2>
					<div className="overflow-x-auto">
						<table className="min-w-full text-sm">
							<thead className="bg-gray-50 text-gray-600">
								<tr>
									<th className="px-3 py-2 text-left font-medium">Product</th>
									<th className="px-3 py-2 text-right font-medium">Price</th>
									<th className="px-3 py-2 text-center font-medium">Qty</th>
									<th className="px-3 py-2 text-center font-medium">Fulfillment</th>
									<th className="px-3 py-2 text-left font-medium">Tracking</th>
									<th className="px-3 py-2 text-right font-medium">Total</th>
								</tr>
							</thead>
							<tbody className="divide-y divide-gray-100">
								{line_items.map((item) => (
									<tr key={item.id}>
										<td className="px-3 py-2">
											<div className="flex items-center gap-2">
												{item.product?.ViewProductImage && (
													<img src={getImageUrl(item.product.ViewProductImage)} alt="" className="w-10 h-10 object-cover rounded" />
												)}
												<span className="font-medium">{item.productName}</span>
												{item.productCode && <span className="text-gray-500 text-xs">({item.productCode})</span>}
											</div>
										</td>
										<td className="px-3 py-2 text-right">৳{Number(item.productPrice).toLocaleString()}</td>
										<td className="px-3 py-2 text-center">{item.quantity}</td>
										<td className="px-3 py-2 text-center">
											<span className={`inline-flex px-2 py-0.5 rounded text-xs font-medium ${
												(item.fulfillment_status ?? "pending") === "shipped" ? "bg-blue-100 text-blue-800" :
												(item.fulfillment_status ?? "pending") === "delivered" ? "bg-green-100 text-green-800" :
												"bg-gray-100 text-gray-700"
											}`}>
												{(item.fulfillment_status ?? "pending").replace(/^\w/, (c) => c.toUpperCase())}
											</span>
										</td>
										<td className="px-3 py-2 font-mono text-xs text-gray-600">
											{item.tracking_number ?? "—"}
										</td>
										<td className="px-3 py-2 text-right font-medium">৳{item.line_total.toLocaleString()}</td>
									</tr>
								))}
							</tbody>
						</table>
					</div>
					<div className="mt-4 pt-4 border-t border-gray-200 flex justify-end">
						<p className="text-sm font-semibold text-gray-900">Your subtotal: ৳{vendor_subtotal.toLocaleString()}</p>
					</div>
				</div>
			</div>

			{/* Add / Update tracking modal */}
			{trackingModal && (
				<div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
					<div className="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 mx-4 max-h-[90vh] overflow-y-auto">
						<h2 className="text-lg font-semibold text-gray-900 mb-2">Fulfillment & tracking</h2>
						<p className="text-sm text-gray-500 mb-4">
							Add a single tracking number for the whole order, or per-item tracking for partial shipments.
						</p>
						<div className="space-y-4">
							<div>
								<label className="block text-sm font-medium text-gray-700 mb-1">Order tracking number</label>
								<input
									type="text"
									value={orderTracking}
									onChange={(e) => setOrderTracking(e.target.value)}
									placeholder="e.g. TRK123456789"
									className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
								/>
							</div>
							<div>
								<p className="text-sm font-medium text-gray-700 mb-2">Per-item tracking (optional)</p>
								<div className="space-y-2">
									{line_items.map((item) => (
										<div key={item.id} className="flex items-center gap-2">
											<span className="text-sm text-gray-600 truncate flex-1 min-w-0">{item.productName}</span>
											<input
												type="text"
												value={lineTrackings[item.id] ?? ""}
												onChange={(e) => setLineTrackings((prev) => ({ ...prev, [item.id]: e.target.value }))}
												placeholder="Tracking"
												className="w-40 border border-gray-300 rounded px-2 py-1 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500"
											/>
										</div>
									))}
								</div>
							</div>
							<label className="flex items-center gap-2">
								<input
									type="checkbox"
									checked={dropshipOrder}
									onChange={(e) => setDropshipOrder(e.target.checked)}
									className="rounded border-gray-300 text-[#2d2a5d] focus:ring-indigo-500"
								/>
								<span className="text-sm text-gray-700">Dropship (hide my branding on packing / label)</span>
							</label>
						</div>
						<div className="flex justify-end gap-2 mt-5">
							<button
								type="button"
								onClick={() => setTrackingModal(false)}
								className="px-4 py-2 text-sm border rounded-lg hover:bg-gray-50"
							>
								Cancel
							</button>
							<button
								type="button"
								disabled={savingTracking}
								onClick={handleSubmitTracking}
								className="px-4 py-2 text-sm bg-[#2d2a5d] text-white rounded-lg hover:bg-[#252947] disabled:opacity-50"
							>
								{savingTracking ? "Saving…" : "Save tracking"}
							</button>
						</div>
					</div>
				</div>
			)}
		</WithVendorAuth>
	);
}
