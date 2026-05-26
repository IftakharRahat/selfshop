"use client";

import { useMemo, useState } from "react";
import { ImagePlus, Loader2, MessageSquare, RefreshCcw, Send, X } from "lucide-react";
import { toast } from "sonner";
import { formatBDT } from "@/lib/format-currency";
import { getImageUrl } from "@/lib/utils";
import {
	type EligibleRefundItem,
	type RefundClaim,
	useGetEligibleRefundOrdersQuery,
	useGetRefundClaimQuery,
	useGetRefundClaimsQuery,
	useReplyRefundClaimMutation,
	useSubmitRefundClaimMutation,
} from "@/redux/features/refund/refundApi";

const statusClass: Record<string, string> = {
	pending: "bg-amber-50 text-amber-700 border-amber-200",
	in_progress: "bg-sky-50 text-sky-700 border-sky-200",
	approved: "bg-emerald-50 text-emerald-700 border-emerald-200",
	rejected: "bg-red-50 text-red-700 border-red-200",
	closed: "bg-gray-100 text-gray-600 border-gray-200",
};

function statusLabel(status: string) {
	return status.replace(/_/g, " ").replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function formatDate(value?: string | null) {
	if (!value) return "N/A";
	const date = new Date(value);
	return Number.isNaN(date.getTime()) ? "N/A" : date.toLocaleDateString();
}

export default function RefundPage() {
	const { data: eligibleData, isLoading: eligibleLoading } = useGetEligibleRefundOrdersQuery();
	const { data: claimsData, isLoading: claimsLoading } = useGetRefundClaimsQuery();
	const [submitClaim, { isLoading: submitting }] = useSubmitRefundClaimMutation();
	const [replyClaim, { isLoading: replying }] = useReplyRefundClaimMutation();

	const eligible = eligibleData?.data?.eligible_orders ?? [];
	const claims = claimsData?.data?.claims ?? [];
	const [activeClaimId, setActiveClaimId] = useState<number | null>(null);
	const [selectedItem, setSelectedItem] = useState<EligibleRefundItem | null>(null);
	const [claimMessage, setClaimMessage] = useState("");
	const [claimImage, setClaimImage] = useState<File | null>(null);
	const [replyMessage, setReplyMessage] = useState("");
	const [replyImage, setReplyImage] = useState<File | null>(null);

	const selectedClaimId = activeClaimId ?? claims[0]?.id ?? null;
	const { data: claimDetailData } = useGetRefundClaimQuery(selectedClaimId ?? 0, {
		skip: !selectedClaimId,
	});
	const selectedClaim: RefundClaim | undefined = claimDetailData?.data?.claim ?? claims.find((claim) => claim.id === selectedClaimId);

	const claimCountByStatus = useMemo(() => {
		return claims.reduce<Record<string, number>>((acc, claim) => {
			acc[claim.status] = (acc[claim.status] ?? 0) + 1;
			return acc;
		}, {});
	}, [claims]);

	const closeModal = () => {
		setSelectedItem(null);
		setClaimMessage("");
		setClaimImage(null);
	};

	const handleSubmitClaim = async () => {
		if (!selectedItem) return;
		if (!claimMessage.trim()) {
			toast.error("Please write a claim message.");
			return;
		}
		const body = new FormData();
		body.append("orderproduct_id", String(selectedItem.orderproduct_id));
		body.append("message", claimMessage.trim());
		if (claimImage) body.append("image", claimImage);
		try {
			const response = await submitClaim(body).unwrap();
			const claim = response.data.claim;
			toast.success("Refund claim submitted.");
			setActiveClaimId(claim.id);
			closeModal();
		} catch (error: any) {
			toast.error(error?.data?.message ?? "Failed to submit refund claim.");
		}
	};

	const handleReply = async () => {
		if (!selectedClaim) return;
		if (!replyMessage.trim()) {
			toast.error("Please write a reply.");
			return;
		}
		const body = new FormData();
		body.append("message", replyMessage.trim());
		if (replyImage) body.append("image", replyImage);
		try {
			await replyClaim({ id: selectedClaim.id, body }).unwrap();
			toast.success("Reply sent.");
			setReplyMessage("");
			setReplyImage(null);
		} catch (error: any) {
			toast.error(error?.data?.message ?? "Failed to send reply.");
		}
	};

	return (
		<div className="m-3 sm:m-4 lg:m-6 space-y-4 mb-24">
			<div className="bg-white rounded-xl border border-gray-100 shadow-sm p-4 sm:p-6">
				<div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
					<div>
						<h1 className="text-xl sm:text-2xl font-semibold text-gray-900">Refund</h1>
						<p className="text-sm text-gray-500">Claim delivered products while their warranty window is active.</p>
					</div>
					<div className="flex flex-wrap gap-2 text-xs">
						{Object.entries(claimCountByStatus).map(([status, count]) => (
							<span key={status} className={`rounded-full border px-3 py-1 font-medium ${statusClass[status] ?? statusClass.closed}`}>
								{statusLabel(status)}: {count}
							</span>
						))}
					</div>
				</div>
			</div>

			<div className="grid grid-cols-1 xl:grid-cols-5 gap-4">
				<section className="xl:col-span-3 bg-white rounded-xl border border-gray-100 shadow-sm p-4 sm:p-5">
					<div className="mb-4 flex items-center justify-between">
						<h2 className="text-base font-semibold text-gray-900">Eligible Delivered Products</h2>
						<RefreshCcw className="h-5 w-5 text-[#E5005F]" />
					</div>
					{eligibleLoading ? (
						<div className="py-10 text-center text-gray-500">Loading eligible products...</div>
					) : eligible.length === 0 ? (
						<div className="rounded-xl border border-dashed border-gray-200 p-8 text-center text-sm text-gray-500">
							No delivered products are currently eligible for refund or exchange claims.
						</div>
					) : (
						<div className="space-y-3">
							{eligible.map((item) => (
								<div key={item.orderproduct_id} className="rounded-xl border border-gray-100 bg-gray-50/60 p-3 sm:p-4">
									<div className="flex flex-col gap-3 sm:flex-row sm:items-center">
										<img
											src={getImageUrl(item.product_image)}
											alt={item.product_name}
											className="h-20 w-20 rounded-lg border border-gray-100 object-cover"
										/>
										<div className="min-w-0 flex-1">
											<h3 className="truncate text-sm font-semibold text-gray-900">{item.product_name}</h3>
											<p className="text-xs text-gray-500">Invoice {item.invoiceID} · Delivered {formatDate(item.delivery_date)}</p>
											<p className="text-xs text-gray-500">
												Qty {item.quantity} · {formatBDT(Number(item.product_price || 0))}TK
												{item.color || item.size ? ` · ${[item.color, item.size].filter(Boolean).join(" / ")}` : ""}
											</p>
											<p className="mt-1 text-xs font-medium text-emerald-700">
												{item.days_remaining} days remaining · expires {formatDate(item.expires_at)}
											</p>
										</div>
										<button
											type="button"
											onClick={() => setSelectedItem(item)}
											className="inline-flex items-center justify-center gap-2 rounded-lg bg-[#E5005F] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#c90055]"
										>
											<MessageSquare className="h-4 w-4" />
											Claim
										</button>
									</div>
								</div>
							))}
						</div>
					)}
				</section>

				<section className="xl:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm p-4 sm:p-5">
					<h2 className="mb-4 text-base font-semibold text-gray-900">Claim History</h2>
					{claimsLoading ? (
						<div className="py-10 text-center text-gray-500">Loading claims...</div>
					) : claims.length === 0 ? (
						<div className="rounded-xl border border-dashed border-gray-200 p-8 text-center text-sm text-gray-500">No refund claims yet.</div>
					) : (
						<div className="space-y-2">
							{claims.map((claim) => (
								<button
									key={claim.id}
									type="button"
									onClick={() => setActiveClaimId(claim.id)}
									className={`w-full rounded-xl border p-3 text-left transition ${selectedClaimId === claim.id ? "border-[#E5005F] bg-pink-50/40" : "border-gray-100 hover:bg-gray-50"}`}
								>
									<div className="flex items-center justify-between gap-2">
										<span className="text-sm font-semibold text-gray-900">{claim.claim_number}</span>
										<span className={`rounded-full border px-2 py-0.5 text-[10px] font-medium ${statusClass[claim.status] ?? statusClass.closed}`}>
											{statusLabel(claim.status)}
										</span>
									</div>
									<p className="mt-1 truncate text-xs text-gray-500">{claim.orderproduct?.productName ?? claim.product?.ProductName ?? "Product"}</p>
								</button>
							))}
						</div>
					)}
				</section>
			</div>

			{selectedClaim && (
				<section className="bg-white rounded-xl border border-gray-100 shadow-sm p-4 sm:p-5">
					<div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
						<div>
							<h2 className="text-base font-semibold text-gray-900">{selectedClaim.claim_number}</h2>
							<p className="text-xs text-gray-500">
								{selectedClaim.orderproduct?.productName ?? selectedClaim.product?.ProductName ?? "Product"} · submitted {formatDate(selectedClaim.created_at)}
							</p>
						</div>
						<span className={`w-fit rounded-full border px-3 py-1 text-xs font-medium ${statusClass[selectedClaim.status] ?? statusClass.closed}`}>
							{statusLabel(selectedClaim.status)}
						</span>
					</div>

					<div className="space-y-3">
						{selectedClaim.messages.map((message) => (
							<div key={message.id} className={`rounded-xl border p-3 ${message.sender_type === "admin" ? "border-sky-100 bg-sky-50/60" : "border-gray-100 bg-gray-50/60"}`}>
								<div className="mb-1 flex items-center justify-between gap-2">
									<span className="text-xs font-semibold text-gray-700">{message.sender_type === "admin" ? "Admin" : "You"}</span>
									<span className="text-[11px] text-gray-400">{formatDate(message.created_at)}</span>
								</div>
								<p className="whitespace-pre-wrap text-sm text-gray-700">{message.message}</p>
								{message.attachment_path && (
									<img src={getImageUrl(message.attachment_path)} alt="Attachment" className="mt-2 h-24 w-24 rounded-lg border border-gray-100 object-cover" />
								)}
							</div>
						))}
					</div>

					{selectedClaim.status !== "closed" && (
						<div className="mt-4 grid gap-3 sm:grid-cols-[1fr_auto]">
							<textarea
								value={replyMessage}
								onChange={(event) => setReplyMessage(event.target.value)}
								rows={3}
								placeholder="Write a reply"
								className="rounded-xl border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#E5005F] focus:ring-2 focus:ring-pink-100"
							/>
							<div className="flex flex-col gap-2">
								<label className="inline-flex cursor-pointer items-center justify-center gap-2 rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
									<ImagePlus className="h-4 w-4" />
									Image
									<input type="file" accept="image/*" className="hidden" onChange={(event) => setReplyImage(event.target.files?.[0] ?? null)} />
								</label>
								<button
									type="button"
									onClick={handleReply}
									disabled={replying}
									className="inline-flex items-center justify-center gap-2 rounded-lg bg-[#E5005F] px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
								>
									{replying ? <Loader2 className="h-4 w-4 animate-spin" /> : <Send className="h-4 w-4" />}
									Send
								</button>
							</div>
						</div>
					)}
				</section>
			)}

			{selectedItem && (
				<div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
					<div className="w-full max-w-lg rounded-xl bg-white p-4 shadow-xl sm:p-5">
						<div className="mb-4 flex items-start justify-between gap-3">
							<div>
								<h2 className="text-lg font-semibold text-gray-900">Submit Refund Claim</h2>
								<p className="text-sm text-gray-500">{selectedItem.product_name}</p>
							</div>
							<button type="button" onClick={closeModal} className="rounded-lg p-2 text-gray-500 hover:bg-gray-100">
								<X className="h-5 w-5" />
							</button>
						</div>
						<div className="space-y-3">
							<textarea
								value={claimMessage}
								onChange={(event) => setClaimMessage(event.target.value)}
								rows={5}
								placeholder="Describe the issue and what support you need"
								className="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#E5005F] focus:ring-2 focus:ring-pink-100"
							/>
							<label className="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-dashed border-gray-200 px-4 py-4 text-sm font-medium text-gray-600 hover:bg-gray-50">
								<ImagePlus className="h-4 w-4" />
								{claimImage ? claimImage.name : "Attach image (optional)"}
								<input type="file" accept="image/*" className="hidden" onChange={(event) => setClaimImage(event.target.files?.[0] ?? null)} />
							</label>
						</div>
						<div className="mt-4 flex justify-end gap-2">
							<button type="button" onClick={closeModal} className="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
							<button
								type="button"
								onClick={handleSubmitClaim}
								disabled={submitting}
								className="inline-flex items-center gap-2 rounded-lg bg-[#E5005F] px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
							>
								{submitting && <Loader2 className="h-4 w-4 animate-spin" />}
								Submit
							</button>
						</div>
					</div>
				</div>
			)}
		</div>
	);
}
