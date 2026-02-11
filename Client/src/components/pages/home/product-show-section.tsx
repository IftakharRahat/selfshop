/* eslint-disable @typescript-eslint/no-unused-vars */
/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { toast } from "sonner";
import ProductCard from "@/components/shared/ProductCard/ProductCard";
import { cn } from "@/lib/utils";
import { useAddToCartMutation } from "@/redux/features/cartApi";
import { useAppSelector } from "@/redux/hooks";
import { handleAsyncWithToast } from "@/utils/handleAsyncWithToast";
import "swiper/css";
import "swiper/css/navigation";
import "swiper/css/pagination";
import { Navigation, Pagination } from "swiper/modules";
import { Swiper, SwiperSlide } from "swiper/react";

export default function ProductShowSection({
	title,
	className,
	productData,
}: any) {
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

	const products = Array.isArray(productData)
		? productData
		: productData?.data || [];

	return (
		<div className={cn("w-full bg-white py-3 sm:py-6 lg:py-10", className)}>
			<div className="container mx-auto px-3 sm:px-6 lg:px-8">
				{/* Section Title */}
				<div className="flex items-center justify-between mb-3 sm:mb-6">
					<h2 className="text-sm sm:text-xl md:text-2xl lg:text-3xl font-semibold text-[#322F35]">
						{title}
					</h2>
				</div>

				{/* Swiper Slider */}
				<Swiper
					modules={[Navigation, Pagination]}
					navigation
					pagination={false}
					spaceBetween={12}
					breakpoints={{
						0: { slidesPerView: 2, spaceBetween: 8 },
						640: { slidesPerView: 2, spaceBetween: 12 },
						1024: { slidesPerView: 4, spaceBetween: 16 },
					}}
					loop={true}
					className="!overflow-x-clip !overflow-y-visible"
				>
					{products.map((product: any) => (
						<SwiperSlide key={product.id} className="!h-auto">
							<ProductCard product={product} />
						</SwiperSlide>
					))}
				</Swiper>
			</div>
		</div>
	);
}
