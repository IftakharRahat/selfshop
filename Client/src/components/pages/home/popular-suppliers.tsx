"use client";

import Image from "next/image";
import Link from "next/link";
import { useState } from "react";
import { Swiper, SwiperSlide } from "swiper/react";
import { FreeMode } from "swiper/modules";
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
}

/** Logo with fallback initials — matches MostPopularBrands sizing */
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
            <div className="w-full h-full flex items-center justify-center bg-gradient-to-br from-emerald-400 to-teal-600 rounded-full text-white font-bold text-base sm:text-lg select-none">
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
            <div className="w-full aspect-square bg-white border border-gray-100 rounded-xl flex flex-col items-center justify-center p-3 sm:p-4 cursor-pointer hover:shadow-md transition-shadow gap-1.5 sm:gap-2">
                {/* Circular logo */}
                <div className="relative w-10 h-10 sm:w-14 sm:h-14 lg:w-16 lg:h-16 rounded-full overflow-hidden shrink-0">
                    <SupplierLogo logo={supplier.logo_path} name={supplier.company_name} />
                    {supplier.is_verified_badge && (
                        <span className="absolute -bottom-0.5 -right-0.5 bg-blue-500 text-white rounded-full w-3.5 h-3.5 sm:w-4 sm:h-4 flex items-center justify-center text-[8px] sm:text-[10px] shadow">
                            ✓
                        </span>
                    )}
                </div>

                {/* Name */}
                <p className="text-[10px] sm:text-xs font-medium text-gray-800 text-center truncate w-full">
                    {supplier.company_name}
                </p>

                {/* Product count */}
                <p className="text-[9px] sm:text-[10px] text-gray-400">
                    {supplier.products_count}{" "}
                    {supplier.products_count === 1 ? "Product" : "Products"}
                </p>
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
            {/* Section title — matches MostPopularBrands */}
            <h2 className="text-sm sm:text-xl md:text-2xl lg:text-3xl font-semibold text-[#322F35] mb-3 sm:mb-5 text-center">
                Popular Suppliers
            </h2>

            {/* ---------- MOBILE SWIPER ---------- */}
            <div className="block md:hidden">
                <Swiper
                    slidesPerView={3.5}
                    spaceBetween={8}
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
            <div className="hidden md:grid md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-8 gap-4">
                {suppliers.map((s) => (
                    <SupplierCard key={s.id} supplier={s} />
                ))}
            </div>
        </div>
    );
};

export default PopularSuppliers;
