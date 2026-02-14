"use client";

import { useState } from "react";
import Link from "next/link";
import { useParams } from "next/navigation";
import WithVendorAuth from "../../WithVendorAuth";
import {
    useGetVendorProductStockQuery,
    useGetVendorWarehousesQuery,
    useAdjustVendorStockMutation,
    useUpdateVendorStockThresholdMutation,
    useAllocateVendorStockMutation,
} from "@/redux/api/vendorApi";
import type { StockMovement, WarehouseStockRow } from "@/redux/api/vendorApi";
import { toast } from "sonner";

const TYPE_LABELS: Record<string, { label: string; color: string }> = {
    purchase: { label: "Purchase", color: "text-emerald-700 bg-emerald-50" },
    sale: { label: "Sale", color: "text-blue-700 bg-blue-50" },
    return: { label: "Return", color: "text-purple-700 bg-purple-50" },
    adjustment: { label: "Adjustment", color: "text-amber-700 bg-amber-50" },
    transfer: { label: "Transfer", color: "text-indigo-700 bg-indigo-50" },
};

export default function VendorProductStockPage() {
    const params = useParams();
    const productId = Number(params.productId);

    const { data, isLoading, error } = useGetVendorProductStockQuery(productId);
    const { data: warehousesData } = useGetVendorWarehousesQuery(undefined);
    const [adjustStock, { isLoading: adjusting }] = useAdjustVendorStockMutation();
    const [updateThreshold, { isLoading: updatingThreshold }] = useUpdateVendorStockThresholdMutation();
    const [allocateStock, { isLoading: allocating }] = useAllocateVendorStockMutation();

    const warehouses = warehousesData?.data?.warehouses ?? [];

    // Adjust modal state
    const [showAdjust, setShowAdjust] = useState(false);
    const [adjustQty, setAdjustQty] = useState("");
    const [adjustType, setAdjustType] = useState("adjustment");
    const [adjustNote, setAdjustNote] = useState("");

    // Threshold edit
    const [editThreshold, setEditThreshold] = useState(false);
    const [thresholdVal, setThresholdVal] = useState("");

    // Allocate to warehouse
    const [showAllocate, setShowAllocate] = useState(false);
    const [allocateWarehouseId, setAllocateWarehouseId] = useState("");
    const [allocateQty, setAllocateQty] = useState("");

    const product = data?.data?.product;
    const movements = data?.data?.movements ?? [];
    const warehouseStock = data?.data?.warehouse_stock ?? [];

    const handleAdjust = async () => {
        const qty = parseInt(adjustQty);
        if (!qty || qty === 0) {
            toast.error("Enter a non-zero quantity");
            return;
        }
        try {
            const res = await adjustStock({ productId, quantity: qty, type: adjustType, note: adjustNote || undefined }).unwrap();
            toast.success(`Stock adjusted. New qty: ${res.data.new_qty}`);
            setShowAdjust(false);
            setAdjustQty("");
            setAdjustNote("");
        } catch (err: unknown) {
            const msg = (err as { data?: { message?: string } })?.data?.message || "Failed to adjust";
            toast.error(msg);
        }
    };

    const handleThresholdSave = async () => {
        const val = parseInt(thresholdVal);
        if (isNaN(val) || val < 0) {
            toast.error("Enter a valid threshold");
            return;
        }
        try {
            await updateThreshold({ productId, low_stock: val }).unwrap();
            toast.success("Low stock threshold updated");
            setEditThreshold(false);
        } catch {
            toast.error("Failed to update threshold");
        }
    };

    const handleAllocate = async () => {
        const warehouseId = parseInt(allocateWarehouseId);
        const qty = parseInt(allocateQty);
        if (!warehouseId || isNaN(qty) || qty < 0) {
            toast.error("Select a warehouse and enter a valid quantity (0 or more)");
            return;
        }
        try {
            await allocateStock({ productId, warehouse_id: warehouseId, quantity: qty }).unwrap();
            toast.success("Stock allocated to warehouse");
            setShowAllocate(false);
            setAllocateWarehouseId("");
            setAllocateQty("");
        } catch (err: unknown) {
            const msg = (err as { data?: { message?: string } })?.data?.message || "Failed to allocate";
            toast.error(msg);
        }
    };

    const formatDate = (dateStr: string) => {
        const d = new Date(dateStr);
        return d.toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" }) +
            " " + d.toLocaleTimeString("en-US", { hour: "2-digit", minute: "2-digit" });
    };

    if (isLoading) {
        return (
            <WithVendorAuth>
                <div className="flex items-center justify-center py-20">
                    <p className="text-sm text-gray-500">Loading stock details...</p>
                </div>
            </WithVendorAuth>
        );
    }

    if (error || !product) {
        return (
            <WithVendorAuth>
                <div className="flex flex-col items-center justify-center py-20">
                    <p className="text-sm text-red-600 mb-3">Product not found or failed to load.</p>
                    <Link href="/vendor/inventory" className="text-sm text-blue-600 hover:underline">
                        ← Back to Inventory
                    </Link>
                </div>
            </WithVendorAuth>
        );
    }

    const badge =
        (product.qty ?? 0) <= 0
            ? { label: "Out of Stock", cls: "bg-red-100 text-red-700" }
            : (product.qty ?? 0) <= (product.low_stock ?? 0)
                ? { label: "Low Stock", cls: "bg-amber-100 text-amber-700" }
                : { label: "In Stock", cls: "bg-emerald-100 text-emerald-700" };

    return (
        <WithVendorAuth>
            <div className="space-y-6">
                {/* Back + Header */}
                <div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
                    <Link href="/vendor/inventory" className="text-sm text-blue-600 hover:underline mb-3 inline-block">
                        ← Back to Inventory
                    </Link>
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <h1 className="text-xl font-bold text-gray-900">{product.ProductName}</h1>
                            <p className="text-sm text-gray-500 mt-0.5 font-mono">{product.ProductSku || "No SKU"}</p>
                        </div>
                        <button
                            onClick={() => setShowAdjust(true)}
                            className="px-4 py-2 text-sm bg-[#2d2a5d] text-white rounded-lg hover:bg-[#252947] font-medium"
                        >
                            Adjust Stock
                        </button>
                    </div>
                </div>

                {/* Stats Row */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div className="rounded-xl border p-4 bg-blue-50 border-blue-200">
                        <p className="text-sm font-medium text-blue-600">Current Stock</p>
                        <p className="text-2xl font-bold text-blue-800 mt-1">{product.qty ?? 0}</p>
                    </div>
                    <div className="rounded-xl border p-4 bg-amber-50 border-amber-200">
                        <p className="text-sm font-medium text-amber-600 flex items-center gap-2">
                            Low Stock Threshold
                            {!editThreshold && (
                                <button
                                    onClick={() => { setEditThreshold(true); setThresholdVal(String(product.low_stock ?? 0)); }}
                                    className="text-xs text-blue-600 hover:underline"
                                >
                                    Edit
                                </button>
                            )}
                        </p>
                        {editThreshold ? (
                            <div className="flex items-center gap-2 mt-1">
                                <input
                                    type="number"
                                    value={thresholdVal}
                                    onChange={(e) => setThresholdVal(e.target.value)}
                                    className="border rounded px-2 py-1 text-sm w-20"
                                />
                                <button
                                    disabled={updatingThreshold}
                                    onClick={handleThresholdSave}
                                    className="text-xs bg-amber-600 text-white px-2 py-1 rounded hover:bg-amber-700 disabled:opacity-50"
                                >
                                    Save
                                </button>
                                <button onClick={() => setEditThreshold(false)} className="text-xs text-gray-500 hover:underline">
                                    Cancel
                                </button>
                            </div>
                        ) : (
                            <p className="text-2xl font-bold text-amber-800 mt-1">{product.low_stock ?? 0}</p>
                        )}
                    </div>
                    <div className="rounded-xl border p-4 bg-gray-50 border-gray-200">
                        <p className="text-sm font-medium text-gray-600">Stock Status</p>
                        <span className={`inline-flex px-2 py-0.5 rounded text-xs font-medium mt-2 ${badge.cls}`}>
                            {badge.label}
                        </span>
                    </div>
                    <div className="rounded-xl border p-4 bg-indigo-50 border-indigo-200">
                        <p className="text-sm font-medium text-indigo-600">Movements Logged</p>
                        <p className="text-2xl font-bold text-indigo-800 mt-1">{data?.data?.pagination?.total ?? 0}</p>
                    </div>
                </div>

                {/* Warehouse Stock & Allocation */}
                <div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
                    <div className="flex items-center justify-between gap-4 mb-3">
                        <h2 className="text-sm font-semibold text-gray-800">Stock by Warehouse</h2>
                        {warehouses.length > 0 && (
                            <button
                                type="button"
                                onClick={() => setShowAllocate(true)}
                                className="text-sm font-medium text-[#2d2a5d] hover:text-[#252947]"
                            >
                                Allocate to warehouse
                            </button>
                        )}
                    </div>
                    {warehouseStock.length > 0 ? (
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                            {warehouseStock.map((ws: WarehouseStockRow) => (
                                <div key={ws.id} className="border rounded-lg p-3">
                                    <p className="text-xs text-gray-500">{ws.warehouse?.name ?? `Warehouse #${ws.warehouse_id}`}</p>
                                    <p className="text-lg font-bold text-gray-900 mt-0.5">{ws.stock}</p>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className="text-sm text-gray-500 py-2">
                            {warehouses.length === 0
                                ? "Add warehouses in Warehouses to allocate stock per location."
                                : "No stock allocated to warehouses yet. Use “Allocate to warehouse” to set quantities per location."}
                        </p>
                    )}
                </div>

                {/* Movement History */}
                <div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
                    <h2 className="text-sm font-semibold text-gray-800 mb-3">Stock Movement History</h2>
                    {movements.length === 0 ? (
                        <p className="text-sm text-gray-500 py-4 text-center">No stock movements recorded yet.</p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full text-sm">
                                <thead className="bg-gray-50 text-gray-600">
                                    <tr>
                                        <th className="px-3 py-2 text-left font-medium">Date</th>
                                        <th className="px-3 py-2 text-center font-medium">Type</th>
                                        <th className="px-3 py-2 text-right font-medium">Qty</th>
                                        <th className="px-3 py-2 text-left font-medium">Reference</th>
                                        <th className="px-3 py-2 text-left font-medium">Note</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {movements.map((m: StockMovement) => {
                                        const meta = TYPE_LABELS[m.type] ?? { label: m.type, color: "text-gray-700 bg-gray-50" };
                                        return (
                                            <tr key={m.id} className="hover:bg-gray-50">
                                                <td className="px-3 py-2 text-gray-600 whitespace-nowrap">
                                                    {formatDate(m.created_at)}
                                                </td>
                                                <td className="px-3 py-2 text-center">
                                                    <span className={`inline-flex px-2 py-0.5 rounded text-xs font-medium ${meta.color}`}>
                                                        {meta.label}
                                                    </span>
                                                </td>
                                                <td className={`px-3 py-2 text-right font-semibold ${m.quantity > 0 ? "text-emerald-600" : "text-red-600"}`}>
                                                    {m.quantity > 0 ? `+${m.quantity}` : m.quantity}
                                                </td>
                                                <td className="px-3 py-2 text-gray-600 text-xs">
                                                    {m.reference_type ? `${m.reference_type} #${m.reference_id}` : "—"}
                                                </td>
                                                <td className="px-3 py-2 text-gray-500 text-xs max-w-[200px] truncate">
                                                    {m.note || "—"}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>

            {/* Allocate to Warehouse Modal */}
            {showAllocate && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                    <div className="bg-white rounded-xl shadow-xl w-full max-w-md p-6 mx-4">
                        <h2 className="text-lg font-semibold text-gray-900 mb-1">Allocate to Warehouse</h2>
                        <p className="text-sm text-gray-500 mb-4">{product.ProductName}</p>
                        <p className="text-xs text-gray-400 mb-3">
                            Set the quantity to hold at the selected warehouse. This does not change total product stock.
                        </p>
                        <div className="space-y-3">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Warehouse</label>
                                <select
                                    value={allocateWarehouseId}
                                    onChange={(e) => setAllocateWarehouseId(e.target.value)}
                                    className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option value="">Select warehouse</option>
                                    {warehouses.map((w) => (
                                        <option key={w.id} value={w.id}>{w.name}{w.label ? ` (${w.label})` : ""}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                                <input
                                    type="number"
                                    min={0}
                                    value={allocateQty}
                                    onChange={(e) => setAllocateQty(e.target.value)}
                                    placeholder="0"
                                    className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>
                        </div>
                        <div className="flex justify-end gap-2 mt-5">
                            <button onClick={() => { setShowAllocate(false); setAllocateWarehouseId(""); setAllocateQty(""); }} className="px-4 py-2 text-sm border rounded-lg hover:bg-gray-50">
                                Cancel
                            </button>
                            <button
                                disabled={allocating}
                                onClick={handleAllocate}
                                className="px-4 py-2 text-sm bg-[#2d2a5d] text-white rounded-lg hover:bg-[#252947] disabled:opacity-50"
                            >
                                {allocating ? "Saving…" : "Allocate"}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Adjust Stock Modal */}
            {showAdjust && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                    <div className="bg-white rounded-xl shadow-xl w-full max-w-md p-6 mx-4">
                        <h2 className="text-lg font-semibold text-gray-900 mb-1">Adjust Stock</h2>
                        <p className="text-sm text-gray-500 mb-4">{product.ProductName}</p>
                        <p className="text-xs text-gray-400 mb-3">Current stock: <strong className="text-gray-700">{product.qty ?? 0}</strong></p>

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
                            <button onClick={() => setShowAdjust(false)} className="px-4 py-2 text-sm border rounded-lg hover:bg-gray-50">
                                Cancel
                            </button>
                            <button
                                disabled={adjusting}
                                onClick={handleAdjust}
                                className="px-4 py-2 text-sm bg-[#2d2a5d] text-white rounded-lg hover:bg-[#252947] disabled:opacity-50"
                            >
                                {adjusting ? "Saving..." : "Apply"}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </WithVendorAuth>
    );
}
