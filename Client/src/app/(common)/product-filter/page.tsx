"use client"; // Important for using hooks
import ProductCard from "@/components/shared/ProductCard/ProductCard";
import { useGetSubcategoryProductsQuery } from "@/redux/features/home/homeApi";
import { useSearchParams } from "next/navigation";

interface Product {
  ProductSlug: string;
  ProductName: string;
  ViewProductImage: string;
  ProductRegularPrice: number;
  rating?: number;
}

const ProductFilterPage = () => {
  const searchParams = useSearchParams();
  const category = searchParams?.get("category") || "";

  const { data: products = [], isLoading, isError, } = useGetSubcategoryProductsQuery(category);

  return (
    <section className="container px-4 md:px-8 lg:px-16 py-10">
      <h1 className="text-2xl md:text-3xl font-bold text-gray-900 mb-6 capitalize">
        {category?.replace(/-/g, " ") || "All Products"}
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
      ) : products.length === 0 ? (
        <p className="text-gray-500 text-center">No products found.</p>
      ) : (
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-5">
          {products?.data?.map((product: Product, index: number) => (
            <ProductCard key={index} product={product} />
          ))}
        </div>
      )}
    </section>
  );
};

export default ProductFilterPage;
