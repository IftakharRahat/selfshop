"use client";

import { Loader2 } from "lucide-react";
import { useSearchParams } from "next/navigation";
import ProductCard from "@/components/shared/ProductCard/ProductCard";
import { useGetSearchResultsQuery } from "@/redux/features/searchApi";

interface Product {
	id: string;
	ProductSlug: string;
	ProductName: string;
	ViewProductImage: string;
	ProductRegularPrice: number;
	rating?: number;
}

export default function SearchPage() {
	const searchParams = useSearchParams();
	const search = searchParams.get("keywords") || "";

	const { data, isLoading, isError } = useGetSearchResultsQuery(search, {
		skip: !search, // avoids call when empty
	});

	const products: Product[] = data?.data || [];

	return (
		<section className="container px-4 md:px-8 lg:px-16 py-10">
			<h1 className="text-2xl md:text-3xl font-bold text-gray-900 mb-6">
				{search ? `Search results for “${search}”` : "Search Products"}
			</h1>

			{isLoading ? (
				<div className="flex justify-center py-10">
					<Loader2 className="w-8 h-8 text-pink-600 animate-spin" />
				</div>
			) : isError ? (
				<p className="text-red-500 text-center">
					Failed to load search results.
				</p>
			) : products.length === 0 ? (
				<p className="text-gray-500 text-center">No products found.</p>
			) : (
				<div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-5">
					{products.map((product, index) => (
						<ProductCard key={index} product={product} />
					))}
				</div>
			)}
		</section>
	);
}
