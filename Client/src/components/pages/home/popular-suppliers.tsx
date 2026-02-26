"use client";

import Image from "next/image";
import Link from "next/link";
import { useState } from "react";
import { Swiper, SwiperSlide } from "swiper/react";
import { FreeMode } from "swiper/modules";
import { MapPin, BadgeCheck, Star } from "lucide-react";
import { getImageUrl } from "@/lib/utils";
import { useGetPopularSuppliersQuery } from "@/redux/features/home/homeApi";
import "swiper/css";
import "swiper/css/free-mode";

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
            <div className="w-full h-full flex items-center justify-center bg-gradient-to-br from-pink-400 to-rose-600 rounded-full text-white font-bold text-sm sm:text-base select-none">
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
            <div className="bg-white border border-gray-100 rounded-xl p-4 sm:p-5 cursor-pointer hover:shadow-md hover:border-pink-100 transition-all flex flex-col items-center text-center gap-2.5 h-full">
                {/* Circular logo */}
                <div className="relative w-14 h-14 sm:w-16 sm:h-16 rounded-full overflow-hidden shrink-0 ring-2 ring-gray-100">
                    <SupplierLogo logo={supplier.logo_path} name={supplier.company_name} />
                    {supplier.is_verified_badge && (
                        <span className="absolute -bottom-0.5 -right-0.5 bg-blue-500 text-white rounded-full w-4 h-4 flex items-center justify-center shadow">
                            <BadgeCheck className="w-3 h-3" />
                        </span>
                    )}
                </div>

                {/* Name */}
                <p className="text-xs sm:text-sm font-semibold text-gray-900 truncate w-full leading-tight">
                    {supplier.company_name}
                </p>

                {/* City */}
                {supplier.city && (
                    <div className="flex items-center gap-1 text-gray-400">
                        <MapPin className="w-3 h-3" />
                        <span className="text-[10px] sm:text-xs">{supplier.city}</span>
                    </div>
                )}

                {/* Rating */}
                {(supplier.avg_product_rating ?? 0) > 0 && (
                    <div className="flex items-center gap-1 text-amber-500">
                        <Star className="w-3 h-3 fill-amber-400 text-amber-400" />
                        <span className="text-[10px] sm:text-xs font-semibold">
                            {supplier.avg_product_rating}
                        </span>
                        <span className="text-[10px] sm:text-xs text-gray-400 font-normal">
                            ({supplier.review_count})
                        </span>
                    </div>
                )}

                {/* Product count badge */}
                <span className="text-[10px] sm:text-xs text-pink-600 bg-pink-50 px-2.5 py-0.5 rounded-full font-medium">
                    {supplier.products_count} {supplier.products_count === 1 ? "Product" : "Products"}
                </span>
            </div>
        </Link>
    );
}

const PopularSuppliers = () => {
    const { data, isLoading } = useGetPopularSuppliersQuery(undefined);
    const suppliers: SupplierItem[] = data?.data ?? [];

    if (isLoading || suppliers.length === 0) return null;

    return (
        <div className="container mx-auto py-3 sm:py-6 lg:py-10 px-3 sm:px-6 lg:px-8">
            {/* Section title — matches other home sections */}
            <h2 className="text-sm sm:text-xl md:text-2xl lg:text-3xl font-semibold text-[#322F35] mb-3 sm:mb-5 text-center">
                Popular Suppliers
            </h2>

            {/* ---------- MOBILE SWIPER ---------- */}
            <div className="block md:hidden">
                <Swiper
                    slidesPerView={2.5}
                    spaceBetween={10}
                    freeMode={true}
                    modules={[FreeMode]}
                >
                    {suppliers.map((s) => (
                        <SwiperSlide key={s.id}>
                            <SupplierCard supplier={s} />
                        </SwiperSlide>
                    ))}
                </Swiper>
            </div>

            {/* ---------- DESKTOP GRID ---------- */}
            <div className="hidden md:grid md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                {suppliers.map((s) => (
                    <SupplierCard key={s.id} supplier={s} />
                ))}
            </div>
        </div>
    );
};

export default PopularSuppliers;
