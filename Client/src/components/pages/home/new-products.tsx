/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { useState } from "react";
import { ShoppingCart } from "lucide-react";
import { useGetAllNewProductsQuery } from "@/redux/features/home/homeApi";
import ProductCard from "@/components/shared/ProductCard/ProductCard";

const INITIAL_VISIBLE = 8; // 4 per row × 2 rows

export default function NewProducts() {
	const [objectQuery] = useState([
		{ name: "page", value: 1 },
		{ name: "limit", value: 40 },
	]);
	const { data: newArrivalsData } = useGetAllNewProductsQuery({ objectQuery });
	const allProducts: any[] = newArrivalsData?.data?.data || [];
	const [showAll, setShowAll] = useState(false);

	const displayedProducts = showAll ? allProducts : allProducts.slice(0, INITIAL_VISIBLE);

	return (
		<div className="w-full bg-white py-3 sm:py-6 lg:py-10">
			<div className="container mx-auto px-3 sm:px-6 lg:px-8">
				{/* Header */}
				<div className="flex items-center justify-between mb-3 sm:mb-6">
					<h2 className="text-sm sm:text-xl md:text-2xl lg:text-3xl font-semibold text-[#322F35]">
						NEW PRODUCTS
					</h2>
					{allProducts.length > INITIAL_VISIBLE && (
						<button
							className="new-products-viewall"
							onClick={() => setShowAll((v) => !v)}
						>
							{showAll ? "Show Less" : "View All"}
						</button>
					)}
				</div>

				{/* Product Grid: 2 cols on mobile, 4 cols on md+ */}
				{displayedProducts.length > 0 ? (
					<div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 sm:gap-4">
						{displayedProducts.map((product: any, i: number) => (
							<ProductCard key={product?.id || i} product={product} />
						))}
					</div>
				) : (
					<div className="flex flex-col items-center justify-center py-12 px-4">
						<div className="w-16 h-16 bg-pink-50 rounded-full flex items-center justify-center mb-3">
							<ShoppingCart className="w-8 h-8 text-[#E5005F]/40" />
						</div>
						<p className="text-gray-800 text-base font-semibold mb-1">No products yet</p>
						<p className="text-gray-400 text-sm">Products will appear here once they&apos;re added</p>
					</div>
				)}
			</div>
		</div>
	);
}
