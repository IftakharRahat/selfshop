/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { Alert, ConfigProvider, Form, Input, Modal } from "antd";
import { useState } from "react";
import { Search, ShieldAlert, ShieldCheck, Loader2 } from "lucide-react";
import { toast } from "sonner";
import {
	useCreateStoreFraudNumberMutation,
	useGetCheckFraudQuery,
} from "@/redux/features/fraudCustomer/fraudCustomerApi";

export default function FraudChecker() {
	const [inputValue, setInputValue] = useState("");
	const [phoneNumber, setPhoneNumber] = useState("");
	const [isModalOpen, setIsModalOpen] = useState(false);
	const [hasSearched, setHasSearched] = useState(false);

	const { data, isFetching, isError } = useGetCheckFraudQuery(phoneNumber, {
		skip: !phoneNumber,
	});

	const [createStoreFraudNumber, { isLoading: isReporting }] =
		useCreateStoreFraudNumberMutation();

	const handleSearch = () => {
		const trimmed = inputValue.trim();
		if (!trimmed) {
			toast.error("Please enter a phone number.");
			return;
		}
		setPhoneNumber(trimmed);
		setHasSearched(true);
	};

	const handleCreate = async (values: any) => {
		try {
			const result: any = await createStoreFraudNumber(values);
			if (result?.data?.status) {
				toast.success("Fraud number reported successfully!");
				setIsModalOpen(false);
			} else {
				toast.error("Failed to report. Please try again.");
			}
		} catch {
			toast.error("An error occurred. Please try again.");
		}
	};

	const fraudRecords = data?.data || [];
	const hasFraudRecords = hasSearched && !isFetching && !isError && fraudRecords.length > 0;
	const isClean = hasSearched && !isFetching && !isError && phoneNumber && fraudRecords.length === 0;

	return (
		<div className="m-3 sm:m-4 lg:m-6 bg-white rounded-xl border border-gray-100 shadow-sm p-4 sm:p-5 lg:p-8 mb-24">
			{/* Header */}
			<div className="flex items-center justify-between mb-6 sm:mb-8">
				<div>
					<h1 className="text-xl sm:text-2xl font-semibold text-gray-900">
						Fraud Checker
					</h1>
					<p className="text-sm text-gray-500 mt-1">
						Check if a phone number has been reported as fraudulent
					</p>
				</div>
				<button
					onClick={() => setIsModalOpen(true)}
					className="bg-[#E5005F] hover:bg-pink-600 !text-white text-sm font-medium rounded-md px-4 py-2 cursor-pointer transition-colors flex items-center gap-2"
				>
					<ShieldAlert className="w-4 h-4" />
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
						<div className="flex gap-3">
							<Input
								id="phoneNumber"
								placeholder="Enter the phone number"
								value={inputValue}
								onChange={(e) => setInputValue(e.target.value)}
								onPressEnter={handleSearch}
								size="large"
								className="flex-1"
							/>
						</div>
					</div>

					{/* Button */}
					<button
						onClick={handleSearch}
						disabled={isFetching}
						className="w-full bg-[#E5005F] hover:bg-pink-600 disabled:opacity-60 !text-white py-3 text-base font-medium rounded-md cursor-pointer transition-colors flex items-center justify-center gap-2"
					>
						{isFetching ? (
							<>
								<Loader2 className="w-5 h-5 animate-spin" />
								Checking...
							</>
						) : (
							<>
								<Search className="w-5 h-5" />
								Check
							</>
						)}
					</button>

					{/* Fraud Results */}
					{hasFraudRecords && (
						<div className="space-y-3 pt-2">
							<div className="flex items-center gap-2">
								<ShieldAlert className="w-5 h-5 text-red-600" />
								<h2 className="text-sm font-semibold text-red-600 uppercase tracking-wide">
									⚠ {fraudRecords.length} Fraud Record{fraudRecords.length > 1 ? "s" : ""} Found
								</h2>
							</div>
							{fraudRecords.map((item: any) => (
								<div
									key={item.id}
									className="bg-red-50/60 border border-red-100 rounded-xl p-4"
								>
									<div className="flex items-center justify-between mb-2">
										<span className="text-sm font-semibold text-gray-900">
											📱 {item.phone}
										</span>
										<span className="px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700 border border-red-200">
											{item.status || "Reported"}
										</span>
									</div>
									<p className="text-sm text-gray-600 mb-2">
										{item.message || "No details provided"}
									</p>
									<p className="text-xs text-gray-400">
										Reported on: {new Date(item.created_at).toLocaleString("en-US", {
											year: "numeric",
											month: "short",
											day: "numeric",
											hour: "2-digit",
											minute: "2-digit",
										})}
									</p>
								</div>
							))}
						</div>
					)}

					{/* No match — safe */}
					{isClean && (
						<div className="mt-2 bg-green-50 border border-green-200 rounded-xl p-6 text-center">
							<ShieldCheck className="w-12 h-12 text-green-500 mx-auto mb-3" />
							<h3 className="font-semibold text-green-700 mb-1">No Fraud Record Found</h3>
							<p className="text-sm text-green-600">
								The phone number <span className="font-mono font-semibold">{phoneNumber}</span> has not been reported as fraudulent.
							</p>
						</div>
					)}

					{/* Error state */}
					{isError && hasSearched && (
						<div className="mt-2">
							<Alert
								message="Failed to check this number. Please try again."
								type="error"
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
							label="Phone Number"
							name="phone"
							rules={[
								{ required: true, message: "Phone is required" },
								{ min: 6, message: "Enter a valid phone number" },
							]}
						>
							<Input size="large" placeholder="e.g. 01XXXXXXXXX" />
						</Form.Item>

						<Form.Item
							label="Reason / Details"
							name="message"
							rules={[{ required: true, message: "Please describe the fraud" }]}
						>
							<Input.TextArea
								rows={3}
								placeholder="Describe what happened (e.g. scam order, fake payment, etc.)"
							/>
						</Form.Item>

						<button
							type="submit"
							disabled={isReporting}
							className="w-full bg-[#E5005F] hover:bg-pink-600 disabled:opacity-60 !text-white py-3 text-base font-medium rounded-md cursor-pointer transition-colors flex items-center justify-center gap-2"
						>
							{isReporting ? (
								<>
									<Loader2 className="w-5 h-5 animate-spin" />
									Submitting...
								</>
							) : (
								"Submit Report"
							)}
						</button>
					</Form>
				</Modal>
			</ConfigProvider>
		</div>
	);
}
