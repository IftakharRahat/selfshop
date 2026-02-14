"use client";

import { useState } from "react";
import Link from "next/link";
import WithVendorAuth from "../WithVendorAuth";
import {
    useGetVendorInventoryQuery,
    useLazyGetVendorInventoryExportQuery,
    useAdjustVendorStockMutation,
} from "@/redux/api/vendorApi";
import type { VendorProduct } from "@/redux/api/vendorApi";
import { toast } from "sonner";

const STATUS_TABS = [
    { key: "all", label: "All Products" },
    { key: "in_stock", label: "In Stock" },
    { key: "low_stock", label: "Low Stock" },
    { key: "out_of_stock", label: "Out of Stock" },
] as const;

export default function VendorInventoryPage() {
    const [search, setSearch] = useState("");
    const [stockStatus, setStockStatus] = useState<string>("all");
    const [page, setPage] = useState(1);

    // Adjust stock modal
    const [adjustModal, setAdjustModal] = useState<{
        open: boolean;
        productId: number;
        productName: string;
    }>({ open: false, productId: 0, productName: "" });
    const [adjustQty, setAdjustQty] = useState("");
    const [adjustType, setAdjustType] = useState("adjustment");
    const [adjustNote, setAdjustNote] = useState("");

    const { data, isLoading, error } = useGetVendorInventoryQuery({
        search: search || undefined,
        stock_status: stockStatus === "all" ? undefined : stockStatus,
        page,
        per_page: 20,
    });
    const [adjustStock, { isLoading: adjusting }] = useAdjustVendorStockMutation();
    const [triggerExport, { isLoading: exporting }] = useLazyGetVendorInventoryExportQuery();

    const summary = data?.data?.summary;
    const products = data?.data?.products ?? [];
    const pagination = data?.data?.pagination;

    const getStockBadge = (qty: number, lowStock: number) => {
        if (qty <= 0) return { label: "Out of Stock", cls: "bg-red-100 text-red-700" };
        if (qty <= lowStock) return { label: "Low Stock", cls: "bg-amber-100 text-amber-700" };
        return { label: "In Stock", cls: "bg-emerald-100 text-emerald-700" };
    };

    const handleAdjustSubmit = async () => {
        const qty = parseInt(adjustQty);
        if (!qty || qty === 0) {
            toast.error("Enter a non-zero quantity");
            return;
        }
        try {
            await adjustStock({
                productId: adjustModal.productId,
                quantity: qty,
                type: adjustType,
                note: adjustNote || undefined,
            }).unwrap();
            toast.success("Stock adjusted successfully");
            setAdjustModal({ open: false, productId: 0, productName: "" });
            setAdjustQty("");
            setAdjustNote("");
        } catch (err: unknown) {
            const msg = (err as { data?: { message?: string } })?.data?.message || "Failed to adjust stock";
            toast.error(msg);
        }
    };

    const handleExport = async () => {
        try {
            const blob = await triggerExport(undefined).unwrap();
            const url = URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = `inventory_${new Date().toISOString().slice(0, 10)}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            toast.success("Inventory exported");
        } catch {
            toast.error("Export failed. Make sure you are logged in.");
        }
    };

    return (
        <WithVendorAuth>
            <div className="space-y-6">
                {/* Header */}
                <div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100 flex items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 mb-1">
                            Inventory Management
                        </h1>
                        <p className="text-sm text-gray-600">
                            Track stock levels, manage inventory, and set low-stock alerts.
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <button
                            onClick={handleExport}
                            disabled={exporting}
                            className="inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 disabled:opacity-50"
                        >
                            {exporting ? "Exporting…" : "Export CSV"}
                        </button>
                        <Link
                            href="/vendor/warehouses"
                            className="inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50"
                        >
                            Warehouses
                        </Link>
                    </div>
                </div>

                {/* Summary Cards */}
                {summary && (
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        {[
                            { label: "Total Products", value: summary.total, color: "bg-blue-50 text-blue-700 border-blue-200" },
                            { label: "In Stock", value: summary.in_stock, color: "bg-emerald-50 text-emerald-700 border-emerald-200" },
                            { label: "Low Stock", value: summary.low_stock, color: "bg-amber-50 text-amber-700 border-amber-200" },
                            { label: "Out of Stock", value: summary.out_of_stock, color: "bg-red-50 text-red-700 border-red-200" },
                        ].map((card) => (
                            <div
                                key={card.label}
                                className={`rounded-xl border p-4 ${card.color}`}
                            >
                                <p className="text-sm font-medium opacity-80">{card.label}</p>
                                <p className="text-2xl font-bold mt-1">{card.value}</p>
                            </div>
                        ))}
                    </div>
                )}

                {/* Table */}
                <div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
                    {/* Tabs + Search */}
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                        <div className="flex gap-1 bg-gray-100 rounded-lg p-1">
                            {STATUS_TABS.map((tab) => (
                                <button
                                    key={tab.key}
                                    onClick={() => { setStockStatus(tab.key); setPage(1); }}
                                    className={`px-3 py-1.5 text-xs font-medium rounded-md transition-colors ${stockStatus === tab.key
                                        ? "bg-white text-gray-900 shadow-sm"
                                        : "text-gray-600 hover:text-gray-900"
                                        }`}
                                >
                                    {tab.label}
                                    {summary && tab.key !== "all" && (
                                        <span className="ml-1 opacity-60">
                                            ({tab.key === "in_stock" ? summary.in_stock : tab.key === "low_stock" ? summary.low_stock : summary.out_of_stock})
                                        </span>
                                    )}
                                </button>
                            ))}
                        </div>
                        <input
                            type="text"
                            placeholder="Search by name or SKU..."
                            value={search}
                            onChange={(e) => { setSearch(e.target.value); setPage(1); }}
                            className="border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 w-full sm:w-64"
                        />
                    </div>

                    {isLoading ? (
                        <p className="text-sm text-gray-500 py-8 text-center">Loading inventory...</p>
                    ) : error ? (
                        <p className="text-sm text-red-600 py-8 text-center">Failed to load inventory.</p>
                    ) : (
                        <>
                            <div className="overflow-x-auto">
                                <table className="min-w-full text-sm">
                                    <thead className="bg-gray-50 text-gray-600">
                                        <tr>
                                            <th className="px-3 py-2 text-left font-medium">Product</th>
                                            <th className="px-3 py-2 text-left font-medium">SKU</th>
                                            <th className="px-3 py-2 text-right font-medium">Current Qty</th>
                                            <th className="px-3 py-2 text-right font-medium">Low Stock</th>
                                            <th className="px-3 py-2 text-center font-medium">Status</th>
                                            <th className="px-3 py-2 text-center font-medium">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100">
                                        {products.length === 0 ? (
                                            <tr>
                                                <td colSpan={6} className="px-3 py-8 text-center text-gray-500">
                                                    No products found for this filter.
                                                </td>
                                            </tr>
                                        ) : (
                                            products.map((p: VendorProduct) => {
                                                const badge = getStockBadge(p.qty ?? 0, p.low_stock ?? 0);
                                                return (
                                                    <tr key={p.id} className="hover:bg-gray-50">
                                                        <td className="px-3 py-2 align-middle">
                                                            <Link
                                                                href={`/vendor/inventory/${p.id}`}
                                                                className="text-gray-900 hover:text-[#2d2a5d] font-medium"
                                                            >
                                                                {p.ProductName}
                                                            </Link>
                                                        </td>
                                                        <td className="px-3 py-2 align-middle text-gray-600 font-mono text-xs">
                                                            {p.ProductSku || "—"}
                                                        </td>
                                                        <td className="px-3 py-2 align-middle text-right font-semibold text-gray-900">
                                                            {p.qty ?? 0}
                                                        </td>
                                                        <td className="px-3 py-2 align-middle text-right text-gray-600">
                                                            {p.low_stock ?? 0}
                                                        </td>
                                                        <td className="px-3 py-2 align-middle text-center">
                                                            <span className={`inline-flex px-2 py-0.5 rounded text-xs font-medium ${badge.cls}`}>
                                                                {badge.label}
                                                            </span>
                                                        </td>
                                                        <td className="px-3 py-2 align-middle text-center">
                                                            <div className="inline-flex items-center gap-2">
                                                                <Link
                                                                    href={`/vendor/inventory/${p.id}`}
                                                                    className="text-xs font-medium text-blue-600 hover:underline"
                                                                >
                                                                    Details
                                                                </Link>
                                                                <button
                                                                    type="button"
                                                                    onClick={() => setAdjustModal({ open: true, productId: p.id, productName: p.ProductName })}
                                                                    className="text-xs font-medium text-indigo-600 hover:underline"
                                                                >
                                                                    Adjust
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                );
                                            })
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            {/* Pagination */}
                            {pagination && pagination.last_page > 1 && (
                                <div className="flex items-center justify-between mt-4 pt-3 border-t border-gray-100">
                                    <p className="text-xs text-gray-500">
                                        Page {pagination.current_page} of {pagination.last_page} ({pagination.total} products)
                                    </p>
                                    <div className="flex gap-1">
                                        <button
                                            disabled={page <= 1}
                                            onClick={() => setPage(page - 1)}
                                            className="px-3 py-1 text-xs border rounded disabled:opacity-40 hover:bg-gray-50"
                                        >
                                            Prev
                                        </button>
                                        <button
                                            disabled={page >= pagination.last_page}
                                            onClick={() => setPage(page + 1)}
                                            className="px-3 py-1 text-xs border rounded disabled:opacity-40 hover:bg-gray-50"
                                        >
                                            Next
                                        </button>
                                    </div>
                                </div>
                            )}
                        </>
                    )}
                </div>
            </div>

            {/* Adjust Stock Modal */}
            {adjustModal.open && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                    <div className="bg-white rounded-xl shadow-xl w-full max-w-md p-6 mx-4">
                        <h2 className="text-lg font-semibold text-gray-900 mb-1">Adjust Stock</h2>
                        <p className="text-sm text-gray-500 mb-4">{adjustModal.productName}</p>

                        <div className="space-y-3">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Quantity (+/-)</label>
                                <input
                                    type="number"
                                    value={adjustQty}
                                    onChange={(e) => setAdjustQty(e.target.value)}
                                    placeholder="e.g. +10 or -5"
                                    className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                />
                                <p className="text-xs text-gray-400 mt-1">Positive to add, negative to subtract.</p>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                                <select
                                    value={adjustType}
                                    onChange={(e) => setAdjustType(e.target.value)}
                                    className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option value="adjustment">Adjustment</option>
                                    <option value="purchase">Purchase / Restock</option>
                                    <option value="return">Return</option>
                                </select>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Note (optional)</label>
                                <input
                                    type="text"
                                    value={adjustNote}
                                    onChange={(e) => setAdjustNote(e.target.value)}
                                    placeholder="e.g. Received from supplier"
                                    className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>
                        </div>

                        <div className="flex justify-end gap-2 mt-5">
                            <button
                                onClick={() => setAdjustModal({ open: false, productId: 0, productName: "" })}
                                className="px-4 py-2 text-sm border rounded-lg hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                disabled={adjusting}
                                onClick={handleAdjustSubmit}
                                className="px-4 py-2 text-sm bg-[#2d2a5d] text-white rounded-lg hover:bg-[#252947] disabled:opacity-50"
                            >
                                {adjusting ? "Saving..." : "Apply Adjustment"}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </WithVendorAuth>
    );
}
