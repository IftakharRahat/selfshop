/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import Image from "next/image";
import { useState } from "react";
import { MapPin, Package, BadgeCheck } from "lucide-react";
import { getImageUrl } from "@/lib/utils";
import { useGetSupplierDetailsQuery } from "@/redux/features/home/homeApi";
import ProductCard from "@/components/shared/ProductCard/ProductCard";

interface SupplierDetailsComponentProps {
    slug: string;
}

export default function SupplierDetailsComponent({
    slug,
}: SupplierDetailsComponentProps) {
    const { data, isLoading, isError } = useGetSupplierDetailsQuery(slug);
    const [bannerError, setBannerError] = useState(false);
    const [logoError, setLogoError] = useState(false);

    if (isLoading) {
        return (
            <div className="container mx-auto px-4 py-20 text-center">
                <div className="inline-block w-8 h-8 border-4 border-gray-200 border-t-[#E5005F] rounded-full animate-spin" />
                <p className="mt-4 text-gray-500">Loading supplier...</p>
            </div>
        );
    }

    if (isError || !data?.data) {
        return (
            <div className="container mx-auto px-4 py-20 text-center">
                <p className="text-gray-500 text-lg">Supplier not found</p>
            </div>
        );
    }

    const vendor = data.data.vendor;
    const products = data.data.products?.data || [];
    const initials = vendor.company_name
        .split(" ")
        .map((w: string) => w[0])
        .join("")
        .slice(0, 2)
        .toUpperCase();

    return (
        <div className="bg-gray-50 min-h-screen">
            {/* ── Hero / Cover ── */}
            <div className="relative">
                {/* Banner */}
                <div className="w-full h-40 sm:h-56 md:h-72 lg:h-80 bg-gradient-to-br from-emerald-400 via-teal-500 to-cyan-500 overflow-hidden">
                    {vendor.banner_path && !bannerError ? (
                        <Image
                            src={getImageUrl(vendor.banner_path)}
                            alt="Cover"
                            fill
                            className="object-cover"
                            onError={() => setBannerError(true)}
                        />
                    ) : (
                        <div className="w-full h-full flex items-center justify-center">
                            <span className="text-white/30 text-6xl sm:text-8xl font-bold select-none">
                                {initials}
                            </span>
                        </div>
                    )}
                </div>

                {/* Logo overlay */}
                <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="relative -mt-14 sm:-mt-16 lg:-mt-20 flex items-end gap-4 sm:gap-6">
                        <div className="w-24 h-24 sm:w-28 sm:h-28 lg:w-36 lg:h-36 rounded-2xl bg-white shadow-lg border-4 border-white overflow-hidden shrink-0">
                            {vendor.logo_path && !logoError ? (
                                <Image
                                    src={getImageUrl(vendor.logo_path)}
                                    alt={vendor.company_name}
                                    width={144}
                                    height={144}
                                    className="w-full h-full object-cover"
                                    onError={() => setLogoError(true)}
                                />
                            ) : (
                                <div className="w-full h-full bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center text-white font-bold text-2xl sm:text-3xl">
                                    {initials}
                                </div>
                            )}
                        </div>

                        {/* Info beside logo */}
                        <div className="pb-2 sm:pb-3">
                            <div className="flex items-center gap-2">
                                <h1 className="text-lg sm:text-2xl lg:text-3xl font-bold text-gray-900">
                                    {vendor.company_name}
                                </h1>
                                {vendor.is_verified_badge && (
                                    <BadgeCheck className="w-5 h-5 sm:w-6 sm:h-6 text-blue-500 shrink-0" />
                                )}
                            </div>

                            <div className="flex flex-wrap items-center gap-3 mt-1 text-sm text-gray-500">
                                {vendor.city && (
                                    <span className="flex items-center gap-1">
                                        <MapPin className="w-3.5 h-3.5" />
                                        {vendor.city}
                                    </span>
                                )}
                                <span className="flex items-center gap-1">
                                    <Package className="w-3.5 h-3.5" />
                                    {vendor.products_count}{" "}
                                    {vendor.products_count === 1 ? "Product" : "Products"}
                                </span>
                                {vendor.business_type && (
                                    <span className="bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-full text-xs font-medium">
                                        {vendor.business_type}
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* ── Products Section ── */}
            <div className="container mx-auto px-3 sm:px-6 lg:px-8 py-6 sm:py-8 lg:py-12">
                <h2 className="text-sm sm:text-xl md:text-2xl font-semibold text-[#322F35] mb-4 sm:mb-6">
                    Products by {vendor.company_name}
                </h2>

                {products.length === 0 ? (
                    <div className="text-center py-16">
                        <Package className="w-12 h-12 text-gray-300 mx-auto mb-3" />
                        <p className="text-gray-400">
                            No products available from this supplier yet.
                        </p>
                    </div>
                ) : (
                    <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 sm:gap-4 lg:gap-6">
                        {products.map((product: any) => (
                            <ProductCard key={product.id} product={product} />
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}
