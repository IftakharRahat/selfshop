"use client";
import { formatBDT } from "@/lib/format-currency";

import { CheckCircle2, Headset, LogOut, XCircle } from "lucide-react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useMemo, useState, useEffect } from "react";
import {
	type PackageInvoice,
	type PackagePlan,
	useCreatePurchaseMutation,
	useGetPricingQuery,
} from "@/redux/features/pricingApi";
import { setUser } from "@/redux/features/auth/authSlice";
import { useAppDispatch, useAppSelector } from "@/redux/hooks";
import { handleAsyncWithToast } from "@/utils/handleAsyncWithToast";
import { trackInitiateCheckout, trackViewPricing } from "@/lib/trackingEvents";

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
		{ label: "Free video course", enabled: true },
		{ label: "Ticketing system", enabled: true },
		{ label: "Product request", enabled: true },
		{ label: "Order analytics", enabled: true },
		{ label: "Winning Product", enabled: true },
		{ label: "Bulk Wholesale Order", enabled: true },
		{ label: "Sales Bonus Campaign", enabled: true },
		{ label: "Livechat Support", enabled: true },
		{ label: "Ecommerce Website", enabled: false },
		{ label: "Free .com domain", enabled: false },
		{ label: "Free One Year Hosting Support", enabled: false },
		{ label: "Technical support", enabled: false },
	],
	standard: [
		{ label: "Dashboard access", enabled: true },
		{ label: "Order management", enabled: true },
		{ label: "Referral income", enabled: true },
		{ label: "Free video course", enabled: true },
		{ label: "Ticketing system", enabled: true },
		{ label: "Product request", enabled: true },
		{ label: "Order analytics", enabled: true },
		{ label: "Winning Product", enabled: true },
		{ label: "Bulk Wholesale Order", enabled: true },
		{ label: "Sales Bonus Campaign", enabled: true },
		{ label: "Livechat Support", enabled: true },
		{ label: "Ecommerce Website", enabled: true },
		{ label: "Free .com domain", enabled: true },
		{ label: "Free One Year Hosting Support", enabled: true },
		{ label: "Technical support", enabled: true },
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
	const dispatch = useAppDispatch();
	const token = useAppSelector((state) => state.auth.access_token);

	// Redirect unauthenticated users to home page with auth modal (standalone page only)
	useEffect(() => {
		if (!token && !onInvoiceCreated) {
			router.replace("/?showAuth=true");
		}
	}, [token, onInvoiceCreated, router]);

	const { data: pricingData, isLoading } = useGetPricingQuery(undefined, { skip: !token });
	const [createPurchase] = useCreatePurchaseMutation();
	const [selectedPlanId, setSelectedPlanId] = useState<number | null>(null);

	const packagePlans = pricingData?.data?.packages ?? [];

	// Fire view_pricing event when pricing page loads
	useEffect(() => {
		if (token && packagePlans.length > 0) {
			trackViewPricing();
		}
	// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [token, packagePlans.length]);

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

		// If user is not logged in, redirect to storefront with auth modal
		if (!token) {
			router.push("/?showAuth=true");
			return;
		}

		const discount = normalizePrice(selectedPlan.discount_price);
		const regular = normalizePrice(selectedPlan.price);
		const amount = discount > 0 ? discount : regular;

		// Fire InitiateCheckout tracking event
		trackInitiateCheckout({
			value: amount,
			currency: "BDT",
			packageName: selectedPlan.package_name,
			packageId: selectedPlan.id,
		});

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

	const [showLogoutModal, setShowLogoutModal] = useState(false);

	const handleLogout = () => {
		setShowLogoutModal(true);
	};

	const confirmLogout = async () => {
		setShowLogoutModal(false);
		await dispatch(setUser({ access_token: null }));
		localStorage.removeItem("access_token");
		router.replace("/");
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

	const includedFeatures = features.filter((f) => f.enabled);
	const excludedFeatures = features.filter((f) => !f.enabled);

	return (
		<>
			<div className="w-full max-w-md mx-auto">
				{token ? (
					<div className="mb-4 flex justify-end">
						<button
							type="button"
							onClick={handleLogout}
							className="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 shadow-sm hover:bg-gray-50 transition-colors"
						>
							<LogOut className="h-3.5 w-3.5" />
							Logout
						</button>
					</div>
				) : null}

				<p className="text-center text-sm text-gray-500 mb-3">
					Select your reseller package and continue payment.
				</p>

				{/* Plan Toggle */}
				<div className="relative rounded-full bg-gray-100 p-1 flex items-center mb-4">
					{packagePlans.slice(0, 2).map((plan) => {
						const isSelected = plan.id === selectedPlan.id;
						return (
							<button
								key={plan.id}
								type="button"
								onClick={() => handleSelectPlan(plan.id)}
								className={`relative z-10 flex-1 rounded-full px-4 py-2 text-sm font-semibold transition-all duration-200 ${isSelected
									? "bg-white text-gray-900 shadow-md"
									: "text-gray-500 hover:text-gray-700"
									}`}
							>
								{plan.package_name}
							</button>
						);
					})}
				</div>

				{/* Pricing Card */}
				<div className="rounded-2xl overflow-hidden shadow-lg border border-gray-100">
					{/* Gradient Price Header */}
					<div className="bg-gradient-to-br from-[#1e1b4b] to-[#312e81] px-5 py-4 text-center">
						<p className="text-indigo-300 text-xs font-medium uppercase tracking-wider mb-1">
							<span className="font-bold text-white text-sm">{selectedPlan.package_name}</span> Plan
						</p>
						<div className="flex items-baseline justify-center gap-1.5">
							{discountPrice > 0 ? (
								<span className="text-base text-indigo-400/70 line-through">
									৳{formatBDT(regularPrice, 0)}
								</span>
							) : null}
							<span className="text-3xl font-bold text-white">
								৳{formatBDT(payablePrice, 0)}
							</span>
						</div>
						<p className="text-indigo-300 text-sm mt-1">
							/ {selectedPlan.validity ?? 12} month{Number(selectedPlan.validity ?? 12) > 1 ? "s" : ""}
						</p>
					</div>

					{/* Features */}
					<div className="bg-white px-5 py-3">
						{/* Included Features */}
						{includedFeatures.length > 0 && (
							<div className="mb-3">
								<p className="text-[11px] font-semibold uppercase tracking-wider text-gray-400 mb-1.5">
									What&apos;s included
								</p>
								<div className="space-y-1">
									{includedFeatures.map((feature) => (
										<div key={feature.label} className="flex items-center gap-2.5">
											<div className="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-50 flex-shrink-0">
												<CheckCircle2 className="h-3.5 w-3.5 text-emerald-500" />
											</div>
											<span className="text-sm text-gray-700">{feature.label}</span>
										</div>
									))}
								</div>
							</div>
						)}

						{/* Excluded Features */}
						{excludedFeatures.length > 0 && (
							<div className="pt-2 border-t border-gray-100">
								<p className="text-[11px] font-semibold uppercase tracking-wider text-gray-400 mb-1.5">
									Not included
								</p>
								<div className="space-y-1">
									{excludedFeatures.map((feature) => (
										<div key={feature.label} className="flex items-center gap-2.5">
											<div className="flex h-5 w-5 items-center justify-center rounded-full bg-gray-50 flex-shrink-0">
												<XCircle className="h-3.5 w-3.5 text-gray-300" />
											</div>
											<span className="text-sm text-gray-400">{feature.label}</span>
										</div>
									))}
								</div>
							</div>
						)}
					</div>

					{/* CTA */}
					<div className="bg-white px-5 pb-4">
						<button
							type="button"
							onClick={handlePurchase}
							className="w-full rounded-xl bg-gradient-to-r from-[#e91e63] to-[#f06292] py-2.5 text-white font-semibold shadow-md hover:shadow-lg hover:from-[#d81b60] hover:to-[#e91e63] transition-all duration-200"
						>
							Proceed To Payment
						</button>
					</div>
				</div>

				{/* Support Banner */}
				<div className="mt-5 rounded-xl overflow-hidden border border-gray-200 shadow-sm">
					<div className="flex items-center">
						<div className="flex-1 bg-gray-50 px-4 py-3">
							<p className="text-xs text-gray-600">
								Need help with payment?
							</p>
						</div>
						<Link
							href="/support"
							className="bg-[#1e1b4b] px-5 py-3 text-white text-sm font-medium flex items-center gap-2 hover:bg-[#312e81] transition-colors"
						>
							<Headset className="h-4 w-4" />
							Support
						</Link>
					</div>
				</div>
			</div>

			{/* Logout Confirmation Modal */}
			{
				showLogoutModal && (
					<div className="fixed inset-0 z-[9999] flex items-center justify-center">
						<div
							className="absolute inset-0 bg-black/50 backdrop-blur-sm"
							onClick={() => setShowLogoutModal(false)}
						/>
						<div className="relative w-full max-w-sm mx-4 bg-white rounded-2xl shadow-2xl p-6">
							<div className="flex justify-center mb-4">
								<div className="flex h-14 w-14 items-center justify-center rounded-full bg-red-50">
									<LogOut className="h-7 w-7 text-red-500" />
								</div>
							</div>
							<div className="text-center mb-6">
								<h3 className="text-lg font-semibold text-gray-900 mb-1">Logout</h3>
								<p className="text-sm text-gray-500">Are you sure you want to log out?</p>
							</div>
							<div className="flex gap-3">
								<button
									onClick={() => setShowLogoutModal(false)}
									className="flex-1 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
								>
									Cancel
								</button>
								<button
									onClick={confirmLogout}
									className="flex-1 rounded-lg bg-[#E5005F] px-4 py-2.5 text-sm font-medium text-white hover:bg-[#c80053] transition-colors shadow-sm"
								>
									Yes, Logout
								</button>
							</div>
						</div>
					</div>
				)}
		</>
	);
}
