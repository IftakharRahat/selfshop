"use client";

import { CheckCircle2, Headset, XCircle } from "lucide-react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useMemo, useState } from "react";
import {
	type PackageInvoice,
	type PackagePlan,
	useCreatePurchaseMutation,
	useGetPricingQuery,
} from "@/redux/features/pricingApi";
import { handleAsyncWithToast } from "@/utils/handleAsyncWithToast";

type PricingFeature = {
	label: string;
	enabled: boolean;
};

type PricingPageProps = {
	onInvoiceCreated?: (invoice: PackageInvoice) => void;
};

const FEATURE_MATRIX: Record<"basic" | "standard", PricingFeature[]> = {
	basic: [
		{ label: "Dashboard access", enabled: true },
		{ label: "Order management", enabled: true },
		{ label: "Referral income", enabled: true },
		{ label: "Ad top-up removal", enabled: false },
		{ label: "Order bonus", enabled: false },
		{ label: "Monthly order report", enabled: false },
		{ label: "Free video course", enabled: true },
		{ label: "Fast checker", enabled: false },
		{ label: "Team member management", enabled: false },
		{ label: "Product request", enabled: false },
		{ label: "Ticketing system", enabled: true },
		{ label: "Order analytics", enabled: false },
	],
	standard: [
		{ label: "Dashboard access", enabled: true },
		{ label: "Order management", enabled: true },
		{ label: "Referral income", enabled: true },
		{ label: "Ad top-up removal", enabled: true },
		{ label: "Order bonus", enabled: true },
		{ label: "Monthly order report", enabled: true },
		{ label: "Free video course", enabled: true },
		{ label: "Fast checker", enabled: true },
		{ label: "Team member management", enabled: true },
		{ label: "Product request", enabled: true },
		{ label: "Ticketing system", enabled: true },
		{ label: "Order analytics", enabled: true },
	],
};

const normalizePrice = (value: unknown): number => {
	if (typeof value === "number") return Number.isFinite(value) ? value : 0;
	if (typeof value === "string") {
		const parsed = Number(value);
		return Number.isFinite(parsed) ? parsed : 0;
	}
	return 0;
};

const resolvePlanType = (plan: PackagePlan): "basic" | "standard" => {
	const name = String(plan?.package_name ?? "").toLowerCase();
	return name.includes("standard") || name.includes("premium")
		? "standard"
		: "basic";
};

const buildInvoiceUrl = (invoice: PackageInvoice): string => {
	const query = new URLSearchParams();
	if (invoice?.id) query.set("invoice_id", String(invoice.id));
	if (invoice?.invoiceID) query.set("invoiceID", String(invoice.invoiceID));
	if (invoice?.package_id) query.set("package_id", String(invoice.package_id));
	return `/invoice?${query.toString()}`;
};

export function PricingPage({ onInvoiceCreated }: PricingPageProps) {
	const router = useRouter();
	const { data: pricingData, isLoading } = useGetPricingQuery();
	const [createPurchase] = useCreatePurchaseMutation();
	const [selectedPlanId, setSelectedPlanId] = useState<number | null>(null);

	const packagePlans = pricingData?.data?.packages ?? [];

	const selectedPlan = useMemo(() => {
		if (!packagePlans.length) return null;
		if (!selectedPlanId) return packagePlans[0];
		return packagePlans.find((plan) => plan.id === selectedPlanId) ?? packagePlans[0];
	}, [packagePlans, selectedPlanId]);

	const handleSelectPlan = (planId: number) => {
		setSelectedPlanId(planId);
	};

	const handlePurchase = async () => {
		if (!selectedPlan) return;

		const discount = normalizePrice(selectedPlan.discount_price);
		const regular = normalizePrice(selectedPlan.price);
		const amount = discount > 0 ? discount : regular;

		const result = await handleAsyncWithToast(
			async () =>
				createPurchase({
					package_id: selectedPlan.id,
					amount,
				}),
			true,
			"Creating invoice...",
			"Invoice created successfully",
		);

		const invoice = result?.data?.data?.invoice;
		if (!invoice?.invoiceID) return;

		if (onInvoiceCreated) {
			onInvoiceCreated(invoice);
			return;
		}

		router.push(buildInvoiceUrl(invoice));
	};

	if (isLoading) {
		return (
			<div className="w-full max-w-3xl mx-auto rounded-2xl border border-pink-100 bg-white p-8 text-center text-gray-500">
				Loading packages...
			</div>
		);
	}

	if (!packagePlans.length || !selectedPlan) {
		return (
			<div className="w-full max-w-3xl mx-auto rounded-2xl border border-pink-100 bg-white p-8 text-center text-gray-500">
				No package is available right now. Please contact support.
			</div>
		);
	}

	const currentPlanType = resolvePlanType(selectedPlan);
	const features = FEATURE_MATRIX[currentPlanType];
	const regularPrice = normalizePrice(selectedPlan.price);
	const discountPrice = normalizePrice(selectedPlan.discount_price);
	const payablePrice = discountPrice > 0 ? discountPrice : regularPrice;

	return (
		<div className="w-full max-w-3xl mx-auto">
			<p className="text-center text-sm sm:text-base text-gray-600 mb-5">
				Thanks for completing registration. Select your reseller package and continue payment.
			</p>

			<div className="rounded-2xl border border-emerald-300 bg-emerald-500/95 px-3 py-3 flex items-center justify-between gap-2 mb-5">
				{packagePlans.slice(0, 2).map((plan) => {
					const isSelected = plan.id === selectedPlan.id;
					return (
						<button
							key={plan.id}
							type="button"
							onClick={() => handleSelectPlan(plan.id)}
							className={`min-w-[140px] rounded-full px-4 py-2 text-sm font-semibold transition-colors ${isSelected
								? "bg-white text-gray-900 shadow"
								: "bg-transparent text-emerald-50 hover:bg-emerald-600"
								}`}
						>
							{plan.package_name}
						</button>
					);
				})}
			</div>

			<div className="rounded-2xl border border-pink-200 bg-white shadow-sm overflow-hidden">
				<div className="border-b border-pink-100 px-4 sm:px-6 py-4">
					<div className="text-center">
						<div className="flex items-center justify-center gap-2 mb-1">
							{discountPrice > 0 ? (
								<span className="text-lg text-red-500 line-through">
									Tk {regularPrice.toLocaleString()}
								</span>
							) : null}
							<span className="text-3xl sm:text-4xl font-bold text-gray-900">
								Tk {payablePrice.toLocaleString()}
							</span>
							<span className="text-base text-gray-600">
								/ {selectedPlan.validity ?? 12} month{Number(selectedPlan.validity ?? 12) > 1 ? "s" : ""}
							</span>
						</div>
						<p className="text-xs sm:text-sm text-gray-500">
							Package: <span className="font-semibold text-gray-700">{selectedPlan.package_name}</span>
						</p>
					</div>
				</div>

				<div className="px-4 sm:px-6 py-5 space-y-2">
					{features.map((feature) => (
						<div key={feature.label} className="flex items-start gap-2.5">
							{feature.enabled ? (
								<CheckCircle2 className="mt-0.5 h-5 w-5 text-emerald-500 flex-shrink-0" />
							) : (
								<XCircle className="mt-0.5 h-5 w-5 text-red-500 flex-shrink-0" />
							)}
							<span className="text-sm text-gray-700">{feature.label}</span>
						</div>
					))}
				</div>

				<div className="px-4 sm:px-6 pb-6">
					<button
						type="button"
						onClick={handlePurchase}
						className="w-full rounded-xl bg-[#E85A3A] py-3 text-white font-semibold hover:bg-[#da4d2d] transition-colors"
					>
						Proceed To Payment
					</button>
				</div>
			</div>

			<Link
				href="/contact"
				className="mt-6 block overflow-hidden rounded-2xl border border-emerald-300 hover:border-emerald-400 transition-colors"
			>
				<div className="grid grid-cols-3">
					<div className="col-span-2 bg-[#FF5C3E] px-4 py-3 text-white text-sm font-semibold">
						Need help with package payment? Contact our team now.
					</div>
					<div className="bg-emerald-600 px-4 py-3 text-white font-semibold flex items-center justify-center gap-2">
						<Headset className="h-5 w-5" />
						<span>Support</span>
					</div>
				</div>
			</Link>
		</div>
	);
}
