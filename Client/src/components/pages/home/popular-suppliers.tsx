"use client";

import Link from "next/link";
import { useRef, useCallback, useEffect, useState } from "react";
import { ChevronLeft, ChevronRight } from "lucide-react";
import { useGetPopularSuppliersQuery } from "@/redux/features/home/homeApi";
import SupplierCard, { SupplierItem } from "@/components/shared/SupplierCard/SupplierCard";

const INITIAL_VISIBLE = 16; // 8 per row × 2 rows on desktop
const SCROLL_KEY = "selfshop_scroll_suppliers";

const PopularSuppliers = () => {
    const { data, isLoading } = useGetPopularSuppliersQuery(undefined);
    const suppliers: SupplierItem[] = data?.data ?? [];
    const scrollRef = useRef<HTMLDivElement>(null);
    const sectionRef = useRef<HTMLElement>(null);
    const [canScrollLeft, setCanScrollLeft] = useState(false);
    const [canScrollRight, setCanScrollRight] = useState(false);

    const displayedSuppliers = suppliers.slice(0, INITIAL_VISIBLE);

    const updateScrollButtons = useCallback(() => {
        const el = scrollRef.current;
        if (!el) return;
        setCanScrollLeft(el.scrollLeft > 5);
        setCanScrollRight(el.scrollLeft < el.scrollWidth - el.clientWidth - 5);
    }, []);

    useEffect(() => {
        const el = scrollRef.current;
        if (!el) return;
        const timer = setTimeout(updateScrollButtons, 100);
        el.addEventListener("scroll", updateScrollButtons, { passive: true });
        window.addEventListener("resize", updateScrollButtons);
        return () => {
            clearTimeout(timer);
            el.removeEventListener("scroll", updateScrollButtons);
            window.removeEventListener("resize", updateScrollButtons);
        };
    }, [updateScrollButtons, displayedSuppliers]);

    // Restore scroll position when returning from "View All" page
    useEffect(() => {
        const saved = sessionStorage.getItem(SCROLL_KEY);
        if (saved) {
            const timer = setTimeout(() => {
                window.scrollTo({ top: parseInt(saved, 10), behavior: "instant" as ScrollBehavior });
                sessionStorage.removeItem(SCROLL_KEY);
            }, 200);
            return () => clearTimeout(timer);
        }
    }, []);

    const scroll = (direction: "left" | "right") => {
        const el = scrollRef.current;
        if (!el) return;
        const cardWidth = el.querySelector(".supplier-card")?.getBoundingClientRect().width ?? 180;
        const scrollAmount = cardWidth * 3;
        el.scrollBy({
            left: direction === "right" ? scrollAmount : -scrollAmount,
            behavior: "smooth",
        });
    };

    const saveScrollAndNavigate = () => {
        sessionStorage.setItem(SCROLL_KEY, String(window.scrollY));
    };

    if (isLoading || suppliers.length === 0) return null;

    return (
        <section className="popular-suppliers-section" ref={sectionRef}>
            {/* Header row */}
            <div className="popular-suppliers-header">
                <h2 className="popular-suppliers-title">Popular Suppliers</h2>
                {suppliers.length > INITIAL_VISIBLE && (
                    <Link
                        href="/all-suppliers"
                        className="popular-suppliers-viewall"
                        onClick={saveScrollAndNavigate}
                    >
                        View All ({suppliers.length})
                    </Link>
                )}
            </div>

            {/* Scrollable container with nav arrows */}
            <div className="popular-suppliers-scroll-wrapper">
                {/* Left arrow */}
                {canScrollLeft && (
                    <button
                        className="popular-suppliers-arrow popular-suppliers-arrow-left"
                        onClick={() => scroll("left")}
                        aria-label="Scroll left"
                    >
                        <ChevronLeft className="w-5 h-5" />
                    </button>
                )}

                {/* Scrollable grid */}
                <div className="popular-suppliers-scroll" ref={scrollRef}>
                    <div className="popular-suppliers-grid">
                        {displayedSuppliers.map((s) => (
                            <SupplierCard key={s.id} supplier={s} />
                        ))}
                    </div>
                </div>

                {/* Right arrow */}
                {canScrollRight && (
                    <button
                        className="popular-suppliers-arrow popular-suppliers-arrow-right"
                        onClick={() => scroll("right")}
                        aria-label="Scroll right"
                    >
                        <ChevronRight className="w-5 h-5" />
                    </button>
                )}
            </div>
        </section>
    );
};

export default PopularSuppliers;
