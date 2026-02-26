/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import Image from "next/image";
import Link from "next/link";
import { useState } from "react";
import {
	MapPin,
	Package,
	BadgeCheck,
	Store,
	ChevronRight,
	Search,
	UserPlus,
	Star,
} from "lucide-react";
import { Tooltip } from "antd";
import { getImageUrl } from "@/lib/utils";
import { useGetSupplierDetailsQuery } from "@/redux/features/home/homeApi";
import ProductCard from "@/components/shared/ProductCard/ProductCard";

interface SupplierDetailsComponentProps {
	slug: string;
}

export default function SupplierDetailsComponent({
	slug,
}: SupplierDetailsComponentProps) {
	const [selectedCategory, setSelectedCategory] = useState<
		number | undefined
	>(undefined);
	const [searchQuery, setSearchQuery] = useState("");
	const [bannerError, setBannerError] = useState(false);
	const [logoError, setLogoError] = useState(false);

	const { data, isLoading, isError } = useGetSupplierDetailsQuery({
		slug,
		category: selectedCategory,
	});

	const [activeTab, setActiveTab] = useState("all");

	if (isLoading) {
		return (
			<div className="min-h-[60vh] flex flex-col items-center justify-center">
				<div className="w-10 h-10 border-4 border-gray-200 border-t-[#E5005F] rounded-full animate-spin" />
				<p className="mt-4 text-gray-400 text-sm">Loading supplier...</p>
			</div>
		);
	}

	if (isError || !data?.data) {
		return (
			<div className="min-h-[60vh] flex flex-col items-center justify-center gap-3">
				<Store className="w-16 h-16 text-gray-200" />
				<p className="text-gray-400 text-lg">Supplier not found</p>
				<Link
					href="/"
					className="text-[#E5005F] text-sm font-medium hover:underline"
				>
					← Back to Home
				</Link>
			</div>
		);
	}

	const vendor = data.data.vendor;
	const categories = data.data.categories || [];
	const products = data.data.products?.data || [];
	const totalProducts = data.data.products?.total || 0;
	const initials = vendor.company_name
		.split(" ")
		.map((w: string) => w[0])
		.join("")
		.slice(0, 2)
		.toUpperCase();

	const filteredProducts = searchQuery
		? products.filter((p: any) =>
			p.ProductName?.toLowerCase().includes(searchQuery.toLowerCase()),
		)
		: products;

	const tabs = [
		{ key: "all", label: "All Products" },
		{ key: "profile", label: "Profile" },
	];

	return (
		<div className="bg-white min-h-screen">
			{/* ── Breadcrumb ── */}
			<div className="border-b border-gray-100">
				<div className="container mx-auto px-4 sm:px-6 lg:px-8 py-2.5">
					<nav className="flex items-center text-xs sm:text-sm text-gray-400">
						<Link
							href="/"
							className="hover:text-[#E5005F] transition-colors flex items-center gap-1"
						>
							Home
						</Link>
						<ChevronRight className="w-3.5 h-3.5 mx-1" />
						<span className="text-gray-600 font-medium truncate">
							{vendor.company_name}
						</span>
					</nav>
				</div>
			</div>

			{/* ── Banner + Shop Info ── */}
			<div className="container mx-auto px-4 sm:px-6 lg:px-8 pt-4">
				{/* Banner */}
				<div className="relative w-full h-32 sm:h-40 md:h-48 lg:h-56 rounded-lg overflow-hidden bg-gradient-to-r from-slate-800 via-slate-700 to-slate-900">
					{vendor.banner_path && !bannerError ? (
						<Image
							src={getImageUrl(vendor.banner_path)}
							alt="Shop Cover"
							fill
							priority
							className="object-cover"
							onError={() => setBannerError(true)}
						/>
					) : (
						<div className="w-full h-full relative">
							<div className="absolute inset-0 bg-gradient-to-r from-emerald-700 via-emerald-600 to-teal-700" />
							<div className="absolute inset-0 opacity-[0.08]">
								<div
									className="w-full h-full"
									style={{
										backgroundImage: `url("data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='1'%3E%3Cpath d='M20 20h20v20H20zM0 0h20v20H0z'/%3E%3C/g%3E%3C/svg%3E")`,
									}}
								/>
							</div>
						</div>
					)}
				</div>

				{/* Shop Info Row */}
				<div className="relative flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-4 pt-2 pb-4 border-b border-gray-100">
					{/* Circular Logo — overlapping the banner */}
					<div className="w-20 h-20 sm:w-24 sm:h-24 rounded-full overflow-hidden border-[3px] border-white shadow-md shrink-0 -mt-10 sm:-mt-12 ml-4 sm:ml-6 bg-white z-10">
						{vendor.logo_path && !logoError ? (
							<Image
								src={getImageUrl(vendor.logo_path)}
								alt={vendor.company_name}
								width={96}
								height={96}
								className="w-full h-full object-cover"
								onError={() => setLogoError(true)}
							/>
						) : (
							<div className="w-full h-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white font-bold text-xl sm:text-2xl">
								{initials}
							</div>
						)}
					</div>

					{/* Name + Badge + Stats */}
					<div className="flex-1 min-w-0 pb-1">
						<div className="flex items-center gap-2 flex-wrap">
							<h1 className="text-lg sm:text-xl lg:text-2xl font-bold text-gray-900">
								{vendor.company_name}
							</h1>
							{vendor.is_verified_badge && (
								<BadgeCheck className="w-5 h-5 text-blue-500 shrink-0" />
							)}
						</div>
						<div className="flex items-center gap-2 mt-1 text-sm text-gray-500">
							{vendor.city && (
								<>
									<span className="flex items-center gap-1">
										<MapPin className="w-3.5 h-3.5" />
										{vendor.city}
									</span>
									<span className="text-gray-300">|</span>
								</>
							)}
							<span className="flex items-center gap-1">
								<Package className="w-3.5 h-3.5" />
								{vendor.products_count}{" "}
								{vendor.products_count === 1 ? "Product" : "Products"}
							</span>
							{vendor.avg_product_rating > 0 && (
								<>
									<span className="text-gray-300">|</span>
									<span className="flex items-center gap-1">
										<Star className="w-3.5 h-3.5 fill-amber-400 text-amber-400" />
										{vendor.avg_product_rating}
										<span className="text-gray-400">
											({vendor.review_count}{" "}
											{vendor.review_count === 1 ? "review" : "reviews"})
										</span>
									</span>
								</>
							)}
							{vendor.business_type && (
								<>
									<span className="text-gray-300">|</span>
									<span>{vendor.business_type}</span>
								</>
							)}
						</div>
					</div>

					{/* Follow Button — right side */}
					<div className="shrink-0 sm:ml-auto">
						<Tooltip title="Coming soon" placement="top">
							<button className="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-[#E5005F] border border-[#E5005F] rounded-full hover:bg-pink-50 transition-colors cursor-pointer">
								<UserPlus className="w-4 h-4" />
								Follow
							</button>
						</Tooltip>
					</div>
				</div>
			</div>

			{/* ── Tab Navigation with Category Filter ── */}
			<div className="border-b border-gray-200 mt-2">
				<div className="container mx-auto px-4 sm:px-6 lg:px-8">
					<div className="flex items-center gap-1.5 overflow-x-auto scrollbar-hide py-2.5">
						{/* Category pills */}
						{categories.length > 0 && (
							<>
								<button
									onClick={() => setSelectedCategory(undefined)}
									className={`px-3.5 py-1.5 text-sm font-medium rounded-full border transition-all cursor-pointer whitespace-nowrap shrink-0 ${!selectedCategory
										? "bg-[#E5005F] text-white border-[#E5005F]"
										: "bg-white text-gray-600 border-gray-200 hover:border-gray-300 hover:text-gray-800"
										}`}
								>
									All
								</button>
								{categories.map((cat: any) => (
									<button
										key={cat.id}
										onClick={() => setSelectedCategory(cat.id)}
										className={`px-3.5 py-1.5 text-sm font-medium rounded-full border transition-all cursor-pointer whitespace-nowrap shrink-0 ${selectedCategory === cat.id
											? "bg-[#E5005F] text-white border-[#E5005F]"
											: "bg-white text-gray-600 border-gray-200 hover:border-gray-300 hover:text-gray-800"
											}`}
									>
										{cat.category_name}
									</button>
								))}

								{/* Divider */}
								<div className="w-px h-6 bg-gray-200 mx-1 shrink-0" />
							</>
						)}

						{/* Page tabs */}
						{tabs.map((tab) => (
							<button
								key={tab.key}
								onClick={() => setActiveTab(tab.key)}
								className={`px-3.5 py-1.5 text-sm font-medium rounded-full border transition-all cursor-pointer whitespace-nowrap shrink-0 ${activeTab === tab.key
									? "bg-white text-[#E5005F] border-[#E5005F]"
									: "bg-white text-gray-500 border-transparent hover:text-gray-700"
									}`}
							>
								{tab.label}
							</button>
						))}
					</div>
				</div>
			</div>

			{/* ── Tab Content ── */}
			{activeTab === "all" && (
				<div className="bg-gray-50">
					<div className="container mx-auto px-4 sm:px-6 lg:px-8 py-5 sm:py-6">
						{/* Toolbar — title + search */}
						<div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-5">
							<h2 className="text-base sm:text-lg lg:text-xl font-semibold text-gray-800">
								{selectedCategory
									? categories.find(
										(c: any) => c.id === selectedCategory,
									)?.category_name || "Filtered Products"
									: "All Products"}
								<span className="text-gray-400 font-normal text-sm ml-1.5">
									({totalProducts})
								</span>
							</h2>

							<div className="relative w-full sm:w-64">
								<Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
								<input
									type="text"
									placeholder="Search products..."
									value={searchQuery}
									onChange={(e) => setSearchQuery(e.target.value)}
									className="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-[#E5005F]/20 focus:border-[#E5005F] transition-colors"
								/>
							</div>
						</div>

						{/* Active filter chip */}
						{selectedCategory && (
							<div className="mb-4 flex items-center gap-2">
								<span className="text-sm text-gray-500">Filtered by:</span>
								<span className="inline-flex items-center gap-1 bg-pink-50 text-[#E5005F] px-2.5 py-1 rounded-full text-xs font-medium">
									{categories.find(
										(c: any) => c.id === selectedCategory,
									)?.category_name}
									<button
										onClick={() => setSelectedCategory(undefined)}
										className="ml-0.5 hover:bg-pink-100 rounded-full w-4 h-4 flex items-center justify-center text-[10px] cursor-pointer"
									>
										✕
									</button>
								</span>
							</div>
						)}

						{/* Product grid */}
						{filteredProducts.length === 0 ? (
							<div className="text-center py-20 bg-white rounded-xl border border-gray-100">
								<Package className="w-14 h-14 text-gray-200 mx-auto mb-3" />
								<p className="text-gray-400 font-medium">
									{searchQuery
										? "No products match your search"
										: "No products available yet"}
								</p>
								{(searchQuery || selectedCategory) && (
									<button
										onClick={() => {
											setSearchQuery("");
											setSelectedCategory(undefined);
										}}
										className="mt-3 text-sm text-[#E5005F] hover:underline cursor-pointer"
									>
										Clear filters
									</button>
								)}
							</div>
						) : (
							<div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 sm:gap-4">
								{filteredProducts.map((product: any) => (
									<ProductCard key={product.id} product={product} />
								))}
							</div>
						)}
					</div>
				</div>
			)}

			{activeTab === "profile" && (
				<div className="bg-gray-50">
					<div className="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
						<div className="bg-white rounded-xl border border-gray-100 p-6 sm:p-8">
							<h3 className="text-lg font-semibold text-gray-800 mb-4">
								About {vendor.company_name}
							</h3>
							<div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
								<div className="space-y-3">
									<div>
										<span className="text-gray-400 block mb-0.5">
											Company Name
										</span>
										<span className="text-gray-800 font-medium">
											{vendor.company_name}
										</span>
									</div>
									{vendor.business_type && (
										<div>
											<span className="text-gray-400 block mb-0.5">
												Business Type
											</span>
											<span className="text-gray-800 font-medium">
												{vendor.business_type}
											</span>
										</div>
									)}
									{vendor.city && (
										<div>
											<span className="text-gray-400 block mb-0.5">
												Location
											</span>
											<span className="text-gray-800 font-medium">
												{vendor.city}
												{vendor.country ? `, ${vendor.country}` : ""}
											</span>
										</div>
									)}
								</div>
								<div className="space-y-3">
									<div>
										<span className="text-gray-400 block mb-0.5">
											Total Products
										</span>
										<span className="text-gray-800 font-medium">
											{vendor.products_count}
										</span>
									</div>
									<div>
										<span className="text-gray-400 block mb-0.5">Status</span>
										<span className="inline-flex items-center gap-1 text-green-600 font-medium">
											<span className="w-1.5 h-1.5 bg-green-500 rounded-full" />
											Verified Supplier
										</span>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			)}
		</div>
	);
}
