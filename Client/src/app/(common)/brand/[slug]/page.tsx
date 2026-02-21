/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { useParams } from "next/navigation";
import Link from "next/link";
import { ChevronRight, Package } from "lucide-react";
import ProductCard from "@/components/shared/ProductCard/ProductCard";
import { useGetBrandProductsQuery } from "@/redux/features/home/homeApi";

export default function BrandProductsPage() {
    const params = useParams();
    const slug = params?.slug as string;
    const brandName = slug?.replace(/-/g, " ") || "Brand";

    const { data, isLoading, isError } = useGetBrandProductsQuery(slug, {
        skip: !slug,
    });

    const products = data?.data || [];

    return (
        <div className="bg-white min-h-screen">
            {/* Breadcrumb */}
            <div className="border-b border-gray-100">
                <div className="container mx-auto px-4 sm:px-6 lg:px-8 py-2.5">
                    <nav className="flex items-center text-xs sm:text-sm text-gray-400">
                        <Link
                            href="/"
                            className="hover:text-[#E5005F] transition-colors"
                        >
                            Home
                        </Link>
                        <ChevronRight className="w-3.5 h-3.5 mx-1" />
                        <span className="text-gray-600 font-medium capitalize truncate">
                            {brandName}
                        </span>
                    </nav>
                </div>
            </div>

            {/* Content */}
            <div className="container mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
                <h1 className="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 mb-6 capitalize">
                    {brandName}
                </h1>

                {isLoading ? (
                    <div className="min-h-[40vh] flex flex-col items-center justify-center">
                        <div className="w-10 h-10 border-4 border-gray-200 border-t-[#E5005F] rounded-full animate-spin" />
                        <p className="mt-4 text-gray-400 text-sm">
                            Loading products...
                        </p>
                    </div>
                ) : isError || products.length === 0 ? (
                    <div className="min-h-[40vh] flex flex-col items-center justify-center gap-3">
                        <Package className="w-14 h-14 text-gray-200" />
                        <p className="text-gray-400 font-medium">
                            No products found for this brand
                        </p>
                        <Link
                            href="/"
                            className="text-[#E5005F] text-sm font-medium hover:underline"
                        >
                            ← Back to Home
                        </Link>
                    </div>
                ) : (
                    <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 sm:gap-4">
                        {products.map((product: any) => (
                            <ProductCard key={product.id} product={product} />
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}
