/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";
import Image from "next/image";
import Link from "next/link";
import { useState, useEffect, useCallback } from "react";
import { Swiper, SwiperSlide } from "swiper/react";
import { Navigation, FreeMode } from "swiper/modules";
import { useGetFlashSaleQuery } from "@/redux/features/home/homeApi";
import { getImageUrl } from "@/lib/utils";
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

    const isExpired =
        timeLeft.days === 0 &&
        timeLeft.hours === 0 &&
        timeLeft.minutes === 0 &&
        timeLeft.seconds === 0;

    if (isLoading) return null;
    if (!flashSale || !data?.status) return null;
    if (isExpired) return null;

    const products = flashSale.products || [];
    if (products.length === 0) return null;

    return (
        <div className="w-full py-3 sm:py-6 lg:py-8">
            <div className="container mx-auto px-3 sm:px-6 lg:px-8">
                <div
                    className="rounded-2xl overflow-hidden"
                    style={{
                        background:
                            "linear-gradient(135deg, #3257d9 0%, #4a6ae5 40%, #6b83ef 70%, #8b9ff5 100%)",
                    }}
                >
                    {/* Header */}
                    <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 lg:px-8 pt-4 sm:pt-5 pb-3">
                        <div className="flex items-center gap-2 sm:gap-3 mb-2 sm:mb-0">
                            <span className="text-xl sm:text-2xl">⚡</span>
                            <h2 className="text-xl sm:text-2xl lg:text-3xl font-bold text-white tracking-tight">
                                {flashSale.title || "Flash Sale"}
                            </h2>
                        </div>

                        <div className="flex items-center gap-3 sm:gap-5">
                            {/* Countdown Timer */}
                            <div className="flex items-center gap-1.5 sm:gap-2">
                                {[
                                    { value: timeLeft.days, label: "D" },
                                    { value: timeLeft.hours, label: "H" },
                                    { value: timeLeft.minutes, label: "M" },
                                    { value: timeLeft.seconds, label: "S" },
                                ].map((unit, i) => (
                                    <div key={i} className="flex items-center gap-1">
                                        <div
                                            className="flex items-center justify-center rounded-lg font-bold text-white text-sm sm:text-lg"
                                            style={{
                                                background: "#E5005F",
                                                minWidth: "32px",
                                                height: "32px",
                                                padding: "2px 6px",
                                            }}
                                        >
                                            {pad(unit.value)}
                                        </div>
                                        <span className="text-white/70 text-xs font-medium">
                                            {unit.label}
                                        </span>
                                        {i < 3 && (
                                            <span className="text-white/50 font-bold mx-0.5">
                                                :
                                            </span>
                                        )}
                                    </div>
                                ))}
                            </div>

                            <Link
                                href="/flash-sale"
                                className="hidden sm:inline-flex items-center gap-1 hover:bg-white/30 backdrop-blur-sm text-white px-4 py-2 rounded-full text-sm font-semibold transition-all"
                                style={{ background: "#E5005F" }}
                            >
                                See All →
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
                            <span className="text-sm sm:text-base font-bold text-gray-900">
                                ৳{product.FlashPrice}
                            </span>
                            {hasDiscount && (
                                <span className="text-[10px] sm:text-xs text-gray-400 line-through">
                                    ৳{product.SalePrice}
                                </span>
                            )}
                        </div>
                        {hasDiscount && (
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
