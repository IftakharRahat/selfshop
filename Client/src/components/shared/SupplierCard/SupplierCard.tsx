"use client";

import { useState } from "react";
import Image from "next/image";
import Link from "next/link";
import { MapPin, BadgeCheck, Star } from "lucide-react";
import { getImageUrl } from "@/lib/utils";

export interface SupplierItem {
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

export default function SupplierCard({ supplier }: { supplier: SupplierItem }) {
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

                {/* City — always render for consistent height */}
                <div className="supplier-city">
                    {supplier.city ? (
                        <>
                            <MapPin className="w-3 h-3" />
                            <span>{supplier.city}</span>
                        </>
                    ) : (
                        <span>&nbsp;</span>
                    )}
                </div>

                {/* Rating — always render for consistent height */}
                <div className="supplier-rating">
                    {(supplier.avg_product_rating ?? 0) > 0 ? (
                        <>
                            <Star className="w-3 h-3 fill-amber-400 text-amber-400" />
                            <span className="supplier-rating-value">
                                {supplier.avg_product_rating}
                            </span>
                            <span className="supplier-rating-count">
                                ({supplier.review_count})
                            </span>
                        </>
                    ) : (
                        <>
                            <Star className="w-3 h-3 text-gray-300" />
                            <span className="supplier-rating-none">No ratings</span>
                        </>
                    )}
                </div>

                {/* Product count badge */}
                <span className="supplier-product-count">
                    {supplier.products_count} {supplier.products_count === 1 ? "Product" : "Products"}
                </span>
            </div>
        </Link>
    );
}
