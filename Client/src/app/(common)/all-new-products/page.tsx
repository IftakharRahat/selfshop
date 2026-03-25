/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { useState, Suspense } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { ArrowLeft, ShoppingCart, ChevronLeft, ChevronRight } from "lucide-react";
import ProductCard from "@/components/shared/ProductCard/ProductCard";
import { useGetAllNewProductsQuery } from "@/redux/features/home/homeApi";

const ITEMS_PER_PAGE = 30;

function AllNewProductsContent() {
    const router = useRouter();
    const searchParams = useSearchParams();
    
    // We fetch all products, since the API doesn't seem to natively paginate the way we want
    const [objectQuery] = useState([
        { name: "page", value: 1 },
        { name: "limit", value: 200 },
    ]);
    const { data, isLoading } = useGetAllNewProductsQuery({ objectQuery });
    const allProducts: any[] = data?.data?.data || [];

    // Derive current page from URL
    const pageParam = searchParams.get("page");
    const currentPage = pageParam ? parseInt(pageParam, 10) : 1;

    const lastPage = Math.ceil(allProducts.length / ITEMS_PER_PAGE);
    
    // Safety check just in case URL page is too high
    const validCurrentPage = currentPage > lastPage && lastPage > 0 ? lastPage : currentPage;

    const displayedProducts = allProducts.slice(
        (validCurrentPage - 1) * ITEMS_PER_PAGE,
        validCurrentPage * ITEMS_PER_PAGE
    );

    // Generate page numbers for pagination
    const getPageNumbers = () => {
        const pages: (number | "...")[] = [];
        if (lastPage <= 7) {
            for (let i = 1; i <= lastPage; i++) pages.push(i);
        } else {
            pages.push(1);
            if (validCurrentPage > 3) pages.push("...");
            const start = Math.max(2, validCurrentPage - 1);
            const end = Math.min(lastPage - 1, validCurrentPage + 1);
            for (let i = start; i <= end; i++) pages.push(i);
            if (validCurrentPage < lastPage - 2) pages.push("...");
            pages.push(lastPage);
        }
        return pages;
    };

    const handlePageChange = (page: number) => {
        router.push(`?page=${page}`, { scroll: false });
        window.scrollTo({ top: 0, behavior: "smooth" });
    };

    const handleBack = () => {
        router.push("/");
    };

    return (
        <section className="all-items-page">
            {/* Header with back button */}
            <div className="all-items-header">
                <button onClick={handleBack} className="all-items-back-btn">
                    <ArrowLeft className="w-4 h-4" />
                    <span>Back to Home</span>
                </button>
                <h1 className="all-items-title">New Products</h1>
            </div>

            {/* Loading */}
            {isLoading && (
                <div className="flex justify-center py-16">
                    <div className="w-8 h-8 border-3 border-[#E5005F] border-t-transparent rounded-full animate-spin"></div>
                </div>
            )}

            {/* Products Grid */}
            {!isLoading && displayedProducts.length > 0 && (
                <>
                    <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-3 sm:gap-4">
                        {displayedProducts.map((product: any, i: number) => (
                            <ProductCard key={product?.id || i} product={product} />
                        ))}
                    </div>

                    {/* Pagination Controls */}
                    {lastPage > 1 && (
                        <div className="flex items-center justify-center gap-1 mt-10">
                            {/* Previous Button */}
                            <button
                                onClick={() => handlePageChange(validCurrentPage - 1)}
                                disabled={validCurrentPage === 1}
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
                                        className={`min-w-[36px] h-9 text-sm rounded-lg border transition-colors ${validCurrentPage === page
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
                                onClick={() => handlePageChange(validCurrentPage + 1)}
                                disabled={validCurrentPage === lastPage}
                                className="flex items-center gap-1 px-3 py-2 text-sm rounded-lg border border-gray-200 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                            >
                                <span className="hidden sm:inline">Next</span>
                                <ChevronRight className="w-4 h-4" />
                            </button>
                        </div>
                    )}
                </>
            )}

            {/* Empty */}
            {!isLoading && allProducts.length === 0 && (
                <div className="flex flex-col items-center justify-center py-16">
                    <div className="w-16 h-16 bg-pink-50 rounded-full flex items-center justify-center mb-3">
                        <ShoppingCart className="w-8 h-8 text-[#E5005F]/40" />
                    </div>
                    <p className="text-gray-500 text-lg">No new products found</p>
                </div>
            )}
        </section>
    );
}

export default function AllNewProductsPage() {
    return (
        <Suspense fallback={
            <div className="flex justify-center py-16">
                <div className="w-8 h-8 border-3 border-[#E5005F] border-t-transparent rounded-full animate-spin"></div>
            </div>
        }>
            <AllNewProductsContent />
        </Suspense>
    );
}
