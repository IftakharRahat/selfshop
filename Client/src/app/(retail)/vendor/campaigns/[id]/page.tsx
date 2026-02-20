"use client";

import {
    useVendorCampaignQuery,
    useSubmitCampaignProductMutation,
    useRemoveCampaignProductMutation,
    useVendorProductsForCampaignQuery,
} from "@/redux/api/campaignApi";
import Link from "next/link";
import { useParams } from "next/navigation";
import { useEffect, useState } from "react";
import {
    ArrowLeft,
    Clock,
    Package,
    Plus,
    Search,
    Trash2,
    X,
    Zap,
} from "lucide-react";

/* ── Countdown hook ───────────────────────────────────────────────────── */
function useCountdown(target: string | null) {
    const [timeLeft, setTimeLeft] = useState("");
    const [expired, setExpired] = useState(false);

    useEffect(() => {
        if (!target) {
            setTimeLeft("—");
            return;
        }
        const tick = () => {
            const diff = new Date(target).getTime() - Date.now();
            if (diff <= 0) {
                setTimeLeft("Ended");
                setExpired(true);
                return;
            }
            const d = Math.floor(diff / 86400000);
            const h = Math.floor((diff % 86400000) / 3600000);
            const m = Math.floor((diff % 3600000) / 60000);
            const s = Math.floor((diff % 60000) / 1000);
            setTimeLeft(`${d}d ${h}h ${m}m ${s}s`);
        };
        tick();
        const iv = setInterval(tick, 1000);
        return () => clearInterval(iv);
    }, [target]);

    return { timeLeft, expired };
}

export default function CampaignDetailPage() {
    const params = useParams();
    const id = Number(params.id);
    const { data, isLoading, error } = useVendorCampaignQuery(id);
    const [submitProduct, { isLoading: submitting }] =
        useSubmitCampaignProductMutation();
    const [removeProduct] = useRemoveCampaignProductMutation();

    // Product picker modal state
    const [showPicker, setShowPicker] = useState(false);
    const [searchQuery, setSearchQuery] = useState("");
    const [selectedProductId, setSelectedProductId] = useState<number | null>(null);
    const [campaignPrice, setCampaignPrice] = useState("");
    const [sellerSku, setSellerSku] = useState("");

    const {
        data: vendorProducts,
        isLoading: loadingProducts,
    } = useVendorProductsForCampaignQuery(searchQuery || undefined);

    const campaign = data?.campaign;
    const vendorSubmittedProducts = data?.vendor_products ?? [];

    const countdown = useCountdown(campaign?.end_time ?? null);
    const regCountdown = useCountdown(campaign?.registration_deadline ?? null);
    const canSubmit =
        campaign?.vendor_registration && !regCountdown.expired && !countdown.expired;

    const apiBase = process.env.NEXT_PUBLIC_BASE_URL?.replace("/api", "") ?? "";

    const formatDate = (d: string | null) => {
        if (!d) return "—";
        return new Date(d).toLocaleDateString("en-US", {
            month: "short",
            day: "numeric",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        });
    };

    const handleSubmit = async () => {
        if (!selectedProductId || !campaignPrice) return;
        try {
            await submitProduct({
                campaignId: id,
                product_id: selectedProductId,
                campaign_price: parseFloat(campaignPrice),
                seller_sku: sellerSku || undefined,
            }).unwrap();
            setShowPicker(false);
            setSelectedProductId(null);
            setCampaignPrice("");
            setSellerSku("");
        } catch (err: unknown) {
            const error = err as { data?: { message?: string } };
            alert(error?.data?.message || "Failed to submit product");
        }
    };

    const handleRemove = async (fspId: number) => {
        if (!confirm("Remove this product from the campaign?")) return;
        try {
            await removeProduct({ campaignId: id, fspId }).unwrap();
        } catch {
            alert("Failed to remove product");
        }
    };

    // Find selected product info for the picker
    const selectedProduct = vendorProducts?.find(
        (p: { id: number }) => p.id === selectedProductId,
    );

    if (isLoading) {
        return (
            <div className="space-y-4">
                <div className="h-8 w-48 bg-gray-200 rounded animate-pulse" />
                <div className="h-48 bg-gray-100 rounded-xl animate-pulse" />
                <div className="h-64 bg-gray-100 rounded-xl animate-pulse" />
            </div>
        );
    }

    if (error || !campaign) {
        return (
            <div className="text-center py-16">
                <p className="text-red-500 font-medium">Campaign not found</p>
                <Link
                    href="/vendor/campaigns"
                    className="text-indigo-600 hover:underline mt-2 inline-block text-sm"
                >
                    ← Back to campaigns
                </Link>
            </div>
        );
    }

    return (
        <div className="space-y-6 max-w-5xl">
            {/* Breadcrumb */}
            <div className="flex items-center gap-2 text-sm text-gray-500">
                <Link
                    href="/vendor/campaigns"
                    className="flex items-center gap-1 hover:text-indigo-600 transition-colors"
                >
                    <ArrowLeft className="h-3.5 w-3.5" />
                    Campaigns
                </Link>
                <span>/</span>
                <span className="text-gray-900 font-medium truncate">
                    {campaign.title}
                </span>
            </div>

            {/* Campaign Info Card */}
            <div className="rounded-xl border border-gray-200 bg-white overflow-hidden shadow-sm">
                {/* Banner */}
                {campaign.banner_image && (
                    <div className="h-48 sm:h-56 relative">
                        <img
                            src={`${apiBase}/${campaign.banner_image}`}
                            alt={campaign.title}
                            className="w-full h-full object-cover"
                        />
                        <div className="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent" />
                        <div className="absolute bottom-4 left-4 right-4">
                            <h1 className="text-xl sm:text-2xl font-bold text-white">
                                {campaign.title}
                            </h1>
                        </div>
                    </div>
                )}
                {!campaign.banner_image && (
                    <div className="px-5 pt-5">
                        <h1 className="text-xl font-bold text-gray-900">
                            {campaign.title}
                        </h1>
                    </div>
                )}

                {/* Info Grid */}
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 p-5">
                    <div className="space-y-1">
                        <p className="text-xs font-medium text-gray-500 uppercase tracking-wide">
                            Start
                        </p>
                        <p className="text-sm font-semibold text-gray-900">
                            {formatDate(campaign.start_time)}
                        </p>
                    </div>
                    <div className="space-y-1">
                        <p className="text-xs font-medium text-gray-500 uppercase tracking-wide">
                            End
                        </p>
                        <p className="text-sm font-semibold text-gray-900">
                            {formatDate(campaign.end_time)}
                        </p>
                    </div>
                    <div className="space-y-1">
                        <p className="text-xs font-medium text-gray-500 uppercase tracking-wide">
                            Time Left
                        </p>
                        <p
                            className={`text-sm font-semibold ${countdown.expired ? "text-red-600" : "text-emerald-600"
                                }`}
                        >
                            <Clock className="h-3.5 w-3.5 inline-block mr-1 -mt-0.5" />
                            {countdown.timeLeft}
                        </p>
                    </div>
                    <div className="space-y-1">
                        <p className="text-xs font-medium text-gray-500 uppercase tracking-wide">
                            Registration
                        </p>
                        <p
                            className={`text-sm font-semibold ${regCountdown.expired
                                    ? "text-red-600"
                                    : "text-amber-600"
                                }`}
                        >
                            {campaign.registration_deadline
                                ? regCountdown.expired
                                    ? "Closed"
                                    : regCountdown.timeLeft
                                : "Open"}
                        </p>
                    </div>
                </div>
            </div>

            {/* Submitted Products Section */}
            <div className="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div className="flex items-center justify-between p-5 border-b border-gray-100">
                    <div className="flex items-center gap-2">
                        <Package className="h-5 w-5 text-indigo-600" />
                        <h2 className="text-base font-semibold text-gray-900">
                            Your Submitted Products
                        </h2>
                        <span className="text-xs font-medium text-gray-400">
                            ({vendorSubmittedProducts.length})
                        </span>
                    </div>
                    {canSubmit && (
                        <button
                            type="button"
                            onClick={() => setShowPicker(true)}
                            className="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-white bg-[#2d2a5d] hover:bg-[#3f3b7a] transition-colors"
                        >
                            <Plus className="h-4 w-4" />
                            Add Product
                        </button>
                    )}
                </div>

                {vendorSubmittedProducts.length === 0 ? (
                    <div className="text-center py-12 px-4">
                        <Zap className="h-10 w-10 mx-auto text-gray-300 mb-3" />
                        <p className="text-gray-500 font-medium">
                            No products submitted yet
                        </p>
                        {canSubmit && (
                            <button
                                type="button"
                                onClick={() => setShowPicker(true)}
                                className="mt-3 text-sm text-indigo-600 hover:underline"
                            >
                                Submit your first product →
                            </button>
                        )}
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-gray-100 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                                    <th className="px-5 py-3">Product</th>
                                    <th className="px-5 py-3">SKU</th>
                                    <th className="px-5 py-3">Retail Price</th>
                                    <th className="px-5 py-3">Campaign Price</th>
                                    <th className="px-5 py-3">Discount</th>
                                    <th className="px-5 py-3">Stock</th>
                                    <th className="px-5 py-3 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                {vendorSubmittedProducts.map((fsp) => {
                                    const p = fsp.product;
                                    const imgSrc = p?.ViewProductImage
                                        ? `${apiBase}/${p.ViewProductImage}`
                                        : "";
                                    return (
                                        <tr
                                            key={fsp.id}
                                            className="border-b border-gray-50 hover:bg-gray-50 transition-colors"
                                        >
                                            <td className="px-5 py-3">
                                                <div className="flex items-center gap-3">
                                                    {imgSrc && (
                                                        <img
                                                            src={imgSrc}
                                                            alt=""
                                                            className="h-10 w-10 rounded-md object-cover border border-gray-200"
                                                            onError={(e) => {
                                                                (e.target as HTMLImageElement).style.display = "none";
                                                            }}
                                                        />
                                                    )}
                                                    <span className="font-medium text-gray-900 line-clamp-1">
                                                        {p?.ProductName ?? "N/A"}
                                                    </span>
                                                </div>
                                            </td>
                                            <td className="px-5 py-3 text-gray-500 font-mono text-xs">
                                                {fsp.seller_sku || p?.ProductSku || "—"}
                                            </td>
                                            <td className="px-5 py-3 text-gray-600">
                                                ৳{p?.ProductRegularPrice ?? 0}
                                            </td>
                                            <td className="px-5 py-3 font-semibold text-indigo-600">
                                                ৳{fsp.campaign_price}
                                            </td>
                                            <td className="px-5 py-3">
                                                <span className="inline-block px-2 py-0.5 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700">
                                                    {fsp.discount_percentage}% OFF
                                                </span>
                                            </td>
                                            <td className="px-5 py-3 text-gray-600">{p?.qty ?? 0}</td>
                                            <td className="px-5 py-3 text-right">
                                                <button
                                                    type="button"
                                                    onClick={() => handleRemove(fsp.id)}
                                                    className="inline-flex items-center gap-1 text-xs text-red-600 hover:text-red-700 font-medium"
                                                    title="Remove"
                                                >
                                                    <Trash2 className="h-3.5 w-3.5" />
                                                    Remove
                                                </button>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            {/* ── Product Picker Modal ─────────────────────────────── */}
            {showPicker && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                    <div className="relative w-full max-w-xl max-h-[90vh] overflow-hidden rounded-xl bg-white shadow-2xl flex flex-col">
                        {/* Modal Header */}
                        <div className="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                            <h3 className="text-base font-semibold text-gray-900">
                                Add Product to Campaign
                            </h3>
                            <button
                                type="button"
                                onClick={() => {
                                    setShowPicker(false);
                                    setSelectedProductId(null);
                                    setCampaignPrice("");
                                    setSellerSku("");
                                }}
                                className="p-1 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100"
                            >
                                <X className="h-5 w-5" />
                            </button>
                        </div>

                        {/* Search */}
                        <div className="px-5 py-3 border-b border-gray-100">
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <input
                                    type="text"
                                    placeholder="Search your products..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    className="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400"
                                />
                            </div>
                        </div>

                        {/* Product List */}
                        <div className="flex-1 overflow-y-auto px-5 py-3 space-y-1 max-h-60">
                            {loadingProducts && (
                                <p className="text-center text-sm text-gray-400 py-4">
                                    Loading...
                                </p>
                            )}
                            {!loadingProducts && vendorProducts?.length === 0 && (
                                <p className="text-center text-sm text-gray-400 py-4">
                                    No products found
                                </p>
                            )}
                            {vendorProducts?.map((p: { id: number; ProductName: string; ViewProductImage: string | null; ProductRegularPrice: number; ProductSalePrice: number; qty: number; ProductSku: string }) => {
                                const alreadySubmitted = vendorSubmittedProducts.some(
                                    (fsp) => fsp.product_id === p.id,
                                );
                                return (
                                    <button
                                        key={p.id}
                                        type="button"
                                        disabled={alreadySubmitted}
                                        onClick={() => {
                                            setSelectedProductId(p.id);
                                            setCampaignPrice("");
                                            setSellerSku("");
                                        }}
                                        className={`w-full flex items-center gap-3 p-2.5 rounded-lg text-left transition-colors ${selectedProductId === p.id
                                                ? "bg-indigo-50 border border-indigo-200"
                                                : alreadySubmitted
                                                    ? "opacity-50 cursor-not-allowed bg-gray-50"
                                                    : "hover:bg-gray-50 border border-transparent"
                                            }`}
                                    >
                                        {p.ViewProductImage && (
                                            <img
                                                src={`${apiBase}/${p.ViewProductImage}`}
                                                alt=""
                                                className="h-9 w-9 rounded-md object-cover border"
                                                onError={(e) => {
                                                    (e.target as HTMLImageElement).style.display = "none";
                                                }}
                                            />
                                        )}
                                        <div className="flex-1 min-w-0">
                                            <p className="text-sm font-medium text-gray-900 truncate">
                                                {p.ProductName}
                                            </p>
                                            <p className="text-xs text-gray-500">
                                                ৳{p.ProductRegularPrice} · Stock: {p.qty}
                                            </p>
                                        </div>
                                        {alreadySubmitted && (
                                            <span className="text-xs text-gray-400 font-medium">
                                                Already added
                                            </span>
                                        )}
                                    </button>
                                );
                            })}
                        </div>

                        {/* Selected Product Form */}
                        {selectedProduct && (
                            <div className="border-t border-gray-200 px-5 py-4 bg-gray-50 space-y-3">
                                <p className="text-sm font-medium text-gray-700">
                                    Set pricing for:{" "}
                                    <span className="text-indigo-600">
                                        {selectedProduct.ProductName}
                                    </span>
                                </p>
                                <div className="grid grid-cols-2 gap-3">
                                    <div>
                                        <label className="text-xs font-medium text-gray-500">
                                            Campaign Price (৳) *
                                        </label>
                                        <input
                                            type="number"
                                            value={campaignPrice}
                                            onChange={(e) => setCampaignPrice(e.target.value)}
                                            placeholder={`Max: ${selectedProduct.ProductRegularPrice}`}
                                            min="0"
                                            className="w-full mt-1 px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 focus:outline-none"
                                        />
                                    </div>
                                    <div>
                                        <label className="text-xs font-medium text-gray-500">
                                            Seller SKU
                                        </label>
                                        <input
                                            type="text"
                                            value={sellerSku}
                                            onChange={(e) => setSellerSku(e.target.value)}
                                            placeholder="Optional"
                                            className="w-full mt-1 px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 focus:outline-none"
                                        />
                                    </div>
                                </div>
                                {campaignPrice && (
                                    <p className="text-xs text-gray-500">
                                        Discount:{" "}
                                        <span className="font-semibold text-emerald-600">
                                            {Math.max(
                                                0,
                                                Math.round(
                                                    ((selectedProduct.ProductRegularPrice -
                                                        parseFloat(campaignPrice)) /
                                                        selectedProduct.ProductRegularPrice) *
                                                    100,
                                                ),
                                            )}
                                            % OFF
                                        </span>{" "}
                                        (from ৳{selectedProduct.ProductRegularPrice})
                                    </p>
                                )}
                            </div>
                        )}

                        {/* Modal Footer */}
                        <div className="flex items-center justify-end gap-2 border-t border-gray-200 px-5 py-3">
                            <button
                                type="button"
                                onClick={() => {
                                    setShowPicker(false);
                                    setSelectedProductId(null);
                                }}
                                className="px-4 py-2 text-sm font-medium text-gray-700 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                disabled={!selectedProductId || !campaignPrice || submitting}
                                onClick={handleSubmit}
                                className="px-4 py-2 text-sm font-medium text-white bg-[#2d2a5d] rounded-lg hover:bg-[#3f3b7a] disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                {submitting ? "Submitting..." : "Submit Product"}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
