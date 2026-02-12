/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { Alert, ConfigProvider, Input, Spin } from "antd";
import { useState } from "react";
import { useTrackOrderQuery } from "@/redux/features/orderApi";

export default function OrderTracking() {
	const [orderId, setOrderId] = useState("");
	const [triggerId, setTriggerId] = useState(""); // for API call
	const [showError, setShowError] = useState(false);

	// API call only when triggerId changes
	const { data, isFetching, isError } = useTrackOrderQuery(triggerId, {
		skip: !triggerId,
	});

	const orderData = data?.data;

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
						<div className="bg-gray-50/60 border border-gray-100 rounded-xl p-4 mt-2">
							{/* Header: Invoice + Status */}
							<div className="flex items-center justify-between mb-3">
								<div>
									<p className="text-sm font-semibold text-gray-900">Order: {orderData.invoiceID}</p>
									<p className="text-xs text-gray-400 mt-0.5">{orderData.orderDate}</p>
								</div>
								<span className="px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
									{orderData.status}
								</span>
							</div>

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
										className="flex items-center justify-between text-sm py-1.5"
									>
										<span className="text-gray-700">{item.productName} <span className="text-gray-400">×{item.quantity}</span></span>
										<span className="font-medium text-gray-900">৳ {item.productPrice}</span>
									</div>
								))}
							</div>
						</div>
					)}
				</div>
			</ConfigProvider>
		</div>
	);
}
