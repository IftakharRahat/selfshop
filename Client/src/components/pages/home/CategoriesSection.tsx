/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import Image from "next/image";
import { useState } from "react";
import { ImageOff } from "lucide-react";
import { Autoplay, Navigation, Pagination } from "swiper/modules";
import { Swiper, SwiperSlide } from "swiper/react";
import { getImageUrl } from "@/lib/utils";
import { useGetAllSlidersQuery } from "@/redux/features/home/homeApi";

import "swiper/css";
import "swiper/css/navigation";
import "swiper/css/pagination";
import CategoryCarousel from "./CategoryCarousel";

function BannerSlide({ slider, index }: { slider: any; index: number }) {
	const [imgError, setImgError] = useState(false);

	return (
		<div className="relative w-full h-[180px] sm:h-[250px] md:h-[350px] lg:h-[500px]">
			{imgError || !slider.image ? (
				<div className="w-full h-full rounded-lg bg-gradient-to-br from-pink-50 via-gray-100 to-pink-50 flex flex-col items-center justify-center gap-3">
					<div className="w-16 h-16 bg-white/80 rounded-full flex items-center justify-center shadow-sm">
						<ImageOff className="w-8 h-8 text-[#E5005F]/30" />
					</div>
					<p className="text-gray-400 text-sm font-medium">Image unavailable</p>
				</div>
			) : (
				<Image
					src={getImageUrl(slider.image)}
					alt={slider?.title || "Slide"}
					fill
					priority={index === 0}
					className="object-cover rounded-lg"
					onError={() => setImgError(true)}
				/>
			)}
		</div>
	);
}

export default function CategoriesSection() {
	const { data: sliderOptions } = useGetAllSlidersQuery(undefined);

	const sliders =
		sliderOptions?.data?.map((slider: any) => ({
			title: slider?.slider_title,
			image: slider?.slider_image,
			link: slider?.slider_btn_link,
		})) || [];

	return (
		<div className="bg-white">
			{/* Categories Section */}
			<div className="container mx-auto px-4 sm:px-6 lg:px-8 ">
				<CategoryCarousel />
			</div>

			{/* Promotional Banner Slider */}
			<div className="container mx-auto px-4 sm:px-6 lg:px-8 pb-2 sm:pb-4 lg:pb-6">
				<Swiper
					modules={[Autoplay, Pagination, Navigation]}
					spaceBetween={20}
					slidesPerView={1}
					loop={true}
					autoplay={{
						delay: 4000,
						disableOnInteraction: false,
					}}
					pagination={{ clickable: true }}
					navigation={false}
					className="rounded-lg overflow-hidden"
				>
					{sliders.map((slider: any, index: number) => (
						<SwiperSlide key={index}>
							<BannerSlide slider={slider} index={index} />
						</SwiperSlide>
					))}
				</Swiper>
			</div>
		</div>
	);
}
