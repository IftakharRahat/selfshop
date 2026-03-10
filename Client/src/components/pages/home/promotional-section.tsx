/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";
import Image from "next/image";
import Link from "next/link";
import { getImageUrl } from "@/lib/utils";
import { useGetPromotionalSectionsQuery } from "@/redux/features/home/homeApi";

interface CategoryCardProps {
	title: string;
	banner: string | null;
	slug: string;
	products: any[];
}

function CategoryCard({ title, banner, slug, products }: CategoryCardProps) {
	return (
		<div className="bg-white rounded-lg border border-pink-100 overflow-hidden flex flex-col">
			{/* Banner Section */}
			<div className="relative h-32 sm:h-44 lg:h-52 overflow-hidden">
				{banner ? (
					<Image
						src={banner}
						alt={title}
						fill
						className="object-cover"
					/>
				) : (
					<div className="w-full h-full bg-gradient-to-br from-pink-100 to-pink-50 flex items-center justify-center">
						<span className="text-pink-300 text-lg font-bold">{title}</span>
					</div>
				)}
			</div>

			{/* Title and Explore Button */}
			<div className="p-2.5 sm:p-4 flex items-center justify-between">
				<h3 className="text-base sm:text-lg lg:text-xl font-bold text-[#E5005F]">
					{title.toUpperCase()}
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
					{products?.slice(0, 2).map((product: any) => (
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
	const { data } = useGetPromotionalSectionsQuery({});

	const sections = data?.data || [];

	if (sections.length === 0) return null;

	return (
		<div className="w-full bg-gray-50 py-3 sm:py-6 lg:py-10">
			<div className="container mx-auto px-3 sm:px-6 lg:px-8">
				<div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">
					{sections.map((section: any) => (
						<CategoryCard
							key={section.id}
							title={section.title}
							banner={section.banner_image}
							slug={section.slug}
							products={section.products}
						/>
					))}
				</div>
			</div>
		</div>
	);
}
