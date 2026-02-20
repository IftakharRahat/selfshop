/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";
import Image from "next/image";
import Link from "next/link";
import { useState, useEffect, useCallback } from "react";
import { useGetFlashSaleQuery } from "@/redux/features/home/homeApi";
import { getImageUrl } from "@/lib/utils";

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
    const [scrollPos, setScrollPos] = useState(0);

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

    const scrollLeft = () => setScrollPos((p) => Math.max(0, p - 1));
    const scrollRight = () =>
        setScrollPos((p) => Math.min(products.length - 1, p + 1));

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
                    <div className="relative px-2 sm:px-4 lg:px-6 pb-4 sm:pb-6">
                        {/* Navigation Arrows */}
                        {products.length > 4 && (
                            <>
                                <button
                                    onClick={scrollLeft}
                                    className="absolute left-0 top-1/2 -translate-y-1/2 z-10 w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-white/90 hover:bg-white shadow-lg flex items-center justify-center transition-all cursor-pointer"
                                    style={{ color: "#E5005F" }}
                                    disabled={scrollPos === 0}
                                >
                                    ‹
                                </button>
                                <button
                                    onClick={scrollRight}
                                    className="absolute right-0 top-1/2 -translate-y-1/2 z-10 w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-white/90 hover:bg-white shadow-lg flex items-center justify-center transition-all cursor-pointer"
                                    style={{ color: "#E5005F" }}
                                    disabled={scrollPos >= products.length - 4}
                                >
                                    ›
                                </button>
                            </>
                        )}

                        {/* Products Grid */}
                        <div className="overflow-hidden">
                            <div
                                className="flex gap-3 sm:gap-4 transition-transform duration-300"
                                style={{
                                    transform: `translateX(-${scrollPos * 25}%)`,
                                }}
                            >
                                {products.map((product: any) => (
                                    <Link
                                        href={`/product/${product.ProductSlug}`}
                                        key={product.id}
                                        className="flex-shrink-0 w-[45%] sm:w-[30%] lg:w-[23%] group"
                                    >
                                        <div className="bg-white rounded-xl overflow-hidden shadow-md hover:shadow-xl transition-all duration-300 group-hover:-translate-y-1 relative">
                                            {/* Discount Badge */}
                                            {product.discount_percentage > 0 && (
                                                <div
                                                    className="absolute top-2 left-2 z-10 px-2 py-0.5 rounded-md text-white text-xs font-bold"
                                                    style={{ background: "#E5005F" }}
                                                >
                                                    -{Math.round(product.discount_percentage)}%
                                                </div>
                                            )}

                                            {/* Product Image */}
                                            <div className="aspect-square relative bg-gray-50 overflow-hidden">
                                                <Image
                                                    src={getImageUrl(product.ViewProductImage)}
                                                    alt={product.ProductName || "Product"}
                                                    fill
                                                    className="object-contain p-2 group-hover:scale-105 transition-transform duration-300"
                                                    sizes="(max-width: 640px) 45vw, (max-width: 1024px) 30vw, 23vw"
                                                />
                                            </div>

                                            {/* Product Info */}
                                            <div className="p-2.5 sm:p-3">
                                                <p className="text-xs sm:text-sm text-gray-700 font-medium line-clamp-2 mb-1.5 min-h-[2.5em]">
                                                    {product.ProductName}
                                                </p>

                                                {/* Price Section */}
                                                <div
                                                    className="rounded-lg px-2.5 py-1.5 flex items-center justify-between"
                                                    style={{ background: "#f2f4ff" }}
                                                >
                                                    <div className="flex flex-col">
                                                        <span
                                                            className="text-sm sm:text-base font-bold"
                                                            style={{ color: "#3257d9" }}
                                                        >
                                                            ৳{product.FlashPrice}
                                                        </span>
                                                        {product.FlashPrice < product.SalePrice && (
                                                            <span className="text-[10px] sm:text-xs text-gray-400 line-through">
                                                                ৳{product.SalePrice}
                                                            </span>
                                                        )}
                                                    </div>
                                                    {product.discount_percentage > 0 && (
                                                        <span
                                                            className="text-[10px] sm:text-xs text-white px-1.5 py-0.5 rounded font-bold"
                                                            style={{ background: "#E5005F" }}
                                                        >
                                                            SAVE{" "}
                                                            {Math.round(product.discount_percentage)}%
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
