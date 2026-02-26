"use client";

import Link from "next/link";
import {
	ArrowUpDown,
	BarChart3,
	CreditCard,
	Home,
	MapPin,
	Menu,
	Package,
	Plus,
	ShoppingBag,
	Star,
	TrendingUp,
	Truck,
	User,
	Wallet,
	X,
	Zap,
} from "lucide-react";
import type { ComponentType, ReactNode } from "react";
import { useEffect, useState } from "react";
import { usePathname, useRouter } from "next/navigation";
import { useAppSelector } from "@/redux/hooks";
import VendorNotificationCenter from "@/components/vendor/VendorNotificationCenter";
import OneSignalInitializer from "@/components/shared/notifications/OneSignalInitializer";
import { useGetVendorProfileQuery } from "@/redux/api/vendorApi";

type NavItem = {
	label: string;
	href: string;
	icon: ComponentType<{ className?: string }>;
	exact?: boolean;
};

const navSections: Array<{ title: string; items: NavItem[] }> = [
	{
		title: "Main",
		items: [
			{ label: "Dashboard", href: "/vendor", icon: Home, exact: true },
			{ label: "Reports", href: "/vendor/reports", icon: BarChart3 },
		],
	},
	{
		title: "Orders",
		items: [
			{ label: "All orders", href: "/vendor/orders", icon: ShoppingBag },
			{ label: "Shipping methods", href: "/vendor/shipping", icon: Truck },
		],
	},
	{
		title: "Campaigns",
		items: [
			{ label: "Campaign Events", href: "/vendor/campaigns", icon: Zap },
		],
	},
	{
		title: "Products",
		items: [
			{ label: "Products", href: "/vendor/products", icon: Package },
			{ label: "Add new product", href: "/vendor/products/new", icon: Plus },
			{
				label: "Category-wise discount",
				href: "/vendor/category-discount",
				icon: ArrowUpDown,
			},
			{ label: "Product reviews", href: "/vendor/reviews", icon: Star },
		],
	},
	{
		title: "Earnings & Payouts",
		items: [
			{ label: "Earnings", href: "/vendor/earnings", icon: TrendingUp },
			{ label: "Payouts", href: "/vendor/payouts", icon: Wallet },
			{
				label: "Payout accounts",
				href: "/vendor/payout-accounts",
				icon: CreditCard,
			},
		],
	},
	{
		title: "Inventory",
		items: [
			{ label: "Inventory", href: "/vendor/inventory", icon: Package },
			{ label: "Warehouses", href: "/vendor/warehouses", icon: MapPin },
		],
	},
	{
		title: "Account",
		items: [{ label: "Profile & KYC", href: "/vendor/profile", icon: User }],
	},
];

/**
 * Shared layout shell for the vendor area with left sidebar navigation,
 * similar to common seller dashboards.
 * Auth is handled per-page so that /vendor/login stays accessible.
 */
export default function VendorLayout({ children }: { children: ReactNode }) {
	const router = useRouter();
	const pathname = usePathname();
	const [mobileNavOpen, setMobileNavOpen] = useState(false);
	const token = useAppSelector((state) => state.auth.access_token);
	const isAuthPage =
		pathname === "/vendor/login" || pathname === "/vendor/register";
	const isVendorProfilePage = pathname === "/vendor/profile";
	const shouldLoadVendorProfile = !isAuthPage && Boolean(token);
	const {
		data: vendorProfileResponse,
		isLoading: isVendorProfileLoading,
		isFetching: isVendorProfileFetching,
		isError: isVendorProfileError,
	} = useGetVendorProfileQuery(undefined, {
		skip: !shouldLoadVendorProfile,
	});
	const hasVendorProfile = Boolean(vendorProfileResponse?.data?.vendor);
	const isVendorProfileResolved =
		!shouldLoadVendorProfile ||
		(!isVendorProfileLoading && !isVendorProfileFetching);
	const notificationDisabled = isAuthPage || !token || !hasVendorProfile;
	const vendorUserId = (
		vendorProfileResponse?.data as
			| { user?: { id?: number | string | null } }
			| undefined
	)?.user?.id;

	useEffect(() => {
		if (isAuthPage) return;

		if (!token) {
			router.replace("/vendor/login");
			return;
		}

		if (!isVendorProfileResolved || isVendorProfileError) {
			return;
		}

		if (!hasVendorProfile && !isVendorProfilePage) {
			router.replace("/vendor/profile");
		}
	}, [
		hasVendorProfile,
		isAuthPage,
		isVendorProfileError,
		isVendorProfilePage,
		isVendorProfileResolved,
		router,
		token,
	]);

	useEffect(() => {
		setMobileNavOpen(false);
	}, [pathname]);

	useEffect(() => {
		if (!mobileNavOpen) return;
		const originalOverflow = document.body.style.overflow;
		document.body.style.overflow = "hidden";
		return () => {
			document.body.style.overflow = originalOverflow;
		};
	}, [mobileNavOpen]);

	const isActive = (item: NavItem) =>
		item.exact
			? pathname === item.href
			: pathname === item.href || pathname?.startsWith(`${item.href}/`);

	const activeHref = navSections
		.flatMap((section) => section.items)
		.filter((item) => isActive(item))
		.sort((a, b) => b.href.length - a.href.length)[0]?.href;

	const navItemClass = (active: boolean) =>
		`group flex items-center gap-2 rounded-md px-2.5 py-2 text-sm transition-colors ${active
			? "bg-[#2d2a5d] text-white shadow-sm"
			: "text-gray-700 hover:bg-indigo-50 hover:text-[#2d2a5d]"
		}`;

	const renderNavigation = (onItemClick?: () => void) => (
		<nav className="no-scrollbar flex-1 overflow-y-auto px-3 py-4 space-y-6 text-sm">
			{navSections.map((section) => (
				<div key={section.title}>
					<p className="px-2 mb-1 text-[11px] font-semibold uppercase tracking-wide text-gray-500">
						{section.title}
					</p>
					<ul className="space-y-1">
						{section.items.map((item) => {
							const active = item.href === activeHref;
							const Icon = item.icon;
							return (
								<li key={item.href}>
									<Link
										href={item.href}
										onClick={onItemClick}
										className={navItemClass(active)}
									>
										<Icon
											className={`h-4 w-4 shrink-0 ${active
												? "text-white"
												: "text-gray-500 group-hover:text-[#2d2a5d]"
												}`}
										/>
										<span className="truncate">{item.label}</span>
									</Link>
								</li>
							);
						})}
					</ul>
				</div>
			))}
		</nav>
	);

	const shouldBlockVendorChildren =
		!isAuthPage &&
		(!token ||
			!isVendorProfileResolved ||
			isVendorProfileError ||
			(!hasVendorProfile && !isVendorProfilePage));

	const guardMessage = !token
		? "Redirecting to vendor login..."
		: !isVendorProfileResolved
			? "Checking vendor access..."
			: isVendorProfileError
				? "Unable to verify vendor access. Please refresh the page."
			: "Redirecting to vendor profile setup...";

	return (
		<div className="min-h-screen bg-gray-50 flex">
			<OneSignalInitializer
				panel="supplier"
				userId={vendorUserId ?? null}
				enabled={!isAuthPage && Boolean(token) && Boolean(vendorUserId)}
			/>

			{!isAuthPage && (
				<aside className="hidden md:flex md:flex-col w-64 bg-white border-r border-gray-200">
					<div className="h-14 flex items-center px-5 border-b border-gray-200">
						<Link
							href="/vendor"
							className="text-lg font-semibold tracking-tight text-[#2d2a5d]"
						>
							SelfShop Supplier
						</Link>
					</div>

					{renderNavigation()}
				</aside>
			)}

			{!isAuthPage && (
				<>
					<button
						type="button"
						aria-label="Close menu"
						onClick={() => setMobileNavOpen(false)}
						className={`fixed inset-0 z-30 bg-black/40 transition-opacity md:hidden ${mobileNavOpen ? "opacity-100" : "pointer-events-none opacity-0"
							}`}
					/>
					<aside
						className={`fixed inset-y-0 left-0 z-40 w-64 max-w-[80vw] flex flex-col bg-white border-r border-gray-200 md:hidden transform transition-transform ${mobileNavOpen ? "translate-x-0" : "-translate-x-full"
							}`}
					>
						<div className="h-14 flex items-center justify-between px-4 border-b border-gray-200">
							<Link
								href="/vendor"
								className="text-base font-semibold tracking-tight text-[#2d2a5d]"
							>
								SelfShop Supplier
							</Link>
							<button
								type="button"
								aria-label="Close menu"
								onClick={() => setMobileNavOpen(false)}
								className="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 text-gray-600 hover:bg-gray-100"
							>
								<X className="h-4 w-4" />
							</button>
						</div>
						{renderNavigation(() => setMobileNavOpen(false))}
					</aside>
				</>
			)}

			<div className="flex-1 min-w-0 flex flex-col">
				{!isAuthPage && (
					<header className="hidden md:flex sticky top-0 z-10 bg-gray-50/95 backdrop-blur border-b border-gray-200 px-4 py-3 sm:px-6 md:px-8 justify-end">
						<VendorNotificationCenter disabled={notificationDisabled} />
					</header>
				)}

				<header className="md:hidden sticky top-0 z-20 bg-white border-b border-gray-200 px-3 py-3 shadow-sm sm:px-4">
					<div className="flex items-center justify-between gap-2">
						<div className="flex min-w-0 items-center gap-2">
							{!isAuthPage && (
								<button
									type="button"
									aria-label="Open menu"
									onClick={() => setMobileNavOpen(true)}
									className="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-gray-200 text-gray-700 hover:bg-gray-50"
								>
									<Menu className="h-4 w-4" />
								</button>
							)}
							<Link
								href="/vendor"
								className="truncate text-base font-semibold tracking-tight text-[#2d2a5d] sm:text-lg"
							>
								SelfShop Supplier
							</Link>
						</div>
						{!isAuthPage && (
							<div className="flex items-center gap-2">
								<VendorNotificationCenter disabled={notificationDisabled} />
								<Link
									href="/vendor/profile"
									className="inline-flex h-9 w-9 items-center justify-center rounded-md border border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-gray-900"
									aria-label="Profile"
								>
									<User className="h-4 w-4" />
								</Link>
							</div>
						)}
					</div>
				</header>

				<main className="min-w-0 px-3 py-4 sm:px-4 sm:py-6 md:px-8 md:py-8">
					{shouldBlockVendorChildren ? (
						<div className="rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-600">
							{guardMessage}
						</div>
					) : (
						children
					)}
				</main>
			</div>
		</div>
	);
}
