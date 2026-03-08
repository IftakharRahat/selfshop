"use client";

import { Check, Copy, Headset, LogOut } from "lucide-react";
import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { useMemo, useState } from "react";
import Swal from "sweetalert2";
import {
	useGetPricingQuery,
	useInitiatePackagePaymentMutation,
} from "@/redux/features/pricingApi";
import { setUser } from "@/redux/features/auth/authSlice";
import { useAppDispatch, useAppSelector } from "@/redux/hooks";
import { handleAsyncWithToast } from "@/utils/handleAsyncWithToast";

const normalizeNumber = (value: unknown): number => {
	if (typeof value === "number") return Number.isFinite(value) ? value : 0;
	if (typeof value === "string") {
		const parsed = Number(value);
		return Number.isFinite(parsed) ? parsed : 0;
	}
	return 0;
};

export function InvoicePage() {
	const router = useRouter();
	const dispatch = useAppDispatch();
	const token = useAppSelector((state) => state.auth.access_token);
	const searchParams = useSearchParams();
	const { data: pricingData } = useGetPricingQuery();
	const [initiatePayment] = useInitiatePackagePaymentMutation();
	const [copied, setCopied] = useState(false);

	const queryInvoiceId = searchParams.get("invoiceID")?.trim() ?? "";
	const queryInvoiceDbId = normalizeNumber(searchParams.get("invoice_id"));
	const queryPackageId = normalizeNumber(searchParams.get("package_id"));

	const currentInvoice = useMemo(() => {
		const apiInvoice = pricingData?.data?.invoice ?? null;

		const fallbackId = queryInvoiceDbId || apiInvoice?.id || 0;
		const fallbackInvoiceCode = queryInvoiceId || apiInvoice?.invoiceID || "";
		const fallbackPackageId = queryPackageId || normalizeNumber(apiInvoice?.package_id);

		if (!fallbackId && !fallbackInvoiceCode) return null;

		return {
			id: fallbackId,
			invoiceID: fallbackInvoiceCode,
			package_id: fallbackPackageId || undefined,
			amount: normalizeNumber(apiInvoice?.amount),
			payable_amount: normalizeNumber(apiInvoice?.payable_amount),
			status: apiInvoice?.status,
		};
	}, [pricingData?.data?.invoice, queryInvoiceDbId, queryInvoiceId, queryPackageId]);

	const packageName = useMemo(() => {
		if (!currentInvoice?.package_id) return "Selected Package";
		const plans = pricingData?.data?.packages ?? [];
		const target = plans.find((plan) => plan.id === currentInvoice.package_id);
		return target?.package_name ?? "Selected Package";
	}, [currentInvoice?.package_id, pricingData?.data?.packages]);

	const payableAmount = useMemo(() => {
		if (!currentInvoice) return 0;
		return normalizeNumber(currentInvoice.payable_amount) > 0
			? normalizeNumber(currentInvoice.payable_amount)
			: normalizeNumber(currentInvoice.amount);
	}, [currentInvoice]);

	const handleCopy = async () => {
		if (!currentInvoice?.invoiceID) return;
		await navigator.clipboard.writeText(currentInvoice.invoiceID);
		setCopied(true);
		setTimeout(() => setCopied(false), 2000);
	};

	const handlePayment = async () => {
		if (!currentInvoice?.id) return;

		const result = await handleAsyncWithToast(
			async () => initiatePayment({ invoice_id: currentInvoice.id }),
			true,
			"Preparing payment gateway...",
			"Redirecting to payment gateway",
		);

		const gatewayUrl = result?.data?.data?.gateway_url;
		if (gatewayUrl) {
			window.location.assign(gatewayUrl);
		}
	};

	const handleLogout = async () => {
		const result = await Swal.fire({
			title: "Are you sure?",
			icon: "warning",
			showCancelButton: true,
			confirmButtonColor: "#3085d6",
			cancelButtonColor: "#d33",
			confirmButtonText: "Yes, Log out",
		});

		if (!result.isConfirmed) return;

		await dispatch(setUser({ access_token: null }));
		localStorage.removeItem("access_token");
		router.replace("/");
	};

	if (!currentInvoice) {
		return (
			<div className="max-w-3xl mx-auto mt-8 rounded-2xl border border-pink-100 bg-white p-8 text-center text-gray-500">
				No active invoice found. Please select a package first.
			</div>
		);
	}

	return (
		<div className="w-full max-w-3xl mx-auto py-6">
			{token ? (
				<div className="mb-4 flex justify-end">
					<button
						type="button"
						onClick={handleLogout}
						className="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
					>
						<LogOut className="h-4 w-4" />
						Logout
					</button>
				</div>
			) : null}

			<div className="rounded-2xl border border-pink-100 bg-white p-5 sm:p-6 shadow-sm">
				<p className="text-gray-600 text-sm sm:text-base leading-7">
					Thanks for selecting your package. We generated an invoice for you.
					Choose any gateway to payment. Your account will
					activate automatically after successful payment confirmation.
				</p>

				<div className="mt-5 rounded-2xl border border-pink-200 bg-pink-50/30 p-4 sm:p-5">
					<div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
						<div>
							<p className="text-xs text-gray-500">Invoice ID</p>
							<p className="text-2xl font-bold text-pink-600">
								{currentInvoice.invoiceID}
							</p>
						</div>
						<button
							type="button"
							onClick={handleCopy}
							className="h-11 rounded-xl px-5 bg-[#E5005F] text-white font-semibold hover:bg-[#ce0055] transition-colors flex items-center justify-center gap-2"
						>
							{copied ? <Check className="h-4 w-4" /> : <Copy className="h-4 w-4" />}
							{copied ? "Copied" : "Copy"}
						</button>
					</div>

					<div className="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
						<div className="rounded-xl border border-pink-100 bg-white p-3">
							<p className="text-xs text-gray-500">Package</p>
							<p className="font-semibold text-gray-800">{packageName}</p>
						</div>
						<div className="rounded-xl border border-pink-100 bg-white p-3">
							<p className="text-xs text-gray-500">Payable</p>
							<p className="font-semibold text-gray-800">Tk {payableAmount.toLocaleString()}</p>
						</div>
					</div>
				</div>

				<button
					type="button"
					onClick={handlePayment}
					className="mt-5 w-full rounded-xl bg-emerald-600 py-3.5 text-white text-lg font-bold hover:bg-emerald-700 transition-colors"
				>
					Pay Now
				</button>
			</div>

			<div className="mt-6 overflow-hidden rounded-2xl border border-emerald-300">
				<div className="grid grid-cols-3">
					<div className="col-span-2 bg-[#FF5C3E] px-4 py-3 text-white text-sm font-semibold">
						Need help with package payment? Contact our team now.
					</div>
					<Link
						href="/support"
						className="bg-emerald-600 px-4 py-3 text-white font-semibold flex items-center justify-center gap-2 hover:bg-emerald-700 transition-colors"
					>
						<Headset className="h-5 w-5" />
						<span>Support</span>
					</Link>
				</div>
			</div>
		</div>
	);
}
