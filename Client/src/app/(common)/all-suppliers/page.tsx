/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { Suspense, useCallback } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { ArrowLeft, ChevronLeft, ChevronRight } from "lucide-react";
import SupplierCard, { SupplierItem } from "@/components/shared/SupplierCard/SupplierCard";
import { useGetPopularSuppliersQuery } from "@/redux/features/home/homeApi";

const ITEMS_PER_PAGE = 30;

const FILTER_OPTIONS = [
    { value: "popular", label: "Popular" },
    { value: "best_rated", label: "Best Rated" },
    { value: "recent", label: "Recent Supplier" },
] as const;

function AllSuppliersContent() {
    const router = useRouter();
    const searchParams = useSearchParams();

    // Read filter & page from URL search params (preserved in browser history)
    const activeFilter = searchParams.get("filter") || "popular";
    const currentPage = parseInt(searchParams.get("page") || "1", 10);

    const { data, isLoading, isFetching } = useGetPopularSuppliersQuery(activeFilter);
    const allSuppliers: SupplierItem[] = data?.data ?? [];

    const lastPage = Math.ceil(allSuppliers.length / ITEMS_PER_PAGE);
    const displayedSuppliers = allSuppliers.slice(
        (currentPage - 1) * ITEMS_PER_PAGE,
        currentPage * ITEMS_PER_PAGE
    );

    // Helper to build the URL with updated search params
    const buildUrl = useCallback((filter: string, page: number) => {
        const params = new URLSearchParams();
        params.set("filter", filter);
        params.set("page", String(page));
        return `/all-suppliers?${params.toString()}`;
    }, []);

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
        router.push(buildUrl(activeFilter, page), { scroll: false });
        window.scrollTo({ top: 0, behavior: "smooth" });
    };

    const handleFilterChange = (filter: string) => {
        router.push(buildUrl(filter, 1), { scroll: false });
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
                <h1 className="all-items-title">All Suppliers</h1>
            </div>

            {/* Filter Tabs */}
            <div className="supplier-filter-tabs">
                {FILTER_OPTIONS.map((opt) => (
                    <button
                        key={opt.value}
                        onClick={() => handleFilterChange(opt.value)}
                        className={`supplier-filter-tab ${activeFilter === opt.value ? "supplier-filter-tab-active" : ""}`}
                    >
                        {opt.label}
                    </button>
                ))}
            </div>

            {/* Loading */}
            {(isLoading || isFetching) && (
                <div className="flex justify-center py-16">
                    <div className="w-8 h-8 border-3 border-[#E5005F] border-t-transparent rounded-full animate-spin"></div>
                </div>
            )}

            {/* Suppliers Grid */}
            {!isLoading && !isFetching && displayedSuppliers.length > 0 && (
                <>
                    <div className="all-suppliers-grid">
                        {displayedSuppliers.map((s: any) => (
                            <SupplierCard key={s.id} supplier={s} />
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
            {!isLoading && !isFetching && allSuppliers.length === 0 && (
                <div className="flex flex-col items-center justify-center py-16">
                    <p className="text-gray-500 text-lg">No suppliers found</p>
                </div>
            )}
        </section>
    );
}

export default function AllSuppliersPage() {
    return (
        <Suspense
            fallback={
                <div className="flex justify-center py-16">
                    <div className="w-8 h-8 border-3 border-[#E5005F] border-t-transparent rounded-full animate-spin"></div>
                </div>
            }
        >
            <AllSuppliersContent />
        </Suspense>
    );
}
