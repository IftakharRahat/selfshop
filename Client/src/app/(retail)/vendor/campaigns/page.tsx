"use client";

import { useVendorCampaignsQuery, type Campaign } from "@/redux/api/campaignApi";
import Link from "next/link";
import { useEffect, useState } from "react";
import { Zap, Clock, Users, Package, ArrowRight } from "lucide-react";

/* ── Countdown timer hook ─────────────────────────────────────────────── */
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

/* ── Campaign Card ────────────────────────────────────────────────────── */
function CampaignCard({ campaign }: { campaign: Campaign }) {
    const { timeLeft, expired } = useCountdown(campaign.end_time);
    const regDeadline = useCountdown(campaign.registration_deadline);
    const apiBase = process.env.NEXT_PUBLIC_BASE_URL?.replace("/api", "") ?? "";

    const bannerUrl = campaign.banner_image
        ? `${apiBase}/${campaign.banner_image}`
        : null;

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

    return (
        <div className="group relative overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition-all hover:shadow-md hover:border-indigo-200">
            {/* Banner */}
            <div className="relative h-40 bg-gradient-to-br from-indigo-500 to-purple-600 overflow-hidden">
                {bannerUrl ? (
                    <img
                        src={bannerUrl}
                        alt={campaign.title}
                        className="w-full h-full object-cover"
                    />
                ) : (
                    <div className="flex items-center justify-center h-full">
                        <Zap className="h-12 w-12 text-white/30" />
                    </div>
                )}
                {/* Countdown badge */}
                <div className={`absolute top-3 right-3 px-3 py-1.5 rounded-full text-xs font-semibold backdrop-blur-sm ${expired
                        ? "bg-red-500/90 text-white"
                        : "bg-white/90 text-gray-800"
                    }`}>
                    <Clock className="h-3 w-3 inline-block mr-1 -mt-0.5" />
                    {expired ? "Ended" : timeLeft}
                </div>
                {/* Status badge */}
                <div className="absolute top-3 left-3 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/90 text-white backdrop-blur-sm">
                    {campaign.status}
                </div>
            </div>

            {/* Content */}
            <div className="p-4 space-y-3">
                <h3 className="text-base font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors line-clamp-1">
                    {campaign.title}
                </h3>

                <div className="grid grid-cols-2 gap-2 text-xs text-gray-500">
                    <div>
                        <span className="font-medium text-gray-700">Start:</span>{" "}
                        {formatDate(campaign.start_time)}
                    </div>
                    <div>
                        <span className="font-medium text-gray-700">End:</span>{" "}
                        {formatDate(campaign.end_time)}
                    </div>
                </div>

                {/* Registration deadline */}
                {campaign.registration_deadline && (
                    <div className={`flex items-center gap-1.5 text-xs px-2.5 py-1.5 rounded-md ${regDeadline.expired
                            ? "bg-red-50 text-red-600"
                            : "bg-amber-50 text-amber-700"
                        }`}>
                        <Clock className="h-3 w-3" />
                        <span className="font-medium">Registration:</span>
                        {regDeadline.expired ? "Closed" : regDeadline.timeLeft}
                    </div>
                )}

                {/* Stats */}
                <div className="flex items-center gap-4 text-xs text-gray-500 pt-1 border-t border-gray-100">
                    <div className="flex items-center gap-1">
                        <Package className="h-3.5 w-3.5" />
                        {campaign.products_count} products
                    </div>
                    {campaign.vendor_product_count !== undefined && campaign.vendor_product_count > 0 && (
                        <div className="flex items-center gap-1 text-indigo-600 font-medium">
                            <Users className="h-3.5 w-3.5" />
                            {campaign.vendor_product_count} submitted
                        </div>
                    )}
                </div>

                {/* CTA */}
                <Link
                    href={`/vendor/campaigns/${campaign.id}`}
                    className="flex items-center justify-center gap-2 w-full px-4 py-2.5 rounded-lg text-sm font-medium text-white bg-[#2d2a5d] hover:bg-[#3f3b7a] transition-colors"
                >
                    {expired || regDeadline.expired ? "View Details" : "Submit Deal"}
                    <ArrowRight className="h-4 w-4" />
                </Link>
            </div>
        </div>
    );
}

/* ── Page Component ───────────────────────────────────────────────────── */
export default function CampaignsPage() {
    const { data: campaigns, isLoading, error } = useVendorCampaignsQuery();

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-xl font-bold text-gray-900 sm:text-2xl">
                        Campaign Events
                    </h1>
                    <p className="mt-1 text-sm text-gray-500">
                        Join active campaigns and submit your products with special pricing
                    </p>
                </div>
                <div className="flex items-center gap-2 px-3 py-1.5 rounded-full bg-indigo-50 text-indigo-700 text-xs font-medium">
                    <Zap className="h-3.5 w-3.5" />
                    {campaigns?.length ?? 0} campaigns
                </div>
            </div>

            {/* Loading */}
            {isLoading && (
                <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    {[1, 2, 3].map((i) => (
                        <div
                            key={i}
                            className="h-80 rounded-xl border border-gray-200 bg-gray-50 animate-pulse"
                        />
                    ))}
                </div>
            )}

            {/* Error */}
            {error && (
                <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-600">
                    Failed to load campaigns. Please try again later.
                </div>
            )}

            {/* Empty */}
            {!isLoading && !error && campaigns?.length === 0 && (
                <div className="text-center py-16 rounded-xl border border-dashed border-gray-300 bg-white">
                    <Zap className="h-10 w-10 mx-auto text-gray-300 mb-3" />
                    <p className="text-gray-500 font-medium">
                        No active campaigns available
                    </p>
                    <p className="text-sm text-gray-400 mt-1">
                        Check back later for new campaign events
                    </p>
                </div>
            )}

            {/* Campaigns Grid */}
            {!isLoading && campaigns && campaigns.length > 0 && (
                <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    {campaigns.map((campaign) => (
                        <CampaignCard key={campaign.id} campaign={campaign} />
                    ))}
                </div>
            )}
        </div>
    );
}
