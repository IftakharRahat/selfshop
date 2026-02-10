/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { Heart, ShoppingCart } from "lucide-react";
import Image from "next/image";
import Link from "next/link";
import type React from "react";
import { useState } from "react";
import { toast } from "sonner";
import { getImageUrl } from "@/lib/utils";
import { useAddToCartMutation } from "@/redux/features/cartApi";
import { useAppSelector } from "@/redux/hooks";
import { handleAsyncWithToast } from "@/utils/handleAsyncWithToast";

interface ProductCardProps {
	product: any;
}

const ProductCard: React.FC<ProductCardProps> = ({ product }) => {
	const token = useAppSelector((state) => state.auth.access_token);
	const [addToCart] = useAddToCartMutation();
	const [imgError, setImgError] = useState(false);

	const handleAddToCart = async () => {
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

				<div className="flex items-center justify-between">
					<span className="text-sm sm:text-base font-bold text-gray-900">
						৳{product.ProductRegularPrice}
					</span>
					<button
						className="cursor-pointer w-7 h-7 sm:w-8 sm:h-8 bg-[#E5005F] hover:bg-[#c9004f] text-white rounded-full flex items-center justify-center transition-colors"
						onClick={handleAddToCart}
					>
						<ShoppingCart className="w-3.5 h-3.5 sm:w-4 sm:h-4" />
					</button>
				</div>
			</div>
		</div>
	);
};

export default ProductCard;
