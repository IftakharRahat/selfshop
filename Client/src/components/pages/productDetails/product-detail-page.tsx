/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { BadgeCheck, ChevronRight, Lock, Minus, Plus, Tag } from "lucide-react";
import Image from "next/image";
import Link from "next/link";
import { useState, useEffect } from "react";
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

import { cn, getImageUrl } from "@/lib/utils";
import { formatBDT } from "@/lib/format-currency";
import { useAppSelector } from "@/redux/hooks";
import OrderNowModal from "./OrderNowModal";
import ProductReviewsSection from "./ProductReviewsSection";
import { useIsActiveReseller } from "@/hooks/useIsActiveReseller";
import { useCheckInShopQuery, useAddToShopMutation, useRemoveFromShopMutation } from "@/redux/api/shopApi";
import { Store } from "lucide-react";

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

export default function ProductDetailPage({ product, flashSale, commissionPercent }: any) {
	const [orderOpen, setOrderOpen] = useState(false);
	const token = useAppSelector((state) => state.auth.access_token);
	const { isActive: isResellerActive } = useIsActiveReseller();

	const [addToCart, { isLoading }] = useAddToCartMutation();

	// ---- Shop Hooks ----
	const { data: shopStatus } = useCheckInShopQuery(product.id, { skip: !token });
	const [addToShop, { isLoading: isAddingToShop }] = useAddToShopMutation();
	const [removeFromShop, { isLoading: isRemovingFromShop }] = useRemoveFromShopMutation();
	const isInShop = shopStatus?.in_shop ?? false;
	const isShopLoading = isAddingToShop || isRemovingFromShop;

	const handleToggleShop = async () => {
		if (!token) {
			Swal.fire({ icon: "error", title: "Unauthorized", text: "Please log in to add products to your shop." });
			return;
		}
		try {
			if (isInShop) {
				await removeFromShop(product.id).unwrap();
				toast.success("Product removed from your shop.");
			} else {
				await addToShop(product.id).unwrap();
				toast.success("Product added to your shop!");
			}
		} catch {
			toast.error("Something went wrong. Please try again.");
		}
	};

	// ---- Transform Backend Data ----
	const images = [
		product.ViewProductImage,
		...JSON.parse(product.PostImage || "[]").map((img: string) => `${img}`),
	];

	const productData = {
		name: product.ProductName,
		category: product.categories?.category_name || `Category #${product.category_id}`,
		quantity: product.qty,
		sku: product.ProductSku,
		commission_percent: parseFloat(commissionPercent || product.commission_percent || "0"),
		minimumPrice: parseFloat(product.min_sell_price || "0"),
		currentPrice: parseFloat(product.ProductResellerPrice || product.ProductRegularPrice || "0"),
		msrpPrice: parseFloat(product.ProductRegularPrice || "0"),
		description: product.ProductDetails,
		images: {
			main: images,
			thumbnails: images,
		},
		sizes: Array.isArray(product.size)
			? product.size
			: typeof product.size === "string"
				? JSON.parse(product.size)
				: [],
		varients: product.varients || [],
		priceTiers: product.price_tiers || [], // Ensure we have product-level tiers
		vendor: product.vendor
			? {
				companyName: product.vendor.company_name || "",
				isVerifiedBadge: Boolean(product.vendor.is_verified_badge),
				slug: product.vendor.slug || "",
				logoPath: product.vendor.logo_path || null,
			}
			: null,
	};

	// ---- UI States ----
	const variants: any[] = productData.varients || [];
	const [activeVariantIdx, setActiveVariantIdx] = useState(0);
	const [activeSizeIdx, setActiveSizeIdx] = useState(0); // New: tracks currently "highlighted" size within active variant
	// Per-variant per-size quantities: { [variantId]: { [size]: qty } }
	const [variantQuantities, setVariantQuantities] = useState<Record<number, Record<string, number>>>({});
	// Per-variant per-size selling prices: { [variantId]: { [size]: priceString } }
	const [variantSellingPrices, setVariantSellingPrices] = useState<Record<number, Record<string, string>>>({});

	const handleSellingPriceChange = (variantId: number, size: string, value: string) => {
		setVariantSellingPrices((prev) => {
			const varSizes = { ...(prev[variantId] || {}) };
			varSizes[size] = value;
			return { ...prev, [variantId]: varSizes };
		});
	};

	// ---- Flash Sale Countdown ----
	const [timeLeft, setTimeLeft] = useState({ h: 0, m: 0, s: 0 });
	useEffect(() => {
		if (!flashSale || !flashSale.flash_sale_end_time) return;

		const timer = setInterval(() => {
			const diff = new Date(flashSale.flash_sale_end_time).getTime() - Date.now();
			if (diff <= 0) {
				setTimeLeft({ h: 0, m: 0, s: 0 });
				clearInterval(timer);
			} else {
				setTimeLeft({
					h: Math.floor((diff / (1000 * 60 * 60))),
					m: Math.floor((diff / (1000 * 60)) % 60),
					s: Math.floor((diff / 1000) % 60),
				});
			}
		}, 1000);
		return () => clearInterval(timer);
	}, [flashSale]);

	const pad = (n: number) => n.toString().padStart(2, "0");

	const handleQtyChange = (variantId: number, size: string, type: "increase" | "decrease", stock?: number) => {
		setVariantQuantities((prev) => {
			const varSizes = { ...(prev[variantId] || {}) };
			const cur = varSizes[size] || 0;
			let next = type === "increase" ? cur + 1 : Math.max(0, cur - 1);
			if (type === "increase" && stock !== undefined && next > stock) {
				next = stock;
				toast.error(`Only ${stock} items in stock for size ${size}`);
			}
			varSizes[size] = next;
			return { ...prev, [variantId]: varSizes };
		});
	};

	const handleQtySet = (variantId: number, size: string, value: string, stock?: number) => {
		const num = parseInt(value, 10);
		setVariantQuantities((prev) => {
			const varSizes = { ...(prev[variantId] || {}) };
			let next = value === '' ? 0 : (isNaN(num) ? 0 : Math.max(0, num));
			if (stock !== undefined && next > stock) {
				next = stock;
				toast.error(`Only ${stock} items in stock for size ${size}`);
			}
			varSizes[size] = next;
			return { ...prev, [variantId]: varSizes };
		});
	};

	// Total quantity across ALL variants and sizes
	const totalQuantity = Object.values(variantQuantities)
		.flatMap((sizes) => Object.values(sizes))
		.reduce((a, b) => a + b, 0);

	const [thumbsSwiper, setThumbsSwiper] = useState<SwiperType | null>(null);

	// Helper: get unit price for a specific size based on its own bulk tiers OR product tiers
	const getSizePrice = (sizeItem: any, qty: number) => {
		// 0. If flash sale is active and valid, it overrides everything
		if (flashSale && flashSale.flash_price > 0) {
			return parseFloat(flashSale.flash_price);
		}

		let rawPrice = 0;

		// 1. Check size-level bulk tiers if any (normalize camelCase/snake_case)
		const tiers = sizeItem.bulkPrices || sizeItem.bulk_prices || [];
		if (tiers && tiers.length > 0) {
			const tier = tiers
				.slice()
				.sort((a: any, b: any) => b.min_qty - a.min_qty)
				.find((t: any) => qty >= t.min_qty);
			if (tier) {
				rawPrice = parseFloat(tier.bulk_price || tier.unit_price);
				return rawPrice * commissionFactor;
			}
		}

		// 2. Check size-level base price if any
		if (sizeItem.price !== null && sizeItem.price !== undefined) {
			const sPrice = parseFloat(sizeItem.price);
			if (sPrice > 0) return sPrice * commissionFactor;
		}

		// 3. Fallback to product-level tiers based on total quantity
		if (productData.priceTiers && productData.priceTiers.length > 0) {
			const tier = productData.priceTiers
				.slice()
				.sort((a: any, b: any) => b.min_qty - a.min_qty)
				.find((t: any) => totalQuantity >= t.min_qty);
			if (tier) return parseFloat(tier.unit_price) * commissionFactor;
		}

		// 4. Final fallback to product-level current price
		return productData.currentPrice * commissionFactor;
	};

	// Determine selling type
	const sellingType: 'wholesale' | 'dropshipping' | 'both' = product.selling_type || 'both';
	const hasProductTiers = productData.priceTiers && productData.priceTiers.length > 0;
	const showWholesale = (sellingType === 'wholesale' || sellingType === 'both') && hasProductTiers;
	const showDropshipping = sellingType === 'dropshipping' || sellingType === 'both';

	// Use per-row selling price inputs when there are multiple variants
	// OR when a variant has multiple sizes (reseller may want different prices per size)
	// Single input only for truly simple products (1 variant with 1 size)
	const usePerRowPricing = (() => {
		if (variants.length > 1) return true;
		// Check if the single variant has multiple sizes
		const v = variants[0];
		if (v?.sizes && v.sizes.length > 1) return true;
		// Also check product-level sizes
		if (productData.sizes && productData.sizes.length > 1) return true;
		return false;
	})();

	// For display consistency in main section, we'll show the "current" effective unit price based on total qty
	const activeTier = hasProductTiers
		? productData.priceTiers
			.slice()
			.sort((a: any, b: any) => b.min_qty - a.min_qty)
			.find((t: any) => totalQuantity >= t.min_qty) ?? productData.priceTiers[0]
		: null;
	const activeTierId = activeTier?.id ?? null;
	const firstSizePrice = (() => {
		const fv = variants[0];
		if (!fv) return productData.currentPrice;
		const fs = fv.sizes?.[0];
		if (!fs) return fv.price || productData.currentPrice;
		return (fs.price > 0) ? fs.price : (fs.bulkPrices?.[0]?.bulk_price || fs.bulk_prices?.[0]?.bulk_price || productData.currentPrice);
	})();

	const commissionFactor = 1 + (productData.commission_percent / 100);
	const effectiveUnitPrice = flashSale && flashSale.flash_price > 0
		? parseFloat(flashSale.flash_price)
		: (activeTier ? parseFloat(activeTier.unit_price) : (firstSizePrice || productData.currentPrice)) * commissionFactor;

	// Validate all selected items have valid selling prices (for dropshipping)
	const validateAllSellingPrices = (): boolean => {
		const items = getSelectedItems();
		let allValid = true;
		for (const item of items) {
			if (!item.sellingPrice || item.sellingPrice < item.price) {
				allValid = false;
				break;
			}
		}
		if (!allValid) {
			toast.error("Please enter a valid selling price for all selected items (must be ≥ cost price).");
		}
		return allValid;
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

		if (showDropshipping && !validateAllSellingPrices()) {
			return;
		}

		const items = getSelectedItems();
		if (items.length === 0) {
			toast.error("Please select at least one item.");
			return;
		}

		for (const item of items) {
			const formData = new FormData();
			formData.append("product_id", product.id);
			formData.append("price", item.price.toString());
			if (showDropshipping && item.sellingPrice) {
				formData.append("selling_price", item.sellingPrice.toString());
			}
			formData.append("qty", item.qty.toString());
			formData.append("size", item.size); // The size name
			if (item.variantId) {
				formData.append("varient_id", item.variantId.toString());
			}
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

		if (showDropshipping && !validateAllSellingPrices()) {
			return;
		}

		const items = getSelectedItems();
		if (items.length === 0) {
			toast.error("Please select at least one item.");
			return;
		}

		let lastResult: any;
		for (const item of items) {
			const formData = new FormData();
			formData.append("product_id", product.id);
			formData.append("price", item.price.toString());
			if (showDropshipping && item.sellingPrice) {
				formData.append("selling_price", item.sellingPrice.toString());
			}
			formData.append("qty", item.qty.toString());
			formData.append("size", item.size); // The size name
			if (item.variantId) {
				formData.append("varient_id", item.variantId.toString());
			}
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
		const items: { variantId: number; variantTitle: string; size: string; qty: number; price: number; sellingPrice: number | null }[] = [];
		for (const [vid, sizes] of Object.entries(variantQuantities)) {
			const v = variants.find((vr: any) => vr.id === Number(vid));
			for (const [sizeName, qty] of Object.entries(sizes)) {
				if (qty > 0) {
					const variantLabel = v?.color_name || v?.title || "";
					// Find the actual size object for price calculation
					const sizeItem = v?.sizes?.find((s: any) => s.size_name === sizeName);
					const itemPrice = sizeItem ? getSizePrice(sizeItem, qty) : effectiveUnitPrice;
					// Get per-item selling price
					const spStr = variantSellingPrices[Number(vid)]?.[sizeName] || "";
					const sp = spStr ? parseFloat(spStr) : null;
					items.push({
						variantId: Number(vid),
						variantTitle: variantLabel,
						size: sizeName,
						qty,
						price: itemPrice,
						sellingPrice: sp && !isNaN(sp) ? sp : null,
					});
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
					<Link href="/" className="text-gray-600 hover:text-pink-600 transition-colors">
						Home
					</Link>
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

						{flashSale && (
							<div className="bg-gradient-to-r from-[#E5005F] to-[#ff4b9c] p-4 rounded-xl text-white shadow-lg overflow-hidden relative">
								<div className="absolute top-0 right-0 p-2 opacity-10">
									<Tag size={100} rotate={45} />
								</div>
								<div className="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
									<div className="flex items-center gap-3">
										<div className="bg-white/20 p-2 rounded-lg backdrop-blur-sm">
											<span className="text-2xl">⚡</span>
										</div>
										<div>
											<p className="text-[10px] uppercase font-bold tracking-widest opacity-90">Flash Sale Active</p>
											<h3 className="text-xl font-bold leading-tight">{flashSale.flash_sale_title}</h3>
										</div>
									</div>
									<div className="flex flex-col items-start sm:items-end gap-1">
										<p className="text-[10px] uppercase font-bold tracking-widest opacity-90">Ends In</p>
										<div className="flex items-center gap-2">
											<div className="bg-white text-[#E5005F] font-bold px-2 py-1 rounded text-lg min-w-[40px] text-center">{pad(timeLeft.h)}</div>
											<span className="font-bold">:</span>
											<div className="bg-white text-[#E5005F] font-bold px-2 py-1 rounded text-lg min-w-[40px] text-center">{pad(timeLeft.m)}</div>
											<span className="font-bold">:</span>
											<div className="bg-white text-[#E5005F] font-bold px-2 py-1 rounded text-lg min-w-[40px] text-center">{pad(timeLeft.s)}</div>
										</div>
									</div>
								</div>
							</div>
						)}
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
								{(() => {
									const currentVariant = variants[activeVariantIdx];
									let qty = 0;
									if (currentVariant?.sizes && currentVariant.sizes.length > 0) {
										const selectedSize = currentVariant.sizes[activeSizeIdx];
										qty = selectedSize?.qty ?? 0;
									} else {
										qty = currentVariant?.qty ?? productData.quantity;
									}
									return qty <= 0
										? <span className="ml-2 inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700">Stock Out</span>
										: <span className="ml-2 text-gray-600">{qty}</span>;
								})()}
							</div>
							<div className="flex items-center">
								<span className="font-medium text-gray-900">SKU :</span>
								<span className="ml-2 text-gray-600">{productData.sku}</span>
							</div>
							<div className="flex items-center">
								<span className="font-medium text-gray-900">
									Price :
								</span>
								<div className="ml-2 flex flex-col">
									{!isResellerActive ? (
										<span className="text-pink-600 font-bold flex items-center gap-1.5 bg-pink-50 px-2 py-0.5 rounded border border-pink-100 text-xs shadow-sm">
											<Lock className="w-3 h-3" /> Active profile required to see price
										</span>
									) : (
										<div className="flex flex-col">
											{productData.msrpPrice > 0 && productData.msrpPrice > effectiveUnitPrice && sellingType !== 'dropshipping' && (
												<span className="text-xs text-gray-400 line-through flex items-center">
													<TbCurrencyTaka size={14} />
													{formatBDT(productData.msrpPrice)}
												</span>
											)}
											<div className="flex items-center text-pink-600 font-bold text-xl">
												<TbCurrencyTaka size={24} />
												{formatBDT(effectiveUnitPrice)}
											</div>
										</div>
									)}
								</div>
							</div>
						</div>

						{/* ── Bulk Discounts for Active Size (moved here from below) ── */}
						{(() => {
							const cv = variants[activeVariantIdx];
							const cvId = cv?.id ?? 0;
							const ss = cv?.sizes?.[activeSizeIdx];
							const ssQty = ss ? (variantQuantities[cvId]?.[ss.size_name] || 0) : 0;
							const bulkPrices = ss?.bulkPrices || ss?.bulk_prices || [];
							if (bulkPrices.length === 0) return null;
							return (
								<div className="p-3 bg-pink-50/50 border border-pink-100 rounded-xl space-y-2">
									<h4 className="text-xs font-bold text-pink-700 uppercase tracking-tight flex items-center gap-1.5">
										<Tag className="w-3 h-3" /> Bulk Discounts for size {ss.size_name}
									</h4>
									<div className="flex flex-wrap gap-2">
										{bulkPrices.map((tier: any, tIdx: number) => {
											const isActiveBulkTier = ssQty >= tier.min_qty && (!tier.max_qty || ssQty <= tier.max_qty);
											return (
												<div
													key={tIdx}
													className={`px-2.5 py-1.5 rounded-lg border text-[11px] font-semibold shadow-sm transition-all duration-200 ${
														isActiveBulkTier
															? 'bg-pink-600 text-white border-pink-600 scale-105 ring-2 ring-pink-300'
															: 'bg-white text-pink-600 border-pink-200'
													}`}
												>
													{tier.min_qty}{tier.max_qty ? `-${tier.max_qty}` : '+'} pcs: <span className={isActiveBulkTier ? 'font-bold' : 'text-pink-700 font-bold'}>{isResellerActive ? `৳${formatBDT(Number(tier.bulk_price || tier.unit_price) * commissionFactor)}` : '***'}</span>
												</div>
											);
										})}
									</div>
								</div>
							);
						})()}


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
														{!isResellerActive ? (
															"***"
														) : (
															<>
																<TbCurrencyTaka size={20} />
																{formatBDT(parseFloat(tier.unit_price) * commissionFactor)}
															</>
														)}
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
													onClick={() => {
														setActiveVariantIdx(idx);
														setActiveSizeIdx(0);
													}}
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
													{v.image ? (
														<Image
															src={getImageUrl(v.image) || "/placeholder.svg"}
															alt={v.title || `Variant ${idx + 1}`}
															width={40}
															height={40}
															className="w-10 h-10 rounded object-cover"
														/>
													) : colorCode ? (
														<div
															className="w-10 h-10 rounded-full border-2 border-gray-200 shadow-inner"
															style={{ backgroundColor: colorCode }}
															title={v.color_name || v.title || colorCode}
														/>
													) : (
														<div className="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-500 border border-gray-300">
															{(v.color_name || v.title || '?').slice(0, 2)}
														</div>
													)}
													{v.image && colorCode && (
														<span
															className="mt-1 inline-block h-2.5 w-2.5 rounded-full border border-gray-300"
															style={{ backgroundColor: colorCode }}
															title={v.color_name || colorCode}
														/>
													)}
													<span className="mt-1 text-[10px] leading-none text-gray-500 font-semibold text-center mt-1">
														{!isResellerActive ? (
															"***"
														) : v.price ? (
															`৳${v.price}`
														) : (
															<span className="text-gray-400 italic">No override</span>
														)}
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

								// First priority: read sizes from explicitly attached variant sizes if available
								let sizesForTable: Array<{ size_name: string, price: string | null, qty: number, bulk_prices: any[] }> = [];
								if (currentVariant?.sizes && currentVariant.sizes.length > 0) {
									sizesForTable = currentVariant.sizes.map((sz: any) => ({
										size_name: sz.size_name,
										price: sz.price,
										qty: sz.qty,
										bulk_prices: sz.bulkPrices || sz.bulk_prices || [] // Normalizing
									}));
								} else {
									// Fallback: Use product-level sizes with variant stock OR product stock
									const overrideStock = currentVariant ? currentVariant.qty : productData.quantity;
									const legacySizes = productData.sizes && productData.sizes.length > 0 ? productData.sizes : ['Default'];
									sizesForTable = legacySizes.map((s: string) => ({
										size_name: s,
										price: null,
										qty: overrideStock,
										bulk_prices: []
									}));
								}

								return (
									<div className="space-y-3">
										<div className="bg-white rounded-lg border border-gray-200 overflow-hidden">
											<div className={`grid gap-0 sm:gap-3 px-2 sm:px-4 py-2 bg-gray-50 text-[10px] sm:text-xs font-semibold text-gray-600 uppercase grid-cols-[2fr_2fr_1fr_3fr] ${showDropshipping && isResellerActive && usePerRowPricing ? 'sm:grid-cols-[1.5fr_1.5fr_1fr_2fr_2fr_1.5fr]' : 'sm:grid-cols-4'}`}>
												<div>Size</div>
												<div>Price</div>
												<div className="text-center">Stock</div>
												<div className="text-right">Quantity</div>
												{showDropshipping && isResellerActive && usePerRowPricing && (
													<>
														<div className="text-center hidden sm:block">My Price</div>
														<div className="text-right hidden sm:block">Earn</div>
													</>
												)}
											</div>
											{sizesForTable.map((sz, szIdx) => {
												const size = sz.size_name;
												const qty = variantQuantities[currentVarId]?.[size] || 0;
												const displayPrice = getSizePrice(sz, qty);
												const isSelected = activeSizeIdx === szIdx;
												// Per-row selling price state
												const rowSellingPrice = variantSellingPrices[currentVarId]?.[size] || "";
												const rowSP = rowSellingPrice ? parseFloat(rowSellingPrice) : 0;
												const rowEarnings = qty > 0 && rowSP >= displayPrice ? (rowSP - displayPrice) * qty : 0;
												const rowPriceInvalid = rowSellingPrice !== "" && rowSP < displayPrice;
												return (
													<div key={size} className={cn("border-t border-gray-100 cursor-pointer transition-colors", isSelected ? "bg-pink-50/50" : "hover:bg-gray-50/50")}>
														{/* Main row: Size / Price / Stock / Qty — always 4 cols on mobile, 6 on sm+ when per-row pricing */}
														<div onClick={() => setActiveSizeIdx(szIdx)} className={`grid gap-0 sm:gap-3 px-2 sm:px-4 py-2 sm:py-3 items-center grid-cols-[2fr_2fr_1fr_3fr] ${showDropshipping && isResellerActive && usePerRowPricing ? 'sm:grid-cols-[1.5fr_1.5fr_1fr_2fr_2fr_1.5fr]' : 'sm:grid-cols-4'}`}>
															<div className="font-medium text-gray-900 text-sm">{size}</div>
															<div className="text-gray-700 flex items-center text-sm gap-1">
																<div className="flex items-center">
																	{isResellerActive ? (
																		<>
																			<TbCurrencyTaka size={14} />
																			{formatBDT(displayPrice)}
																		</>
																	) : (
																		<span className="text-xs text-gray-400">Locked</span>
																	)}
																</div>
																{sz.bulk_prices?.length > 0 && (
																	<span className="text-[9px] bg-green-100 text-green-700 px-1 rounded font-bold uppercase leading-tight">
																		Bulk
																	</span>
																)}
															</div>
															<div className="text-sm text-center">{sz.qty <= 0 ? <span className="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-700">Stock Out</span> : <span className="text-gray-600">{sz.qty}</span>}</div>
															<div className="flex items-center justify-end gap-1" onClick={(e) => e.stopPropagation()}>
																<button
																	onClick={() => handleQtyChange(currentVarId, size, "decrease", sz.qty)}
																	className="w-8 h-8 border border-gray-300 rounded-lg flex items-center justify-center hover:bg-gray-50 transition-colors bg-white"
																>
																	<Minus className="w-3 h-3" />
																</button>
																<input
																	type="number"
																	min={0}
																	max={sz.qty}
																	value={qty || ''}
																	onChange={(e) => handleQtySet(currentVarId, size, e.target.value, sz.qty)}
																	placeholder="0"
																	className={`w-14 h-8 rounded-lg text-center border text-sm font-medium outline-none focus:ring-1 focus:ring-pink-400 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none ${qty > 0 ? 'bg-pink-50 border-pink-300 text-pink-700' : 'bg-white border-gray-200'}`}
																/>
																<button
																	disabled={qty >= sz.qty || sz.qty <= 0}
																	onClick={() => handleQtyChange(currentVarId, size, "increase", sz.qty)}
																	className={`w-8 h-8 border rounded-lg flex items-center justify-center transition-colors ${qty >= sz.qty ? 'opacity-40 cursor-not-allowed bg-gray-100 border-gray-200' : 'hover:bg-pink-50 border-gray-300 bg-white'}`}
																>
																	<Plus className="w-3 h-3" />
																</button>
															</div>
															{/* Desktop: inline My Price + Earn columns */}
															{showDropshipping && isResellerActive && usePerRowPricing && (
																<>
																	<div className="hidden sm:flex items-center justify-center" onClick={(e) => e.stopPropagation()}>
																		<input
																			type="number"
																			min={Math.ceil(displayPrice)}
																			value={rowSellingPrice}
																			onChange={(e) => handleSellingPriceChange(currentVarId, size, e.target.value)}
																			placeholder={`≥${Math.ceil(displayPrice)}`}
																			className={`w-20 h-8 rounded-lg text-center border text-sm font-medium outline-none focus:ring-1 focus:ring-pink-400 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none ${rowPriceInvalid ? 'border-red-400 bg-red-50 text-red-600' : rowSP >= displayPrice && rowSellingPrice ? 'border-green-400 bg-green-50 text-green-700' : 'bg-white border-gray-200'}`}
																		/>
																	</div>
																	<div className="hidden sm:block text-right text-sm">
																		{rowEarnings > 0 ? (
																			<span className="text-green-600 font-semibold flex items-center justify-end">
																				+৳{formatBDT(rowEarnings)}
																			</span>
																		) : rowPriceInvalid ? (
																			<span className="text-red-500 text-[10px]">Too low</span>
																		) : (
																			<span className="text-gray-400 text-xs">—</span>
																		)}
																	</div>
																</>
															)}
														</div>
														{/* Mobile: My Price + Earn sub-row */}
														{showDropshipping && isResellerActive && usePerRowPricing && (
															<div className="sm:hidden flex items-center gap-2 px-2 pb-2 pt-1" onClick={(e) => e.stopPropagation()}>
																<span className="text-[10px] text-gray-500 font-semibold uppercase shrink-0">My Price:</span>
																<input
																	type="number"
																	min={Math.ceil(displayPrice)}
																	value={rowSellingPrice}
																	onChange={(e) => handleSellingPriceChange(currentVarId, size, e.target.value)}
																	placeholder={`≥${Math.ceil(displayPrice)}`}
																	className={`w-24 h-8 rounded-lg text-center border text-sm font-medium outline-none focus:ring-1 focus:ring-pink-400 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none ${rowPriceInvalid ? 'border-red-400 bg-red-50 text-red-600' : rowSP >= displayPrice && rowSellingPrice ? 'border-green-400 bg-green-50 text-green-700' : 'bg-white border-gray-200'}`}
																/>
																<span className="text-sm shrink-0">
																	{rowEarnings > 0 ? (
																		<span className="text-green-600 font-semibold">+৳{formatBDT(rowEarnings)}</span>
																	) : rowPriceInvalid ? (
																		<span className="text-red-500 text-[10px]">Too low</span>
																	) : null}
																</span>
															</div>
														)}
													</div>
												);
											})}
											{/* Total Row */}
											{(() => {
												const selectedItems = getSelectedItems();
												const totalPrice = selectedItems.reduce((sum, item) => sum + (item.price * item.qty), 0);
												return (
													<div className={`grid gap-0 sm:gap-3 px-2 sm:px-4 py-2 border-t-2 border-gray-200 bg-gray-50 font-semibold text-sm grid-cols-[2fr_2fr_1fr_3fr] ${showDropshipping && isResellerActive && usePerRowPricing ? 'sm:grid-cols-[1.5fr_1.5fr_1fr_2fr_2fr_1.5fr]' : 'sm:grid-cols-4'}`}>
														<div className="text-gray-900">Total</div>
														<div className="text-pink-600 flex items-center">
															{isResellerActive ? (
																<>
																	<TbCurrencyTaka size={16} />
																	{formatBDT(totalPrice)}
																</>
															) : (
																"***"
															)}
														</div>
														<div></div>
														<div className="text-right text-gray-700">{totalQuantity} pcs</div>
														{showDropshipping && isResellerActive && usePerRowPricing && (
															<>
																<div className="hidden sm:block"></div>
																<div className="hidden sm:block text-right">
																	{(() => {
																		// Calculate total earnings from all selected items across all variants
																		const allItems = getSelectedItems();
																		const totalEarn = allItems.reduce((sum, item) => {
																			if (item.sellingPrice && item.sellingPrice >= item.price) {
																				return sum + (item.sellingPrice - item.price) * item.qty;
																			}
																			return sum;
																		}, 0);
																		return totalEarn > 0 ? (
																			<span className="text-green-600 font-bold">+৳{formatBDT(totalEarn)}</span>
																		) : (
																			<span className="text-gray-400">—</span>
																		);
																	})()}
																</div>
															</>
														)}
													</div>
												);
											})()}
										</div>
									</div>
								);
							})()}
						</div>


						{/* Unit Price + Total Price */}
						{(() => {
							const currentVariant = variants[activeVariantIdx];
							const selectedSize = currentVariant?.sizes?.[activeSizeIdx];
							const unitPrice = selectedSize
								? getSizePrice(selectedSize, variantQuantities[currentVariant.id]?.[selectedSize.size_name] || 0)
								: effectiveUnitPrice;

							const selectedItems = getSelectedItems();
							const totalPrice = selectedItems.reduce((sum, item) => sum + (item.price * item.qty), 0);

							// Size specific bulk tiers
							const sizeBulkPrices = selectedSize?.bulkPrices || selectedSize?.bulk_prices || [];

							return (
								<div className="space-y-4">
									<div className="space-y-1">
										<div className="flex items-baseline gap-3">
											<div className="text-3xl font-bold text-gray-900 flex items-center digit-font">
												{isResellerActive ? (
													<>
														<TbCurrencyTaka size={35} />
														{formatBDT(unitPrice)}
													</>
												) : (
													"***"
												)}
											</div>
											{flashSale && isResellerActive && (
												<div className="text-lg text-gray-400 line-through flex items-center">
													<TbCurrencyTaka size={20} />
													{formatBDT(parseFloat(flashSale.original_price))}
												</div>
											)}
											{isResellerActive && <span className="text-sm font-normal text-gray-500">/pc</span>}
										</div>
										{totalQuantity > 0 && (
											<div className="flex items-center gap-2 text-sm">
												<span className="text-gray-500">Total ({totalQuantity} pcs):</span>
												<span className="font-bold text-lg text-pink-600 flex items-center digit-font">
													{isResellerActive ? (
														<>
															<TbCurrencyTaka size={20} />
															{formatBDT(totalPrice)}
														</>
													) : (
														"***"
													)}
												</span>
											</div>
										)}
									</div>


								</div>
							);
						})()}

						{/* Dropshipping: Total Earnings Summary (per-row mode) OR Single Input (simple mode) */}
						{showDropshipping && isResellerActive && (() => {
							const allItems = getSelectedItems();
							const totalEarnings = allItems.reduce((sum, item) => {
								if (item.sellingPrice && item.sellingPrice >= item.price) {
									return sum + (item.sellingPrice - item.price) * item.qty;
								}
								return sum;
							}, 0);

							if (!usePerRowPricing) {
								// Single selling price input for simple products
								// Use fallback variant ID 0 when no variants exist (matches size table logic)
								const fallbackVarId = variants[0]?.id ?? 0;
								const fallbackSizes = variants[0]?.sizes?.length > 0
									? variants[0].sizes.map((s: any) => s.size_name)
									: (productData.sizes?.length > 0 ? productData.sizes : ['Default']);
								const singleSP = variantSellingPrices[fallbackVarId]?.[fallbackSizes[0]] || "";
								const spNum = singleSP ? parseFloat(singleSP) : 0;
								const isTooLow = singleSP !== "" && spNum < effectiveUnitPrice;
								return (
									<div className="space-y-3">
										<h3 className="font-medium text-gray-900">Your selling price</h3>
										<input
											type="number"
											placeholder={`Enter your selling price (≥${Math.ceil(effectiveUnitPrice)})`}
											value={singleSP}
											onChange={(e) => {
												const val = e.target.value;
												// Sync to ALL variant/size combinations (including fallback)
												setVariantSellingPrices((prev) => {
													const next = { ...prev };
													if (variants.length > 0) {
														for (const v of variants) {
															const sizes = v.sizes?.length > 0 ? v.sizes.map((s: any) => s.size_name) : fallbackSizes;
															const varSizes: Record<string, string> = {};
															for (const sz of sizes) {
																varSizes[sz] = val;
															}
															next[v.id] = varSizes;
														}
													} else {
														// No variants — use fallback variant ID 0
														const varSizes: Record<string, string> = {};
														for (const sz of fallbackSizes) {
															varSizes[sz] = val;
														}
														next[fallbackVarId] = varSizes;
													}
													return next;
												});
											}}
											className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none ${isTooLow ? 'border-red-400 bg-red-50' : 'border-gray-300'}`}
										/>
										{isTooLow && (
											<p className="text-red-500 text-sm mt-1">Price must be at least ৳{formatBDT(effectiveUnitPrice)}</p>
										)}
										{totalEarnings > 0 && totalQuantity > 0 && (
											<p className="text-green-600 text-sm mt-1">
												Your total earn {formatBDT(totalEarnings)} TK
											</p>
										)}
									</div>
								);
							}

							// Per-row mode: show total earnings banner
							const hasAnyPriceSet = allItems.some(i => i.sellingPrice && i.sellingPrice > 0);
							if (!hasAnyPriceSet || totalQuantity === 0) return null;
							return (
								<div className="bg-green-50 border border-green-200 rounded-xl p-3 flex items-center justify-between">
									<span className="text-sm font-medium text-green-800">Total Earnings</span>
									<span className="text-lg font-bold text-green-600 flex items-center">
										+৳{formatBDT(totalEarnings)}
									</span>
								</div>
							);
						})()}

						<div className="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-4 ">
							{!isResellerActive ? (
								<button
									onClick={() => (window.location.href = "/pricing")}
									className="flex-1 px-6 py-3 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition-colors font-bold cursor-pointer flex items-center justify-center gap-2 group"
								>
									<Lock className="w-5 h-5 group-hover:scale-110 transition-transform" />
									Active Profile Required to Order
								</button>
							) : (
								<>
									<button
										onClick={handleToggleShop}
										disabled={isShopLoading}
										className={`flex items-center justify-center gap-2 px-4 py-3 rounded-lg font-medium cursor-pointer transition-colors border ${isInShop
											? "border-pink-500 bg-pink-50 text-pink-600 hover:bg-pink-100"
											: "border-emerald-500 bg-emerald-50 text-emerald-700 hover:bg-emerald-100"
											}`}
									>
										<Store className="w-4 h-4" />
										{isShopLoading ? "..." : isInShop ? "Remove from Shop" : "Add to Shop"}
									</button>
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
								</>
							)}
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
