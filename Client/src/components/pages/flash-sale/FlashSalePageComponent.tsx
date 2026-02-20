/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";
import Image from "next/image";
import Link from "next/link";
import { useState, useEffect, useCallback } from "react";
import { Zap } from "lucide-react";
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

export default function FlashSalePageComponent() {
    const { data, isLoading } = useGetFlashSaleQuery(undefined);
    const flashSale = data?.data;
    const timeLeft = useCountdown(flashSale?.end_time ?? null);

    const isExpired =
        timeLeft.days === 0 &&
        timeLeft.hours === 0 &&
        timeLeft.minutes === 0 &&
        timeLeft.seconds === 0;

    if (isLoading) {
        return (
            <div className="min-h-[60vh] flex items-center justify-center">
                <div className="animate-spin rounded-full h-8 w-8 border-2 border-[#E5005F] border-t-transparent" />
            </div>
        );
    }

    if (!flashSale || !data?.status || isExpired) {
        return (
            <div className="min-h-[60vh] flex flex-col items-center justify-center gap-3 px-4">
                <Zap className="w-12 h-12 text-gray-300" />
                <h2 className="text-xl font-semibold text-gray-600">
                    No Active Flash Sale
                </h2>
                <p className="text-gray-400 text-sm text-center">
                    Check back later for amazing deals!
                </p>
                <Link
                    href="/"
                    className="mt-2 text-sm font-medium text-[#E5005F] hover:underline"
                >
                    ← Back to Home
                </Link>
            </div>
        );
    }

    const products = flashSale.products || [];

    return (
        <div className="bg-gray-50 min-h-screen">
            {/* Header Banner */}
            <div
                className="w-full py-6 sm:py-8"
                style={{
                    background:
                        "linear-gradient(135deg, #3257d9 0%, #4a6ae5 40%, #6b83ef 70%, #8b9ff5 100%)",
                }}
            >
                <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div className="flex items-center gap-3">
                            <span className="text-2xl sm:text-3xl">⚡</span>
                            <h1 className="text-2xl sm:text-3xl lg:text-4xl font-bold text-white tracking-tight">
                                {flashSale.title || "Flash Sale"}
                            </h1>
                        </div>

                        {/* Countdown */}
                        <div className="flex items-center gap-2">
                            <span className="text-white/70 text-sm mr-1">Ends in</span>
                            {[
                                { value: timeLeft.days, label: "D" },
                                { value: timeLeft.hours, label: "H" },
                                { value: timeLeft.minutes, label: "M" },
                                { value: timeLeft.seconds, label: "S" },
                            ].map((unit, i) => (
                                <div key={i} className="flex items-center gap-1">
                                    <div className="flex items-center justify-center rounded-lg font-bold text-white text-base sm:text-lg bg-[#E5005F] min-w-[36px] h-[36px] px-2">
                                        {pad(unit.value)}
                                    </div>
                                    <span className="text-white/70 text-xs font-medium">
                                        {unit.label}
                                    </span>
                                    {i < 3 && (
                                        <span className="text-white/50 font-bold mx-0.5">:</span>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>

            {/* Products Grid */}
            <div className="container mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
                {products.length === 0 ? (
                    <p className="text-center text-gray-400 py-12">
                        No products in this flash sale yet.
                    </p>
                ) : (
                    <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 sm:gap-4">
                        {products.map((product: any) => (
                            <FlashProductCard key={product.id} product={product} />
                        ))}
                    </div>
                )}
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
        <Link href={`/product/${product.ProductSlug}`} className="block h-full">
            <div className="bg-white rounded-xl overflow-hidden hover:shadow-md transition-shadow duration-200 h-full flex flex-col group relative">
                {/* Discount Badge */}
                {hasDiscount && (
                    <div className="absolute top-2 left-2 z-10 bg-[#E5005F] text-white text-[10px] sm:text-xs font-bold px-2 py-0.5 rounded-md">
                        -{Math.round(product.discount_percentage)}%
                    </div>
                )}

                {/* Product Image */}
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
