/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import type React from "react";
import { useForm } from "react-hook-form";
import { z } from "zod";
import money from "@/assets/images/dashboard/Group (3).png";
import {
	useCreateBalanceTransferMutation,
	useGetAllBalanceTransfersQuery,
} from "@/redux/features/balanceTransferlistsApi";
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

// ✅ Infer Type
type TransferFormValues = z.infer<typeof transferSchema>;

export function TransferForm() {
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

	const [createBalanceTransfer] = useCreateBalanceTransferMutation();
	const { data: balanceTransfersData, isLoading } =
		useGetAllBalanceTransfersQuery(undefined);

	const onSubmit = async (data: TransferFormValues) => {
		try {
			const formData = new FormData();
			formData.append("withdrew_amount", data.amount);
			formData.append("to_account_number", data.accountNumber);

			if (data.additionalInfo) {
				formData.append("to_additional_info", data.additionalInfo);
			}

			await handleAsyncWithToast(async () => {
				return createBalanceTransfer(formData);
			});

			reset();
		} catch (error: any) {
			console.error("❌ Transfer failed:", error);
		}
	};

	return (
		<>
			<div className="w-full md:p-6">
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
							How much money do you want to transfer?
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

					{/* Account Number */}
					<div className="space-y-2">
						<label className="text-sm font-medium text-gray-900">
							To Send Money to Someone
						</label>
						<input
							type="text"
							placeholder="Enter the account number"
							className="h-12 ps-4 border-gray-200 w-full rounded-md border"
							{...register("accountNumber")}
						/>
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
					>
						Transfer Now
					</button>
				</form>
			</div>

			{/* Transfer History */}
			<div className="w-full m-4 lg:m-6 md:bg-white rounded-md md:p-8">
				<div className="p-0">
					<h2 className="text-lg font-semibold text-gray-900">
						Transfer history
					</h2>
					<div className="flex items-center justify-between p-4 border-b border-gray-200">
						<h2 className="text-sm font-medium text-gray-900">All transfers</h2>
					</div>

					<div className="overflow-x-auto">
						<table className="w-full">
							<thead>
								<tr>
									<th className="text-left p-4 text-sm font-medium text-gray-600">
										ID
									</th>
									<th className="text-left p-4 text-sm font-medium text-gray-600">
										Date
									</th>
									<th className="text-left p-4 text-sm font-medium text-gray-600">
										To Account
									</th>
									<th className="text-left p-4 text-sm font-medium text-gray-600">
										Amount
									</th>
									<th className="text-left p-4 text-sm font-medium text-gray-600">
										Status
									</th>
								</tr>
							</thead>

							<tbody>
								{isLoading ? (
									<tr>
										<td
											colSpan={5}
											className="p-4 text-center text-sm text-gray-500"
										>
											Loading transfer history...
										</td>
									</tr>
								) : balanceTransfersData?.data?.length > 0 ? (
									balanceTransfersData.data.map((transfer: any) => (
										<tr
											key={transfer.id}
											className="border-b border-gray-100 hover:bg-gray-50"
										>
											<td className="p-4 text-sm text-gray-900">
												#{transfer.id}
											</td>
											<td className="p-4 text-sm text-gray-900">
												{new Date(transfer.created_at).toLocaleDateString()}
											</td>
											<td className="p-4 text-sm text-gray-600">
												{transfer.to_account_number}
											</td>
											<td className="p-4 text-sm text-gray-600">
												৳{transfer.withdrew_amount}
											</td>
											<td className="p-4 text-sm text-gray-600">
												<span
													className={`inline-block px-3 py-1 rounded-full text-xs font-semibold ${
														transfer.status === "Paid"
															? "bg-green-100 text-green-800"
															: transfer.status === "Pending"
																? "bg-yellow-100 text-yellow-800"
																: "bg-red-100 text-red-800"
													}`}
												>
													{transfer.status}
												</span>
											</td>
										</tr>
									))
								) : (
									<tr>
										<td
											colSpan={5}
											className="p-4 text-center text-sm text-gray-500"
										>
											No transfer history found.
										</td>
									</tr>
								)}
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</>
	);
}
