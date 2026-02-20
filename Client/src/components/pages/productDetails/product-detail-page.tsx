/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { ChevronRight, Minus, Plus } from "lucide-react";
import Image from "next/image";
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

type ColorOption = {
	id: string | number;
	name?: string;
	color: string;
};

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
	const [selectedSizes, setSelectedSizes] = useState<string[]>([]);
	const [selectedVarient, setSelectedVarient] = useState<any>(
		product?.varients?.[0] ?? null,
	);
	const [quantities, setQuantities] = useState<Record<string, number>>({});
	const [sellingPrice, setSellingPrice] = useState("");
	const [priceError, setPriceError] = useState<string | null>(null);

	const toggleSize = (size: string) => {
		setSelectedSizes((prev) => {
			if (prev.includes(size)) {
				// Remove size and its quantity
				setQuantities((q) => {
					const next = { ...q };
					delete next[size];
					return next;
				});
				return prev.filter((s) => s !== size);
			} else {
				// Add size with default qty 1
				setQuantities((q) => ({ ...q, [size]: 1 }));
				return [...prev, size];
			}
		});
	};

	const handleQtyChange = (size: string, type: "increase" | "decrease") => {
		setQuantities((prev) => ({
			...prev,
			[size]: type === "increase" ? (prev[size] || 0) + 1 : Math.max(1, (prev[size] || 1) - 1),
		}));
	};

	const totalQuantity = Object.values(quantities).reduce((a, b) => a + b, 0);

	const [thumbsSwiper, setThumbsSwiper] = useState<SwiperType | null>(null);

	// Determine selling type
	const sellingType: 'wholesale' | 'dropshipping' | 'both' = product.selling_type || 'both';
	const hasTiers = product.price_tiers && product.price_tiers.length > 0;
	const showWholesale = (sellingType === 'wholesale' || sellingType === 'both') && hasTiers;
	const showDropshipping = sellingType === 'dropshipping' || sellingType === 'both';
	const [activeTierId, setActiveTierId] = useState<number | null>(
		hasTiers ? product.price_tiers[0]?.id ?? null : null
	);



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
		const validPrice = validateSellingPrice();
		if (!validPrice) return;

		if (selectedSizes.length === 0) {
			toast.error("Please select at least one size.");
			return;
		}

		for (const size of selectedSizes) {
			const qty = quantities[size] || 1;
			const formData = new FormData();
			formData.append("product_id", product.id);
			formData.append("price", validPrice.toString());
			formData.append("qty", qty.toString());
			formData.append("size", size);
			if (selectedColors.length > 0) {
				formData.append("color", selectedColors.map(c => c.name).join(","));
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
		const validPrice = validateSellingPrice();
		if (!validPrice) return;

		if (selectedSizes.length === 0) {
			toast.error("Please select at least one size.");
			return;
		}

		let lastResult: any;
		for (const size of selectedSizes) {
			const qty = quantities[size] || 1;
			const formData = new FormData();
			formData.append("product_id", product.id);
			formData.append("price", validPrice.toString());
			formData.append("qty", qty.toString());
			formData.append("size", size);
			if (selectedColors.length > 0) {
				formData.append("color", selectedColors.map(c => c.name).join(","));
			}

			lastResult = await handleAsyncWithToast(async () => {
				return addToCart(formData);
			}, false);
		}

		if (lastResult?.data?.status) {
			window.location.href = "/order-confirmation";
		}
	};
	console.log(selectedVarient);
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

	const derivedColors: ColorOption[] = [
		{ id: 1, name: "Red", color: "red" },
		{ id: 2, name: "Blue", color: "blue" },
		{ id: 3, name: "Green", color: "green" },
		{ id: 4, name: "Black", color: "black" },
	];

	const [selectedColors, setSelectedColors] = useState<ColorOption[]>([]);

	const toggleColor = (c: ColorOption) => {
		setSelectedColors((prev) =>
			prev.find((sc) => sc.id === c.id)
				? prev.filter((sc) => sc.id !== c.id)
				: [...prev, c],
		);
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
							<div className="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-700">
								<span>Sold by {productData.vendor.companyName}</span>
								{productData.vendor.isVerifiedBadge && (
									<span className="rounded-full bg-sky-600 px-2 py-0.5 text-[10px] font-semibold text-white">
										Verified
									</span>
								)}
							</div>
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
							variant={selectedVarient}
						/>

						{/* ── Wholesale: MoveOn-Style Tier Price Badges ── */}
						{showWholesale && (
							<div className="mt-4">
								<h3 className="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
									📊 Wholesale Price Tiers
								</h3>
								<div className="flex flex-wrap gap-2">
									{product.price_tiers.map((tier: any) => {
										const isActive = activeTierId === tier.id;
										const qtyLabel = tier.max_qty
											? `${tier.min_qty} - ${tier.max_qty} Pcs`
											: `≥${tier.min_qty} Pcs`;
										return (
											<button
												key={tier.id}
												onClick={() => {
													setActiveTierId(tier.id);
													setSellingPrice(tier.unit_price);
																								if (tier.variant_title && !selectedSizes.includes(tier.variant_title)) {
													toggleSize(tier.variant_title);
												}
													toast.success(`Selected: ${qtyLabel}`);
												}}
												className={`relative flex flex-col items-center px-4 py-3 rounded-xl border-2 transition-all cursor-pointer min-w-[120px] ${isActive
													? 'border-emerald-500 bg-emerald-50 shadow-md shadow-emerald-100'
													: 'border-gray-200 bg-white hover:border-gray-300 hover:shadow-sm'
													}`}
											>
												{isActive && (
													<span className="absolute -top-2 -right-2 w-5 h-5 bg-emerald-500 rounded-full flex items-center justify-center">
														<svg className="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={3}>
															<path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
														</svg>
													</span>
												)}
												<span className={`text-lg font-bold ${isActive ? 'text-emerald-700' : 'text-gray-900'
													}`}>
													৳{parseFloat(tier.unit_price).toFixed(2)}
												</span>
												<span className={`text-[11px] font-medium mt-0.5 ${isActive ? 'text-emerald-600' : 'text-gray-500'
													}`}>
													{qtyLabel}
												</span>
												{tier.delivery_charge && (
													<span className="text-[10px] text-gray-400 mt-0.5">
														Deliv: ৳{parseFloat(tier.delivery_charge).toFixed(0)}
													</span>
												)}
											</button>
										);
									})}
								</div>
							</div>
						)}

						{/* Size Selection */}
						<div className="bg-[#F4F4F4] p-4 space-y-4 rounded-lg">
							{/* Color Multi-Select */}
							<div className="mb-3">
								<h3 className="font-medium mb-2 text-sm text-gray-900">Color <span className="text-xs text-gray-500">(select multiple)</span></h3>
								<div className="flex gap-3 overflow-x-auto p-1">
									{derivedColors.map((c) => {
										const isSelected = selectedColors.some((sc) => sc.id === c.id);
										return (
											<button
												key={c.id}
												onClick={() => toggleColor(c)}
												className={`relative flex flex-col items-center p-1.5 rounded-md transition min-w-12 ${isSelected
													? "ring-2 ring-pink-500 bg-pink-50"
													: "hover:ring-1 hover:ring-gray-300"
													}`}
											>
												{isSelected && (
													<span className="absolute -top-1 -right-1 w-4 h-4 bg-pink-500 rounded-full flex items-center justify-center">
														<svg className="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={3}>
															<path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
														</svg>
													</span>
												)}
												<div
													className="w-6 h-6 rounded-full border-2"
													style={{ backgroundColor: c.color, borderColor: isSelected ? '#ec4899' : '#d1d5db' }}
												></div>
												<span className={`text-[10px] mt-1 ${isSelected ? 'font-semibold text-pink-600' : ''}`}>{c.name}</span>
											</button>
										);
									})}
								</div>
							</div>

							{/* Size Multi-Select */}
							<div className="space-y-3">
								<h3 className="font-medium text-gray-900">Size <span className="text-xs text-gray-500">(select multiple)</span></h3>
								<div className="flex flex-wrap gap-2">
									{productData.sizes.map((size: any) => {
										const isSelected = selectedSizes.includes(size);
										return (
											<button
												key={size}
												onClick={() => toggleSize(size)}
												className={`relative px-4 py-2 border rounded-lg text-sm font-medium transition-all ${isSelected
													? "border-pink-500 text-pink-600 bg-pink-50 shadow-sm"
													: "border-gray-300 text-gray-700 hover:border-gray-400 bg-white"
													}`}
											>
												{isSelected && (
													<span className="absolute -top-1.5 -right-1.5 w-4 h-4 bg-pink-500 rounded-full flex items-center justify-center">
														<svg className="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={3}>
															<path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
														</svg>
													</span>
												)}
												{size}
											</button>
										);
									})}
								</div>
							</div>

							{/* Per-Size Quantity Table */}
							{selectedSizes.length > 0 && (
								<div className="space-y-3">
									<h3 className="font-medium text-gray-900">Quantity per Size</h3>
									<div className="bg-white rounded-lg border border-gray-200 overflow-hidden">
										<div className="grid grid-cols-3 gap-4 px-4 py-2 bg-gray-50 text-xs font-semibold text-gray-600 uppercase">
											<div>Size</div>
											<div>Price</div>
											<div className="text-right">Quantity</div>
										</div>
										{selectedSizes.map((size) => (
											<div key={size} className="grid grid-cols-3 gap-4 px-4 py-3 border-t border-gray-100 items-center">
												<div className="font-medium text-gray-900 text-sm">{size}</div>
												<div className="text-gray-700 flex items-center text-sm">
													<TbCurrencyTaka size={16} />
													{productData.currentPrice.toFixed(2)}
												</div>
												<div className="flex items-center justify-end gap-1">
													<button
														onClick={() => handleQtyChange(size, "decrease")}
														className="w-8 h-8 border border-gray-300 rounded-lg flex items-center justify-center hover:bg-gray-50 transition-colors bg-white"
													>
														<Minus className="w-3 h-3" />
													</button>
													<span className="w-10 h-8 rounded-lg flex items-center justify-center bg-white border border-gray-200 text-sm font-medium">
														{quantities[size] || 0}
													</span>
													<button
														onClick={() => handleQtyChange(size, "increase")}
														className="w-8 h-8 border border-gray-300 rounded-lg flex items-center justify-center hover:bg-pink-50 transition-colors bg-white"
													>
														<Plus className="w-3 h-3" />
													</button>
												</div>
											</div>
										))}
										{/* Total Row */}
										<div className="grid grid-cols-3 gap-4 px-4 py-2 border-t-2 border-gray-200 bg-gray-50 font-semibold text-sm">
											<div className="text-gray-900">Total</div>
											<div className="text-pink-600 flex items-center">
												<TbCurrencyTaka size={16} />
												{(productData.currentPrice * totalQuantity).toFixed(2)}
											</div>
											<div className="text-right text-gray-700">{totalQuantity} pcs</div>
										</div>
									</div>
								</div>
							)}
						</div>

						{/* Unit Price + Total Price */}
						<div className="space-y-1">
							<div className="text-3xl font-bold text-gray-900 flex items-center">
								<TbCurrencyTaka size={35} />
								{productData.currentPrice.toFixed(2)}
								<span className="text-sm font-normal text-gray-500 ml-1">/pc</span>
							</div>
							{totalQuantity > 0 && (
								<div className="flex items-center gap-2 text-sm">
									<span className="text-gray-500">Total ({totalQuantity} pcs):</span>
									<span className="font-bold text-lg text-pink-600 flex items-center">
										<TbCurrencyTaka size={20} />
										{(productData.currentPrice * totalQuantity).toFixed(2)}
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

				{/* Description */}
				<div className="mt-12">
					<h2 className="text-xl font-semibold text-pink-600 border-b w-fit">
						Description
					</h2>
					{/* <div className=" h-0.5 bg-pink-600 mb-4"></div> */}
					<div
						dangerouslySetInnerHTML={{ __html: productData.description }}
						className="text-gray-700 leading-relaxed w-full overflow-hidden"
					/>
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
