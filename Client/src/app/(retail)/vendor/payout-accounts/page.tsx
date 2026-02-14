"use client";

import { useState } from "react";
import Link from "next/link";
import WithVendorAuth from "../WithVendorAuth";
import {
	useGetVendorPayoutAccountsQuery,
	useCreateVendorPayoutAccountMutation,
	useUpdateVendorPayoutAccountMutation,
	useDeleteVendorPayoutAccountMutation,
} from "@/redux/api/vendorApi";
import type { VendorPayoutAccount } from "@/redux/api/vendorApi";
import { toast } from "sonner";

type ChannelType = "bank" | "mobile_wallet" | "other";

interface FormState {
	channel_type: ChannelType;
	provider_name: string;
	account_name: string;
	account_number: string;
	routing_number: string;
	is_default: boolean;
}

const EMPTY_FORM: FormState = {
	channel_type: "bank",
	provider_name: "",
	account_name: "",
	account_number: "",
	routing_number: "",
	is_default: false,
};

function channelLabel(t: string): string {
	if (t === "bank") return "Bank";
	if (t === "mobile_wallet") return "Mobile wallet";
	return "Other";
}

export default function VendorPayoutAccountsPage() {
	const { data, isLoading, error } = useGetVendorPayoutAccountsQuery(undefined);
	const [createAccount, { isLoading: creating }] = useCreateVendorPayoutAccountMutation();
	const [updateAccount, { isLoading: updating }] = useUpdateVendorPayoutAccountMutation();
	const [deleteAccount, { isLoading: deleting }] = useDeleteVendorPayoutAccountMutation();

	const [modal, setModal] = useState<{ open: boolean; editId: number | null }>({ open: false, editId: null });
	const [form, setForm] = useState<FormState>(EMPTY_FORM);

	const accounts = data?.data?.payout_accounts ?? [];

	const openCreate = () => {
		setForm(EMPTY_FORM);
		setModal({ open: true, editId: null });
	};

	const openEdit = (a: VendorPayoutAccount) => {
		setForm({
			channel_type: (a.channel_type as ChannelType) || "bank",
			provider_name: a.provider_name ?? "",
			account_name: a.account_name ?? "",
			account_number: a.account_number ?? "",
			routing_number: a.routing_number ?? "",
			is_default: a.is_default,
		});
		setModal({ open: true, editId: a.id });
	};

	const handleSubmit = async () => {
		if (!form.account_name.trim() || !form.account_number.trim()) {
			toast.error("Account name and account number are required");
			return;
		}
		try {
			if (modal.editId) {
				await updateAccount({
					id: modal.editId,
					channel_type: form.channel_type,
					provider_name: form.provider_name || undefined,
					account_name: form.account_name,
					account_number: form.account_number,
					routing_number: form.routing_number || undefined,
					is_default: form.is_default,
				}).unwrap();
				toast.success("Payout account updated");
			} else {
				await createAccount({
					channel_type: form.channel_type,
					provider_name: form.provider_name || undefined,
					account_name: form.account_name,
					account_number: form.account_number,
					routing_number: form.routing_number || undefined,
					is_default: form.is_default,
				}).unwrap();
				toast.success("Payout account added");
			}
			setModal({ open: false, editId: null });
		} catch (err: unknown) {
			const msg = (err as { data?: { message?: string } })?.data?.message || "Failed to save";
			toast.error(msg);
		}
	};

	const handleDelete = async (id: number, label: string) => {
		if (!confirm("Remove payout account \"" + label + "\"?")) return;
		try {
			await deleteAccount(id).unwrap();
			toast.success("Payout account removed");
		} catch (err: unknown) {
			const msg = (err as { data?: { message?: string } })?.data?.message || "Failed to delete";
			toast.error(msg);
		}
	};

	const setField = (key: keyof FormState, val: string | boolean) =>
		setForm((prev) => ({ ...prev, [key]: val }));

	return (
		<WithVendorAuth>
			<div className="space-y-6">
				<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100 flex items-center justify-between gap-4">
					<div>
						<h1 className="text-2xl font-bold text-gray-900 mb-1">Payout accounts</h1>
						<p className="text-sm text-gray-600">Bank or mobile wallet accounts where you receive payouts.</p>
					</div>
					<div className="flex items-center gap-2">
						<Link href="/vendor/payouts" className="inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50">
							Payouts
						</Link>
						<button onClick={openCreate} className="inline-flex items-center px-4 py-2 rounded-lg bg-[#2d2a5d] text-white text-sm font-medium hover:bg-[#252947]">
							Add account
						</button>
					</div>
				</div>

				<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
					{isLoading ? (
						<p className="text-sm text-gray-500 py-8 text-center">Loading...</p>
					) : error ? (
						<p className="text-sm text-red-600 py-8 text-center">Failed to load payout accounts.</p>
					) : accounts.length === 0 ? (
						<div className="py-12 text-center">
							<p className="text-gray-500 mb-2">No payout accounts yet.</p>
							<button onClick={openCreate} className="text-sm text-[#2d2a5d] hover:underline font-medium">Add your first payout account</button>
						</div>
					) : (
						<div className="grid gap-4 md:grid-cols-2">
							{accounts.map((a: VendorPayoutAccount) => (
								<div key={a.id} className={"border rounded-xl p-4 " + (a.is_default ? "border-indigo-300 bg-indigo-50/30" : "border-gray-200")}>
									<div className="flex items-start justify-between">
										<div>
											<h3 className="font-semibold text-gray-900 flex items-center gap-2">
												{channelLabel(a.channel_type)}
												{a.is_default && <span className="text-[10px] bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded font-medium">DEFAULT</span>}
												{!a.is_active && <span className="text-[10px] bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded font-medium">INACTIVE</span>}
											</h3>
											{a.provider_name && <p className="text-xs text-gray-500 mt-0.5">{a.provider_name}</p>}
											<p className="text-sm text-gray-700 mt-1">{a.account_name}</p>
											<p className="text-xs text-gray-500 font-mono mt-0.5">{a.account_number}{a.routing_number ? " · Routing: " + a.routing_number : ""}</p>
										</div>
										<div className="flex gap-2">
											<button onClick={() => openEdit(a)} className="text-xs text-blue-600 hover:underline font-medium">Edit</button>
											<button disabled={deleting} onClick={() => handleDelete(a.id, a.account_name || channelLabel(a.channel_type))} className="text-xs text-red-600 hover:underline font-medium disabled:opacity-50">Remove</button>
										</div>
									</div>
								</div>
							))}
						</div>
					)}
				</div>
			</div>

			{modal.open && (
				<div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
					<div className="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 mx-4 max-h-[90vh] overflow-y-auto">
						<h2 className="text-lg font-semibold text-gray-900 mb-4">{modal.editId ? "Edit payout account" : "New payout account"}</h2>
						<div className="space-y-3">
							<div>
								<label className="block text-sm font-medium text-gray-700 mb-1">Type *</label>
								<select value={form.channel_type} onChange={(e) => setField("channel_type", e.target.value as ChannelType)} className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
									<option value="bank">Bank</option>
									<option value="mobile_wallet">Mobile wallet</option>
									<option value="other">Other</option>
								</select>
							</div>
							<div>
								<label className="block text-sm font-medium text-gray-700 mb-1">Provider / Bank name</label>
								<input type="text" value={form.provider_name} onChange={(e) => setField("provider_name", e.target.value)} className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="e.g. bKash, Bank name" />
							</div>
							<div>
								<label className="block text-sm font-medium text-gray-700 mb-1">Account name *</label>
								<input type="text" value={form.account_name} onChange={(e) => setField("account_name", e.target.value)} className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Name on account" />
							</div>
							<div>
								<label className="block text-sm font-medium text-gray-700 mb-1">Account number *</label>
								<input type="text" value={form.account_number} onChange={(e) => setField("account_number", e.target.value)} className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Account or wallet number" />
							</div>
							<div>
								<label className="block text-sm font-medium text-gray-700 mb-1">Routing number (optional)</label>
								<input type="text" value={form.routing_number} onChange={(e) => setField("routing_number", e.target.value)} className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="For banks" />
							</div>
							<div>
								<label className="inline-flex items-center gap-2 cursor-pointer">
									<input type="checkbox" checked={form.is_default} onChange={(e) => setField("is_default", e.target.checked)} className="accent-indigo-600" />
									<span className="text-sm text-gray-700">Use as default for payouts</span>
								</label>
							</div>
						</div>
						<div className="flex justify-end gap-2 mt-5">
							<button onClick={() => setModal({ open: false, editId: null })} className="px-4 py-2 text-sm border rounded-lg hover:bg-gray-50">Cancel</button>
							<button disabled={creating || updating} onClick={handleSubmit} className="px-4 py-2 text-sm bg-[#2d2a5d] text-white rounded-lg hover:bg-[#252947] disabled:opacity-50">{creating || updating ? "Saving..." : modal.editId ? "Update" : "Add"}</button>
						</div>
					</div>
				</div>
			)}
		</WithVendorAuth>
	);
}
