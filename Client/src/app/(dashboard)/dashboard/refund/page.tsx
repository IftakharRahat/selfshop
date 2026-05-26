"use client";

import { useState } from "react";
import {
	useGetWarrantyProductsQuery,
	useGetWarrantyClaimsQuery,
	useSubmitWarrantyClaimMutation,
} from "@/redux/features/home/homeApi";
import { RotateCcw, Clock, CheckCircle, XCircle, ChevronDown, ChevronUp, ImagePlus, X, Send, AlertTriangle } from "lucide-react";
import Image from "next/image";
import { toast } from "react-hot-toast";

interface OrderProduct {
	order_product_id: number;
	product_id: number;
	product_name: string;
	product_code: string;
	product_image: string | null;
	product_price: number;
	quantity: number;
	warranty_days: number;
	delivered_at: string;
	expires_at: string;
	days_left: number;
	already_claimed: boolean;
	vendor_id: number | null;
}

interface WarrantyOrder {
	order_id: number;
	invoice_id: string;
	order_date: string;
	delivered_at: string;
	customer_name: string | null;
	products: OrderProduct[];
}

interface ClaimItem {
	id: number;
	claim_number: string;
	order_id: number;
	invoice_id: string | null;
	product_id: number;
	product_name: string | null;
	product_image: string | null;
	warranty_days: number;
	delivered_at: string;
	expires_at: string;
	days_left: number;
	reason: string;
	images: string[] | null;
	status: string;
	admin_note: string | null;
	responded_at: string | null;
	created_at: string;
}

export default function RefundPage() {
	const [activeTab, setActiveTab] = useState<"eligible" | "claims">("eligible");
	const [expandedOrder, setExpandedOrder] = useState<number | null>(null);
	const [claimModal, setClaimModal] = useState<{ open: boolean; order: WarrantyOrder | null; product: OrderProduct | null }>({
		open: false,
		order: null,
		product: null,
	});
	const [reason, setReason] = useState("");
	const [images, setImages] = useState<File[]>([]);

	const { data: productsData, isLoading: productsLoading } = useGetWarrantyProductsQuery(undefined);
	const { data: claimsData, isLoading: claimsLoading } = useGetWarrantyClaimsQuery(undefined);
	const [submitClaim, { isLoading: submitting }] = useSubmitWarrantyClaimMutation();

	const orders: WarrantyOrder[] = productsData?.data || [];
	const claims: ClaimItem[] = claimsData?.data || [];

	const getDaysLeftColor = (days: number) => {
		if (days > 10) return "text-emerald-600 bg-emerald-50 border-emerald-200";
		if (days > 5) return "text-amber-600 bg-amber-50 border-amber-200";
		return "text-red-600 bg-red-50 border-red-200";
	};

	const getStatusBadge = (status: string) => {
		switch (status) {
			case "pending":
				return (
					<span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
						<Clock className="w-3 h-3" /> Pending
					</span>
				);
			case "approved":
				return (
					<span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
						<CheckCircle className="w-3 h-3" /> Approved
					</span>
				);
			case "rejected":
				return (
					<span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
						<XCircle className="w-3 h-3" /> Rejected
					</span>
				);
			default:
				return <span className="text-xs">{status}</span>;
		}
	};

	const handleOpenClaim = (order: WarrantyOrder, product: OrderProduct) => {
		setClaimModal({ open: true, order, product });
		setReason("");
		setImages([]);
	};

	const handleImageAdd = (e: React.ChangeEvent<HTMLInputElement>) => {
		const files = Array.from(e.target.files || []);
		if (images.length + files.length > 3) {
			toast.error("Maximum 3 images allowed");
			return;
		}
		setImages((prev) => [...prev, ...files]);
	};

	const handleRemoveImage = (index: number) => {
		setImages((prev) => prev.filter((_, i) => i !== index));
	};

	const handleSubmitClaim = async () => {
		if (!claimModal.order || !claimModal.product) return;
		if (reason.trim().length < 10) {
			toast.error("Please provide a reason (at least 10 characters)");
			return;
		}

		const formData = new FormData();
		formData.append("order_id", String(claimModal.order.order_id));
		formData.append("product_id", String(claimModal.product.product_id));
		formData.append("order_product_id", String(claimModal.product.order_product_id));
		formData.append("reason", reason);
		images.forEach((img) => {
			formData.append("images[]", img);
		});

		try {
			const res = await submitClaim(formData).unwrap();
			if (res.status) {
				toast.success(res.message || "Refund claim submitted successfully!");
				setClaimModal({ open: false, order: null, product: null });
				setActiveTab("claims");
			} else {
				toast.error(res.message || "Failed to submit claim");
			}
		} catch (err: unknown) {
			const error = err as { data?: { message?: string } };
			toast.error(error?.data?.message || "Failed to submit claim");
		}
	};

	return (
		<div className="max-w-4xl mx-auto pb-8">
			{/* Page Header */}
			<div className="mb-6">
				<div className="flex items-center gap-3 mb-1">
					<div className="w-10 h-10 rounded-xl bg-gradient-to-br from-[#E5005F] to-[#b80050] flex items-center justify-center">
						<RotateCcw className="w-5 h-5 text-white" />
					</div>
					<div>
						<h1 className="text-xl font-bold text-gray-900">Refund</h1>
						<p className="text-sm text-gray-500">Claim refund for warranty products</p>
					</div>
				</div>
			</div>

			{/* Tab Switcher */}
			<div className="flex gap-1 bg-gray-100 p-1 rounded-xl mb-6">
				<button
					onClick={() => setActiveTab("eligible")}
					className={`flex-1 py-2.5 px-4 rounded-lg text-sm font-semibold transition-all ${
						activeTab === "eligible"
							? "bg-white text-gray-900 shadow-sm"
							: "text-gray-500 hover:text-gray-700"
					}`}
				>
					Eligible Orders
				</button>
				<button
					onClick={() => setActiveTab("claims")}
					className={`flex-1 py-2.5 px-4 rounded-lg text-sm font-semibold transition-all ${
						activeTab === "claims"
							? "bg-white text-gray-900 shadow-sm"
							: "text-gray-500 hover:text-gray-700"
					}`}
				>
					My Claims {claims.length > 0 && <span className="ml-1 bg-[#E5005F] text-white text-xs px-1.5 py-0.5 rounded-full">{claims.length}</span>}
				</button>
			</div>

			{/* Eligible Orders Tab */}
			{activeTab === "eligible" && (
				<div className="space-y-3">
					{productsLoading ? (
						<div className="text-center py-16">
							<div className="w-8 h-8 border-2 border-[#E5005F] border-t-transparent rounded-full animate-spin mx-auto mb-3" />
							<p className="text-gray-500">Loading eligible products...</p>
						</div>
					) : orders.length === 0 ? (
						<div className="text-center py-16 bg-white rounded-2xl border border-gray-100">
							<RotateCcw className="w-12 h-12 text-gray-300 mx-auto mb-3" />
							<h3 className="text-lg font-semibold text-gray-700 mb-1">No Eligible Products</h3>
							<p className="text-sm text-gray-500">You don&apos;t have any delivered products with active warranty.</p>
						</div>
					) : (
						orders.map((order) => {
							const isExpanded = expandedOrder === order.order_id;
							return (
								<div key={order.order_id} className="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
									{/* Order Header — clickable to expand */}
									<button
										onClick={() => setExpandedOrder(isExpanded ? null : order.order_id)}
										className="w-full flex items-center justify-between p-4 hover:bg-gray-50 transition-colors text-left"
									>
										<div>
											<div className="flex items-center gap-2 mb-1">
												<span className="font-bold text-gray-900">#{order.invoice_id}</span>
												<span className="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-medium">Delivered</span>
											</div>
											<p className="text-xs text-gray-500">
												Delivered: {new Date(order.delivered_at).toLocaleDateString("en-GB", { day: "numeric", month: "short", year: "numeric" })}
												{" · "}{order.products.length} warranty product{order.products.length !== 1 ? "s" : ""}
											</p>
										</div>
										{isExpanded ? <ChevronUp className="w-5 h-5 text-gray-400" /> : <ChevronDown className="w-5 h-5 text-gray-400" />}
									</button>

									{/* Product List */}
									{isExpanded && (
										<div className="border-t border-gray-100 divide-y divide-gray-50">
											{order.products.map((product) => (
												<div key={product.order_product_id} className="p-4 flex items-start gap-3">
													{/* Product Image */}
													<div className="w-14 h-14 rounded-lg bg-gray-100 overflow-hidden flex-shrink-0 border border-gray-200">
														{product.product_image ? (
															<Image
																src={product.product_image.startsWith("http") ? product.product_image : `${process.env.NEXT_PUBLIC_IMAGE_URL}/${product.product_image}`}
																alt={product.product_name}
																width={56}
																height={56}
																className="object-cover w-full h-full"
															/>
														) : (
															<div className="w-full h-full flex items-center justify-center text-gray-400 text-xs">N/A</div>
														)}
													</div>

													{/* Product Info */}
													<div className="flex-1 min-w-0">
														<h4 className="text-sm font-semibold text-gray-900 truncate">{product.product_name}</h4>
														<p className="text-xs text-gray-500 mt-0.5">
															Code: {product.product_code} · Qty: {product.quantity} · ৳{product.product_price}
														</p>
														<div className="flex items-center gap-2 mt-2">
															<span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold border ${getDaysLeftColor(product.days_left)}`}>
																<Clock className="w-3 h-3" />
																{product.days_left} days left
															</span>
															<span className="text-xs text-gray-400">
																({product.warranty_days}d warranty)
															</span>
														</div>
													</div>

													{/* Claim Button */}
													<div className="flex-shrink-0">
														{product.already_claimed ? (
															<span className="text-xs text-gray-400 bg-gray-100 px-3 py-2 rounded-lg font-medium">Claimed</span>
														) : (
															<button
																onClick={() => handleOpenClaim(order, product)}
																className="text-xs font-semibold text-white bg-[#E5005F] hover:bg-[#c80050] px-3 py-2 rounded-lg transition-colors"
															>
																Claim Refund
															</button>
														)}
													</div>
												</div>
											))}
										</div>
									)}
								</div>
							);
						})
					)}
				</div>
			)}

			{/* My Claims Tab */}
			{activeTab === "claims" && (
				<div className="space-y-3">
					{claimsLoading ? (
						<div className="text-center py-16">
							<div className="w-8 h-8 border-2 border-[#E5005F] border-t-transparent rounded-full animate-spin mx-auto mb-3" />
							<p className="text-gray-500">Loading claims...</p>
						</div>
					) : claims.length === 0 ? (
						<div className="text-center py-16 bg-white rounded-2xl border border-gray-100">
							<RotateCcw className="w-12 h-12 text-gray-300 mx-auto mb-3" />
							<h3 className="text-lg font-semibold text-gray-700 mb-1">No Claims Yet</h3>
							<p className="text-sm text-gray-500">You haven&apos;t submitted any refund claims.</p>
						</div>
					) : (
						claims.map((claim) => (
							<div key={claim.id} className="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
								<div className="flex items-start gap-3">
									{/* Product Image */}
									<div className="w-12 h-12 rounded-lg bg-gray-100 overflow-hidden flex-shrink-0 border border-gray-200">
										{claim.product_image ? (
											<Image
												src={claim.product_image.startsWith("http") ? claim.product_image : `${process.env.NEXT_PUBLIC_IMAGE_URL}/${claim.product_image}`}
												alt={claim.product_name || ""}
												width={48}
												height={48}
												className="object-cover w-full h-full"
											/>
										) : (
											<div className="w-full h-full flex items-center justify-center text-gray-400 text-xs">N/A</div>
										)}
									</div>

									<div className="flex-1 min-w-0">
										<div className="flex items-center justify-between gap-2 mb-1">
											<h4 className="text-sm font-semibold text-gray-900 truncate">{claim.product_name}</h4>
											{getStatusBadge(claim.status)}
										</div>
										<p className="text-xs text-gray-500 mb-1">
											Claim: {claim.claim_number} · Invoice: #{claim.invoice_id}
										</p>
										<p className="text-xs text-gray-500">
											Submitted: {new Date(claim.created_at).toLocaleDateString("en-GB", { day: "numeric", month: "short", year: "numeric" })}
										</p>
										{claim.reason && (
											<p className="text-xs text-gray-600 mt-2 bg-gray-50 p-2 rounded-lg line-clamp-2">{claim.reason}</p>
										)}
										{claim.admin_note && (
											<div className="mt-2 p-2 rounded-lg bg-blue-50 border border-blue-100">
												<p className="text-xs font-semibold text-blue-700 mb-0.5">Admin Response:</p>
												<p className="text-xs text-blue-600">{claim.admin_note}</p>
											</div>
										)}
									</div>
								</div>
							</div>
						))
					)}
				</div>
			)}

			{/* Claim Modal */}
			{claimModal.open && claimModal.order && claimModal.product && (
				<div className="fixed inset-0 z-50 flex items-end sm:items-center justify-center">
					<div className="fixed inset-0 bg-black/50 backdrop-blur-sm" onClick={() => setClaimModal({ open: false, order: null, product: null })} />
					<div className="relative bg-white w-full sm:max-w-lg sm:rounded-2xl rounded-t-2xl max-h-[90vh] overflow-y-auto shadow-2xl">
						{/* Modal Header */}
						<div className="sticky top-0 bg-white border-b border-gray-100 px-5 py-4 flex items-center justify-between z-10">
							<h3 className="text-lg font-bold text-gray-900">Claim Refund</h3>
							<button
								onClick={() => setClaimModal({ open: false, order: null, product: null })}
								className="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center hover:bg-gray-200 transition-colors"
							>
								<X className="w-4 h-4" />
							</button>
						</div>

						<div className="p-5 space-y-5">
							{/* Product Info (read-only) */}
							<div className="flex items-start gap-3 p-3 bg-gray-50 rounded-xl">
								<div className="w-14 h-14 rounded-lg bg-white overflow-hidden flex-shrink-0 border border-gray-200">
									{claimModal.product.product_image ? (
										<Image
											src={claimModal.product.product_image.startsWith("http") ? claimModal.product.product_image : `${process.env.NEXT_PUBLIC_IMAGE_URL}/${claimModal.product.product_image}`}
											alt={claimModal.product.product_name}
											width={56}
											height={56}
											className="object-cover w-full h-full"
										/>
									) : (
										<div className="w-full h-full flex items-center justify-center text-gray-400 text-xs">N/A</div>
									)}
								</div>
								<div className="flex-1 min-w-0">
									<h4 className="text-sm font-semibold text-gray-900 truncate">{claimModal.product.product_name}</h4>
									<p className="text-xs text-gray-500 mt-0.5">
										Invoice: #{claimModal.order.invoice_id} · ৳{claimModal.product.product_price}
									</p>
									<div className="flex items-center gap-2 mt-1.5">
										<span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold border ${getDaysLeftColor(claimModal.product.days_left)}`}>
											<Clock className="w-3 h-3" />
											{claimModal.product.days_left} days left
										</span>
									</div>
								</div>
							</div>

							{/* Warning */}
							<div className="flex items-start gap-2 p-3 bg-amber-50 rounded-xl border border-amber-200">
								<AlertTriangle className="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5" />
								<p className="text-xs text-amber-700">Once submitted, your claim will be reviewed by the admin. Please provide a clear reason and supporting images if available.</p>
							</div>

							{/* Reason */}
							<div>
								<label className="block text-sm font-semibold text-gray-700 mb-1.5">
									Reason for Refund <span className="text-red-500">*</span>
								</label>
								<textarea
									value={reason}
									onChange={(e) => setReason(e.target.value)}
									className="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#E5005F]/20 focus:border-[#E5005F] resize-none"
									rows={4}
									placeholder="Describe why you want a refund for this product..."
								/>
								<p className="text-xs text-gray-400 mt-1">{reason.length}/2000 characters (minimum 10)</p>
							</div>

							{/* Image Upload */}
							<div>
								<label className="block text-sm font-semibold text-gray-700 mb-1.5">
									Attach Images <span className="text-gray-400 font-normal">(optional, max 3)</span>
								</label>
								<div className="flex gap-2 flex-wrap">
									{images.map((img, i) => (
										<div key={i} className="relative w-20 h-20 rounded-xl overflow-hidden border border-gray-200 group">
											<Image src={URL.createObjectURL(img)} alt="" fill className="object-cover" />
											<button
												onClick={() => handleRemoveImage(i)}
												className="absolute top-1 right-1 w-5 h-5 rounded-full bg-red-500 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
											>
												<X className="w-3 h-3" />
											</button>
										</div>
									))}
									{images.length < 3 && (
										<label className="w-20 h-20 rounded-xl border-2 border-dashed border-gray-300 flex flex-col items-center justify-center cursor-pointer hover:border-[#E5005F] hover:bg-pink-50 transition-colors">
											<ImagePlus className="w-5 h-5 text-gray-400" />
											<span className="text-[10px] text-gray-400 mt-0.5">Add</span>
											<input
												type="file"
												accept="image/jpeg,image/png,image/jpg,image/webp"
												className="hidden"
												onChange={handleImageAdd}
											/>
										</label>
									)}
								</div>
							</div>

							{/* Submit */}
							<button
								onClick={handleSubmitClaim}
								disabled={submitting || reason.trim().length < 10}
								className="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl font-semibold text-white bg-[#E5005F] hover:bg-[#c80050] disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
							>
								{submitting ? (
									<div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin" />
								) : (
									<Send className="w-4 h-4" />
								)}
								{submitting ? "Submitting..." : "Submit Refund Claim"}
							</button>
						</div>
					</div>
				</div>
			)}
		</div>
	);
}
