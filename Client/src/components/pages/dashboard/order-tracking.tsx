/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { Alert, Button, Card, ConfigProvider, Input, Spin } from "antd";
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
		<div className="m-4 lg:m-6 md:bg-white rounded-md">
			<div className="w-full md:shadow-sm md:p-8 bg-white p-4 rounded-md">
				<h1 className="text-2xl font-semibold text-gray-900 mb-8">
					Track your order
				</h1>

				<div className="space-y-6">
					{/* Input */}
					<div className="space-y-2">
						<label
							htmlFor="orderId"
							className="text-sm font-medium text-gray-700"
						>
							Order ID
						</label>

						<ConfigProvider
							theme={{
								token: {
									colorPrimary: "#E5005F",
								},
							}}
						>
							<Input
								id="orderId"
								placeholder="Enter the order ID"
								value={orderId}
								onChange={(e) => setOrderId(e.target.value)}
								size="large"
							/>
						</ConfigProvider>
					</div>

					{/* Button */}
					<Button
						type="primary"
						size="large"
						block
						style={{ backgroundColor: "#E5005F" }}
						onClick={handleSearch}
					>
						Search now
					</Button>

					{/* Error */}
					{(showError || isError) && (
						<Alert
							message="No Records Found. Please call our customer care or use Live Chat"
							type="error"
							showIcon
						/>
					)}

					{/* Loader */}
					{isFetching && (
						<div className="flex justify-center py-6">
							<Spin size="large" />
						</div>
					)}

					{/* Order Details */}
					{!isFetching && orderData && (
						<Card title={`Order: ${orderData.invoiceID}`} className="mt-4">
							<p>
								<strong>Status:</strong> {orderData.status}
							</p>
							<p>
								<strong>Order Date:</strong> {orderData.orderDate}
							</p>
							<p>
								<strong>Delivery Charge:</strong> ৳{orderData.deliveryCharge}
							</p>

							<br />

							<h3 className="text-lg font-semibold">Customer Details</h3>
							<p>Name: {orderData.customers?.customerName}</p>
							<p>Phone: {orderData.customers?.customerPhone}</p>
							<p>Address: {orderData.customers?.customerAddress}</p>

							<br />

							<h3 className="text-lg font-semibold">Products</h3>
							<ul className="list-disc ml-5">
								{orderData.orderproducts?.map((item: any) => (
									<li key={item.id}>
										{item.productName} — Qty: {item.quantity} — Price: ৳
										{item.productPrice}
									</li>
								))}
							</ul>

							<br />

							<h3 className="text-lg font-semibold">Courier</h3>
							<p>{orderData.couriers?.courierName}</p>
						</Card>
					)}
				</div>
			</div>
		</div>
	);
}
