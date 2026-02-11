/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";
import { Heart, ShoppingCart } from "lucide-react";
import Image from "next/image";
import Link from "next/link";
import { useState } from "react";
import { toast } from "sonner";
import ProductCard from "@/components/shared/ProductCard/ProductCard";
import { cn, getImageUrl } from "@/lib/utils";
import { useAddToCartMutation } from "@/redux/features/cartApi";
import { useAppSelector } from "@/redux/hooks";
import type { TProductSectionProps } from "@/types/product";
import { handleAsyncWithToast } from "@/utils/handleAsyncWithToast";
import "swiper/css";
import "swiper/css/navigation";
import { Navigation } from "swiper/modules";
import { Swiper, SwiperSlide } from "swiper/react";

/** Compact row card used for regular products */
function RowProductCard({ product, onAddToCart }: { product: any; onAddToCart: (p: any) => void }) {
	const [imgError, setImgError] = useState(false);

	return (
		<div className="bg-white rounded-xl border border-gray-100 overflow-hidden group cursor-pointer hover:shadow-md transition-shadow">
			<div className="flex items-center">
				<div className="relative w-20 h-20 sm:w-24 sm:h-24 flex-shrink-0 overflow-hidden">
					<Link href={`/product/${product?.ProductSlug}`}>
						<Image
							src={
								imgError || !product?.ViewProductImage
									? "/placeholder.svg"
									: getImageUrl(product.ViewProductImage)
							}
							alt={product?.ProductName || "Product"}
							width={96}
							height={96}
							className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
							onError={() => setImgError(true)}
						/>
					</Link>
					<button className="absolute top-1.5 right-1.5 w-5 h-5 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-sm hover:bg-white transition-all">
						<Heart className="w-2.5 h-2.5 text-gray-500 hover:text-[#E5005F]" />
					</button>
				</div>

				<div className="flex-1 p-2.5 sm:p-3 min-w-0">
					<Link href={`/product/${product?.ProductSlug}`}>
						<h3 className="text-gray-800 font-medium text-xs sm:text-sm line-clamp-1 mb-1.5">
							{product?.ProductName}
						</h3>
					</Link>
					<div className="flex items-center justify-between">
						<div className="flex items-center gap-1.5">
							<span className="text-sm sm:text-base font-bold text-gray-900">
								৳{product?.ProductSalePrice}
							</span>
							{product?.ProductRegularPrice && product.ProductRegularPrice !== product.ProductSalePrice && (
								<span className="text-[10px] sm:text-xs text-gray-400 line-through">
									৳{product?.ProductRegularPrice}
								</span>
							)}
						</div>
						<button
							onClick={() => onAddToCart(product)}
							className="cursor-pointer w-7 h-7 bg-[#E5005F] hover:bg-[#c9004f] text-white rounded-full flex items-center justify-center transition-colors"
						>
							<ShoppingCart className="w-3 h-3 sm:w-3.5 sm:h-3.5" />
						</button>
					</div>
				</div>
			</div>
		</div>
	);
}

/** Large featured card for desktop view */
function FeaturedCard({ product, onAddToCart }: { product: any; onAddToCart: (p: any) => void }) {
	const [imgError, setImgError] = useState(false);

	return (
		<div className="bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
			<div className="flex flex-row">
				{/* Product Image */}
				<div className="flex-shrink-0 w-52 lg:w-64 overflow-hidden">
					<Link href={`/product/${product?.ProductSlug}`}>
						<Image
							src={
								imgError || !product?.ViewProductImage
									? "/placeholder.svg"
									: getImageUrl(product.ViewProductImage)
							}
							alt={product?.ProductName || "Product"}
							width={256}
							height={256}
							className="w-full h-full object-cover hover:scale-105 transition-transform duration-300"
							onError={() => setImgError(true)}
						/>
					</Link>
				</div>

				{/* Product Info */}
				<div className="flex-1 p-5 lg:p-6 flex flex-col justify-center">
					<Link href={`/product/${product?.ProductSlug}`}>
						<h3 className="text-base lg:text-lg font-medium text-gray-900 mb-3 line-clamp-2 hover:text-[#E5005F] transition-colors">
							{product?.ProductName}
						</h3>
					</Link>

					{/* Price */}
					<div className="flex items-center gap-2.5 mb-4">
						<span className="text-xl lg:text-2xl font-bold text-gray-900">
							৳{product?.ProductSalePrice}
						</span>
						{product?.ProductRegularPrice && product.ProductRegularPrice !== product.ProductSalePrice && (
							<span className="text-sm lg:text-base text-gray-400 line-through">
								৳{product?.ProductRegularPrice}
							</span>
						)}
					</div>

					{/* Add to Cart Button */}
					<button
						onClick={() => onAddToCart(product)}
						className="cursor-pointer bg-[#E5005F] hover:bg-[#c9004f] text-white px-5 py-2.5 rounded-lg flex items-center justify-center gap-2 transition-colors font-medium text-sm w-fit"
					>
						<ShoppingCart className="w-4 h-4" />
						<span>Add to cart</span>
					</button>
				</div>
			</div>
		</div>
	);
}

export default function ProductSection({
	title,
	featuredProducts = [],
	regularProducts = [],
	className = "",
}: TProductSectionProps) {
	const token = useAppSelector((state) => state.auth.access_token);
	const [addToCart] = useAddToCartMutation();

	const handleAddToCart = async (product: any) => {
		if (!token) {
			toast.info("Please log in to add to cart");
			return;
		}
		const formData = new FormData();
		formData.append("product_id", product.id);
		formData.append("price", product.ProductRegularPrice.toString());
		formData.append("qty", "1");
		formData.append("size", product.sizes?.[0] || "");

		await handleAsyncWithToast(async () => {
			return addToCart(formData);
		});
	};

	const allProducts = [...featuredProducts, ...regularProducts];

	return (
		<div className={cn("w-full bg-white py-3 sm:py-6 lg:py-10", className)}>
			<div className="container mx-auto px-3 sm:px-6 lg:px-8">
				{/* Section Title */}
				<div className="flex items-center justify-between mb-3 sm:mb-6">
					<h2 className="text-sm sm:text-xl md:text-2xl lg:text-3xl font-semibold text-[#322F35]">
						{title}
					</h2>
				</div>

				{/* ========== MOBILE VIEW ========== */}
				<div className="lg:hidden">
					{/* Featured as Swiper */}
					{featuredProducts.length > 0 && (
						<Swiper
							modules={[Navigation]}
							navigation
							spaceBetween={8}
							slidesPerView={2}
							loop={true}
							className="!overflow-x-clip !overflow-y-visible"
						>
							{featuredProducts.map((product: any, i: number) => (
								<SwiperSlide key={product?.id || i} className="!h-auto">
									<ProductCard product={product} />
								</SwiperSlide>
							))}
						</Swiper>
					)}

					{/* Regular as Row Cards */}
					{regularProducts.length > 0 && (
						<div className="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-3">
							{regularProducts.map((product: any, i: number) => (
								<RowProductCard
									key={product?.id || i}
									product={product}
									onAddToCart={handleAddToCart}
								/>
							))}
						</div>
					)}
				</div>

				{/* ========== DESKTOP VIEW ========== */}
				<div className="hidden lg:block">
					{/* Featured Products — Large Cards */}
					{featuredProducts.length > 0 && (
						<div className="grid grid-cols-2 gap-5 mb-6">
							{featuredProducts.map((product: any, i: number) => (
								<FeaturedCard
									key={product?.id || i}
									product={product}
									onAddToCart={handleAddToCart}
								/>
							))}
						</div>
					)}

					{/* Regular Products — Row Cards */}
					{regularProducts.length > 0 && (
						<div className="grid grid-cols-3 gap-4">
							{regularProducts.map((product: any, i: number) => (
								<RowProductCard
									key={product?.id || i}
									product={product}
									onAddToCart={handleAddToCart}
								/>
							))}
						</div>
					)}
				</div>

				{/* Empty State */}
				{featuredProducts.length === 0 && regularProducts.length === 0 && (
					<div className="flex flex-col items-center justify-center py-12 px-4">
						<div className="w-16 h-16 bg-pink-50 rounded-full flex items-center justify-center mb-3">
							<ShoppingCart className="w-8 h-8 text-[#E5005F]/40" />
						</div>
						<p className="text-gray-800 text-base font-semibold mb-1">No products yet</p>
						<p className="text-gray-400 text-sm">Products will appear here once they&apos;re added</p>
					</div>
				)}
			</div>
		</div>
	);
}
