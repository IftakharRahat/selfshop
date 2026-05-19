/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";
import Image from "next/image";
import Link from "next/link";
import { useState, useEffect, useCallback } from "react";
import { Swiper, SwiperSlide } from "swiper/react";
import { Navigation, FreeMode } from "swiper/modules";
import { Lock } from "lucide-react";
import { useIsActiveReseller } from "@/hooks/useIsActiveReseller";
import { useGetFlashSaleQuery } from "@/redux/features/home/homeApi";
import { getImageUrl } from "@/lib/utils";
import { formatBDT } from "@/lib/format-currency";
import "swiper/css";
import "swiper/css/navigation";
import "swiper/css/free-mode";

interface TimeLeft {
    days: number;
    hours: number;
    minutes: number;
    seconds: number;
}

function useCountdown(endTime: string | null): TimeLeft {
    const calculateTimeLeft = useCallback((): TimeLeft => {
        if (!endTime) return { days: 0, hours: 0, minutes: 0, seconds: 0 };
        const diff = new Date(endTime).getTime() - Date.now();
        if (diff <= 0) return { days: 0, hours: 0, minutes: 0, seconds: 0 };
        return {
            days: Math.floor(diff / (1000 * 60 * 60 * 24)),
            hours: Math.floor((diff / (1000 * 60 * 60)) % 24),
            minutes: Math.floor((diff / (1000 * 60)) % 60),
            seconds: Math.floor((diff / 1000) % 60),
        };
    }, [endTime]);

    const [timeLeft, setTimeLeft] = useState<TimeLeft>(calculateTimeLeft);

    useEffect(() => {
        setTimeLeft(calculateTimeLeft());
        const timer = setInterval(() => {
            setTimeLeft(calculateTimeLeft());
        }, 1000);
        return () => clearInterval(timer);
    }, [calculateTimeLeft]);

    return timeLeft;
}

function pad(n: number): string {
    return n.toString().padStart(2, "0");
}

export default function FlashSaleSection() {
    const { data, isLoading } = useGetFlashSaleQuery(undefined);
    const flashSale = data?.data;
    const timeLeft = useCountdown(flashSale?.end_time ?? null);

    if (isLoading) return null;
    if (!flashSale || !data?.status) return null;

    const products = flashSale.products || [];
    if (products.length === 0) return null;

    return (
        <div className="w-full py-3 sm:py-6 lg:py-8">
            <div className="container mx-auto px-3 sm:px-6 lg:px-8">
                <div
                    className="rounded-2xl overflow-hidden"
                    style={{
                        background:
                            "linear-gradient(90deg, #b3003b 0%, #E5005F 100%)",
                    }}
                >
                    {/* Header */}
                    <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 lg:px-8 pt-4 sm:pt-5 pb-3">
                        <div className="flex items-center gap-2 sm:gap-3 mb-2 sm:mb-0">
                            <span className="text-xl sm:text-2xl">⚡</span>
                            <h2 className="text-xl sm:text-2xl lg:text-3xl font-bold italic text-white tracking-tight">
                                {flashSale.title || "Flash Sale"}
                            </h2>
                        </div>

                        <div className="flex items-center gap-3 sm:gap-5">
                            {/* Countdown Timer */}
                            <div className="flex items-center gap-2 sm:gap-3">
                                <span className="text-white font-medium text-xs sm:text-sm hidden sm:inline-block">Ending in</span>
                                <div className="flex items-center gap-1.5 sm:gap-2">
                                    {[
                                        { value: timeLeft.days, label: "Days" },
                                        { value: timeLeft.hours, label: "Hours" },
                                        { value: timeLeft.minutes, label: "Min" },
                                        { value: timeLeft.seconds, label: "Sec" },
                                    ].map((unit) => (
                                        <div key={unit.label} className="flex flex-col items-center gap-1">
                                            <div
                                                className="flex items-center justify-center rounded font-bold text-black text-sm sm:text-base"
                                                style={{
                                                    background: "#FFFFFF",
                                                    minWidth: "32px",
                                                    height: "32px",
                                                    padding: "2px 6px",
                                                }}
                                            >
                                                {pad(unit.value)}
                                            </div>
                                            <span className="text-[9px] sm:text-[10px] font-bold text-white/80 leading-none">
                                                {unit.label}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <Link
                                href="/flash-sale"
                                className="inline-flex items-center gap-1 bg-white text-[#E5005F] hover:bg-gray-100 px-3 sm:px-4 py-1.5 sm:py-2 rounded-full text-xs sm:text-sm font-bold transition-all ml-2 sm:ml-4 shadow-sm"
                            >
                                See More {'>'}
                            </Link>
                        </div>
                    </div>

                    {/* Product Carousel */}
                    <div className="px-2 sm:px-4 lg:px-6 pb-4 sm:pb-6">
                        {/* ---------- MOBILE SWIPER ---------- */}
                        <div className="block md:hidden">
                            <Swiper
                                slidesPerView={2.3}
                                spaceBetween={8}
                                freeMode={true}
                                modules={[FreeMode]}
                            >
                                {products.map((product: any) => (
                                    <SwiperSlide key={product.id} className="!h-auto">
                                        <FlashProductCard product={product} />
                                    </SwiperSlide>
                                ))}
                            </Swiper>
                        </div>

                        {/* ---------- DESKTOP SWIPER ---------- */}
                        <div className="hidden md:block">
                            <Swiper
                                modules={[Navigation]}
                                navigation
                                spaceBetween={14}
                                slidesPerView={5}
                                className="!overflow-x-clip !overflow-y-visible"
                                breakpoints={{
                                    768: { slidesPerView: 3, spaceBetween: 10 },
                                    1024: { slidesPerView: 4, spaceBetween: 14 },
                                    1280: { slidesPerView: 5, spaceBetween: 14 },
                                }}
                            >
                                {products.map((product: any) => (
                                    <SwiperSlide key={product.id} className="!h-auto">
                                        <FlashProductCard product={product} />
                                    </SwiperSlide>
                                ))}
                            </Swiper>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

function FlashProductCard({ product }: { product: any }) {
    const [imgError, setImgError] = useState(false);
    const { isActive: isResellerActive } = useIsActiveReseller();

    if (!product) return null;

    const hasDiscount =
        product.discount_percentage > 0 &&
        product.FlashPrice < product.SalePrice;

    return (
        <Link
            href={`/product/${product.ProductSlug}`}
            className="block h-full"
        >
            <div className="bg-white rounded-xl overflow-hidden hover:shadow-md transition-shadow duration-200 h-full flex flex-col group">
                {/* Discount Badge */}
                {hasDiscount && (
                    <div className="absolute top-2 left-2 z-10 bg-[#E5005F] text-white text-[10px] sm:text-xs font-bold px-2 py-0.5 rounded-md">
                        -{Math.round(product.discount_percentage)}%
                    </div>
                )}

                {/* Product Image — matches ProductCard pattern */}
                <div className="relative overflow-hidden aspect-square">
                    <Image
                        src={
                            imgError || !product.ViewProductImage
                                ? "/placeholder.svg"
                                : getImageUrl(product.ViewProductImage)
                        }
                        alt={product.ProductName || "Product"}
                        width={600}
                        height={400}
                        className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                        onError={() => setImgError(true)}
                    />
                </div>

                {/* Product Info */}
                <div className="p-2.5 sm:p-3 flex flex-col gap-1.5 flex-1">
                    <p className="text-xs sm:text-sm text-gray-800 font-medium line-clamp-2 leading-snug min-h-[2.5em]">
                        {product.ProductName}
                    </p>

                    {/* Price Section */}
                    <div className="mt-auto flex items-center justify-between">
                        <div className="flex flex-col">
                            {isResellerActive ? (
                                <>
                                    <span className="text-sm sm:text-base font-bold text-gray-900 digit-font">
                                        ৳{formatBDT(product.FlashPrice)}
                                    </span>
                                    {hasDiscount && (
                                        <span className="text-[10px] sm:text-xs text-gray-400 line-through digit-font">
                                            ৳{formatBDT(product.SalePrice)}
                                        </span>
                                    )}
                                </>
                            ) : (
                                <div className="flex flex-col">
                                    <span className="text-gray-400 text-xs font-bold">***</span>
                                    <span className="text-[10px] text-pink-600 font-bold flex items-center gap-1">
                                        <Lock className="w-2.5 h-2.5" /> Active profile required
                                    </span>
                                </div>
                            )}
                        </div>
                        {isResellerActive && hasDiscount && (
                            <span className="text-[10px] sm:text-xs text-white bg-[#E5005F] px-1.5 py-0.5 rounded font-bold">
                                SAVE {Math.round(product.discount_percentage)}%
                            </span>
                        )}
                    </div>
                </div>
            </div>
        </Link>
    );
}
