/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { useEffect, useState } from "react";
import { useForm } from "react-hook-form";
import { z } from "zod";

import money from "@/assets/images/dashboard/Group (3).png";
import { getImageUrl } from "@/lib/utils";
import {
	useCreateWithdrawRequestMutation,
	useGetAllWithdrawMethodsQuery,
	useGetWithdrawListQuery,
} from "@/redux/features/withdrawApi";
import { handleAsyncWithToast } from "@/utils/handleAsyncWithToast";

// ✅ Zod Schema
const transferSchema = z.object({
	amount: z
		.string()
		.nonempty("Amount is required")
		.refine((val) => !isNaN(Number(val)) && Number(val) > 0, {
			message: "Amount must be a positive number",
		}),
	accountNumber: z
		.string()
		.nonempty("Account number is required")
		.min(6, "Account number must be at least 6 digits"),
	additionalInfo: z
		.string()
		.max(200, "Additional info must be less than 200 characters")
		.optional(),
});

// ✅ Infer Type from Schema
type TransferFormValues = z.infer<typeof transferSchema>;

export function WithdrawIncome() {
	const [selectedMethod, setSelectedMethod] = useState<string>("");

	const { data: withdrawMethodsData, isLoading: methodsLoading } =
		useGetAllWithdrawMethodsQuery(undefined);
	const { data: withdrawListData, isLoading: listLoading } =
		useGetWithdrawListQuery(undefined);
	const [createWithdrawRequest, { isLoading: creatingRequest }] =
		useCreateWithdrawRequestMutation();

	const {
		register,
		handleSubmit,
		formState: { errors },
		reset,
	} = useForm<TransferFormValues>({
		resolver: zodResolver(transferSchema),
		defaultValues: {
			amount: "",
			accountNumber: "",
			additionalInfo: "",
		},
	});

	const onSubmit = async (data: TransferFormValues) => {
		if (!selectedMethod) return;

		// Find method to get its ID
		const method = withdrawMethods.find(
			(m: any) => m.paymentTypeName === selectedMethod,
		);

		if (!method) {
			alert("Please select a valid withdrawal method.");
			return;
		}

		// ✅ Build FormData
		const formData = new FormData();
		formData.append("withdrew_amount", data.amount);
		formData.append("paymenttype_id", method.id.toString());
		formData.append("to_account_number", data.accountNumber);
		if (data.additionalInfo) {
			formData.append("to_additional_info", data.additionalInfo);
		}

		try {
			await handleAsyncWithToast(async () => {
				return await createWithdrawRequest(formData);
			});
			reset();
		} catch (error: any) {
			console.error("Error submitting withdraw request:", error);
			alert(error?.data?.message || "Failed to submit withdraw request");
		}
	};

	const withdrawMethods = withdrawMethodsData?.data || [];
	const withdrawList = withdrawListData?.data || [];

	// Set default selected method after API loads
	useEffect(() => {
		if (!selectedMethod && withdrawMethods.length > 0) {
			setSelectedMethod(withdrawMethods[0].paymentTypeName);
		}
	}, [withdrawMethods, selectedMethod]);

	return (
		<>
			{/* Withdraw Form */}
			<div className="w-full">
				{/* Balance Info */}
				<div className="border-0 shadow-none bg-gray-100 p-4 rounded-md">
					<div className="flex items-start justify-between">
						<div>
							<p className="text-sm text-gray-600">Your total balance</p>
							<p className="text-2xl font-semibold text-[#E5005F] mb-4">
								৳ pending for api
							</p>
							<p className="text-sm text-green-600">
								Your last withdraw: ৳ pending for api
							</p>
						</div>
						<img src={money.src} alt="Money" className="w-6 h-6" />
					</div>
				</div>

				{/* Form */}
				<form onSubmit={handleSubmit(onSubmit)} className="space-y-6 mt-6">
					{/* Amount */}
					<div className="space-y-2">
						<label className="text-sm font-medium text-gray-900">
							How much money do you want to withdraw?
						</label>
						<div className="relative flex items-center gap-2">
							<div className="bg-gray-200/80 h-12 w-14 flex items-center justify-center rounded-md">
								৳
							</div>
							<input
								type="text"
								placeholder="Enter Your Amount"
								className="pl-4 h-12 border-gray-200 w-full rounded-md border"
								{...register("amount")}
							/>
						</div>
						{errors.amount && (
							<p className="text-sm text-red-500">{errors.amount.message}</p>
						)}
					</div>

					{/* Withdrawal Method */}
					<div className="space-y-2">
						<label className="text-sm font-medium text-gray-900">
							Select withdrawal method
						</label>
						<div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
							{methodsLoading ? (
								<p>Loading methods...</p>
							) : (
								withdrawMethods.map((method: any) => (
									<div
										key={method.id}
										className={`flex items-center gap-2 cursor-pointer px-3 py-2.5 rounded-lg border transition-colors h-12 ${selectedMethod === method.paymentTypeName
											? "border-[#E5005F] bg-[#FDEDF4]"
											: "border-gray-200 hover:border-gray-300"
											}`}
										onClick={() => setSelectedMethod(method.paymentTypeName)}
									>
										<img
											src={getImageUrl(method.icon)}
											alt={method?.paymentTypeName || "Payment method"}
											className="w-7 h-7 object-contain flex-shrink-0"
										/>
										<span className="text-sm font-medium truncate">
											{method.paymentTypeName}
										</span>
									</div>
								))
							)}
						</div>
					</div>

					{/* Account Info */}
					<div className="space-y-2">
						<label className="text-sm font-medium text-gray-900">
							Please provide your payment information
						</label>
						<div className="relative flex items-center gap-2">
							<div className="bg-gray-200/80 h-12 w-16 flex items-center justify-center rounded-md text-sm">
								{selectedMethod || "-"}
							</div>
							<input
								type="text"
								placeholder="Enter the account number"
								className="pl-4 h-12 border-gray-200 w-full rounded-md border"
								{...register("accountNumber")}
							/>
						</div>
						{errors.accountNumber && (
							<p className="text-sm text-red-500">
								{errors.accountNumber.message}
							</p>
						)}
					</div>

					{/* Additional Info */}
					<div className="space-y-2">
						<label className="text-sm font-medium text-gray-900">
							Additional information
						</label>
						<textarea
							placeholder="If needed enter additional support"
							className="min-h-[80px] ps-4 pt-1 border-gray-200 w-full rounded-md border resize-none"
							{...register("additionalInfo")}
						/>
						{errors.additionalInfo && (
							<p className="text-sm text-red-500">
								{errors.additionalInfo.message}
							</p>
						)}
					</div>

					{/* Submit Button */}
					<button
						type="submit"
						className="w-full h-12 bg-[#E5005F] hover:bg-pink-600 !text-white font-medium rounded-md cursor-pointer"
						disabled={creatingRequest}
					>
						{creatingRequest ? "Submitting..." : "Request for withdraw"}
					</button>
				</form>
			</div>

			{/* Payment History */}
			<div className="w-full mt-4 sm:mt-6 pt-4 sm:pt-6 border-t border-gray-100">
				<h2 className="text-base sm:text-lg font-semibold text-gray-900 mb-3 sm:mb-4">
					Payment History
				</h2>

				{/* Loading */}
				{listLoading && (
					<div className="py-10 text-center text-gray-500 text-sm">
						Loading payment history...
					</div>
				)}

				{/* Mobile Card Layout */}
				{!listLoading && (
					<div className="md:hidden space-y-3">
						{withdrawList.length > 0 ? (
							withdrawList.map((withdraw: any) => (
								<div
									key={withdraw.id}
									className="bg-gray-50/60 border border-gray-100 rounded-xl p-3"
								>
									<div className="flex items-center justify-between mb-1">
										<p className="text-xs text-gray-500">#{withdraw.id}</p>
										<span
											className={`px-2 py-0.5 rounded-full text-[10px] font-medium ${withdraw.status === "Completed"
												? "bg-green-50 text-green-700 border border-green-200"
												: withdraw.status === "Pending"
													? "bg-amber-50 text-amber-700 border border-amber-200"
													: "bg-red-50 text-red-700 border border-red-200"
												}`}
										>
											{withdraw.status}
										</span>
									</div>

									<p className="text-lg font-bold text-gray-900 mb-1.5">৳ {withdraw.withdrew_amount}</p>

									<div className="flex items-center justify-between text-xs text-gray-400">
										<span>{withdraw.paymenttype_name}</span>
										<span>{new Date(withdraw.created_at).toLocaleDateString()}</span>
									</div>
								</div>
							))
						) : (
							<div className="py-10 text-center text-gray-400 text-sm">
								No withdrawal history found.
							</div>
						)}
					</div>
				)}

				{/* Desktop Table Layout */}
				{!listLoading && (
					<div className="hidden md:block overflow-x-auto">
						<table className="w-full">
							<thead>
								<tr className="bg-gray-50/80">
									<th className="p-4 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">
										Invoice ID
									</th>
									<th className="p-4 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">
										Date
									</th>
									<th className="p-4 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">
										Payment Method
									</th>
									<th className="p-4 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">
										Amount
									</th>
									<th className="p-4 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">
										Status
									</th>
								</tr>
							</thead>

							<tbody>
								{withdrawList.length > 0 ? (
									withdrawList.map((withdraw: any) => (
										<tr
											key={withdraw.id}
											className="border-b border-gray-50 hover:bg-gray-50/50 transition-colors"
										>
											<td className="p-4 text-sm font-medium text-gray-900">
												#{withdraw.id}
											</td>
											<td className="p-4 text-sm text-gray-500">
												{new Date(withdraw.created_at).toLocaleDateString()}
											</td>
											<td className="p-4 text-sm text-gray-700">
												{withdraw.paymenttype_name}
											</td>
											<td className="p-4 text-sm font-bold text-gray-900">
												৳ {withdraw.withdrew_amount}
											</td>
											<td className="p-4">
												<span
													className={`px-2.5 py-1 rounded-full text-xs font-medium ${withdraw.status === "Completed"
														? "bg-green-50 text-green-700 border border-green-200"
														: withdraw.status === "Pending"
															? "bg-amber-50 text-amber-700 border border-amber-200"
															: "bg-red-50 text-red-700 border border-red-200"
														}`}
												>
													{withdraw.status}
												</span>
											</td>
										</tr>
									))
								) : (
									<tr>
										<td colSpan={5} className="py-12 text-center text-gray-400 text-sm">
											No withdrawal history found.
										</td>
									</tr>
								)}
							</tbody>
						</table>
					</div>
				)}
			</div>
		</>
	);
}
