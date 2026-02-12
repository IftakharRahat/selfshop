/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { Alert, Button, ConfigProvider, Form, Input, Modal } from "antd";
import { useState } from "react";
import {
	useCreateStoreFraudNumberMutation,
	useGetCheckFraudQuery,
} from "@/redux/features/fraudCustomer/fraudCustomerApi";

export default function FraudChecker() {
	const [orderId, setOrderId] = useState("");
	const [phoneNumber, setPhoneNumber] = useState("");
	const [isModalOpen, setIsModalOpen] = useState(false);

	const { data, isFetching, isError } = useGetCheckFraudQuery(phoneNumber, {
		skip: !phoneNumber,
	});

	const [createStoreFraudNumber] = useCreateStoreFraudNumberMutation();

	const handleSearch = () => {
		setPhoneNumber(orderId);
	};

	const handleCreate = async (values: any) => {
		await createStoreFraudNumber(values);
		setIsModalOpen(false);
	};

	return (
		<div className="m-3 sm:m-4 lg:m-6 bg-white rounded-xl border border-gray-100 shadow-sm p-4 sm:p-5 lg:p-8 mb-24">
			{/* Header */}
			<div className="flex items-center justify-between mb-6 sm:mb-8">
				<h1 className="text-xl sm:text-2xl font-semibold text-gray-900">
					Fraud Checker
				</h1>
				<button
					onClick={() => setIsModalOpen(true)}
					className="bg-[#E5005F] hover:bg-pink-600 !text-white text-sm font-medium rounded-md px-4 py-2 cursor-pointer transition-colors"
				>
					+ Report Fraud
				</button>
			</div>

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
							htmlFor="phoneNumber"
							className="text-sm font-medium text-gray-700"
						>
							Phone Number
						</label>
						<Input
							id="phoneNumber"
							placeholder="Enter the phone number"
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
						Check
					</button>

					{/* Fraud Results */}
					{!isFetching && !isError && data?.data?.length > 0 && (
						<div className="space-y-3 pt-2">
							<h2 className="text-sm font-semibold text-red-600 uppercase tracking-wide">
								⚠ Fraud Record Found
							</h2>
							{data.data.map((item: any) => (
								<div
									key={item.id}
									className="bg-red-50/60 border border-red-100 rounded-xl p-4"
								>
									<div className="flex items-center justify-between mb-2">
										<span className="text-sm font-semibold text-gray-900">{item.phone}</span>
										<span className="px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700 border border-red-200">
											{item.status}
										</span>
									</div>
									<p className="text-sm text-gray-600 mb-2">{item.message}</p>
									<p className="text-xs text-gray-400">
										{new Date(item.created_at).toLocaleString()}
									</p>
								</div>
							))}
						</div>
					)}

					{/* No match */}
					{!isFetching && phoneNumber && data?.data?.length === 0 && (
						<div className="mt-2">
							<Alert
								message="No fraud record found for this phone number."
								type="success"
								showIcon
							/>
						</div>
					)}
				</div>

				{/* Report Modal */}
				<Modal
					title="Report Fraud Number"
					open={isModalOpen}
					onCancel={() => setIsModalOpen(false)}
					footer={null}
					centered
				>
					<Form layout="vertical" onFinish={handleCreate} className="pt-4">
						<Form.Item
							label="Phone"
							name="phone"
							rules={[{ required: true, message: "Phone is required" }]}
						>
							<Input size="large" placeholder="Enter phone number" />
						</Form.Item>

						<Form.Item
							label="Message"
							name="message"
							rules={[{ required: true, message: "Message is required" }]}
						>
							<Input.TextArea rows={3} placeholder="Enter message" />
						</Form.Item>

						<button
							type="submit"
							className="w-full bg-[#E5005F] hover:bg-pink-600 !text-white py-3 text-base font-medium rounded-md cursor-pointer transition-colors"
						>
							Submit
						</button>
					</Form>
				</Modal>
			</ConfigProvider>
		</div>
	);
}
