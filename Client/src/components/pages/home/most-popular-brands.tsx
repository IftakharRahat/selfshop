/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import Image from "next/image";
import React, { useState } from "react";
import { Swiper, SwiperSlide } from "swiper/react";
import { getImageUrl } from "@/lib/utils";
import { useGetAllBrandsQuery } from "@/redux/features/home/homeApi";
import "swiper/css";
import "swiper/css/free-mode";
import { FreeMode } from "swiper/modules";

/** Renders a brand image; on error falls back to a text badge. */
function BrandImage({
	icon,
	name,
	width,
	height,
	className,
}: {
	icon: string;
	name: string;
	width: number;
	height: number;
	className?: string;
}) {
	const [errored, setErrored] = useState(false);

	if (errored) {
		return (
			<div
				style={{ width, height }}
				className="flex items-center justify-center bg-gray-100 rounded text-xs font-medium text-gray-500 text-center px-1"
			>
				{name}
			</div>
		);
	}

	return (
		<Image
			src={getImageUrl(icon)}
			alt={name || "Brand"}
			width={width}
			height={height}
			className={className}
			onError={() => setErrored(true)}
		/>
	);
}

const MostPopularBrands = () => {
	const { data } = useGetAllBrandsQuery(undefined);

	const brands =
		data?.data?.map((brand: any) => ({
			id: brand.id,
			name: brand.brand_name,
			icon: brand.brand_icon,
			slug: brand.slug,
		})) || [];

	return (
		<div className="container mx-auto py-3 sm:py-6 lg:py-10 px-3 sm:px-6 lg:px-8">
			{/* Section title */}
			<h2 className="text-sm sm:text-xl md:text-2xl lg:text-3xl font-semibold text-[#322F35] mb-3 sm:mb-5 text-center">
				Most Popular Brands
			</h2>

			{/* ---------- MOBILE SWIPER ---------- */}
			<div className="block md:hidden">
				<Swiper
					slidesPerView={3.5}
					spaceBetween={8}
					freeMode={true}
					modules={[FreeMode]}
				>
					{brands.map((brand: any) => (
						<SwiperSlide key={brand.id}>
							<div className="w-full aspect-square bg-white border border-gray-100 rounded-xl flex items-center justify-center p-3 cursor-pointer hover:shadow-md transition-shadow">
								<BrandImage
									icon={brand.icon}
									name={brand.name}
									width={72}
									height={72}
									className="object-contain max-w-full max-h-full"
								/>
							</div>
						</SwiperSlide>
					))}
				</Swiper>
			</div>

			{/* ---------- DESKTOP GRID ---------- */}
			<div className="hidden md:grid md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-8 gap-4">
				{brands.map((brand: any) => (
					<div
						key={brand.id}
						className="aspect-square bg-white border border-gray-100 rounded-xl flex items-center justify-center p-4 cursor-pointer hover:shadow-md transition-shadow"
					>
						<BrandImage
							icon={brand.icon}
							name={brand.name}
							width={80}
							height={80}
							className="object-contain max-w-full max-h-full"
						/>
					</div>
				))}
			</div>
		</div>
	);
};

export default MostPopularBrands;
