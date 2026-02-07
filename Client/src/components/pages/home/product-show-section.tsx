/* eslint-disable @typescript-eslint/no-unused-vars */
/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import ProductCard from "@/components/shared/ProductCard/ProductCard";
import { cn } from "@/lib/utils";
import { useAddToCartMutation } from "@/redux/features/cartApi";
import { handleAsyncWithToast } from "@/utils/handleAsyncWithToast";
import "swiper/css";
import "swiper/css/navigation";
import "swiper/css/pagination";
import { Navigation, Pagination } from "swiper/modules";
import { Swiper, SwiperSlide } from "swiper/react";

export default function ProductShowSection({ title, className, productData }: any) {
  const [addToCart] = useAddToCartMutation();
  const handleAddToCart = async (product: any) => {
    const formData = new FormData();
    formData.append("product_id", product.id);
    formData.append("price", product.ProductRegularPrice.toString());
    formData.append("qty", "1");
    formData.append("size", product.sizes?.[0] || ""); // fallback to empty if no size

    console.log("Added to cart:", {
      product_id: product.id,
      price: product.ProductRegularPrice,
      qty: 1,
      size: product.sizes?.[0] || "",
    });

    await handleAsyncWithToast(async () => {
      return addToCart(formData);
    });
  };

  const products = Array.isArray(productData) ? productData : productData?.data || [];
  const renderStars = (rating: number = 0) =>
    Array.from({ length: 5 }, (_, i) => (
      <span key={i} className={i < rating ? "text-yellow-400" : "text-gray-300"}>
        ★
      </span>
    ));

  return (
    <div className={cn("w-full bg-white py-8 lg:py-12", className)}>
      <div className="container mx-auto px-4 sm:px-6 lg:px-8">
        {/* Section Title */}
        <div className="flex items-center justify-between">
          <h2 className="text-md md:text-2xl lg:text-3xl font-semibold text-[#322F35] mb-4 md:mb-8">{title}</h2>
          {/* <button className="hidden sm:block px-4 py-2 bg-[#E5005F] text-xs text-white rounded-md">
            View All
          </button> */}
        </div>

        {/* Swiper Slider */}
        <Swiper
          modules={[Navigation, Pagination]}
          navigation
          pagination={false}
          spaceBetween={20}
          breakpoints={{
            0: { slidesPerView: 2, spaceBetween: 10 },
            1024: { slidesPerView: 4 },
          }}
          loop={true}
        >
          {products.map((product: any) => (
            <SwiperSlide key={product.id}>
              <ProductCard product={product} />
            </SwiperSlide>
          ))}
        </Swiper>

        <div className="flex items-center justify-center mt-4">
          {/* <button className="px-4 py-2 bg-[#E5005F] text-xs text-white rounded-md">View All</button> */}
        </div>
      </div>
    </div>
  );
}
