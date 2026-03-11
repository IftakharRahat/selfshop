/* eslint-disable @typescript-eslint/no-explicit-any */
import Image from "next/image";
import Link from "next/link";
import { Store } from "lucide-react";
import { formatBDT } from "@/lib/format-currency";

interface PageProps {
    params: Promise<{
        userId: string;
    }>;
}

async function getPublicShop(userId: string) {
    const res = await fetch(
        `${process.env.NEXT_PUBLIC_BASE_URL}/reseller-shop/${userId}`,
        {
            method: "GET",
            cache: "no-store",
        },
    );
    if (!res.ok) return null;
    return res.json();
}

function getImageUrl(path: string | null) {
    if (!path) return "";
    if (path.startsWith("http")) return path;
    const base = process.env.NEXT_PUBLIC_IMAGE_URL || process.env.NEXT_PUBLIC_BASE_URL || "";
    return `${base}/${path}`;
}

export default async function PublicShopPage({ params }: PageProps) {
    const { userId } = await params;
    const result = await getPublicShop(userId);

    if (!result?.status || !result.data) {
        return (
            <div className="min-h-screen bg-gray-50 flex items-center justify-center">
                <div className="text-center">
                    <Store className="w-16 h-16 text-gray-300 mx-auto mb-4" />
                    <h1 className="text-2xl font-bold text-gray-900 mb-2">
                        Shop Not Found
                    </h1>
                    <p className="text-gray-500 mb-4">
                        This shop doesn&apos;t exist or has no products.
                    </p>
                    <Link
                        href="/"
                        className="inline-block px-6 py-2.5 rounded-lg bg-pink-600 text-white font-medium hover:bg-pink-700 transition-colors"
                    >
                        Go Home
                    </Link>
                </div>
            </div>
        );
    }

    const { shop_name, products } = result.data;

    return (
        <div className="min-h-screen bg-gray-50">
            {/* Header */}
            <div className="bg-white border-b border-gray-200">
                <div className="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div className="flex items-center gap-4">
                        <div className="w-14 h-14 rounded-2xl bg-gradient-to-br from-pink-500 to-rose-600 flex items-center justify-center text-white shadow-lg">
                            <Store className="w-7 h-7" />
                        </div>
                        <div>
                            <h1 className="text-2xl sm:text-3xl font-bold text-gray-900">
                                {shop_name}
                            </h1>
                            <p className="text-sm text-gray-500 mt-0.5">
                                {products.length} product
                                {products.length !== 1 ? "s" : ""} available
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {/* Products Grid */}
            <div className="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {products.length === 0 ? (
                    <div className="py-20 text-center">
                        <Store className="w-12 h-12 text-gray-300 mx-auto mb-3" />
                        <p className="text-gray-500">
                            This shop has no products yet.
                        </p>
                    </div>
                ) : (
                    <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 sm:gap-4">
                        {products.map((product: any) => (
                            <Link
                                key={product.id}
                                href={`/product/${product.slug}`}
                                className="group bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-lg hover:border-gray-200 transition-all"
                            >
                                {/* Image */}
                                <div className="aspect-square bg-gray-50 relative overflow-hidden">
                                    {product.image ? (
                                        <Image
                                            src={getImageUrl(product.image)}
                                            alt={product.name}
                                            fill
                                            className="object-cover group-hover:scale-105 transition-transform duration-300"
                                        />
                                    ) : (
                                        <div className="w-full h-full flex items-center justify-center text-gray-300">
                                            <Store className="w-10 h-10" />
                                        </div>
                                    )}
                                </div>

                                {/* Info */}
                                <div className="p-3">
                                    <h3 className="text-xs sm:text-sm font-medium text-gray-900 line-clamp-2 leading-snug group-hover:text-pink-600 transition-colors">
                                        {product.name}
                                    </h3>
                                    <p className="mt-1.5 text-sm font-bold text-pink-600">
                                        ৳{" "}
                                        {formatBDT(
                                            parseFloat(product.regular_price), 0
                                        )}
                                    </p>
                                </div>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}
