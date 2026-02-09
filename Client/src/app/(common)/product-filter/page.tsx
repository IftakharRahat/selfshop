"use client"; // Important for using hooks
import { useSearchParams } from "next/navigation";
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
}

const ProductFilterPage = () => {
	const searchParams = useSearchParams();
	const category = searchParams?.get("category") ?? "";
	const subcategory = searchParams?.get("subcategory") ?? "";

	// Use category endpoint when ?category= is set and ?subcategory= is not; otherwise use subcategory (or all products when both empty)
	const useCategory = Boolean(category && !subcategory);
	const subcategorySlug = subcategory || (category ? undefined : "");

	const {
		data: categoryData,
		isLoading: categoryLoading,
		isError: categoryError,
	} = useGetCategoryProductsQuery(category, { skip: !useCategory });
	const {
		data: subcategoryData,
		isLoading: subcategoryLoading,
		isError: subcategoryError,
	} = useGetSubcategoryProductsQuery(subcategory ? subcategory : "", {
		skip: useCategory,
	});

	const products = useCategory ? categoryData : subcategoryData;
	const isLoading = useCategory ? categoryLoading : subcategoryLoading;
	const isError = useCategory ? categoryError : subcategoryError;

	const title = subcategory
		? subcategory.replace(/-/g, " ")
		: category
			? category.replace(/-/g, " ")
			: "All Products";

	return (
		<section className="container px-4 md:px-8 lg:px-16 py-10">
			<h1 className="text-2xl md:text-3xl font-bold text-gray-900 mb-6 capitalize">
				{title}
			</h1>

			{isLoading ? (
				<p className="text-gray-500 text-center py-10">Loading products...</p>
			) : isError ? (
				<div className="flex flex-col items-center justify-center py-10 text-center">
					<p className="text-red-500 text-lg font-medium mb-2">
						{"Failed to fetch products"}
					</p>
					<p className="text-gray-500">Please try again later.</p>
				</div>
			) : !products?.data?.length ? (
				<p className="text-gray-500 text-center">No products found.</p>
			) : (
				<div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-5">
					{products.data.map((product: Product, index: number) => (
						<ProductCard key={index} product={product} />
					))}
				</div>
			)}
		</section>
	);
};

export default ProductFilterPage;
