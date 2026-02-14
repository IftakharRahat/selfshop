/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { Alert, ConfigProvider, Input, Spin } from "antd";
import { useState, useEffect } from "react";
import { useSearchParams } from "next/navigation";
import { useTrackOrderQuery } from "@/redux/features/orderApi";
import OrderDetailCard from "./order-detail-card";

export default function OrderTracking() {
	const searchParams = useSearchParams();
	const [orderId, setOrderId] = useState("");
	const [triggerId, setTriggerId] = useState("");
	const [showError, setShowError] = useState(false);

	const { data, isFetching, isError } = useTrackOrderQuery(triggerId, {
		skip: !triggerId,
	});

	const orderData = data?.data;

	// Optional: prefill search box when invoiceID is in URL (user can still click Search)
	useEffect(() => {
		const invoiceID = searchParams?.get("invoiceID")?.trim();
		if (invoiceID && !orderId) {
			setOrderId(invoiceID);
		}
	}, [searchParams, orderId]);

	const handleSearch = () => {
		if (!orderId.trim()) {
			setShowError(true);
			return;
		}
		setTriggerId(orderId.trim());
		setShowError(false);
	};

	return (
		<div className="m-3 sm:m-4 lg:m-6 bg-white rounded-xl border border-gray-100 shadow-sm p-4 sm:p-5 lg:p-8 mb-24">
			<h1 className="text-xl sm:text-2xl font-semibold text-gray-900 mb-6 sm:mb-8">
				Track your order
			</h1>

			<ConfigProvider
				theme={{
					token: {
						colorPrimary: "#E5005F",
					},
				}}
			>
				<div className="space-y-6">
					{/* Input */}
					<div className="space-y-2">
						<label
							htmlFor="orderId"
							className="text-sm font-medium text-gray-700"
						>
							Order ID
						</label>
						<Input
							id="orderId"
							placeholder="Enter the order ID"
							value={orderId}
							onChange={(e) => setOrderId(e.target.value)}
							onPressEnter={handleSearch}
							size="large"
						/>
					</div>

					{/* Button */}
					<button
						onClick={handleSearch}
						className="w-full bg-[#E5005F] hover:bg-pink-600 !text-white py-3 text-base font-medium rounded-md cursor-pointer transition-colors"
					>
						Search now
					</button>

					{/* Error */}
					{(showError || isError) && (
						<div className="mt-2">
							<Alert
								message="No Records Found. Please call our customer care or use Live Chat"
								type="error"
								showIcon
							/>
						</div>
					)}

					{/* Loader */}
					{isFetching && (
						<div className="flex justify-center py-6">
							<Spin size="large" />
						</div>
					)}

					{/* Order Details */}
					{!isFetching && !isError && orderData && (
						<div className="mt-2">
							<OrderDetailCard orderData={orderData} />
						</div>
					)}
				</div>
			</ConfigProvider>
		</div>
	);
}
