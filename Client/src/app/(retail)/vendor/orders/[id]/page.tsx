"use client";

import { useParams } from "next/navigation";
import Link from "next/link";
import WithVendorAuth from "../../WithVendorAuth";
import { useGetVendorOrderQuery } from "@/redux/api/vendorApi";
import { getImageUrl } from "@/lib/utils";

export default function VendorOrderDetailPage() {
	const params = useParams();
	const id = Number(params?.id);
	const { data, isLoading, error } = useGetVendorOrderQuery(id, { skip: !id || isNaN(id) });
	const detail = data?.data;

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

	return (
		<WithVendorAuth>
			<div className="space-y-6">
				<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100 flex items-center justify-between gap-4">
					<div>
						<h1 className="text-2xl font-bold text-gray-900 mb-1">Order {order.invoiceID}</h1>
						<p className="text-sm text-gray-600">Your items in this order.</p>
					</div>
					<Link href="/vendor/orders" className="text-sm font-medium text-gray-600 hover:text-gray-900">Back to orders</Link>
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
									"bg-amber-100 text-amber-800"
								}`}>
									{order.status}
								</span>
							</dd>
							<dt className="text-gray-500">Payment</dt>
							<dd className="font-medium">{order.Payment ?? "—"}</dd>
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
		</WithVendorAuth>
	);
}
