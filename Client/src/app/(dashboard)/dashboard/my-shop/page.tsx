/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import Image from "next/image";
import Link from "next/link";
import { Copy, ExternalLink, Store, Trash2 } from "lucide-react";
import { toast } from "sonner";
import { getImageUrl } from "@/lib/utils";
import {
    useGetShopProductsQuery,
    useRemoveFromShopMutation,
} from "@/redux/api/shopApi";
import { useGetMeQuery } from "@/redux/features/auth/authApi";
import { useAppSelector } from "@/redux/hooks";

export default function MyShopPage() {
    const token = useAppSelector((state) => state.auth.access_token);
    const { data: meData } = useGetMeQuery(undefined);
    const userId = meData?.data?.profile?.id;
    const { data, isLoading, isError } = useGetShopProductsQuery(undefined, {
        skip: !token,
    });
    const [removeFromShop, { isLoading: isRemoving }] =
        useRemoveFromShopMutation();

    const shopProducts = data?.data || [];

    const publicShopUrl =
        typeof window !== "undefined" && userId
            ? `${window.location.origin}/shop/${userId}`
            : "";

    const handleRemove = async (productId: number) => {
        try {
            await removeFromShop(productId).unwrap();
            toast.success("Product removed from your shop.");
        } catch {
            toast.error("Failed to remove product.");
        }
    };

    const handleCopyLink = () => {
        if (!publicShopUrl) {
            toast.error("Shop link not available yet.");
            return;
        }
        navigator.clipboard
            .writeText(publicShopUrl)
            .then(() => toast.success("Shop link copied to clipboard!"))
            .catch(() => toast.error("Failed to copy link."));
    };

    return (
        <div className="m-3 sm:m-4 lg:m-6 bg-white rounded-xl border border-gray-100 shadow-sm p-3 sm:p-5 lg:p-6 mb-24">
            {/* Header */}
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center">
                        <Store className="w-5 h-5 text-emerald-600" />
                    </div>
                    <div>
                        <h2 className="text-lg sm:text-2xl font-semibold text-gray-900">
                            My Shop
                        </h2>
                        <p className="text-xs text-gray-500">
                            {shopProducts.length} product
                            {shopProducts.length !== 1 ? "s" : ""} in your shop
                        </p>
                    </div>
                </div>
                {publicShopUrl && shopProducts.length > 0 && (
                    <div className="flex items-center gap-2">
                        <button
                            onClick={handleCopyLink}
                            className="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                        >
                            <Copy className="w-3.5 h-3.5" />
                            Copy Link
                        </button>
                        <Link
                            href={`/shop/${userId}`}
                            target="_blank"
                            className="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-pink-600 text-sm font-medium text-white hover:bg-pink-700 transition-colors"
                        >
                            <ExternalLink className="w-3.5 h-3.5" />
                            Visit Shop
                        </Link>
                    </div>
                )}
            </div>

            {/* Public link info */}
            {publicShopUrl && shopProducts.length > 0 && (
                <div className="mb-5 p-3 rounded-lg bg-blue-50 border border-blue-100 flex flex-col sm:flex-row sm:items-center gap-2">
                    <p className="text-xs text-blue-700 font-medium">
                        🔗 Your public shop link:
                    </p>
                    <code className="text-xs text-blue-600 bg-blue-100 px-2 py-0.5 rounded break-all">
                        {publicShopUrl}
                    </code>
                </div>
            )}

            {/* Loading / Error */}
            {isLoading && (
                <div className="py-16 text-center text-gray-500 text-sm">
                    Loading your shop products...
                </div>
            )}
            {isError && (
                <div className="py-16 text-center text-red-500 text-sm">
                    Failed to load shop products.
                </div>
            )}

            {/* Empty state */}
            {!isLoading && !isError && shopProducts.length === 0 && (
                <div className="py-16 text-center">
                    <Store className="w-12 h-12 text-gray-300 mx-auto mb-3" />
                    <p className="text-gray-500 text-sm">
                        Your shop is empty.
                    </p>
                    <p className="text-gray-400 text-xs mt-1">
                        Browse products and click &ldquo;Add to Shop&rdquo; to
                        start curating your collection.
                    </p>
                    <Link
                        href="/"
                        className="inline-block mt-4 px-4 py-2 rounded-lg bg-pink-600 text-white text-sm font-medium hover:bg-pink-700 transition-colors"
                    >
                        Browse Products
                    </Link>
                </div>
            )}

            {/* Product Grid */}
            {!isLoading && !isError && shopProducts.length > 0 && (
                <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
                    {shopProducts.map((item: any) => {
                        const product = item.product;
                        if (!product) return null;

                        return (
                            <div
                                key={item.id}
                                className="group bg-white border border-gray-100 rounded-xl overflow-hidden hover:shadow-md hover:border-gray-200 transition-all"
                            >
                                {/* Image */}
                                <Link
                                    href={`/product/${product.slug}`}
                                    className="block aspect-square bg-gray-50 relative overflow-hidden"
                                >
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
                                </Link>

                                {/* Info */}
                                <div className="p-2.5 sm:p-3">
                                    <Link
                                        href={`/product/${product.slug}`}
                                        className="block"
                                    >
                                        <h3 className="text-xs sm:text-sm font-medium text-gray-900 line-clamp-2 leading-snug group-hover:text-pink-600 transition-colors">
                                            {product.name}
                                        </h3>
                                    </Link>

                                    <div className="flex items-center justify-between mt-2">
                                        <span className="text-sm font-bold text-pink-600">
                                            ৳{" "}
                                            {parseFloat(
                                                product.regular_price,
                                            ).toFixed(0)}
                                        </span>
                                        <button
                                            onClick={() =>
                                                handleRemove(item.product_id)
                                            }
                                            disabled={isRemoving}
                                            className="p-1.5 rounded-md text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors"
                                            title="Remove from shop"
                                        >
                                            <Trash2 className="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
