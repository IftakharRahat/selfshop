"use client"; // Important for using hooks
import { useSearchParams } from "next/navigation";
import { useState } from "react";
import { ArrowUpDown, Star } from "lucide-react";
import ProductCard from "@/components/shared/ProductCard/ProductCard";
import {
	useGetCategoryProductsQuery,
	useGetSubcategoryProductsQuery,
} from "@/redux/features/home/homeApi";

interface Product {
	ProductSlug: string;
	ProductName: string;
	ViewProductImage: string;
	ProductRegularPrice: number;
	rating?: number;
	avg_rating?: number;
	review_count?: number;
}

const SORT_OPTIONS = [
	{ value: "rating", label: "Top Rated" },
	{ value: "newest", label: "Newest First" },
	{ value: "oldest", label: "Oldest First" },
	{ value: "price_asc", label: "Price: Low to High" },
	{ value: "price_desc", label: "Price: High to Low" },
] as const;

const ProductFilterPage = () => {
	const searchParams = useSearchParams();
	const category = searchParams?.get("category") ?? "";
	const subcategory = searchParams?.get("subcategory") ?? "";
	const [sort, setSort] = useState("rating");

	// Use category endpoint when ?category= is set and ?subcategory= is not; otherwise use subcategory (or all products when both empty)
	const useCategory = Boolean(category && !subcategory);
	const subcategorySlug = subcategory || (category ? undefined : "");

	const {
		data: categoryData,
		isLoading: categoryLoading,
		isError: categoryError,
	} = useGetCategoryProductsQuery(
		{ slug: category, sort },
		{ skip: !useCategory },
	);
	const {
		data: subcategoryData,
		isLoading: subcategoryLoading,
		isError: subcategoryError,
	} = useGetSubcategoryProductsQuery(
		{ slug: subcategory || "", sort },
		{ skip: useCategory },
	);

	const products = useCategory ? categoryData : subcategoryData;
	const isLoading = useCategory ? categoryLoading : subcategoryLoading;
	const isError = useCategory ? categoryError : subcategoryError;

	const title = subcategory
		? subcategory.replace(/-/g, " ")
		: category
			? category.replace(/-/g, " ")
			: "All Products";

	const productList: Product[] = products?.data ?? [];

	return (
		<section className="container px-4 md:px-8 lg:px-16 py-10">
			{/* Header with title and sort */}
			<div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
				<h1 className="text-2xl md:text-3xl font-bold text-gray-900 capitalize">
					{title}
					{productList.length > 0 && (
						<span className="text-base font-normal text-gray-400 ml-2">
							({productList.length})
						</span>
					)}
				</h1>

				{/* Sort Dropdown */}
				<div className="flex items-center gap-2">
					<ArrowUpDown className="w-4 h-4 text-gray-400" />
					<select
						value={sort}
						onChange={(e) => setSort(e.target.value)}
						className="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#E5005F]/20 focus:border-[#E5005F] cursor-pointer"
					>
						{SORT_OPTIONS.map((opt) => (
							<option key={opt.value} value={opt.value}>
								{opt.label}
							</option>
						))}
					</select>
				</div>
			</div>

			{isLoading ? (
				<div className="flex justify-center py-16">
					<div className="w-8 h-8 border-3 border-gray-200 border-t-[#E5005F] rounded-full animate-spin" />
				</div>
			) : isError ? (
				<div className="flex flex-col items-center justify-center py-10 text-center">
					<p className="text-red-500 text-lg font-medium mb-2">
						{"Failed to fetch products"}
					</p>
					<p className="text-gray-500">Please try again later.</p>
				</div>
			) : !productList.length ? (
				<p className="text-gray-500 text-center py-16">No products found.</p>
			) : (
				<div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-5">
					{productList.map((product: Product, index: number) => (
						<ProductCard key={index} product={product} />
					))}
				</div>
			)}
		</section>
	);
};

export default ProductFilterPage;
