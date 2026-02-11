/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";
import Image, { StaticImageData } from "next/image";
import Link from "next/link";
import { useState } from "react";
import banner3 from "@/assets/images/newImages/2.png";
import banner1 from "@/assets/images/newImages/3.png";
import banner6 from "@/assets/images/newImages/4.png";
import banner5 from "@/assets/images/newImages/Profit.png";
import banner2 from "@/assets/images/newImages/Ready for boost.png";
import banner4 from "@/assets/images/newImages/subel112.png";
import { getImageUrl } from "@/lib/utils";
import { useGetAllCollectionsQuery } from "@/redux/features/home/homeApi";

interface CategoryCardProps {
	title: string;
	banner: StaticImageData;
	slug: string;
	products: any[];
}

function CategoryCard({ title, banner, slug, products }: CategoryCardProps) {
	return (
		<div className="bg-white rounded-lg border border-pink-100 overflow-hidden flex flex-col">
			{/* Banner Section */}
			<div className="relative h-32 sm:h-44 lg:h-52 overflow-hidden">
				<Image
					src={banner || "/placeholder.svg"}
					alt={title}
					fill
					className="object-cover"
				/>
			</div>

			{/* Title and Explore Button */}
			<div className="p-2.5 sm:p-4 flex items-center justify-between">
				<h3 className="text-base sm:text-lg lg:text-xl font-bold text-[#E5005F]">
					{title}
				</h3>
				<Link href={`/category?category=${slug}`} scroll>
					<button className="cursor-pointer bg-[#E5005F] text-white px-3 sm:px-5 py-1.5 sm:py-2 rounded-xl text-xs sm:text-sm font-medium hover:bg-pink-700 transition-colors">
						Explore
					</button>
				</Link>
			</div>

			{/* Products Grid */}
			<div className="px-2.5 pb-2.5 sm:px-4 sm:pb-4 flex-1">
				<div className="grid grid-cols-2 gap-4">
					{products?.map((product: any) => (
						<div key={product.id} className="text-center">
							<Link href={`/product/${product?.ProductSlug}`}>
								<div className="rounded-lg mb-1.5 sm:mb-2 cursor-pointer aspect-square flex items-center justify-center overflow-hidden">
									<Image
										src={getImageUrl(product?.ViewProductImage)}
										alt={
											product?.ProductName || product?.name || "Product"
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

export default function PromotionalSection() {
	const [objectQuery] = useState([
		{ name: "page", value: 1 },
		{ name: "limit", value: 2 },
	]);

	const { data } = useGetAllCollectionsQuery({
		objectQuery,
		slug: "hot_selling",
	});
	const { data: readyToBoostData } = useGetAllCollectionsQuery({
		objectQuery,
		slug: "ready_to_bost",
	});
	const { data: limitedOffersData } = useGetAllCollectionsQuery({
		objectQuery,
		slug: "limited_offer",
	});
	const { data: newArrivalsData } = useGetAllCollectionsQuery({
		objectQuery,
		slug: "new_arrivel",
	});
	const { data: profitableProductData } = useGetAllCollectionsQuery({
		objectQuery,
		slug: "profitable_product",
	});
	const { data: summerCollectionData } = useGetAllCollectionsQuery({
		objectQuery,
		slug: "summer_collection",
	});

	const categories = [
		{ title: "HOT SELLING", banner: banner1, slug: "hot_selling", products: data?.data?.data },
		{ title: "READY TO BOOST", banner: banner2, slug: "ready_to_bost", products: readyToBoostData?.data?.data },
		{ title: "LIMITED OFFERS", banner: banner3, slug: "limited_offer", products: limitedOffersData?.data?.data },
		{ title: "NEW ARRIVALS", banner: banner4, slug: "new_arrivel", products: newArrivalsData?.data?.data },
		{ title: "BEST SELLERS", banner: banner5, slug: "profitable_product", products: profitableProductData?.data?.data },
		{ title: "TRENDING NOW", banner: banner6, slug: "summer_collection", products: summerCollectionData?.data?.data },
	];

	return (
		<div className="w-full bg-gray-50 py-3 sm:py-6 lg:py-10">
			<div className="container mx-auto px-3 sm:px-6 lg:px-8">
				<div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">
					{categories.map((cat) => (
						<CategoryCard
							key={cat.slug}
							title={cat.title}
							banner={cat.banner}
							slug={cat.slug}
							products={cat.products}
						/>
					))}
				</div>
			</div>
		</div>
	);
}
