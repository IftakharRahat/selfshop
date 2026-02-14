"use client";

import { useState } from "react";
import Link from "next/link";
import WithVendorAuth from "../WithVendorAuth";
import {
    useGetVendorWarehousesQuery,
    useCreateVendorWarehouseMutation,
    useUpdateVendorWarehouseMutation,
    useDeleteVendorWarehouseMutation,
} from "@/redux/api/vendorApi";
import type { VendorWarehouse } from "@/redux/api/vendorApi";
import { toast } from "sonner";

interface FormState {
    name: string;
    label: string;
    country: string;
    state: string;
    city: string;
    postcode: string;
    address_line_1: string;
    address_line_2: string;
    is_default: boolean;
}

const EMPTY_FORM: FormState = {
    name: "",
    label: "",
    country: "",
    state: "",
    city: "",
    postcode: "",
    address_line_1: "",
    address_line_2: "",
    is_default: false,
};

export default function VendorWarehousesPage() {
    const { data, isLoading, error } = useGetVendorWarehousesQuery(undefined);
    const [createWarehouse, { isLoading: creating }] = useCreateVendorWarehouseMutation();
    const [updateWarehouse, { isLoading: updating }] = useUpdateVendorWarehouseMutation();
    const [deleteWarehouse, { isLoading: deleting }] = useDeleteVendorWarehouseMutation();

    const [modal, setModal] = useState<{ open: boolean; editId: number | null }>({ open: false, editId: null });
    const [form, setForm] = useState<FormState>(EMPTY_FORM);

    const warehouses = data?.data?.warehouses ?? [];

    const openCreate = () => {
        setForm(EMPTY_FORM);
        setModal({ open: true, editId: null });
    };

    const openEdit = (w: VendorWarehouse) => {
        setForm({
            name: w.name,
            label: w.label ?? "",
            country: w.country ?? "",
            state: w.state ?? "",
            city: w.city ?? "",
            postcode: w.postcode ?? "",
            address_line_1: w.address_line_1 ?? "",
            address_line_2: w.address_line_2 ?? "",
            is_default: w.is_default,
        });
        setModal({ open: true, editId: w.id });
    };

    const handleSubmit = async () => {
        if (!form.name.trim()) {
            toast.error("Warehouse name is required");
            return;
        }
        try {
            if (modal.editId) {
                await updateWarehouse({ id: modal.editId, ...form }).unwrap();
                toast.success("Warehouse updated");
            } else {
                await createWarehouse(form).unwrap();
                toast.success("Warehouse created");
            }
            setModal({ open: false, editId: null });
        } catch (err: unknown) {
            const msg = (err as { data?: { message?: string } })?.data?.message || "Failed to save";
            toast.error(msg);
        }
    };

    const handleDelete = async (id: number, name: string) => {
        if (!confirm(`Delete warehouse "${name}"?`)) return;
        try {
            await deleteWarehouse(id).unwrap();
            toast.success("Warehouse deleted");
        } catch (err: unknown) {
            const msg = (err as { data?: { message?: string } })?.data?.message || "Failed to delete";
            toast.error(msg);
        }
    };

    const setField = (key: keyof FormState, val: string | boolean) =>
        setForm((prev) => ({ ...prev, [key]: val }));

    return (
        <WithVendorAuth>
            <div className="space-y-6">
                {/* Header */}
                <div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100 flex items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 mb-1">Warehouses</h1>
                        <p className="text-sm text-gray-600">
                            Manage your warehouse locations for stock allocation.
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Link
                            href="/vendor/inventory"
                            className="inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50"
                        >
                            ← Inventory
                        </Link>
                        <button
                            onClick={openCreate}
                            className="inline-flex items-center px-4 py-2 rounded-lg bg-[#2d2a5d] text-white text-sm font-medium hover:bg-[#252947]"
                        >
                            Add Warehouse
                        </button>
                    </div>
                </div>

                {/* Warehouse List */}
                <div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
                    {isLoading ? (
                        <p className="text-sm text-gray-500 py-8 text-center">Loading warehouses...</p>
                    ) : error ? (
                        <p className="text-sm text-red-600 py-8 text-center">Failed to load warehouses.</p>
                    ) : warehouses.length === 0 ? (
                        <div className="py-12 text-center">
                            <p className="text-gray-500 mb-2">No warehouses yet.</p>
                            <button onClick={openCreate} className="text-sm text-[#2d2a5d] hover:underline font-medium">
                                Create your first warehouse
                            </button>
                        </div>
                    ) : (
                        <div className="grid gap-4 md:grid-cols-2">
                            {warehouses.map((w: VendorWarehouse) => (
                                <div
                                    key={w.id}
                                    className={`border rounded-xl p-4 transition-shadow hover:shadow-md ${w.is_default ? "border-indigo-300 bg-indigo-50/30" : "border-gray-200"
                                        }`}
                                >
                                    <div className="flex items-start justify-between">
                                        <div>
                                            <h3 className="font-semibold text-gray-900 flex items-center gap-2">
                                                {w.name}
                                                {w.is_default && (
                                                    <span className="text-[10px] bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded font-medium">
                                                        DEFAULT
                                                    </span>
                                                )}
                                                {!w.is_active && (
                                                    <span className="text-[10px] bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded font-medium">
                                                        INACTIVE
                                                    </span>
                                                )}
                                            </h3>
                                            {w.label && <p className="text-xs text-gray-500 mt-0.5">{w.label}</p>}
                                        </div>
                                        <div className="flex gap-2">
                                            <button
                                                onClick={() => openEdit(w)}
                                                className="text-xs text-blue-600 hover:underline font-medium"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                disabled={deleting}
                                                onClick={() => handleDelete(w.id, w.name)}
                                                className="text-xs text-red-600 hover:underline font-medium disabled:opacity-50"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                    <div className="mt-2 text-xs text-gray-500 space-y-0.5">
                                        {w.address_line_1 && <p>{w.address_line_1}</p>}
                                        {w.address_line_2 && <p>{w.address_line_2}</p>}
                                        {(w.city || w.state || w.postcode) && (
                                            <p>{[w.city, w.state, w.postcode].filter(Boolean).join(", ")}</p>
                                        )}
                                        {w.country && <p>{w.country}</p>}
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>

            {/* Create / Edit Modal */}
            {modal.open && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                    <div className="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 mx-4 max-h-[90vh] overflow-y-auto">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">
                            {modal.editId ? "Edit Warehouse" : "New Warehouse"}
                        </h2>

                        <div className="space-y-3">
                            <div className="grid grid-cols-2 gap-3">
                                <div className="col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                                    <input
                                        type="text"
                                        value={form.name}
                                        onChange={(e) => setField("name", e.target.value)}
                                        className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        placeholder="Main Warehouse"
                                    />
                                </div>
                                <div className="col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Label</label>
                                    <input
                                        type="text"
                                        value={form.label}
                                        onChange={(e) => setField("label", e.target.value)}
                                        className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        placeholder="Office / Shop / Storage"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Country</label>
                                    <input
                                        type="text"
                                        value={form.country}
                                        onChange={(e) => setField("country", e.target.value)}
                                        className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">State</label>
                                    <input
                                        type="text"
                                        value={form.state}
                                        onChange={(e) => setField("state", e.target.value)}
                                        className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">City</label>
                                    <input
                                        type="text"
                                        value={form.city}
                                        onChange={(e) => setField("city", e.target.value)}
                                        className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Postcode</label>
                                    <input
                                        type="text"
                                        value={form.postcode}
                                        onChange={(e) => setField("postcode", e.target.value)}
                                        className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    />
                                </div>
                                <div className="col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Address Line 1</label>
                                    <input
                                        type="text"
                                        value={form.address_line_1}
                                        onChange={(e) => setField("address_line_1", e.target.value)}
                                        className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    />
                                </div>
                                <div className="col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Address Line 2</label>
                                    <input
                                        type="text"
                                        value={form.address_line_2}
                                        onChange={(e) => setField("address_line_2", e.target.value)}
                                        className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    />
                                </div>
                                <div className="col-span-2">
                                    <label className="inline-flex items-center gap-2 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={form.is_default}
                                            onChange={(e) => setField("is_default", e.target.checked)}
                                            className="accent-indigo-600"
                                        />
                                        <span className="text-sm text-gray-700">Set as default warehouse</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div className="flex justify-end gap-2 mt-5">
                            <button
                                onClick={() => setModal({ open: false, editId: null })}
                                className="px-4 py-2 text-sm border rounded-lg hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                disabled={creating || updating}
                                onClick={handleSubmit}
                                className="px-4 py-2 text-sm bg-[#2d2a5d] text-white rounded-lg hover:bg-[#252947] disabled:opacity-50"
                            >
                                {creating || updating ? "Saving..." : modal.editId ? "Update" : "Create"}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </WithVendorAuth>
    );
}
