"use client";
import { useSearchParams, useRouter } from "next/navigation";
import { Suspense } from "react";
import { ArrowUpDown, ChevronLeft, ChevronRight } from "lucide-react";
import ProductCard from "@/components/shared/ProductCard/ProductCard";
import {
	useGetCategoryProductsQuery,
	useGetSubcategoryProductsQuery,
	useGetMinicategoryProductsQuery,
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

function ProductFilterContent() {
	const router = useRouter();
	const searchParams = useSearchParams();
	
	const category = searchParams?.get("category") ?? "";
	const subcategory = searchParams?.get("subcategory") ?? "";
	const minicategory = searchParams?.get("minicategory") ?? "";
	
	const sort = searchParams?.get("sort") || "rating";
	const pageParam = searchParams?.get("page") || "1";
	const currentPage = parseInt(pageParam, 10) || 1;

	// Determine which query to use: minicategory > subcategory > category
	const useMinicategory = Boolean(minicategory);
	const useSubcategory = Boolean(subcategory && !minicategory);
	const useCategory = Boolean(category && !subcategory && !minicategory);

	const {
		data: categoryData,
		isLoading: categoryLoading,
		isError: categoryError,
		isFetching: categoryFetching,
	} = useGetCategoryProductsQuery(
		{ slug: category, sort, page: currentPage },
		{ skip: !useCategory },
	);
	const {
		data: subcategoryData,
		isLoading: subcategoryLoading,
		isError: subcategoryError,
		isFetching: subcategoryFetching,
	} = useGetSubcategoryProductsQuery(
		{ slug: subcategory || "", sort, page: currentPage },
		{ skip: !useSubcategory },
	);
	const {
		data: minicategoryData,
		isLoading: minicategoryLoading,
		isError: minicategoryError,
		isFetching: minicategoryFetching,
	} = useGetMinicategoryProductsQuery(
		{ slug: minicategory, sort, page: currentPage },
		{ skip: !useMinicategory },
	);

	const responseData = useMinicategory ? minicategoryData : useCategory ? categoryData : subcategoryData;
	const isLoading = useMinicategory ? minicategoryLoading : useCategory ? categoryLoading : subcategoryLoading;
	const isError = useMinicategory ? minicategoryError : useCategory ? categoryError : subcategoryError;
	const isFetching = useMinicategory ? minicategoryFetching : useCategory ? categoryFetching : subcategoryFetching;

	const title = minicategory
		? minicategory.replace(/-/g, " ")
		: subcategory
			? subcategory.replace(/-/g, " ")
			: category
				? category.replace(/-/g, " ")
				: "All Products";

	// Handle paginated response structure
	const paginationData = responseData?.data;
	const productList: Product[] = paginationData?.data ?? paginationData ?? [];
	const lastPage = paginationData?.last_page ?? 1;
	const totalProducts = paginationData?.total ?? productList.length;

	// Generate page numbers for pagination
	const getPageNumbers = () => {
		const pages: (number | "...")[] = [];
		if (lastPage <= 7) {
			for (let i = 1; i <= lastPage; i++) pages.push(i);
		} else {
			pages.push(1);
			if (currentPage > 3) pages.push("...");
			const start = Math.max(2, currentPage - 1);
			const end = Math.min(lastPage - 1, currentPage + 1);
			for (let i = start; i <= end; i++) pages.push(i);
			if (currentPage < lastPage - 2) pages.push("...");
			pages.push(lastPage);
		}
		return pages;
	};

	const handlePageChange = (page: number) => {
		const params = new URLSearchParams(searchParams?.toString());
		params.set("page", page.toString());
		router.push(`?${params.toString()}`, { scroll: false });
		window.scrollTo({ top: 0, behavior: "smooth" });
	};

	const handleSortChange = (newSort: string) => {
		const params = new URLSearchParams(searchParams?.toString());
		params.set("sort", newSort);
		params.set("page", "1"); // Reset to first page
		router.push(`?${params.toString()}`, { scroll: false });
	};

	return (
		<section className="container px-4 md:px-8 lg:px-16 py-10">
			{/* Header with title and sort */}
			<div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
				<h1 className="text-2xl md:text-3xl font-bold text-gray-900 capitalize">
					{title}
					{totalProducts > 0 && (
						<span className="text-base font-normal text-gray-400 ml-2">
							({totalProducts})
						</span>
					)}
				</h1>

				{/* Sort Dropdown */}
				<div className="flex items-center gap-2">
					<ArrowUpDown className="w-4 h-4 text-gray-400" />
					<select
						value={sort}
						onChange={(e) => handleSortChange(e.target.value)}
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
				<>
					{/* Product Grid */}
					<div className={`grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-5 ${isFetching ? "opacity-50 pointer-events-none" : ""}`}>
						{productList.map((product: Product, index: number) => (
							<ProductCard key={index} product={product} />
						))}
					</div>

					{/* Pagination Controls */}
					{lastPage > 1 && (
						<div className="flex items-center justify-center gap-1 mt-10">
							{/* Previous Button */}
							<button
								onClick={() => handlePageChange(currentPage - 1)}
								disabled={currentPage === 1}
								className="flex items-center gap-1 px-3 py-2 text-sm rounded-lg border border-gray-200 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
							>
								<ChevronLeft className="w-4 h-4" />
								<span className="hidden sm:inline">Previous</span>
							</button>

							{/* Page Numbers */}
							{getPageNumbers().map((page, idx) =>
								page === "..." ? (
									<span key={`dots-${idx}`} className="px-2 py-2 text-sm text-gray-400">
										...
									</span>
								) : (
									<button
										key={page}
										onClick={() => handlePageChange(page as number)}
										className={`min-w-[36px] h-9 text-sm rounded-lg border transition-colors ${currentPage === page
												? "bg-[#E5005F] text-white border-[#E5005F]"
												: "border-gray-200 hover:bg-gray-50 text-gray-700"
											}`}
									>
										{page}
									</button>
								),
							)}

							{/* Next Button */}
							<button
								onClick={() => handlePageChange(currentPage + 1)}
								disabled={currentPage === lastPage}
								className="flex items-center gap-1 px-3 py-2 text-sm rounded-lg border border-gray-200 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
							>
								<span className="hidden sm:inline">Next</span>
								<ChevronRight className="w-4 h-4" />
							</button>
						</div>
					)}
				</>
			)}
		</section>
	);
}

export default function ProductFilterPage() {
	return (
		<Suspense fallback={
			<div className="flex justify-center py-16">
				<div className="w-8 h-8 border-3 border-gray-200 border-t-[#E5005F] rounded-full animate-spin" />
			</div>
		}>
			<ProductFilterContent />
		</Suspense>
	);
}
