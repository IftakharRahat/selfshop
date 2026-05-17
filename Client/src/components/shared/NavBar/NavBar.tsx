/* eslint-disable @typescript-eslint/no-unused-vars */
/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import {
	Button,
	ConfigProvider,
	Divider,
	Dropdown,
	Form,
	Input,
	Menu,
	Modal,
	Tabs,
} from "antd";
import { ChevronDown, Loader2, Lock, Menu as MenuIcon, Search, ShoppingCart, User, X } from "lucide-react";
import Image from "next/image";
import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { useCallback, useEffect, useRef, useState } from "react";
import { FaApple } from "react-icons/fa";
import { FcGoogle } from "react-icons/fc";
import Swal from "sweetalert2";
import BecomeADropshiiper from "@/assets/icons/Become a dropshiiper.png";
import logo from "@/assets/icons/NavLogo.png";
import shopLogo from "@/assets/icons/shoplogo.png";
import TrackYourOrder from "@/assets/icons/Track your order.png";
import { PricingPage } from "@/components/pages/dashboard/pricing-page";
import {
	useGetMeQuery,
	useLoginMutation,
	useRegisterMutation,
} from "@/redux/features/auth/authApi";
import { setUser } from "@/redux/features/auth/authSlice";
import { useGetAllCartItemsQuery } from "@/redux/features/cartApi";
import { type PackageInvoice } from "@/redux/features/pricingApi";
import {
	useGetAllMenusQuery,
	useGetAllNavbarCategoryDropdownOptionsQuery,
} from "@/redux/features/home/homeApi";
import { useAppDispatch, useAppSelector } from "@/redux/hooks";
import { handleAsyncWithToast } from "@/utils/handleAsyncWithToast";
import { getImageUrl } from "@/lib/utils";
import AuthModal from "../AuthModal";
import CartDrawer from "../CartDrawer/CartDrawer";
import DropDownBtn from "./DropDownBtn";
import { useIsActiveReseller } from "@/hooks/useIsActiveReseller";

export default function Navbar() {
	const dispatch = useAppDispatch();
	const token = useAppSelector((state) => state.auth.access_token);
	const { data: user } = useGetMeQuery(token, { skip: !token });
	const { isActive: isResellerActive } = useIsActiveReseller();
	const [isCartOpen, setIsCartOpen] = useState(false);
	const { data: cartItems } = useGetAllCartItemsQuery(undefined, {
		skip: !token,
	});

	const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
	const [isLoginModalOpen, setIsLoginModalOpen] = useState(false);
	const [isPricingModalOpen, setIsPricingModalOpen] = useState(false);
	const [authInitialMode, setAuthInitialMode] = useState<"login" | "register">("login");
	const [campaignCode, setCampaignCode] = useState<string | undefined>(undefined);
	const [searchValue, setSearchValue] = useState("");
	const [suggestions, setSuggestions] = useState<any[]>([]);
	const [showSuggestions, setShowSuggestions] = useState(false);
	const [isSearching, setIsSearching] = useState(false);
	const searchRef = useRef<HTMLDivElement>(null);
	const mobileSearchRef = useRef<HTMLDivElement>(null);
	const debounceTimer = useRef<NodeJS.Timeout | null>(null);

	// Debounced live search
	const fetchSuggestions = useCallback(async (query: string) => {
		if (query.trim().length < 2) {
			setSuggestions([]);
			setShowSuggestions(false);
			return;
		}
		setIsSearching(true);
		try {
			const res = await fetch(
				`${process.env.NEXT_PUBLIC_BASE_URL}/search?keywords=${encodeURIComponent(query)}&limit=8`
			);
			const data = await res.json();
			const items = data?.data?.data || data?.data || [];
			setSuggestions(Array.isArray(items) ? items : []);
			setShowSuggestions(true);
		} catch {
			setSuggestions([]);
		}
		setIsSearching(false);
	}, []);

	const handleSearchInput = (value: string) => {
		setSearchValue(value);
		if (debounceTimer.current) clearTimeout(debounceTimer.current);
		debounceTimer.current = setTimeout(() => fetchSuggestions(value), 300);
	};

	const handleSearchFocus = () => {
		if (searchValue.trim().length >= 2) {
			if (suggestions.length > 0) {
				setShowSuggestions(true);
			} else {
				fetchSuggestions(searchValue);
			}
		}
	};

	// Close suggestions on click outside
	useEffect(() => {
		const handleClickOutside = (e: MouseEvent) => {
			if (
				searchRef.current && !searchRef.current.contains(e.target as Node) &&
				mobileSearchRef.current && !mobileSearchRef.current.contains(e.target as Node)
			) {
				setShowSuggestions(false);
			}
		};
		document.addEventListener('mousedown', handleClickOutside);
		return () => document.removeEventListener('mousedown', handleClickOutside);
	}, []);

	const handleSuggestionClick = (slug: string) => {
		setShowSuggestions(false);
		router.push(`/product/${slug}`);
	};

	const handleSearchSubmit = () => {
		if (searchValue.trim()) {
			setShowSuggestions(false);
			router.push(`/search?keywords=${searchValue}`);
		}
	};



	// Suggestion dropdown component
	const SuggestionDropdown = () => {
		if ((!showSuggestions && !isSearching) || searchValue.trim().length < 2) return null;
		return (
			<div className="absolute left-0 right-0 top-full mt-1 bg-white rounded-xl shadow-2xl border border-gray-100 z-[9999] max-h-[400px] overflow-y-auto">
				{isSearching ? (
					<div className="flex items-center justify-center py-6">
						<Loader2 className="w-5 h-5 text-pink-500 animate-spin" />
						<span className="ml-2 text-sm text-gray-500">Searching...</span>
					</div>
				) : suggestions.length === 0 ? (
					<div className="py-6 text-center text-sm text-gray-400">No products found</div>
				) : (
					<>
						{suggestions.map((product: any) => (
							<div
								key={product.id}
								onClick={() => handleSuggestionClick(product.ProductSlug)}
								className="flex items-center gap-3 px-4 py-2.5 hover:bg-pink-50 cursor-pointer transition-colors border-b border-gray-50 last:border-b-0"
							>
								<div className="w-10 h-10 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0">
									{product.ViewProductImage && (
										<img
											src={getImageUrl(product.ViewProductImage)}
											alt={product.ProductName}
											className="w-full h-full object-cover"
										/>
									)}
								</div>
								<div className="flex-1 min-w-0">
									<p className="text-sm font-medium text-gray-800 truncate">{product.ProductName}</p>
									{isResellerActive ? (
										<p className="text-xs text-pink-600 font-semibold">৳ {product.ProductSalePrice || product.ProductRegularPrice}</p>
									) : (
										<div className="flex items-center gap-1 mt-0.5">
											<span className="text-gray-400 text-xs font-bold">***</span>
											<span className="text-[9px] text-pink-600 font-bold bg-pink-50 px-1 py-0.5 rounded flex items-center gap-0.5 w-fit">
												<Lock className="w-2.5 h-2.5 shrink-0" />
												<span>Active profile required</span>
											</span>
										</div>
									)}
								</div>
							</div>
						))}
						{searchValue.trim() && (
							<div
								onClick={handleSearchSubmit}
								className="px-4 py-3 text-center text-sm font-medium text-pink-600 hover:bg-pink-50 cursor-pointer border-t border-gray-100"
							>
								See all results for "{searchValue}"
							</div>
						)}
					</>
				)}
			</div>
		);
	};

	const router = useRouter();
	const searchParams = useSearchParams();

	// Auto-open auth modal when redirected with ?showAuth=true or ?showAuth=register
	useEffect(() => {
		const showAuth = searchParams.get("showAuth");
		if (showAuth && !token) {
			if (showAuth === "register") {
				setAuthInitialMode("register");
			} else {
				setAuthInitialMode("login");
			}
			// Capture campaign code if present
			const campaign = searchParams.get("campaign");
			if (campaign) {
				setCampaignCode(campaign);
			}
			setIsLoginModalOpen(true);
			// Clean up the URL by removing params
			const url = new URL(window.location.href);
			url.searchParams.delete("showAuth");
			url.searchParams.delete("campaign");
			window.history.replaceState({}, "", url.pathname + url.search);
		}
	}, [searchParams, token]);

	const handleLogout = async () => {
		const result = await Swal.fire({
			title: "Are you sure?",
			icon: "warning",
			showCancelButton: true,
			confirmButtonColor: "#3085d6",
			cancelButtonColor: "#d33",
			confirmButtonText: "Yes, Log out",
		});
		if (result.isConfirmed) {
			await dispatch(
				setUser({
					access_token: null,
				}),
			);
			await localStorage.removeItem("access_token");
			// await dispatch(logout());
			Swal.fire({
				title: "Logged out!",
				icon: "success",
				timer: 1500,
				showConfirmButton: false,
			});
		}
	};

	const { data: categoryDropdownOptions } =
		useGetAllNavbarCategoryDropdownOptionsQuery(undefined);
	const { data: menuOptions } = useGetAllMenusQuery(undefined);

	// Map API data to DropdownMenu format
	const mappedMenuData =
		categoryDropdownOptions?.data?.map((cat: any, index: number) => ({
			id: index,
			name: cat.category_name,
			href: `/product-filter?category=${cat.slug}`, // ✅ optional
			sub_items: cat.subcategories?.map((sub: any, index: number) => ({
				id: index,
				name: sub.sub_category_name,
				href: `/product-filter?subcategory=${sub.slug}`, // ✅ subcategory endpoint
				sub_sub_items: sub.minicategories?.map((mini: any, miniIndex: number) => ({
					id: miniIndex,
					name: mini.mini_category_name,
					href: `/product-filter?minicategory=${mini.slug}`,
				})) || [], // If you don’t have deeper levels
			})),
		})) || [];

	const categories =
		menuOptions?.data?.map((menu: any) => ({
			item: menu?.category_name,
			icon: menu?.category_icon,
			slug: menu?.slug,
		})) || [];

	const userMenu = (
		<Menu>
			<Menu.Item key="profile">
				<Link href="/dashboard/settings">Profile</Link>
			</Menu.Item>
			<Menu.Item key="logout" onClick={handleLogout} danger>
				Logout
			</Menu.Item>
		</Menu>
	);

	const [form] = Form.useForm();
	const [activeTab, setActiveTab] = useState("reseller");
	const [isLogin, setIsLogin] = useState(true);
	const [isRegistration, setIsRegistration] = useState(false);
	const [expandedCategory, setExpandedCategory] = useState<number | null>(null);

	const handlePackageInvoiceCreated = (invoice: PackageInvoice) => {
		setIsPricingModalOpen(false);
		if (!invoice?.invoiceID) return;

		const query = new URLSearchParams();
		if (invoice?.id) query.set("invoice_id", String(invoice.id));
		if (invoice?.invoiceID) query.set("invoiceID", String(invoice.invoiceID));
		if (invoice?.package_id) query.set("package_id", String(invoice.package_id));

		router.push(`/invoice?${query.toString()}`);
	};

	return (
		<>
			{/* <LoginModal open={isLoginModalOpen} onCancel={() => setIsLoginModalOpen(false)} /> */}
			{/* Top notification bar */}
			<div className="hidden lg:block bg-gradient-to-r from-[#B9006E] to-[#E7005E] py-2 text-white px-4 sm:px-6 lg:px-8">
				<div className="container flex items-center justify-between  ">
					<div className="flex items-center">
						<img src={shopLogo.src} alt="Shop Logo" className="w-6 h-6 mr-2" />
						<p>Explore Mega offer winter for getting hottest drops.</p>
					</div>
					<div className="flex items-center gap-6">
						<Link href="/dashboard/track-orders" className="flex items-center hover:opacity-80 transition-opacity">
							<img
								src={TrackYourOrder.src}
								alt="Track Your Order"
								className="w-6 h-6 mr-2"
							/>
							<p>Track your order</p>
						</Link>
						<div className="flex items-center">
							<img
								src={BecomeADropshiiper.src}
								alt="Become a Dropshipper"
								className="w-6 h-6 mr-2"
							/>
							<p>Become a dropshipper</p>
						</div>
					</div>
				</div>
			</div>
			<div className="bg-gradient-to-r from-[#D701640F] to-[#D701640F] ">
				{/* Main navbar */}
				<div className="container  py-3 lg:py-3 border-b border-b-[#4E4E4E17] ">
					<div className="flex items-center justify-between h-12">
						{/* Logo */}
						<Link href="/">
							<img src={logo.src} alt="SelfShop Logo" className="w-44 " />
						</Link>

						{/* Search bar - hidden on mobile */}
						<div className="hidden lg:flex flex-1 max-w-2xl mx-8">
							<div className="relative w-full" ref={searchRef}>
								<input
									type="text"
									placeholder="Search product or Store"
									value={searchValue}
									onChange={(e) => handleSearchInput(e.target.value)}
									onKeyDown={(e) => e.key === "Enter" && handleSearchSubmit()}
									onFocus={handleSearchFocus}
									className="w-full pl-4 pr-12 py-2 border bg-white border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-[#E7005E] "
									autoComplete="off"
								/>
								<button
									onClick={handleSearchSubmit}
									className="absolute right-0 top-0 h-full px-4 bg-[#E7005E] hover:bg-pink-600 text-white rounded-r-full rounded-l-none"
								>
									<Search className="h-4 w-4" />
								</button>
								<SuggestionDropdown />
							</div>
						</div>

						{/* Right side - User and Cart */}
						<div className="flex items-center space-x-6">
							{token && user?.data?.profile?.name ? (
								<Link
									href="/dashboard/settings"
									className="hidden sm:flex items-center space-x-2 text-gray-700 hover:text-pink-600 transition-colors"
								>
									<User className="h-5 w-5" />
									<span className="hidden sm:block">
										Hello, {user?.data?.profile?.name}
									</span>
								</Link>
							) : (
								<div
									onClick={() => setIsLoginModalOpen(true)}
									className="hidden sm:flex items-center space-x-2 text-gray-700 cursor-pointer"
								>
									<User className="h-5 w-5" />
									<span>Hello, Sign in</span>
								</div>
							)}

							{/* <div className="flex items-center space-x-2 text-gray-700">
                <ShoppingCart className="h-5 w-5" />
                <span>Cart</span>
              </div> */}
							{/* mobile */}
							<div>
								<div className="flex items-center gap-2">
									<div
										className="hidden md:flex items-center space-x-0.5 md:space-x-2 text-gray-700 cursor-pointer"
										onClick={() => setIsCartOpen(true)}
									>
										<ShoppingCart className="h-4 w-4 md:h-5 md:w-5" />
										<span className="text-xs md:text-base">
											Cart ({cartItems?.data?.length || 0})
										</span>
									</div>
									<div className="sm:hidden">
										{/* mobile */}
										{token && user?.data?.profile?.name ? (
											<Link
												href="/dashboard/settings"
												className="flex items-center space-x-2 text-gray-700 hover:text-pink-600 transition-colors"
											>
												<User className="h-5 w-5" />
											</Link>
										) : (
											<div
												onClick={() => setIsLoginModalOpen(true)}
												className="flex items-center space-x-2 text-gray-700 cursor-pointer"
											>
												<User className="h-5 w-5" />
											</div>
										)}
									</div>
								</div>
								<CartDrawer
									isOpen={isCartOpen}
									onClose={() => setIsCartOpen(false)}
								/>
							</div>
							{/* Mobile menu button */}
							<button
								className="lg:hidden"
								onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
							>
								{isMobileMenuOpen ? (
									<X className="h-5 w-5" />
								) : (
									<MenuIcon className="h-5 w-5" />
								)}
							</button>
						</div>
					</div>

					{/* Mobile search bar */}
					<div className="lg:hidden pb-4">
						<div className="relative" ref={mobileSearchRef}>
							<input
								type="text"
								value={searchValue}
								onChange={(e) => handleSearchInput(e.target.value)}
								onKeyDown={(e) => e.key === "Enter" && handleSearchSubmit()}
								onFocus={handleSearchFocus}
								placeholder="Search product or Store"
								className="w-full pl-4 pr-12 py-2 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-[#E7005E] focus:border-transparent"
								autoComplete="off"
							/>
							<button
								onClick={handleSearchSubmit}
								className="absolute right-0 top-0 h-full px-4 bg-[#E7005E] hover:bg-pink-600 text-white rounded-r-full rounded-l-none"
							>
								<Search className="h-4 w-4" />
							</button>
							<SuggestionDropdown />
						</div>
					</div>
				</div>

				{/* Categories navigation */}
				<div className="">
					<div className="container px-4 sm:px-6 lg:px-8">
						{/* Desktop categories */}
						<div className="hidden lg:flex items-center gap-3 space-x-1 py-2 ">
							{/* All Categories Dropdown Menu */}
							<DropDownBtn title="All Categories" menuData={mappedMenuData} />
							<div className="flex items-center gap-2 overflow-hidden">
								{categories.slice(0, 8).map((category: any) => (
									<button
										key={category?.slug}
										onClick={() => router.push(`/product-filter?category=${category?.slug}`)}
										className="cursor-pointer text-gray-700 hover:text-[#E5005F] hover:border-[#E5005F] border border-gray-200 rounded-full px-4 py-1.5 whitespace-nowrap text-sm font-medium transition-colors"
									>
										{category?.item}
									</button>
								))}
							</div>
						</div>

						{/* Mobile categories */}
						{/* {isMobileMenuOpen && (
              <div className=" lg:hidden py-3 space-y-2">
              
                {categories.map((category: any) => (
                  <button key={category?.id} className="w-full justify-start text-gray-700 hover:text-pink-600 hover:bg-pink-50 rounded-lg">
                    {category?.item}
                  </button>
                ))}
              </div>
            )} */}
						{isMobileMenuOpen && (
							<div className="lg:hidden pb-4 animate-in slide-in-from-top duration-200">
								{/* Mobile Quick Actions */}
								<div className="flex items-center gap-3 px-1 pb-3 border-b border-gray-100">
									<button
										onClick={() => setIsCartOpen(true)}
										className="flex-1 flex items-center justify-center gap-2 py-2.5 bg-gradient-to-r from-[#E5005F] to-[#B9006E] text-white rounded-full text-sm font-medium shadow-sm hover:shadow-md transition-all duration-200"
									>
										<ShoppingCart className="h-4 w-4" />
										Cart ({cartItems?.data?.length || 0})
									</button>
									<Link
										href="/dashboard/track-orders"
										onClick={() => setIsMobileMenuOpen(false)}
										className="flex-1 flex items-center justify-center gap-2 py-2.5 border border-gray-200 text-gray-700 rounded-full text-sm font-medium hover:border-[#E5005F] hover:text-[#E5005F] transition-all duration-200"
									>
										Track Order
									</Link>
								</div>

								{/* Category List */}
								<div className="mt-3 bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
									{mappedMenuData.map((category: any, idx: number) => (
										<div key={category.id}>
											{idx > 0 && <div className="h-px bg-gray-100 mx-4" />}
											<div className="group">
												{/* Main category row */}
												<div className="flex items-center">
													<Link
														href={category.href}
														onClick={() => setIsMobileMenuOpen(false)}
														className="flex-1 px-4 py-3 text-sm font-semibold text-gray-800 hover:text-[#E5005F] transition-colors duration-200"
													>
														{category.name}
													</Link>
													{category.sub_items && category.sub_items.length > 0 && (
														<button
															onClick={() =>
																setExpandedCategory(
																	expandedCategory === category.id ? null : category.id,
																)
															}
															className="px-4 py-3 text-gray-400 hover:text-[#E5005F] transition-all duration-200"
															aria-label={`Toggle ${category.name} subcategories`}
														>
															<ChevronDown
																className={`h-4 w-4 transition-transform duration-300 ease-in-out ${expandedCategory === category.id ? "rotate-180 text-[#E5005F]" : ""}`}
															/>
														</button>
													)}
												</div>

												{/* Subcategories with smooth expand */}
												<div
													className={`overflow-hidden transition-all duration-300 ease-in-out ${expandedCategory === category.id ? "max-h-96 opacity-100" : "max-h-0 opacity-0"}`}
												>
													{category.sub_items && category.sub_items.length > 0 && (
														<div className="mx-4 mb-3 pl-3 border-l-2 border-[#E5005F]/20 space-y-0.5">
															{category.sub_items.map((sub: any) => (
																<Link
																	key={sub.id}
																	href={sub.href}
																	onClick={() => setIsMobileMenuOpen(false)}
																	className="block px-3 py-2 text-sm text-gray-600 hover:text-[#E5005F] hover:bg-[#E5005F]/5 rounded-lg transition-all duration-200"
																>
																	{sub.name}
																</Link>
															))}
														</div>
													)}
												</div>
											</div>
										</div>
									))}
								</div>
							</div>
						)}
					</div>
				</div>
			</div>

			<ConfigProvider
				theme={{
					token: {
						colorPrimary: "#e91e63",
						colorLink: "#e91e63",
					},
				}}
			>
				<AuthModal
					open={isLoginModalOpen}
					onClose={() => {
						setIsLoginModalOpen(false);
						setAuthInitialMode("login");
						setCampaignCode(undefined);
					}}
					setIsPricingModalOpen={setIsPricingModalOpen}
					initialMode={authInitialMode}
					campaignCode={campaignCode}
				/>

				<Modal
					open={isPricingModalOpen}
					// onCancel={() => setIsPricingModalOpen(false)}
					footer={null}
					width={520}
					centered
					closeIcon={null}
				>
					<div className="text-center pt-2 pb-4">
						<div className="flex items-center justify-center mb-3">
							<img src={logo.src} alt="SelfShop Logo" className="w-44" />
						</div>

						<PricingPage onInvoiceCreated={handlePackageInvoiceCreated} />
					</div>
				</Modal>
			</ConfigProvider>
		</>
	);
}
