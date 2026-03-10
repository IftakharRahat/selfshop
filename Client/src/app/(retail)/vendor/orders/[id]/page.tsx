"use client";

import { useState } from "react";
import { useParams } from "next/navigation";
import Link from "next/link";
import WithVendorAuth from "../../WithVendorAuth";
import {
	useAddVendorOrderTrackingMutation,
	useGetVendorOrderQuery,
	useSendVendorOrderToWarehouseMutation,
	useUpdateVendorOrderStatusMutation,
} from "@/redux/api/vendorApi";
import { getImageUrl } from "@/lib/utils";
import { toast } from "sonner";

const badgeClass = (status: string) => {
	const s = status.toLowerCase();
	if (s.includes("deliver")) return "bg-green-100 text-green-800";
	if (s.includes("reject") || s.includes("cancel") || s.includes("return")) return "bg-red-100 text-red-800";
	if (s.includes("accept") || s.includes("confirm")) return "bg-emerald-100 text-emerald-800";
	if (s.includes("ship") || s.includes("way") || s.includes("transit")) return "bg-blue-100 text-blue-800";
	return "bg-amber-100 text-amber-800";
};

export default function VendorOrderDetailPage() {
	const params = useParams();
	const id = Number(params?.id);
	const { data, isLoading, error } = useGetVendorOrderQuery(id, { skip: !id || isNaN(id) });

	const [updateStatus, { isLoading: savingStatus }] = useUpdateVendorOrderStatusMutation();
	const [sendToWarehouse, { isLoading: sendingWarehouse }] = useSendVendorOrderToWarehouseMutation();
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
	const displayStatus = order.display_status ?? order.customer_status ?? order.status;
	const lowerRawStatus = (order.status ?? "").toLowerCase();

	const isRejected = lowerRawStatus.includes("cancel") || lowerRawStatus.includes("reject") || lowerRawStatus.includes("return");
	const isDelivered = lowerRawStatus.includes("deliver");
	const isAccepted = lowerRawStatus === "confirmed" || (displayStatus ?? "").toLowerCase() === "accepted";
	const canAccept = lowerRawStatus === "pending" || lowerRawStatus === "processing";
	const canReject = !isRejected && !isDelivered;
	const canSendWarehouse = !isRejected && !isDelivered && !order.warehouse_sent_at && isAccepted;
	const canAddTracking = !isRejected && !isDelivered;

	const openTrackingModal = () => {
		setOrderTracking(order.tracking_number ?? "");
		setLineTrackings(
			Object.fromEntries(line_items.map((item) => [item.id, item.tracking_number ?? ""]))
		);
		setDropshipOrder(false);
		setTrackingModal(true);
	};

	const handleAccept = async () => {
		try {
			await updateStatus({ orderId: id, action: "accept" }).unwrap();
			toast.success("Order accepted");
		} catch (err: unknown) {
			const e = err as { data?: { message?: string } };
			toast.error(e?.data?.message ?? "Failed to accept order");
		}
	};

	const handleReject = async () => {
		const reason = window.prompt("Optional rejection reason")?.trim() || undefined;

		try {
			await updateStatus({ orderId: id, action: "cancel", cancel_reason: reason }).unwrap();
			toast.success("Order rejected");
		} catch (err: unknown) {
			const e = err as { data?: { message?: string } };
			toast.error(e?.data?.message ?? "Failed to reject order");
		}
	};

	const handleSendWarehouse = async () => {
		try {
			await sendToWarehouse({ orderId: id }).unwrap();
			toast.success("Order sent to warehouse");
		} catch (err: unknown) {
			const e = err as { data?: { message?: string } };
			toast.error(e?.data?.message ?? "Failed to send order to warehouse");
		}
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
				<div className="rounded-xl bg-white p-4 sm:p-6 shadow-sm border border-gray-100 flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
					<div>
						<h1 className="text-2xl font-bold text-gray-900 mb-1">Order {order.invoiceID}</h1>
						<p className="text-sm text-gray-600">Your items in this order.</p>
					</div>
					<div className="flex w-full flex-wrap items-center gap-2 sm:w-auto sm:justify-end">
						{canAccept && (
							<button
								type="button"
								onClick={handleAccept}
								disabled={savingStatus}
								className="inline-flex w-full items-center justify-center px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 disabled:opacity-60 sm:w-auto"
							>
								Accept
							</button>
						)}
						{canReject && (
							<button
								type="button"
								onClick={handleReject}
								disabled={savingStatus}
								className="inline-flex w-full items-center justify-center px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 disabled:opacity-60 sm:w-auto"
							>
								Reject
							</button>
						)}
						{canSendWarehouse && (
							<button
								type="button"
								onClick={handleSendWarehouse}
								disabled={sendingWarehouse}
								className="inline-flex w-full items-center justify-center px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 disabled:opacity-60 sm:w-auto"
							>
								{sendingWarehouse ? "Sending..." : "Send to warehouse"}
							</button>
						)}
						{canAddTracking && (
							<button
								type="button"
								onClick={openTrackingModal}
								className="inline-flex w-full items-center justify-center px-4 py-2 rounded-lg bg-[#2d2a5d] text-white text-sm font-medium hover:bg-[#252947] sm:w-auto"
							>
								Add / update tracking
							</button>
						)}
						<Link href="/vendor/orders" className="inline-flex w-full items-center justify-center text-sm font-medium text-gray-600 hover:text-gray-900 sm:w-auto">Back to orders</Link>
					</div>
				</div>

				<div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
					<div className="rounded-xl bg-white p-4 sm:p-6 shadow-sm border border-gray-100">
						<h2 className="text-sm font-semibold text-gray-900 mb-3">Order info</h2>
						<dl className="space-y-2 text-sm">
							<dt className="text-gray-500">Date</dt>
							<dd className="font-medium">{order.orderDate ?? "—"}</dd>
							<dt className="text-gray-500">Delivery date</dt>
							<dd className="font-medium">{order.deliveryDate ?? "—"}</dd>
							<dt className="text-gray-500">Status</dt>
							<dd>
								<span className={`inline-flex px-2 py-0.5 rounded text-xs font-medium ${badgeClass(displayStatus ?? order.status)}`}>
									{displayStatus ?? order.status}
								</span>
							</dd>
							<dt className="text-gray-500">Courier live status</dt>
							<dd className="font-medium">{order.steadfast_status ?? "—"}</dd>
							<dt className="text-gray-500">Warehouse sent at</dt>
							<dd className="font-medium">{order.warehouse_sent_at ? new Date(order.warehouse_sent_at).toLocaleString() : "—"}</dd>
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
							{/* Tracking link hidden from supplier — exposes customer details */}
							{order.carrybee_parcel_id && (
								<>
									<dt className="text-gray-500">Carry Bee delivery</dt>
									<dd>
										<span className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-sm font-semibold ${(order.carrybee_status ?? '').toLowerCase().includes('deliver') ? 'bg-green-50 text-green-700' :
											(order.carrybee_status ?? '').toLowerCase().includes('transit') || (order.carrybee_status ?? '').toLowerCase().includes('picked') ? 'bg-blue-50 text-blue-700' :
												(order.carrybee_status ?? '').toLowerCase().includes('fail') || (order.carrybee_status ?? '').toLowerCase().includes('return') ? 'bg-red-50 text-red-700' :
													'bg-amber-50 text-amber-700'
											}`}>
											🚚 {order.carrybee_status ?? 'Pending'}
										</span>
									</dd>
									<dt className="text-gray-500">Carry Bee consignment</dt>
									<dd>
										<div className="flex items-center gap-2 mt-1">
											<span className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-100 text-indigo-800 font-mono text-base font-bold tracking-wide border border-indigo-200">
												📦 {order.carrybee_parcel_id}
											</span>
											<button
												type="button"
												onClick={() => {
													navigator.clipboard.writeText(order.carrybee_parcel_id ?? "");
													toast.success("Consignment ID copied!");
												}}
												className="p-1.5 rounded-md hover:bg-indigo-50 text-indigo-600 transition-colors" title="Copy consignment ID"
											>
												<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2" /><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1" /></svg>
											</button>
										</div>
									</dd>
								</>
							)}
							{order.parcel_id && (
								<>
									<dt className="text-gray-500">Parcel ID</dt>
									<dd>
										<span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-700 font-mono text-sm font-semibold">
											📦 {order.parcel_id}
										</span>
									</dd>
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
						<div className="rounded-xl bg-white p-4 sm:p-6 shadow-sm border border-gray-100">
							<h2 className="text-sm font-semibold text-gray-900 mb-3">Shipping / Customer</h2>
							<dl className="space-y-2 text-sm">
								<dt className="text-gray-500">Name</dt>
								<dd className="font-medium">{customer.customerName}</dd>
								<dt className="text-gray-500">Phone</dt>
								<dd className="font-medium">
									{customer.customerPhone
										? `${'*'.repeat(Math.max(0, customer.customerPhone.length - 4))}${customer.customerPhone.slice(-4)}`
										: '—'}
								</dd>
								<dt className="text-gray-500">Address</dt>
								<dd className="text-gray-700">{customer.customerAddress}</dd>
							</dl>
						</div>
					)}
				</div>

				<div className="rounded-xl bg-white p-4 sm:p-6 shadow-sm border border-gray-100">
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
											<span className={`inline-flex px-2 py-0.5 rounded text-xs font-medium ${(item.fulfillment_status ?? "pending") === "shipped" ? "bg-blue-100 text-blue-800" :
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
										<div key={item.id} className="flex flex-col items-stretch gap-2 sm:flex-row sm:items-center">
											<span className="text-sm text-gray-600 truncate flex-1 min-w-0">{item.productName}</span>
											<input
												type="text"
												value={lineTrackings[item.id] ?? ""}
												onChange={(e) => setLineTrackings((prev) => ({ ...prev, [item.id]: e.target.value }))}
												placeholder="Tracking"
												className="w-full rounded border border-gray-300 px-2 py-1 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:w-40"
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
								{savingTracking ? "Saving..." : "Save tracking"}
							</button>
						</div>
					</div>
				</div>
			)}
		</WithVendorAuth>
	);
}
