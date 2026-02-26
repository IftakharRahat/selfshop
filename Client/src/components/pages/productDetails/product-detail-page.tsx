/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { BadgeCheck, ChevronRight, Minus, Plus } from "lucide-react";
import Image from "next/image";
import Link from "next/link";
import { useState } from "react";
import { toast } from "sonner";
import { TbCurrencyTaka } from "react-icons/tb";
import type { Swiper as SwiperType } from "swiper";
import { FreeMode, Navigation, Pagination, Thumbs } from "swiper/modules";
import { Swiper, SwiperSlide } from "swiper/react";
// Import Swiper styles
import { useAddToCartMutation } from "@/redux/features/cartApi";
import { handleAsyncWithToast } from "@/utils/handleAsyncWithToast";
import "swiper/css";
import "swiper/css/free-mode";
import "swiper/css/navigation";
import "swiper/css/pagination";
import "swiper/css/thumbs";

import { MdOutlineFileDownload } from "react-icons/md";
import Swal from "sweetalert2";
import { z } from "zod";
import { cn, getImageUrl } from "@/lib/utils";
import { useAppSelector } from "@/redux/hooks";
import OrderNowModal from "./OrderNowModal";
import ProductReviewsSection from "./ProductReviewsSection";

type ColorOption = {
	id: string | number;
	name?: string;
	color: string;
};

function SupplierMiniLogo({ logo, name }: { logo: string | null; name: string }) {
	const [errored, setErrored] = useState(false);

	if (!logo || errored) {
		const initials = name
			.split(" ")
			.map((w) => w[0])
			.join("")
			.slice(0, 2)
			.toUpperCase();
		return (
			<div className="w-8 h-8 rounded-full bg-gradient-to-br from-pink-400 to-rose-600 flex items-center justify-center text-white font-bold text-[10px] select-none shrink-0">
				{initials}
			</div>
		);
	}

	return (
		<Image
			src={getImageUrl(logo)}
			alt={name}
			width={32}
			height={32}
			className="w-8 h-8 rounded-full object-cover shrink-0 ring-1 ring-gray-200"
			onError={() => setErrored(true)}
		/>
	);
}

function DesktopTabs({
	description,
	productId,
}: {
	description: string;
	productId: number;
}) {
	const [activeTab, setActiveTab] = useState<"description" | "reviews">(
		"description",
	);

	return (
		<div>
			{/* Tab Headers */}
			<div className="flex border-b border-gray-200">
				<button
					onClick={() => setActiveTab("description")}
					className={`px-6 py-3 text-sm font-semibold transition-colors relative cursor-pointer ${activeTab === "description"
							? "text-pink-600"
							: "text-gray-500 hover:text-gray-700"
						}`}
				>
					Description
					{activeTab === "description" && (
						<span className="absolute bottom-0 left-0 right-0 h-0.5 bg-pink-600 rounded-t" />
					)}
				</button>
				<button
					onClick={() => setActiveTab("reviews")}
					className={`px-6 py-3 text-sm font-semibold transition-colors relative cursor-pointer ${activeTab === "reviews"
							? "text-pink-600"
							: "text-gray-500 hover:text-gray-700"
						}`}
				>
					Reviews
					{activeTab === "reviews" && (
						<span className="absolute bottom-0 left-0 right-0 h-0.5 bg-pink-600 rounded-t" />
					)}
				</button>
			</div>

			{/* Tab Content */}
			<div className="pt-6">
				{activeTab === "description" ? (
					<div
						dangerouslySetInnerHTML={{ __html: description }}
						className="text-gray-700 leading-relaxed w-full overflow-hidden"
					/>
				) : (
					<ProductReviewsSection productId={productId} />
				)}
			</div>
		</div>
	);
}

export default function ProductDetailPage({ product }: any) {
	const [orderOpen, setOrderOpen] = useState(false);
	const token = useAppSelector((state) => state.auth.access_token);

	const [addToCart, { isLoading }] = useAddToCartMutation();

	// ---- Transform Backend Data ----
	const images = [
		product.ViewProductImage,
		...JSON.parse(product.PostImage || "[]").map((img: string) => `${img}`),
	];

	const productData = {
		name: product.ProductName,
		category: `Category #${product.category_id}`,
		quantity: product.qty,
		sku: product.ProductSku,
		minimumPrice: parseFloat(product.min_sell_price),
		currentPrice: parseFloat(product.ProductResellerPrice),
		sizes: JSON.parse(product.size || "[]"),
		description: product.ProductDetails,
		images: {
			main: images,
			thumbnails: images,
		},
		varients: product.varients || [],
		vendor: product.vendor
			? {
				companyName: product.vendor.company_name || "",
				isVerifiedBadge: Boolean(product.vendor.is_verified_badge),
				slug: product.vendor.slug || "",
				logoPath: product.vendor.logo_path || null,
			}
			: null,
	};
	const sellingPriceSchema = z
		.number({ required_error: "Selling price is required" })
		.min(
			productData.minimumPrice,
			`Price must be at least ${productData.minimumPrice} taka.`,
		);

	// ---- UI States ----
	const variants: any[] = productData.varients || [];
	const [activeVariantIdx, setActiveVariantIdx] = useState(0);
	// Per-variant per-size quantities: { [variantId]: { [size]: qty } }
	const [variantQuantities, setVariantQuantities] = useState<Record<number, Record<string, number>>>({});
	const [sellingPrice, setSellingPrice] = useState("");
	const [priceError, setPriceError] = useState<string | null>(null);

	const handleQtyChange = (variantId: number, size: string, type: "increase" | "decrease") => {
		setVariantQuantities((prev) => {
			const varSizes = { ...(prev[variantId] || {}) };
			const cur = varSizes[size] || 0;
			varSizes[size] = type === "increase" ? cur + 1 : Math.max(0, cur - 1);
			return { ...prev, [variantId]: varSizes };
		});
	};

	const handleQtySet = (variantId: number, size: string, value: string) => {
		const num = parseInt(value, 10);
		setVariantQuantities((prev) => {
			const varSizes = { ...(prev[variantId] || {}) };
			varSizes[size] = value === '' ? 0 : (isNaN(num) ? 0 : Math.max(0, num));
			return { ...prev, [variantId]: varSizes };
		});
	};

	// Total quantity across ALL variants and sizes
	const totalQuantity = Object.values(variantQuantities)
		.flatMap((sizes) => Object.values(sizes))
		.reduce((a, b) => a + b, 0);

	const [thumbsSwiper, setThumbsSwiper] = useState<SwiperType | null>(null);

	// Determine selling type
	const sellingType: 'wholesale' | 'dropshipping' | 'both' = product.selling_type || 'both';
	const hasTiers = product.price_tiers && product.price_tiers.length > 0;
	const showWholesale = (sellingType === 'wholesale' || sellingType === 'both') && hasTiers;
	const showDropshipping = sellingType === 'dropshipping' || sellingType === 'both';

	// Auto-select active pricing tier based on total quantity
	const activeTier = hasTiers
		? product.price_tiers
			.slice()
			.sort((a: any, b: any) => b.min_qty - a.min_qty)
			.find((t: any) => totalQuantity >= t.min_qty) ?? product.price_tiers[0]
		: null;
	const activeTierId = activeTier?.id ?? null;
	const effectiveUnitPrice = activeTier ? parseFloat(activeTier.unit_price) : productData.currentPrice;



	const validateSellingPrice = () => {
		try {
			const parsedPrice = sellingPriceSchema.parse(Number(sellingPrice));
			setPriceError(null);
			return parsedPrice;
		} catch (err: any) {
			setPriceError(err.errors?.[0]?.message || "Invalid price");
			return null;
		}
	};

	const handleAddToCart = async () => {
		if (!token) {
			Swal.fire({
				icon: "error",
				title: "Unauthorized",
				text: "Please log in to add items to your cart.",
			});
			return;
		}
		const validPrice = showDropshipping ? validateSellingPrice() : effectiveUnitPrice;
		if (!validPrice) return;

		const items = getSelectedItems();
		if (items.length === 0) {
			toast.error("Please select at least one item.");
			return;
		}

		for (const item of items) {
			const formData = new FormData();
			formData.append("product_id", product.id);
			formData.append("price", validPrice.toString());
			formData.append("qty", item.qty.toString());
			formData.append("size", item.size);
			if (item.variantTitle) {
				formData.append("color", item.variantTitle);
			}

			await handleAsyncWithToast(async () => {
				return addToCart(formData);
			});
		}
	};

	const handleBuyNow = async () => {
		if (!token) {
			Swal.fire({
				icon: "error",
				title: "Unauthorized",
				text: "Please log in to add items to your cart.",
			});
			return;
		}
		const validPrice = showDropshipping ? validateSellingPrice() : effectiveUnitPrice;
		if (!validPrice) return;

		const items = getSelectedItems();
		if (items.length === 0) {
			toast.error("Please select at least one item.");
			return;
		}

		let lastResult: any;
		for (const item of items) {
			const formData = new FormData();
			formData.append("product_id", product.id);
			formData.append("price", validPrice.toString());
			formData.append("qty", item.qty.toString());
			formData.append("size", item.size);
			if (item.variantTitle) {
				formData.append("color", item.variantTitle);
			}

			lastResult = await handleAsyncWithToast(async () => {
				return addToCart(formData);
			}, false);
		}

		if (lastResult?.data?.status) {
			window.location.href = "/order-confirmation";
		}
	};
	const handleOrderNow = async () => {
		if (!token) {
			Swal.fire({
				icon: "error",
				title: "Unauthorized",
				text: "Please log in to add items to your cart.",
			});
			return;
		}
		setOrderOpen(true);
	};

	// Helper: get items that have non-zero quantity for cart submission
	const getSelectedItems = () => {
		const items: { variantId: number; variantTitle: string; size: string; qty: number }[] = [];
		for (const [vid, sizes] of Object.entries(variantQuantities)) {
			const v = variants.find((vr: any) => vr.id === Number(vid));
			for (const [size, qty] of Object.entries(sizes)) {
				if (qty > 0) {
					const variantLabel = v?.color_name || v?.title || "";
					items.push({ variantId: Number(vid), variantTitle: variantLabel, size, qty });
				}
			}
		}
		return items;
	};

	const handleDownloadImage = (imgPath: string) => {
		const imageUrl = getImageUrl(imgPath);
		const link = document.createElement("a");
		link.href = imageUrl;
		// Optional: extract filename from URL
		link.download = imgPath.split("/").pop() || "product-image.jpg";
		document.body.appendChild(link);
		link.click();
		document.body.removeChild(link);
	};

	return (
		<div className="min-h-screen bg-white">
			<div className="container mx-auto px-4 sm:px-6 lg:px-8 py-6">
				{/* Breadcrumb */}
				<nav className="flex items-center space-x-2 text-sm mb-8">
					<span className="text-gray-600">Home</span>
					<ChevronRight className="w-4 h-4 text-gray-400" />
					<span className="text-pink-600 font-medium">product details</span>
				</nav>

				<div className="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 border-b border-gray-200 pb-8 lg:pb-12">
					{/* Product Images */}
					<div className="space-y-4">
						{/* Main Image Swiper */}
						<div className="aspect-[4/4] bg-gradient-to-br from-purple-300 to-purple-400 rounded-lg overflow-hidden">
							<Swiper
								modules={[Navigation, Pagination, Thumbs]}
								thumbs={{
									swiper:
										thumbsSwiper && !thumbsSwiper.destroyed
											? thumbsSwiper
											: null,
								}}
								navigation={{
									nextEl: ".swiper-button-next-custom",
									prevEl: ".swiper-button-prev-custom",
								}}
								// pagination={{
								//   clickable: false,
								//   bulletClass: "swiper-pagination-bullet-custom",
								//   bulletActiveClass: "swiper-pagination-bullet-active-custom",
								// }}
								className="w-full h-full"
								spaceBetween={10}
								slidesPerView={1}
							>
								{productData.images.main.map((image, index) => (
									<SwiperSlide key={index} className="relative">
										<div className="w-full h-full flex items-center justify-center relative">
											<Image
												src={getImageUrl(image) || "/placeholder.svg"}
												alt={`${productData.name} - View ${index + 1}`}
												width={500}
												height={600}
												className="w-full h-full object-cover"
												priority={index === 0}
											/>
										</div>
										<div
											className="absolute bottom-0 right-0 z-50 bg-[#CCFF8D] p-1 text-xs rounded-tl py-1 px-5 flex items-center gap-1 cursor-pointer hover:opacity-90 transition-opacity"
											onClick={() => handleDownloadImage(image)}
										>
											<MdOutlineFileDownload size={20} />
											Download image
										</div>
									</SwiperSlide>
								))}

								{/* Custom Navigation Buttons */}
								<div className="swiper-button-prev-custom absolute left-2 top-1/2 -translate-y-1/2 z-10 w-8 h-8 bg-white/80 rounded-full flex items-center justify-center cursor-pointer hover:bg-white transition-colors">
									<ChevronRight className="w-4 h-4 rotate-180" />
								</div>
								<div className="swiper-button-next-custom absolute right-2 top-1/2 -translate-y-1/2 z-10 w-8 h-8 bg-white/80 rounded-full flex items-center justify-center cursor-pointer hover:bg-white transition-colors">
									<ChevronRight className="w-4 h-4" />
								</div>
							</Swiper>
						</div>

						{/* Thumbnail Swiper */}
						<div className="w-full">
							<Swiper
								modules={[FreeMode, Thumbs]}
								onSwiper={setThumbsSwiper}
								spaceBetween={15}
								slidesPerView={5}
								freeMode={true}
								watchSlidesProgress={true}
								loop={true}
								className="thumbnail-swiper"
							>
								{productData.images.thumbnails.map((thumbnail, index) => (
									<SwiperSlide key={index}>
										<div className=" rounded-lg overflow-hidden border-2 border-gray-200 cursor-pointer hover:border-pink-500 transition-colors">
											<Image
												src={getImageUrl(thumbnail) || "/placeholder.svg"}
												alt={`Product thumbnail ${index + 1}`}
												width={80}
												height={80}
												className="w-full h-full object-cover"
											/>
										</div>
									</SwiperSlide>
								))}
							</Swiper>
						</div>
					</div>

					{/* Product Details */}
					<div className="space-y-6">
						<h1 className="text-2xl sm:text-3xl font-bold text-gray-900">
							{productData.name}
						</h1>
						{productData.vendor?.companyName && (
							<Link
								href={productData.vendor.slug ? `/supplier/${productData.vendor.slug}` : "#"}
								className="group/vendor inline-flex items-center gap-2.5 rounded-xl border border-gray-100 bg-gray-50/80 px-3 py-2 hover:border-pink-200 hover:bg-pink-50/50 transition-all"
							>
								{/* Supplier Logo */}
								<SupplierMiniLogo
									logo={productData.vendor.logoPath}
									name={productData.vendor.companyName}
								/>
								<div className="flex flex-col">
									<span className="text-[10px] text-gray-400 leading-none">Sold by</span>
									<span className="text-xs font-semibold text-gray-800 group-hover/vendor:text-[#E5005F] transition-colors flex items-center gap-1">
										{productData.vendor.companyName}
										{productData.vendor.isVerifiedBadge && (
											<BadgeCheck className="w-3.5 h-3.5 text-blue-500" />
										)}
									</span>
								</div>
								<ChevronRight className="w-3.5 h-3.5 text-gray-300 group-hover/vendor:text-[#E5005F] ml-auto transition-colors" />
							</Link>
						)}

						{/* Selling Type Badge */}
						<div className="flex items-center gap-2">
							{sellingType === 'wholesale' && (
								<span className="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
									🏭 Wholesale
								</span>
							)}
							{sellingType === 'dropshipping' && (
								<span className="inline-flex items-center gap-1 rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
									🚀 Dropshipping
								</span>
							)}
							{sellingType === 'both' && (
								<span className="inline-flex items-center gap-1 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
									🔄 Wholesale + Dropshipping
								</span>
							)}
						</div>

						<div className="space-y-3 text-sm">
							<div className="flex items-center">
								<span className="font-medium text-gray-900">Category :</span>
								<span className="ml-2 text-gray-600">
									{productData.category}
								</span>
							</div>
							<div className="flex items-center">
								<span className="font-medium text-gray-900">Quantity :</span>
								<span className="ml-2 text-gray-600">
									{productData.quantity}
								</span>
							</div>
							<div className="flex items-center">
								<span className="font-medium text-gray-900">SKU :</span>
								<span className="ml-2 text-gray-600">{productData.sku}</span>
							</div>
							<div className="flex items-center">
								<span className="font-medium text-gray-900">
									Minimum Sell Price :
								</span>
								<span className="ml-2 text-gray-600 flex items-center">
									<TbCurrencyTaka size={20} />
									{productData.minimumPrice.toFixed(2)}
								</span>
							</div>
						</div>



						{/* Bulk (Variants) */}
						<OrderNowModal
							open={orderOpen}
							onClose={() => setOrderOpen(false)}
							variant={variants[activeVariantIdx] ?? null}
						/>

						{/* ── Wholesale: Auto-Highlight Tier Price Badges ── */}
						{
							showWholesale && (
								<div className="mt-4">
									<div className="flex flex-nowrap overflow-x-auto gap-2 pb-1 -mx-1 px-1">
										{product.price_tiers.map((tier: any) => {
											const isActive = activeTierId === tier.id;
											const qtyLabel = tier.max_qty
												? `${tier.min_qty}-${tier.max_qty} Pcs`
												: `${tier.min_qty}+ Pcs`;
											return (
												<div
													key={tier.id}
													className={`relative flex flex-col items-center px-3 py-2 sm:px-5 sm:py-3 rounded-xl border transition-all min-w-[100px] sm:min-w-[130px] shrink-0 ${isActive
														? 'border-pink-500 bg-pink-50 shadow-md shadow-pink-100'
														: 'border-gray-200 bg-white'
														}`}
												>
													<span className={`text-base sm:text-lg font-bold flex items-center ${isActive ? 'text-pink-700' : 'text-gray-900'}`}>
														<TbCurrencyTaka size={20} />
														{parseFloat(tier.unit_price).toFixed(2)}
													</span>
													<span className={`text-[11px] font-medium mt-0.5 ${isActive ? 'text-pink-600' : 'text-gray-500'}`}>
														{qtyLabel}
													</span>
												</div>
											);
										})}
									</div>
									{totalQuantity > 0 && (
										<p className="text-xs text-gray-500 mt-2">
											Total selected: {totalQuantity} pcs
										</p>
									)}
								</div>
							)
						}

						{/* ── Variant (Color) Selection + Per-Color Size Table ── */}
						<div className="bg-[#F4F4F4] p-2 sm:p-4 space-y-3 sm:space-y-4 rounded-lg">
							{variants.length > 0 && (
								<div>
									<h3 className="font-medium mb-2 text-sm text-gray-900">
										Color: <span className="font-bold">{variants[activeVariantIdx]?.title || 'Default'}</span>
									</h3>
									<div className="flex gap-3 overflow-x-auto p-1">
										{variants.map((v: any, idx: number) => {
											const isActive = idx === activeVariantIdx;
											const variantStock = Number(v.qty ?? 0);
											const selectedVarQty = Object.values(
												variantQuantities[Number(v.id)] ?? {},
											).reduce((sum, qty) => sum + Number(qty || 0), 0);
											const colorCode = typeof v.color_code === "string" ? v.color_code : "";
											return (
												<button
													key={v.id}
													onClick={() => setActiveVariantIdx(idx)}
													className={`relative flex flex-col items-center p-1 rounded-md transition min-w-[52px] ${isActive
														? "ring-2 ring-pink-500 bg-white"
														: "hover:ring-1 hover:ring-gray-300"
														}`}
												>
													<span
														className={`absolute -top-2 -right-2 min-w-[20px] h-5 px-1 rounded-full text-[10px] font-bold flex items-center justify-center ${selectedVarQty > 0 ? 'bg-pink-500 text-white' : 'bg-gray-400 text-white'}`}
														title={`Selected: ${selectedVarQty}`}
													>
														{selectedVarQty}
													</span>
													{productData.images.main[idx] ? (
														<Image
															src={getImageUrl(productData.images.main[idx]) || "/placeholder.svg"}
															alt={v.title || `Variant ${idx + 1}`}
															width={40}
															height={40}
															className="w-10 h-10 rounded object-cover"
														/>
													) : (
														<div className="w-10 h-10 rounded bg-gray-300 flex items-center justify-center text-[10px] font-bold text-white">
															{(v.title || '?')[0]}
														</div>
													)}
													{colorCode && (
														<span
															className="mt-1 inline-block h-2.5 w-2.5 rounded-full border border-gray-300"
															style={{ backgroundColor: colorCode }}
															title={v.color_name || colorCode}
														/>
													)}
													<span className="mt-1 text-[10px] leading-none text-gray-500">
														Stock {variantStock}
													</span>
												</button>
											);
										})}
									</div>
								</div>
							)}

							{/* Per-Color Size + Quantity Table */}
							{(() => {
								const currentVariant = variants[activeVariantIdx];
								const currentVarId = currentVariant?.id ?? 0;
								const currentStock = currentVariant?.qty ?? productData.quantity;
								const sizesForTable = productData.sizes.length > 0 ? productData.sizes : ['Default'];

								return (
									<div className="space-y-3">
										<div className="bg-white rounded-lg border border-gray-200 overflow-hidden">
											<div className="grid grid-cols-[2fr_2fr_1fr_3fr] sm:grid-cols-4 gap-0 sm:gap-3 px-2 sm:px-4 py-2 bg-gray-50 text-[10px] sm:text-xs font-semibold text-gray-600 uppercase">
												<div>Size</div>
												<div>Price</div>
												<div className="text-center">Stock</div>
												<div className="text-right">Quantity</div>
											</div>
											{sizesForTable.map((size: string) => {
												const qty = variantQuantities[currentVarId]?.[size] || 0;
												return (
													<div key={size} className="grid grid-cols-[2fr_2fr_1fr_3fr] sm:grid-cols-4 gap-0 sm:gap-3 px-2 sm:px-4 py-2 sm:py-3 border-t border-gray-100 items-center">
														<div className="font-medium text-gray-900 text-sm">{size}</div>
														<div className="text-gray-700 flex items-center text-sm">
															<TbCurrencyTaka size={14} />
															{effectiveUnitPrice.toFixed(2)}
														</div>
														<div className="text-gray-600 text-sm text-center">{currentStock}</div>
														<div className="flex items-center justify-end gap-1">
															<button
																onClick={() => handleQtyChange(currentVarId, size, "decrease")}
																className="w-8 h-8 border border-gray-300 rounded-lg flex items-center justify-center hover:bg-gray-50 transition-colors bg-white"
															>
																<Minus className="w-3 h-3" />
															</button>
															<input
																type="number"
																min={0}
																value={qty || ''}
																onChange={(e) => handleQtySet(currentVarId, size, e.target.value)}
																placeholder="0"
																className={`w-14 h-8 rounded-lg text-center border text-sm font-medium outline-none focus:ring-1 focus:ring-pink-400 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none ${qty > 0 ? 'bg-pink-50 border-pink-300 text-pink-700' : 'bg-white border-gray-200'}`}
															/>
															<button
																onClick={() => handleQtyChange(currentVarId, size, "increase")}
																className="w-8 h-8 border border-gray-300 rounded-lg flex items-center justify-center hover:bg-pink-50 transition-colors bg-white"
															>
																<Plus className="w-3 h-3" />
															</button>
														</div>
													</div>
												);
											})}
											{/* Total Row */}
											<div className="grid grid-cols-[2fr_2fr_1fr_3fr] sm:grid-cols-4 gap-0 sm:gap-3 px-2 sm:px-4 py-2 border-t-2 border-gray-200 bg-gray-50 font-semibold text-sm">
												<div className="text-gray-900">Total</div>
												<div className="text-pink-600 flex items-center">
													<TbCurrencyTaka size={16} />
													{(effectiveUnitPrice * totalQuantity).toFixed(2)}
												</div>
												<div></div>
												<div className="text-right text-gray-700">{totalQuantity} pcs</div>
											</div>
										</div>
									</div>
								);
							})()}
						</div>


						{/* Unit Price + Total Price */}
						<div className="space-y-1">
							<div className="text-3xl font-bold text-gray-900 flex items-center">
								<TbCurrencyTaka size={35} />
								{effectiveUnitPrice.toFixed(2)}
								<span className="text-sm font-normal text-gray-500 ml-1">/pc</span>
							</div>
							{totalQuantity > 0 && (
								<div className="flex items-center gap-2 text-sm">
									<span className="text-gray-500">Total ({totalQuantity} pcs):</span>
									<span className="font-bold text-lg text-pink-600 flex items-center">
										<TbCurrencyTaka size={20} />
										{(effectiveUnitPrice * totalQuantity).toFixed(2)}
									</span>
								</div>
							)}
						</div>

						{/* Dropshipping: Selling Price + Earnings */}
						{showDropshipping && (
							<div className="space-y-3">
								<h3 className="font-medium text-gray-900">Your selling price</h3>
								<input
									type="number"
									placeholder="Enter your selling price"
									value={sellingPrice}
									onChange={(e) => setSellingPrice(e.target.value)}
									className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
								/>
								{priceError && (
									<p className="text-red-500 text-sm mt-1">{priceError}</p>
								)}{" "}
								{priceError == null && (
									<p
										className={cn(
											"text-green-600 text-sm mt-1",
											Number(
												(
													(Number(sellingPrice) - productData.currentPrice) *
													(totalQuantity || 1)
												).toFixed(2),
											) > 0
												? ""
												: "hidden",
										)}
									>
										Your total earn{" "}
										{(
											(Number(sellingPrice) - productData.currentPrice) *
											(totalQuantity || 1)
										).toFixed(2)}{" "}
										TK
									</p>
								)}
							</div>
						)}

						<div className="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-4 ">
							<button
								onClick={handleAddToCart}
								className="flex-1 px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium cursor-pointer"
							>
								Add to cart
							</button>
							<button
								onClick={handleBuyNow}
								className="flex-1 px-6 py-3 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition-colors font-medium cursor-pointer"
							>
								{isLoading ? "Processing..." : "Buy Now"}
							</button>
						</div>
					</div>
				</div>

				{/* Description & Reviews — Tabs on desktop, stacked on mobile (reviews first) */}
				<div className="mt-12">
					{/* Mobile: stacked, reviews first */}
					<div className="md:hidden space-y-8">
						<ProductReviewsSection productId={product.id} />
						<div>
							<h2 className="text-xl font-semibold text-pink-600 border-b w-fit mb-4">
								Description
							</h2>
							<div
								dangerouslySetInnerHTML={{ __html: productData.description }}
								className="text-gray-700 leading-relaxed w-full overflow-hidden"
							/>
						</div>
					</div>

					{/* Desktop: tabs */}
					<div className="hidden md:block">
						<DesktopTabs
							description={productData.description}
							productId={product.id}
						/>
					</div>
				</div>
			</div>

			{/* Swiper Styles */}
			<style jsx global>{`
        .swiper-pagination-bullet-custom {
          width: 8px;
          height: 8px;
          background: rgba(255, 255, 255, 0.5);
          opacity: 1;
          margin: 0 4px;
        }
        .swiper-pagination-bullet-active-custom {
          background: white;
        }
        .thumbnail-swiper .swiper-slide-thumb-active div {
          border-color: #ec4899;
        }
        .swiper-pagination {
          bottom: 16px;
        }
      `}</style>
		</div>
	);
}
