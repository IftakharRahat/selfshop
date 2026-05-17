/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { Heart, Lock, ShoppingCart } from "lucide-react";
import Image from "next/image";
import Link from "next/link";
import type React from "react";
import { useState } from "react";
import { toast } from "sonner";
import { getImageUrl } from "@/lib/utils";
import { useAddToCartMutation } from "@/redux/features/cartApi";
import { useAppSelector } from "@/redux/hooks";
import { handleAsyncWithToast } from "@/utils/handleAsyncWithToast";
import { useIsActiveReseller } from "@/hooks/useIsActiveReseller";
import { formatBDT } from "@/lib/format-currency";

interface ProductCardProps {
	product: any;
}

const ProductCard: React.FC<ProductCardProps> = ({ product }) => {
	const token = useAppSelector((state) => state.auth.access_token);
	const { isActive: isResellerActive } = useIsActiveReseller();
	const [addToCart] = useAddToCartMutation();
	const [imgError, setImgError] = useState(false);

	if (!product) return null;

	const handleAddToCart = async () => {
		if (!token) {
			toast.info("Please log in to add to cart");
			return;
		}
		const formData = new FormData();
		formData.append("product_id", product.id);
		const sellingPrice = product.storefront_price || product.ProductResellerPrice || product.ProductSalePrice || product.ProductRegularPrice;
		formData.append("price", sellingPrice.toString());
		formData.append("qty", "1");
		formData.append("size", product.sizes?.[0] || "");

		await handleAsyncWithToast(async () => {
			return addToCart(formData);
		});
	};

	return (
		<div className="group cursor-pointer bg-white border border-gray-100 rounded-xl hover:shadow-md transition-shadow duration-200 h-full flex flex-col overflow-hidden">
			{/* Image */}
			<div className="relative overflow-hidden aspect-square">
				<Link href={`/product/${product?.ProductSlug}`}>
					<Image
						src={
							imgError || !product.ViewProductImage
								? "/placeholder.svg"
								: getImageUrl(product.ViewProductImage)
						}
						alt={product?.ProductName || "Product"}
						width={600}
						height={400}
						className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
						onError={() => setImgError(true)}
					/>
				</Link>
				<button className="absolute top-2 right-2 w-7 h-7 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-sm hover:bg-white hover:scale-110 transition-all">
					<Heart className="w-3.5 h-3.5 text-gray-500 hover:text-[#E5005F]" />
				</button>
			</div>

			{/* Info */}
			<div className="p-2.5 sm:p-3 flex flex-col gap-1.5 mt-auto">
				<Link href={`/product/${product?.ProductSlug}`}>
					<h3 className="text-gray-800 font-medium text-xs sm:text-sm leading-snug line-clamp-2">
						{product.ProductName}
					</h3>
				</Link>

				{isResellerActive ? (
					<div className="flex items-center justify-between w-full">
						<div className="flex flex-col">
							{product.ProductSalePrice > (product.storefront_price || product.ProductResellerPrice || product.ProductSalePrice || product.ProductRegularPrice) && product.selling_type !== 'dropshipping' && (
								<span className="text-gray-400 line-through text-xs digit-font">
									৳{formatBDT(product.ProductSalePrice)}
								</span>
							)}
							<span className="text-gray-900 font-bold text-sm digit-font">
								৳{formatBDT(product.storefront_price || product.ProductResellerPrice || product.ProductSalePrice || product.ProductRegularPrice)}
							</span>
						</div>
						<button
							className="cursor-pointer w-7 h-7 sm:w-8 sm:h-8 bg-[#E5005F] hover:bg-[#c9004f] rounded-full flex items-center justify-center transition-colors shrink-0"
						style={{ color: 'white' }}
							onClick={handleAddToCart}
						>
							<ShoppingCart className="w-3.5 h-3.5 sm:w-4 sm:h-4" />
						</button>
					</div>
				) : (
					<div className="flex items-center justify-between w-full gap-1">
						<span className="text-gray-400 text-xs font-bold">***</span>
						<span className="text-[10px] text-pink-600 font-bold bg-pink-50 px-1.5 py-0.5 rounded flex items-center gap-0.5 whitespace-nowrap">
							<Lock className="w-2.5 h-2.5 shrink-0" />
							<span className="hidden sm:inline">Active profile required</span>
							<span className="sm:hidden">Login required</span>
						</span>
					</div>
				)}
			</div>
		</div>
	);
};

export default ProductCard;
