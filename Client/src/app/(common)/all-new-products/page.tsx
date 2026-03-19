/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { ArrowLeft, ShoppingCart, ChevronLeft, ChevronRight } from "lucide-react";
import ProductCard from "@/components/shared/ProductCard/ProductCard";
import { useGetAllNewProductsQuery } from "@/redux/features/home/homeApi";

const SCROLL_KEY = "selfshop_scroll_newproducts";
const ITEMS_PER_PAGE = 30;

export default function AllNewProductsPage() {
    const router = useRouter();
    const [objectQuery] = useState([
        { name: "page", value: 1 },
        { name: "limit", value: 200 },
    ]);
    const { data, isLoading } = useGetAllNewProductsQuery({ objectQuery });
    const allProducts: any[] = data?.data?.data || [];

    const [currentPage, setCurrentPage] = useState(1);
    const lastPage = Math.ceil(allProducts.length / ITEMS_PER_PAGE);
    const displayedProducts = allProducts.slice(
        (currentPage - 1) * ITEMS_PER_PAGE,
        currentPage * ITEMS_PER_PAGE
    );

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
        setCurrentPage(page);
        window.scrollTo({ top: 0, behavior: "smooth" });
    };

    const handleBack = () => {
        router.back();
        setTimeout(() => {
            const saved = sessionStorage.getItem(SCROLL_KEY);
            if (saved) {
                window.scrollTo({ top: parseInt(saved, 10), behavior: "instant" as ScrollBehavior });
                sessionStorage.removeItem(SCROLL_KEY);
            }
        }, 300);
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
