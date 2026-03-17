"use client";

import Image from "next/image";
import Link from "next/link";
import { useState, useRef, useCallback, useEffect } from "react";
import { MapPin, BadgeCheck, Star, ChevronLeft, ChevronRight } from "lucide-react";
import { getImageUrl } from "@/lib/utils";
import { useGetPopularSuppliersQuery } from "@/redux/features/home/homeApi";

interface SupplierItem {
    id: number;
    company_name: string;
    slug: string;
    logo_path: string | null;
    banner_path: string | null;
    business_type: string | null;
    city: string | null;
    is_verified_badge: boolean;
    products_count: number;
    avg_product_rating?: number;
    review_count?: number;
}

/** Logo with fallback initials */
function SupplierLogo({
    logo,
    name,
}: {
    logo: string | null;
    name: string;
}) {
    const [errored, setErrored] = useState(false);

    if (!logo || errored) {
        const initials = name
            .split(" ")
            .map((w) => w[0])
            .join("")
            .slice(0, 2)
            .toUpperCase();
        return (
            <div className="supplier-logo-fallback">
                {initials}
            </div>
        );
    }

    return (
        <Image
            src={getImageUrl(logo)}
            alt={name}
            width={80}
            height={80}
            className="object-cover rounded-full w-full h-full"
            onError={() => setErrored(true)}
        />
    );
}

function SupplierCard({ supplier }: { supplier: SupplierItem }) {
    return (
        <Link href={`/supplier/${supplier.slug}`}>
            <div className="supplier-card">
                {/* Circular logo */}
                <div className="supplier-logo-wrapper">
                    <SupplierLogo logo={supplier.logo_path} name={supplier.company_name} />
                    {supplier.is_verified_badge && (
                        <span className="supplier-verified-badge">
                            <BadgeCheck className="w-3 h-3" />
                        </span>
                    )}
                </div>

                {/* Name */}
                <p className="supplier-name">
                    {supplier.company_name}
                </p>

                {/* City */}
                {supplier.city && (
                    <div className="supplier-city">
                        <MapPin className="w-3 h-3" />
                        <span>{supplier.city}</span>
                    </div>
                )}

                {/* Rating */}
                {(supplier.avg_product_rating ?? 0) > 0 && (
                    <div className="supplier-rating">
                        <Star className="w-3 h-3 fill-amber-400 text-amber-400" />
                        <span className="supplier-rating-value">
                            {supplier.avg_product_rating}
                        </span>
                        <span className="supplier-rating-count">
                            ({supplier.review_count})
                        </span>
                    </div>
                )}

                {/* Product count badge */}
                <span className="supplier-product-count">
                    {supplier.products_count} {supplier.products_count === 1 ? "Product" : "Products"}
                </span>
            </div>
        </Link>
    );
}

const COLS_VISIBLE = 6; // columns visible at once on desktop
const ROWS = 2;
const INITIAL_VISIBLE = COLS_VISIBLE * ROWS; // 12 cards shown initially (2 rows × 6 cols)

const PopularSuppliers = () => {
    const { data, isLoading } = useGetPopularSuppliersQuery(undefined);
    const suppliers: SupplierItem[] = data?.data ?? [];
    const [showAll, setShowAll] = useState(false);
    const scrollRef = useRef<HTMLDivElement>(null);
    const [canScrollLeft, setCanScrollLeft] = useState(false);
    const [canScrollRight, setCanScrollRight] = useState(false);

    const displayedSuppliers = showAll ? suppliers : suppliers.slice(0, INITIAL_VISIBLE);

    const updateScrollButtons = useCallback(() => {
        const el = scrollRef.current;
        if (!el) return;
        setCanScrollLeft(el.scrollLeft > 5);
        setCanScrollRight(el.scrollLeft < el.scrollWidth - el.clientWidth - 5);
    }, []);

    useEffect(() => {
        const el = scrollRef.current;
        if (!el) return;
        // Initial check after render
        const timer = setTimeout(updateScrollButtons, 100);
        el.addEventListener("scroll", updateScrollButtons, { passive: true });
        window.addEventListener("resize", updateScrollButtons);
        return () => {
            clearTimeout(timer);
            el.removeEventListener("scroll", updateScrollButtons);
            window.removeEventListener("resize", updateScrollButtons);
        };
    }, [updateScrollButtons, displayedSuppliers]);

    const scroll = (direction: "left" | "right") => {
        const el = scrollRef.current;
        if (!el) return;
        const cardWidth = el.querySelector(".supplier-card")?.getBoundingClientRect().width ?? 180;
        const scrollAmount = cardWidth * 3; // scroll 3 cards at a time
        el.scrollBy({
            left: direction === "right" ? scrollAmount : -scrollAmount,
            behavior: "smooth",
        });
    };

    if (isLoading || suppliers.length === 0) return null;

    return (
        <section className="popular-suppliers-section">
            {/* Header row */}
            <div className="popular-suppliers-header">
                <h2 className="popular-suppliers-title">Popular Suppliers</h2>
                {suppliers.length > INITIAL_VISIBLE && (
                    <button
                        className="popular-suppliers-viewall"
                        onClick={() => setShowAll((v) => !v)}
                    >
                        {showAll ? "Show Less" : `View All (${suppliers.length})`}
                    </button>
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
