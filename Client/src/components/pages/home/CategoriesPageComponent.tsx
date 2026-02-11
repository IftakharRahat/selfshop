/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { useEffect, useState } from "react";

import Image from "next/image";
import { useRouter } from "next/navigation";
import { getImageUrl } from "@/lib/utils";
import { useGetAllNavbarCategoryDropdownOptionsQuery } from "@/redux/features/home/homeApi";
import { ChevronDown, ChevronLeft, Package } from "lucide-react";

/** Image with fallback — shows a Package icon when the image is missing or broken */
function CategoryImage({
	src,
	alt,
	width,
	height,
	className,
}: {
	src: string | null | undefined;
	alt: string;
	width: number;
	height: number;
	className?: string;
}) {
	const [hasError, setHasError] = useState(false);
	const imageUrl = src ? getImageUrl(src) : "";

	if (!imageUrl || hasError) {
		return (
			<Package
				className={className}
				style={{ width, height }}
				strokeWidth={1.5}
				color="#9ca3af"
			/>
		);
	}

	return (
		<Image
			src={imageUrl}
			alt={alt}
			width={width}
			height={height}
			className={className}
			onError={() => setHasError(true)}
		/>
	);
}

export default function CategoriesPageComponent() {
	const router = useRouter();

	const { data: menuOptions } =
		useGetAllNavbarCategoryDropdownOptionsQuery(undefined);

	const categories =
		menuOptions?.data?.map((menu: any) => ({
			id: menu?.id,
			name: menu?.category_name,
			icon: menu?.category_icon,
			slug: menu?.slug,
			subcategories: menu?.subcategories?.map((sub: any) => ({
				...sub,
				minicategories: sub?.minicategories || [],
			})),
		})) || [];

	const [selectedCategory, setSelectedCategory] = useState<any>(null);
	const [expandedSubcategory, setExpandedSubcategory] = useState<
		number | null
	>(null);

	// Auto-select first category when data loads
	useEffect(() => {
		if (categories.length > 0 && !selectedCategory) {
			setSelectedCategory(categories[0]);
		}
	}, [menuOptions]);

	const handleSubcategoryClick = (sub: any) => {
		if (sub.minicategories && sub.minicategories.length > 0) {
			setExpandedSubcategory(
				expandedSubcategory === sub.id ? null : sub.id,
			);
		} else {
			router.push(
				`/product-filter?category=${selectedCategory?.slug}&subcategory=${sub.slug}`,
			);
		}
	};

	return (
		<div className="fixed inset-0 z-40 flex flex-col bg-white">
			{/* HEADER */}
			<div className="flex items-center gap-3 px-4 py-3 border-b border-gray-100 flex-shrink-0">
				<button
					onClick={() => router.push("/")}
					className="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition"
				>
					<ChevronLeft className="w-5 h-5 text-gray-700" />
				</button>
				<h1 className="text-lg font-semibold text-gray-900">Categories</h1>
			</div>

			{/* TWO-PANEL LAYOUT */}
			<div className="flex flex-1 overflow-hidden">
				{/* LEFT SIDE — CATEGORY SIDEBAR */}
				<div className="w-[85px] flex-shrink-0 bg-gray-50/80 border-r border-gray-100 overflow-y-auto py-3 pb-14 flex flex-col gap-0.5">
					{categories.map((cat: any) => {
						const isSelected = selectedCategory?.id === cat.id;
						return (
							<div
								key={cat.id}
								onClick={() => {
									setSelectedCategory(cat);
									setExpandedSubcategory(null);
									document.getElementById("subcategory-panel")?.scrollTo(0, 0);
								}}
								className={`flex flex-col items-center gap-1.5 px-1.5 py-2.5 mx-1 cursor-pointer rounded-xl transition-all duration-200
									${isSelected
										? "bg-white shadow-sm"
										: "hover:bg-white/60"
									}
								`}
							>
								<div
									className={`w-10 h-10 rounded-full flex items-center justify-center transition-all duration-200
										${isSelected
											? "bg-pink-50 border-2 border-[#E5005F]"
											: "bg-white border border-gray-200"
										}
									`}
								>
									<CategoryImage
										src={cat?.icon}
										alt={cat?.name || "Category"}
										width={24}
										height={24}
										className="w-6 h-6"
									/>
								</div>
								<span
									className={`text-[10px] leading-tight text-center line-clamp-2 transition-colors duration-200
										${isSelected
											? "font-semibold text-[#E5005F]"
											: "font-medium text-gray-500"
										}
									`}
								>
									{cat.name}
								</span>
							</div>
						);
					})}
				</div>

				{/* RIGHT SIDE — SUBCATEGORY LIST VIEW */}
				<div className="flex-1 overflow-y-auto px-3 pb-20" id="subcategory-panel">
					<h2 className="text-lg font-semibold  sticky top-0 bg-white py-3 z-20">
						{selectedCategory?.name}
					</h2>

					<div className="flex flex-col gap-1.5">
						{selectedCategory?.subcategories?.map((sub: any) => (
							<div key={sub.id}>
								{/* Subcategory row */}
								<div
									onClick={() => handleSubcategoryClick(sub)}
									className={`flex items-center justify-between px-2.5 py-2 rounded-xl border cursor-pointer transition-all
										${expandedSubcategory === sub.id
											? "border-blue-200 bg-blue-50/50"
											: "border-gray-200 bg-white hover:border-gray-300"
										}
									`}
								>
									<div className="flex items-center gap-2.5">
										<div className="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0">
											<CategoryImage
												src={sub.subcategory_icon}
												alt={
													sub?.sub_category_name ||
													"Subcategory"
												}
												width={28}
												height={28}
												className="w-7 h-7"
											/>
										</div>
										<span className="text-sm font-medium text-gray-800">
											{sub.sub_category_name}
										</span>
									</div>

									{sub.minicategories &&
										sub.minicategories.length > 0 && (
											<ChevronDown
												className={`w-5 h-5 text-gray-400 transition-transform duration-300 flex-shrink-0 ${expandedSubcategory === sub.id
													? "rotate-180"
													: ""
													}`}
											/>
										)}
								</div>

								{/* Expanded child categories (minicategories) */}
								{expandedSubcategory === sub.id &&
									sub.minicategories &&
									sub.minicategories.length > 0 && (
										<div className="grid grid-cols-3 gap-3 pt-3 pb-1 px-2">
											{sub.minicategories.map(
												(mini: any) => (
													<div
														key={mini.id}
														onClick={() =>
															router.push(
																`/product-filter?category=${selectedCategory?.slug}&subcategory=${sub.slug}&minicategory=${mini.slug}`,
															)
														}
														className="flex flex-col items-center text-center cursor-pointer group"
													>
														<div className="w-12 h-12 mb-1 rounded-lg bg-gray-100 flex items-center justify-center group-hover:bg-gray-200 transition">
															<CategoryImage
																src={
																	mini.minicategory_icon
																}
																alt={
																	mini?.mini_category_name ||
																	"Mini Category"
																}
																width={36}
																height={36}
																className="w-9 h-9"
															/>
														</div>
														<span className="text-[11px] font-medium text-gray-600 group-hover:text-blue-600 leading-tight">
															{
																mini.mini_category_name
															}
														</span>
													</div>
												),
											)}
										</div>
									)}
							</div>
						))}
					</div>
				</div>
			</div>
		</div>
	);
}
