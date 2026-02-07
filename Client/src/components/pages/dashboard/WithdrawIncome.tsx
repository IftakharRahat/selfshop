/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { useState, useEffect } from "react";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";

import money from "@/assets/images/dashboard/Group (3).png";
import { useCreateWithdrawRequestMutation, useGetAllWithdrawMethodsQuery, useGetWithdrawListQuery } from "@/redux/features/withdrawApi";
import { handleAsyncWithToast } from "@/utils/handleAsyncWithToast";

// ✅ Zod Schema
const transferSchema = z.object({
  amount: z
    .string()
    .nonempty("Amount is required")
    .refine((val) => !isNaN(Number(val)) && Number(val) > 0, {
      message: "Amount must be a positive number",
    }),
  accountNumber: z.string().nonempty("Account number is required").min(6, "Account number must be at least 6 digits"),
  additionalInfo: z.string().max(200, "Additional info must be less than 200 characters").optional(),
});

// ✅ Infer Type from Schema
type TransferFormValues = z.infer<typeof transferSchema>;

export function WithdrawIncome() {
  const [selectedMethod, setSelectedMethod] = useState<string>("");

  const { data: withdrawMethodsData, isLoading: methodsLoading } = useGetAllWithdrawMethodsQuery(undefined);
  const { data: withdrawListData, isLoading: listLoading } = useGetWithdrawListQuery(undefined);
  const [createWithdrawRequest, { isLoading: creatingRequest }] = useCreateWithdrawRequestMutation();

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
    const method = withdrawMethods.find((m: any) => m.paymentTypeName === selectedMethod);

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
      <div className="w-full m-4 lg:m-6 md:bg-white rounded-md md:p-8">
        {/* Balance Info */}
        <div className="border-0 shadow-none bg-gray-100 p-4 rounded-md">
          <div className="flex items-start justify-between">
            <div>
              <p className="text-sm text-gray-600">Your total balance</p>
              <p className="text-2xl font-semibold text-[#E5005F] mb-4">৳ pending for api</p>
              <p className="text-sm text-green-600">Your last withdraw: ৳ pending for api</p>
            </div>
            <img src={money.src} alt="Money" className="w-6 h-6" />
          </div>
        </div>

        {/* Form */}
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-6 mt-6">
          {/* Amount */}
          <div className="space-y-2">
            <label className="text-sm font-medium text-gray-900">How much money do you want to withdraw?</label>
            <div className="relative flex items-center gap-2">
              <div className="bg-gray-200/80 h-12 w-14 flex items-center justify-center rounded-md">৳</div>
              <input
                type="text"
                placeholder="Enter Your Amount"
                className="pl-4 h-12 border-gray-200 w-full rounded-md border"
                {...register("amount")}
              />
            </div>
            {errors.amount && <p className="text-sm text-red-500">{errors.amount.message}</p>}
          </div>

          {/* Withdrawal Method */}
          <div className="space-y-2">
            <label className="text-sm font-medium text-gray-900">Select withdrawal method</label>
            <div className="flex items-center gap-4 flex-wrap">
              {methodsLoading ? (
                <p>Loading methods...</p>
              ) : (
                withdrawMethods.map((method: any) => (
                  <div
                    key={method.id}
                    className={`flex items-center gap-2 cursor-pointer p-2 rounded-md border ${
                      selectedMethod === method.paymentTypeName ? "border-[#E5005F] bg-[#FDEDF4]" : "border-gray-300"
                    }`}
                    onClick={() => setSelectedMethod(method.paymentTypeName)}
                  >
                    <img src={`https://api-v1.selfshop.com.bd/${method.icon}`} alt={method.paymentTypeName} className="w-8 h-8" />
                    <span className="text-sm font-medium">{method.paymentTypeName}</span>
                  </div>
                ))
              )}
            </div>
          </div>

          {/* Account Info */}
          <div className="space-y-2">
            <label className="text-sm font-medium text-gray-900">Please provide your payment information here</label>
            <div className="relative flex items-center gap-2">
              <div className="bg-gray-200/80 h-12 w-16 flex items-center justify-center rounded-md text-sm">{selectedMethod || "-"}</div>
              <input
                type="text"
                placeholder="Enter the account number"
                className="pl-4 h-12 border-gray-200 w-full rounded-md border"
                {...register("accountNumber")}
              />
            </div>
            {errors.accountNumber && <p className="text-sm text-red-500">{errors.accountNumber.message}</p>}
          </div>

          {/* Additional Info */}
          <div className="space-y-2">
            <label className="text-sm font-medium text-gray-900">Additional information</label>
            <textarea
              placeholder="If needed enter additional support"
              className="min-h-[80px] ps-4 pt-1 border-gray-200 w-full rounded-md border resize-none"
              {...register("additionalInfo")}
            />
            {errors.additionalInfo && <p className="text-sm text-red-500">{errors.additionalInfo.message}</p>}
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
      <div className="w-full m-4 lg:m-6 md:bg-white rounded-md md:p-8">
        <div className="p-0">
          <h2 className="text-lg font-semibold text-gray-900">Payment history</h2>
          <div className="flex items-center justify-between p-4 border-b border-gray-200">
            <h2 className="text-sm font-medium text-gray-900">All incomes</h2>
          </div>

          {/* Desktop Table */}
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr>
                  <th className="text-left p-4 text-sm font-medium text-gray-600">Invoice ID</th>
                  <th className="text-left p-4 text-sm font-medium text-gray-600">Date</th>
                  <th className="text-left p-4 text-sm font-medium text-gray-600">Payment Method</th>
                  <th className="text-left p-4 text-sm font-medium text-gray-600">Amount</th>
                  <th className="text-left p-4 text-sm font-medium text-gray-600">Status</th>
                </tr>
              </thead>
              <tbody>
                {listLoading ? (
                  <tr>
                    <td colSpan={5} className="p-4 text-center text-sm text-gray-500">
                      Loading payment history...
                    </td>
                  </tr>
                ) : withdrawList.length > 0 ? (
                  withdrawList.map((withdraw: any) => (
                    <tr key={withdraw.id} className="border-b border-gray-100 hover:bg-gray-50">
                      <td className="p-4 text-sm text-gray-900">#{withdraw.id}</td>
                      <td className="p-4 text-sm text-gray-900">{new Date(withdraw.created_at).toLocaleDateString()}</td>
                      <td className="p-4 text-sm text-gray-600">{withdraw.paymenttype_name}</td>
                      <td className="p-4 text-sm text-gray-600">৳{withdraw.withdrew_amount}</td>
                      <td className="p-4 text-sm text-gray-600">
                        <span
                          className={`inline-block px-3 py-1 rounded-full text-xs font-semibold ${
                            withdraw.status === "Completed"
                              ? "bg-green-100 text-green-800"
                              : withdraw.status === "Pending"
                              ? "bg-yellow-100 text-yellow-800"
                              : "bg-red-100 text-red-800"
                          }`}
                        >
                          {withdraw.status}
                        </span>
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan={5} className="p-4 text-center text-sm text-gray-500">
                      No withdrawal history found.
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
