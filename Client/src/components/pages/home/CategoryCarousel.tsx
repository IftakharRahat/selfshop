/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";
import React, { useState, useRef } from "react";
import { Navigation } from "swiper/modules";
import { Swiper, SwiperSlide } from "swiper/react";
import type { Swiper as SwiperType } from "swiper";

import "swiper/css";
import "swiper/css/navigation";

import Image from "next/image";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { ChevronLeft, ChevronRight } from "lucide-react";
import { getImageUrl } from "@/lib/utils";
import { useGetAllMenusQuery } from "@/redux/features/home/homeApi";

export default function CategoryCarousel() {
	const router = useRouter();
	const { data: menuOptions } = useGetAllMenusQuery(undefined);
	const swiperRef = useRef<SwiperType | null>(null);

	const [showMore] = useState(false);

	const categories =
		menuOptions?.data?.map((menu: any) => ({
			name: menu?.category_name,
			icon: menu?.category_icon,
			slug: menu?.slug,
		})) || [];

	const mobileCategories = showMore ? categories : categories.slice(0, 8);

	return (
		<div>
			{/* MOBILE VIEW */}
			<div className="lg:hidden mb-4">
				<div className="flex items-center justify-between my-2">
					<h4 className="font-semibold text-sm">Popular Categories</h4>
					{categories.length > 8 && (
						<div className="text-end">
							<Link href="/categories" passHref>
								<button className="cursor-pointer px-2.5 py-[2px] text-[11px] font-medium text-[#E5005F] border border-[#E5005F] rounded-full hover:bg-pink-50 transition-colors">
									See All
								</button>
							</Link>
						</div>
					)}
				</div>

				<div className="grid grid-cols-4 sm:grid-cols-4 md:grid-cols-6 gap-2 sm:gap-4">
					{mobileCategories.map((category: any, index: number) => (
						<div
							key={index}
							className="flex flex-col items-center text-center group cursor-pointer"
							onClick={() =>
								router.push(`/product-filter?category=${category?.slug}`)
							}
						>
							<div className="w-full aspect-square bg-white rounded-xl shadow-sm flex flex-col items-center justify-center gap-1 group-hover:shadow-md transition-all overflow-hidden p-2">
								<div className="w-8 h-8 sm:w-10 sm:h-10 flex-shrink-0">
									<Image
										src={getImageUrl(category?.icon)}
										alt={category?.name || "Category"}
										width={40}
										height={40}
										className="w-full h-full object-contain"
									/>
								</div>
								<span className="text-[9px] sm:text-[11px] text-gray-700 font-medium group-hover:text-[#E5005F] transition-colors line-clamp-2 leading-tight">
									{category.name}
								</span>
							</div>
						</div>
					))}
				</div>
			</div>

			{/* DESKTOP VIEW */}
			<div className="hidden lg:block w-full mx-auto py-6">
				<div className="bg-white rounded-xl shadow-sm py-6 px-8 relative">
					{/* Header */}
					<div className="flex items-center justify-between mb-6">
						<h2 className="text-xl font-semibold text-gray-800">
							Popular Categories
						</h2>
						{/* Custom Navigation Buttons */}
						<div className="flex items-center gap-2">
							<button
								onClick={() => swiperRef.current?.slidePrev()}
								className="cursor-pointer w-9 h-9 bg-white border border-gray-200 rounded-full flex items-center justify-center shadow-sm hover:bg-[#E5005F] hover:border-[#E5005F] hover:text-white text-gray-600 transition-all duration-200"
								aria-label="Previous slide"
							>
								<ChevronLeft className="w-5 h-5" />
							</button>
							<button
								onClick={() => swiperRef.current?.slideNext()}
								className="cursor-pointer w-9 h-9 bg-white border border-gray-200 rounded-full flex items-center justify-center shadow-sm hover:bg-[#E5005F] hover:border-[#E5005F] hover:text-white text-gray-600 transition-all duration-200"
								aria-label="Next slide"
							>
								<ChevronRight className="w-5 h-5" />
							</button>
						</div>
					</div>

					{/* Swiper */}
					<Swiper
						onSwiper={(swiper) => (swiperRef.current = swiper)}
						spaceBetween={16}
						slidesPerView={8}
						loop={true}
						modules={[Navigation]}
						breakpoints={{
							1024: { slidesPerView: 6 },
							1280: { slidesPerView: 8 },
							1536: { slidesPerView: 9 },
						}}
					>
						{categories.map((cat: any, i: number) => (
							<SwiperSlide key={cat.slug || cat.name + i}>
								<Link
									href={`/product-filter?category=${cat.slug || "category"}`}
									className="flex flex-col items-center gap-3 py-2 group"
								>
									<div className="w-20 h-20 lg:w-24 lg:h-24 bg-gray-50 border-2 border-gray-100 rounded-full flex items-center justify-center overflow-hidden p-3 group-hover:border-[#E5005F]/30 group-hover:shadow-md transition-all duration-200">
										<Image
											src={getImageUrl(cat.icon)}
											alt={cat?.name || "Category"}
											width={80}
											height={80}
											className="w-full h-full object-contain"
										/>
									</div>

									<span className="text-sm text-center text-gray-700 font-medium group-hover:text-[#E5005F] transition-colors line-clamp-2 max-w-[100px]">
										{cat.name}
									</span>
								</Link>
							</SwiperSlide>
						))}
					</Swiper>
				</div>
			</div>
		</div>
	);
}
