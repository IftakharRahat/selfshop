"use client";

import { useState } from "react";
import Link from "next/link";
import WithVendorAuth from "../WithVendorAuth";
import {
	useGetVendorEarningsSummaryQuery,
	useGetVendorPayoutAccountsQuery,
	useGetVendorPayoutRequestsQuery,
	useGetVendorPayoutsQuery,
	useCreateVendorPayoutRequestMutation,
} from "@/redux/api/vendorApi";
import { toast } from "sonner";

export default function VendorPayoutsPage() {
	const [amount, setAmount] = useState("");
	const [payoutAccountId, setPayoutAccountId] = useState<number | "">("");
	const [reqPage, setReqPage] = useState(1);
	const [histPage, setHistPage] = useState(1);

	const { data: summaryData } = useGetVendorEarningsSummaryQuery(undefined);
	const { data: accountsData } = useGetVendorPayoutAccountsQuery(undefined);
	const { data: requestsData, isLoading: requestsLoading } = useGetVendorPayoutRequestsQuery({ page: reqPage, per_page: 10 });
	const { data: payoutsData, isLoading: payoutsLoading } = useGetVendorPayoutsQuery({ page: histPage, per_page: 10 });
	const [createRequest, { isLoading: submitting }] = useCreateVendorPayoutRequestMutation();

	const summary = summaryData?.data;
	const availableBalance = summary ? Number(summary.available_balance) : 0;
	const pendingRequestAmount = summary ? Number(summary.pending_payout_request_amount || 0) : 0;
	const accounts = accountsData?.data?.payout_accounts ?? [];
	const defaultAccount = accounts.find((a: { is_default: boolean }) => a.is_default) ?? accounts[0];
	const requests = requestsData?.data?.payout_requests ?? [];
	const reqPagination = requestsData?.data?.pagination;
	const payouts = payoutsData?.data?.payouts ?? [];
	const histPagination = payoutsData?.data?.pagination;

	const handleRequest = async () => {
		const num = parseFloat(amount);
		if (isNaN(num) || num <= 0) {
			toast.error("Enter a valid amount");
			return;
		}
		if (num > availableBalance) {
			toast.error("Amount cannot exceed available balance");
			return;
		}
		if (pendingRequestAmount > 0) {
			toast.error("You already have a pending payout request");
			return;
		}
		try {
			await createRequest({
				amount: num,
				payout_account_id: payoutAccountId === "" ? (defaultAccount?.id ?? undefined) : (payoutAccountId as number),
			}).unwrap();
			toast.success("Payout request submitted");
			setAmount("");
		} catch (err: unknown) {
			const msg = (err as { data?: { message?: string } })?.data?.message || "Failed to submit request";
			toast.error(msg);
		}
	};

	return (
		<WithVendorAuth>
			<div className="space-y-6">
				<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100 flex items-center justify-between gap-4">
					<div>
						<h1 className="text-2xl font-bold text-gray-900 mb-1">Payouts</h1>
						<p className="text-sm text-gray-600">
							Request a payout to your bank or mobile wallet.
						</p>
					</div>
					<Link
						href="/vendor/earnings"
						className="inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50"
					>
						Earnings
					</Link>
				</div>

				{/* Balance & Request */}
				<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
					<div className="flex flex-col sm:flex-row sm:items-end gap-4">
						<div className="flex-1">
							<p className="text-sm font-medium text-gray-600 mb-1">Available balance</p>
							<p className="text-2xl font-bold text-green-700">৳{availableBalance.toLocaleString()}</p>
							{pendingRequestAmount > 0 && (
								<p className="text-xs text-amber-600 mt-1">Pending request: ৳{pendingRequestAmount.toLocaleString()}</p>
							)}
						</div>
						<div className="flex flex-col sm:flex-row gap-3 flex-wrap">
							<div>
								<label className="block text-xs font-medium text-gray-500 mb-1">Amount (৳)</label>
								<input
									type="number"
									min="1"
									step="0.01"
									value={amount}
									onChange={(e) => setAmount(e.target.value)}
									placeholder="0"
									className="w-full sm:w-36 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
								/>
							</div>
							{accounts.length > 1 && (
								<div>
									<label className="block text-xs font-medium text-gray-500 mb-1">Payout to</label>
									<select
										value={payoutAccountId === "" ? (defaultAccount?.id ?? "") : payoutAccountId}
										onChange={(e) => setPayoutAccountId(e.target.value === "" ? "" : Number(e.target.value))}
										className="w-full sm:w-48 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
									>
										{accounts.map((a: { id: number; channel_type: string; account_name: string; account_number: string }) => (
											<option key={a.id} value={a.id}>
												{a.account_name || a.channel_type} •••{String(a.account_number).slice(-4)}
											</option>
										))}
									</select>
								</div>
							)}
							<button
								disabled={submitting || availableBalance <= 0 || pendingRequestAmount > 0}
								onClick={handleRequest}
								className="self-end sm:self-auto px-4 py-2 rounded-lg bg-[#2d2a5d] text-white text-sm font-medium hover:bg-[#252947] disabled:opacity-50 disabled:cursor-not-allowed"
							>
								{submitting ? "Submitting..." : "Request payout"}
							</button>
						</div>
					</div>
					{accounts.length === 0 && (
						<p className="text-sm text-amber-600 mt-3">
							<Link href="/vendor/payout-accounts" className="underline">Add a payout account</Link> to request payouts.
						</p>
					)}
				</div>

				{/* Payout requests */}
				<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
					<h2 className="text-sm font-semibold text-gray-800 mb-3">Payout requests</h2>
					{requestsLoading ? (
						<p className="text-sm text-gray-500 py-4">Loading...</p>
					) : requests.length === 0 ? (
						<p className="text-sm text-gray-500 py-4">No payout requests yet.</p>
					) : (
						<>
							<div className="overflow-x-auto">
								<table className="min-w-full text-sm">
									<thead className="bg-gray-50 text-gray-600">
										<tr>
											<th className="px-3 py-2 text-left font-medium">Date</th>
											<th className="px-3 py-2 text-right font-medium">Amount</th>
											<th className="px-3 py-2 text-left font-medium">Account</th>
											<th className="px-3 py-2 text-center font-medium">Status</th>
										</tr>
									</thead>
									<tbody className="divide-y divide-gray-100">
										{requests.map((r: { id: number; amount: number; status: string; created_at: string; payout_account?: { account_name: string; account_number: string } | null }) => (
											<tr key={r.id} className="hover:bg-gray-50">
												<td className="px-3 py-2 text-gray-600">{new Date(r.created_at).toLocaleDateString()}</td>
												<td className="px-3 py-2 text-right font-medium">৳{Number(r.amount).toLocaleString()}</td>
												<td className="px-3 py-2 text-gray-600">
													{r.payout_account ? `${r.payout_account.account_name} •••${String(r.payout_account.account_number).slice(-4)}` : "—"}
												</td>
												<td className="px-3 py-2 text-center">
													<span className={`inline-flex px-2 py-0.5 rounded text-xs font-medium ${
														r.status === "approved" ? "bg-green-100 text-green-700" :
														r.status === "rejected" ? "bg-red-100 text-red-700" :
														"bg-amber-100 text-amber-700"
													}`}>
														{r.status}
													</span>
												</td>
											</tr>
										))}
									</tbody>
								</table>
							</div>
							{reqPagination && reqPagination.last_page > 1 && (
								<div className="flex justify-between items-center mt-3 pt-3 border-t border-gray-100">
									<p className="text-xs text-gray-500">Page {reqPagination.current_page} of {reqPagination.last_page}</p>
									<div className="flex gap-1">
										<button
											disabled={reqPage <= 1}
											onClick={() => setReqPage((p) => p - 1)}
											className="px-3 py-1 text-xs border rounded disabled:opacity-40 hover:bg-gray-50"
										>Prev</button>
										<button
											disabled={reqPage >= reqPagination.last_page}
											onClick={() => setReqPage((p) => p + 1)}
											className="px-3 py-1 text-xs border rounded disabled:opacity-40 hover:bg-gray-50"
										>Next</button>
									</div>
								</div>
							)}
						</>
					)}
				</div>

				{/* Payout history */}
				<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
					<h2 className="text-sm font-semibold text-gray-800 mb-3">Payout history</h2>
					{payoutsLoading ? (
						<p className="text-sm text-gray-500 py-4">Loading...</p>
					) : payouts.length === 0 ? (
						<p className="text-sm text-gray-500 py-4">No payouts yet.</p>
					) : (
						<>
							<div className="overflow-x-auto">
								<table className="min-w-full text-sm">
									<thead className="bg-gray-50 text-gray-600">
										<tr>
											<th className="px-3 py-2 text-left font-medium">Date</th>
											<th className="px-3 py-2 text-right font-medium">Amount</th>
											<th className="px-3 py-2 text-left font-medium">Reference</th>
											<th className="px-3 py-2 text-center font-medium">Status</th>
										</tr>
									</thead>
									<tbody className="divide-y divide-gray-100">
										{payouts.map((p: { id: number; amount: number; status: string; reference: string | null; paid_at: string | null; created_at: string }) => (
											<tr key={p.id} className="hover:bg-gray-50">
												<td className="px-3 py-2 text-gray-600">{new Date(p.created_at).toLocaleDateString()}</td>
												<td className="px-3 py-2 text-right font-medium">৳{Number(p.amount).toLocaleString()}</td>
												<td className="px-3 py-2 text-gray-600">{p.reference ?? "—"}</td>
												<td className="px-3 py-2 text-center">
													<span className={`inline-flex px-2 py-0.5 rounded text-xs font-medium ${
														p.status === "paid" ? "bg-green-100 text-green-700" : "bg-gray-100 text-gray-700"
													}`}>
														{p.status}
													</span>
												</td>
											</tr>
										))}
									</tbody>
								</table>
							</div>
							{histPagination && histPagination.last_page > 1 && (
								<div className="flex justify-between items-center mt-3 pt-3 border-t border-gray-100">
									<p className="text-xs text-gray-500">Page {histPagination.current_page} of {histPagination.last_page}</p>
									<div className="flex gap-1">
										<button
											disabled={histPage <= 1}
											onClick={() => setHistPage((p) => p - 1)}
											className="px-3 py-1 text-xs border rounded disabled:opacity-40 hover:bg-gray-50"
										>Prev</button>
										<button
											disabled={histPage >= histPagination.last_page}
											onClick={() => setHistPage((p) => p + 1)}
											className="px-3 py-1 text-xs border rounded disabled:opacity-40 hover:bg-gray-50"
										>Next</button>
									</div>
								</div>
							)}
						</>
					)}
				</div>
			</div>
		</WithVendorAuth>
	);
}
