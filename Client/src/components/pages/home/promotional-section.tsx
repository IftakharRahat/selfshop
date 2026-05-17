/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";
import Image from "next/image";
import Link from "next/link";
import { getImageUrl } from "@/lib/utils";
import { useGetPromotionalSectionsQuery } from "@/redux/features/home/homeApi";
import ProductCard from "@/components/shared/ProductCard/ProductCard";
import "swiper/css";
import "swiper/css/navigation";
import "swiper/css/pagination";
import { Navigation, Pagination } from "swiper/modules";
import { Swiper, SwiperSlide } from "swiper/react";

/* ─── Card Layout: Banner + 2-product grid ─── */
function CardSection({ section }: { section: any }) {
	return (
		<div className="bg-white rounded-lg border border-pink-100 overflow-hidden flex flex-col">
			{/* Banner */}
			<div className="relative h-32 sm:h-44 lg:h-52 overflow-hidden">
				{section.banner_image ? (
					<Image
						src={section.banner_image}
						alt={section.title}
						fill
						className="object-cover"
					/>
				) : (
					<div className="w-full h-full bg-gradient-to-br from-pink-100 to-pink-50 flex items-center justify-center">
						<span className="text-pink-300 text-lg font-bold">
							{section.title}
						</span>
					</div>
				)}
			</div>

			{/* Title + Explore */}
			<div className="p-2.5 sm:p-4 flex items-center justify-between">
				<h3 className="text-base sm:text-lg lg:text-xl font-bold text-[#E5005F]">
					{section.title.toUpperCase()}
				</h3>
				<Link href={`/section/${section.slug}`} scroll>
					<button className="cursor-pointer bg-[#E5005F] px-3 sm:px-5 py-1.5 sm:py-2 rounded-xl text-xs sm:text-sm font-medium hover:bg-pink-700 transition-colors" style={{ color: 'white' }}>
						Explore
					</button>
				</Link>
			</div>

			{/* Products Grid (2 items) */}
			<div className="px-2.5 pb-2.5 sm:px-4 sm:pb-4 flex-1">
				<div className="grid grid-cols-2 gap-4">
					{section.products?.slice(0, 2).map((product: any) => (
						<div key={product.id} className="text-center">
							<Link href={`/product/${product?.ProductSlug}`}>
								<div className="rounded-lg mb-1.5 sm:mb-2 cursor-pointer aspect-square flex items-center justify-center overflow-hidden">
									<Image
										src={getImageUrl(product?.ViewProductImage)}
										alt={
											product?.ProductName ||
											product?.name ||
											"Product"
										}
										width={200}
										height={200}
										className="w-full h-full object-cover rounded"
									/>
								</div>
								<p className="text-xs sm:text-sm text-gray-700 font-medium line-clamp-2">
									{product?.ProductName}
								</p>
							</Link>
						</div>
					))}
				</div>
			</div>
		</div>
	);
}

/* ─── Slider Layout: Full-width Swiper carousel ─── */
function SliderSection({ section }: { section: any }) {
	const products = section.products || [];
	const bgColor = section.bg_color || "#ffffff";

	return (
		<div className="w-full py-3 sm:py-6 lg:py-10" style={{ backgroundColor: bgColor }}>
			<div className="container mx-auto px-3 sm:px-6 lg:px-8">
				{/* Title */}
				<div className="flex items-center justify-between mb-3 sm:mb-6">
					<h2 className="text-sm sm:text-xl md:text-2xl lg:text-3xl font-semibold text-[#322F35]">
						{section.title.toUpperCase()}
					</h2>
				</div>

				{/* Swiper */}
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
					loop={products.length > 4}
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

/* ─── Main Component: renders all sections in order ─── */
export default function PromotionalSection() {
	const { data } = useGetPromotionalSectionsQuery({});
	const sections = data?.data || [];

	if (sections.length === 0) return null;

	// Group card sections together, render slider sections independently
	const cardSections = sections.filter((s: any) => s.layout_type === "card" || !s.layout_type);
	const sliderSections = sections.filter((s: any) => s.layout_type === "slider");

	return (
		<>
			{/* Card sections in a grid */}
			{cardSections.length > 0 && (
				<div className="w-full bg-gray-50 py-3 sm:py-6 lg:py-10">
					<div className="container mx-auto px-3 sm:px-6 lg:px-8">
						<div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">
							{cardSections.map((section: any) => (
								<CardSection key={section.id} section={section} />
							))}
						</div>
					</div>
				</div>
			)}

			{/* Slider sections rendered independently */}
			{sliderSections.map((section: any) => (
				<SliderSection key={section.id} section={section} />
			))}
		</>
	);
}
