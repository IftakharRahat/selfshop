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

type ChannelType = "bank" | "bkash" | "nagad" | "rocket";

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

const CHANNEL_OPTIONS: { value: ChannelType; label: string; color: string; bg: string; border: string }[] = [
	{ value: "bank", label: "Bank", color: "#2d2a5d", bg: "#2d2a5d10", border: "#2d2a5d40" },
	{ value: "bkash", label: "bKash", color: "#E2136E", bg: "#E2136E12", border: "#E2136E40" },
	{ value: "nagad", label: "Nagad", color: "#F6921E", bg: "#F6921E12", border: "#F6921E40" },
	{ value: "rocket", label: "Rocket", color: "#8C3494", bg: "#8C349412", border: "#8C349440" },
];

function channelLabel(t: string): string {
	return CHANNEL_OPTIONS.find((c) => c.value === t)?.label ?? t;
}

function channelStyle(t: string) {
	return CHANNEL_OPTIONS.find((c) => c.value === t) ?? CHANNEL_OPTIONS[0];
}

const isMobileWallet = (t: ChannelType) => t === "bkash" || t === "nagad" || t === "rocket";

/** Map UI channel type to backend-accepted values */
function toApiPayload(form: FormState) {
	const isWallet = isMobileWallet(form.channel_type);
	return {
		channel_type: isWallet ? "mobile_wallet" : form.channel_type,
		provider_name: isWallet ? channelLabel(form.channel_type) : (form.provider_name || undefined),
		account_name: form.account_name,
		account_number: form.account_number,
		routing_number: form.routing_number || undefined,
		is_default: form.is_default,
	};
}

/** Detect UI channel type from backend data */
function detectChannelType(a: VendorPayoutAccount): ChannelType {
	if (a.channel_type === "mobile_wallet") {
		const p = (a.provider_name ?? "").toLowerCase();
		if (p.includes("nagad")) return "nagad";
		if (p.includes("rocket")) return "rocket";
		return "bkash";
	}
	return (a.channel_type as ChannelType) || "bank";
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
			channel_type: detectChannelType(a),
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
			toast.error("Please enter an account name and account number.");
			return;
		}
		if (form.channel_type === "bank" && !form.provider_name) {
			toast.error("Please enter a bank name.");
			return;
		}
		try {
			const payload = toApiPayload(form);
			if (modal.editId) {
				await updateAccount({ id: modal.editId, ...payload }).unwrap();
				toast.success("Payout account updated successfully.");
			} else {
				await createAccount(payload).unwrap();
				toast.success("Payout account added successfully.");
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
			toast.success("Payout account removed successfully.");
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
				<div className="rounded-xl bg-white p-4 sm:p-6 shadow-sm border border-gray-100 flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
					<div>
						<h1 className="text-2xl font-bold text-gray-900 mb-1">Payout accounts</h1>
						<p className="text-sm text-gray-600">Bank or mobile wallet accounts where you receive payouts.</p>
					</div>
					<div className="flex w-full flex-wrap items-center gap-2 sm:w-auto sm:justify-end">
						<Link href="/vendor/payouts" className="inline-flex w-full items-center justify-center px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 sm:w-auto">
							Payouts
						</Link>
						<button onClick={openCreate} className="inline-flex w-full items-center justify-center px-4 py-2 rounded-lg bg-[#2d2a5d] text-white text-sm font-medium hover:bg-[#252947] sm:w-auto">
							Add account
						</button>
					</div>
				</div>

				<div className="rounded-xl bg-white p-4 sm:p-6 shadow-sm border border-gray-100">
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
							{accounts.map((a: VendorPayoutAccount) => {
								const uiType = detectChannelType(a);
								const cs = channelStyle(uiType);
								return (
								<div key={a.id} className="border rounded-xl p-4 transition-all" style={{ borderColor: a.is_default ? cs.border : "#e5e7eb", backgroundColor: a.is_default ? cs.bg : "transparent", borderLeftWidth: 4, borderLeftColor: cs.color }}>
									<div className="flex items-start justify-between">
										<div>
											<h3 className="font-semibold text-gray-900 flex items-center gap-2">
												<span style={{ color: cs.color }}>{channelLabel(uiType)}</span>
												{a.is_default && <span className="text-[10px] px-1.5 py-0.5 rounded font-medium" style={{ backgroundColor: cs.bg, color: cs.color }}>DEFAULT</span>}
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
								);
							})}
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
								<label className="block text-sm font-medium text-gray-700 mb-2">Account Type *</label>
								<div className="grid grid-cols-4 gap-2">
									{CHANNEL_OPTIONS.map((opt) => (
										<button
											key={opt.value}
											type="button"
											onClick={() => { setForm({ ...EMPTY_FORM, channel_type: opt.value, is_default: form.is_default }); }}
											className={"flex flex-col items-center gap-1 rounded-lg px-3 py-3 text-xs font-semibold border-2 transition-all cursor-pointer " + (form.channel_type === opt.value ? "ring-1 ring-offset-1" : "opacity-60 hover:opacity-100")}
											style={{
												backgroundColor: form.channel_type === opt.value ? opt.bg : "#f9fafb",
												borderColor: form.channel_type === opt.value ? opt.color : "#e5e7eb",
												color: form.channel_type === opt.value ? opt.color : "#6b7280",
												ringColor: opt.color,
											}}
										>
											{opt.label}
										</button>
									))}
								</div>
							</div>

							{/* Branded header for mobile wallets */}
							{isMobileWallet(form.channel_type) && (
								<div className="rounded-lg px-4 py-3" style={{ backgroundColor: channelStyle(form.channel_type).bg, border: `1px solid ${channelStyle(form.channel_type).border}` }}>
									<p className="text-sm font-bold" style={{ color: channelStyle(form.channel_type).color }}>{channelLabel(form.channel_type)}</p>
									<p className="text-xs text-gray-500">Enter your {channelLabel(form.channel_type)} account details below</p>
								</div>
							)}

							{/* Bank name — only for bank type */}
							{form.channel_type === "bank" && (
								<div>
									<label className="block text-sm font-medium text-gray-700 mb-1">Bank name *</label>
									<input type="text" value={form.provider_name} onChange={(e) => setField("provider_name", e.target.value)} className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="e.g. Dutch-Bangla Bank, BRAC Bank" />
								</div>
							)}

							<div>
								<label className="block text-sm font-medium text-gray-700 mb-1">{isMobileWallet(form.channel_type) ? `${channelLabel(form.channel_type)} account name` : "Account name"} *</label>
								<input type="text" value={form.account_name} onChange={(e) => setField("account_name", e.target.value)} className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder={isMobileWallet(form.channel_type) ? `Name registered on ${channelLabel(form.channel_type)}` : "Name on account"} />
							</div>
							<div>
								<label className="block text-sm font-medium text-gray-700 mb-1">{isMobileWallet(form.channel_type) ? `${channelLabel(form.channel_type)} number` : "Account number"} *</label>
								<input type={isMobileWallet(form.channel_type) ? "tel" : "text"} value={form.account_number} onChange={(e) => setField("account_number", e.target.value)} className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder={isMobileWallet(form.channel_type) ? "01XXXXXXXXX" : "Account number"} />
							</div>
							{form.channel_type === "bank" && (
								<div>
									<label className="block text-sm font-medium text-gray-700 mb-1">Routing number (optional)</label>
									<input type="text" value={form.routing_number} onChange={(e) => setField("routing_number", e.target.value)} className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Bank routing number" />
								</div>
							)}

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
