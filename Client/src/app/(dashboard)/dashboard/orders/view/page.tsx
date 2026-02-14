"use client";

import { useSearchParams } from "next/navigation";
import { useTrackOrderQuery } from "@/redux/features/orderApi";
import OrderDetailCard from "@/components/pages/dashboard/order-detail-card";
import Link from "next/link";

export default function OrderViewPage() {
	const searchParams = useSearchParams();
	const invoiceID = searchParams?.get("invoiceID")?.trim() ?? "";

	const { data, isFetching, isError } = useTrackOrderQuery(invoiceID, {
		skip: !invoiceID,
	});

	const orderData = data?.data;

	if (!invoiceID) {
		return (
			<div className="m-3 sm:m-4 lg:m-6 bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-24">
				<Link href="/dashboard/orders" className="text-sm font-medium text-[#E5005F] hover:underline mb-4 inline-block">
					← Back to orders
				</Link>
				<p className="text-gray-600">No order specified. Use a link from your orders list.</p>
			</div>
		);
	}

	if (isFetching) {
		return (
			<div className="m-3 sm:m-4 lg:m-6 bg-white rounded-xl border border-gray-100 shadow-sm p-8 mb-24 text-center">
				<p className="text-gray-500">Loading order details...</p>
			</div>
		);
	}

	if (isError || !orderData) {
		return (
			<div className="m-3 sm:m-4 lg:m-6 bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-24">
				<Link href="/dashboard/orders" className="text-sm font-medium text-[#E5005F] hover:underline mb-4 inline-block">
					← Back to orders
				</Link>
				<p className="text-red-600">Order not found. It may have been placed with a different account.</p>
			</div>
		);
	}

	return (
		<div className="m-3 sm:m-4 lg:m-6 bg-white rounded-xl border border-gray-100 shadow-sm p-4 sm:p-5 lg:p-8 mb-24">
			<h1 className="text-xl sm:text-2xl font-semibold text-gray-900 mb-6">Order details</h1>
			<OrderDetailCard
				orderData={orderData}
				showBackLink
				backHref="/dashboard/orders"
				backLabel="← Back to orders"
			/>
		</div>
	);
}
