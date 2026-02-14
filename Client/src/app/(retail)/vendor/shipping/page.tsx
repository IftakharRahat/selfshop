"use client";

import { useState } from "react";
import WithVendorAuth from "../WithVendorAuth";
import {
    useGetVendorShippingMethodsQuery,
    useCreateVendorShippingMethodMutation,
    useUpdateVendorShippingMethodMutation,
    useDeleteVendorShippingMethodMutation,
} from "@/redux/api/vendorApi";
import type { VendorShippingMethod } from "@/redux/api/vendorApi";
import { toast } from "sonner";

interface FormState {
    name: string;
    type: "flat" | "weight" | "zone";
    rate: string;
    min_order_amount: string;
    max_order_amount: string;
    per_kg_rate: string;
    description: string;
    is_default: boolean;
    is_active: boolean;
}

const EMPTY_FORM: FormState = {
    name: "",
    type: "flat",
    rate: "",
    min_order_amount: "",
    max_order_amount: "",
    per_kg_rate: "",
    description: "",
    is_default: false,
    is_active: true,
};

export default function VendorShippingPage() {
    const { data, isLoading, error } = useGetVendorShippingMethodsQuery(undefined);
    const [createMethod, { isLoading: creating }] = useCreateVendorShippingMethodMutation();
    const [updateMethod, { isLoading: updating }] = useUpdateVendorShippingMethodMutation();
    const [deleteMethod, { isLoading: deleting }] = useDeleteVendorShippingMethodMutation();

    const [modal, setModal] = useState<{ open: boolean; edit: VendorShippingMethod | null }>({ open: false, edit: null });
    const [form, setForm] = useState<FormState>(EMPTY_FORM);

    const methods = data?.data?.shipping_methods ?? [];

    const openCreate = () => {
        setForm(EMPTY_FORM);
        setModal({ open: true, edit: null });
    };

    const openEdit = (m: VendorShippingMethod) => {
        setForm({
            name: m.name,
            type: m.type,
            rate: String(m.rate),
            min_order_amount: m.min_order_amount != null ? String(m.min_order_amount) : "",
            max_order_amount: m.max_order_amount != null ? String(m.max_order_amount) : "",
            per_kg_rate: m.per_kg_rate != null ? String(m.per_kg_rate) : "",
            description: m.description ?? "",
            is_default: m.is_default,
            is_active: m.is_active,
        });
        setModal({ open: true, edit: m });
    };

    const setField = (key: keyof FormState, value: string | boolean) =>
        setForm((prev) => ({ ...prev, [key]: value }));

    const handleSubmit = async () => {
        if (!form.name.trim()) {
            toast.error("Name is required");
            return;
        }
        const rate = parseFloat(form.rate);
        if (isNaN(rate) || rate < 0) {
            toast.error("Enter a valid rate");
            return;
        }
        const payload = {
            name: form.name.trim(),
            type: form.type,
            rate,
            min_order_amount: form.min_order_amount ? parseFloat(form.min_order_amount) : undefined,
            max_order_amount: form.max_order_amount ? parseFloat(form.max_order_amount) : undefined,
            per_kg_rate: form.per_kg_rate ? parseFloat(form.per_kg_rate) : undefined,
            description: form.description.trim() || undefined,
            is_default: form.is_default,
            is_active: form.is_active,
        };
        try {
            if (modal.edit) {
                await updateMethod({ id: modal.edit.id, ...payload }).unwrap();
                toast.success("Shipping method updated");
            } else {
                await createMethod(payload).unwrap();
                toast.success("Shipping method created");
            }
            setModal({ open: false, edit: null });
        } catch (err: unknown) {
            const msg = (err as { data?: { message?: string } })?.data?.message || "Failed to save";
            toast.error(msg);
        }
    };

    const handleDelete = async (id: number, name: string) => {
        if (!confirm(`Delete "${name}"?`)) return;
        try {
            await deleteMethod(id).unwrap();
            toast.success("Shipping method deleted");
        } catch (err: unknown) {
            const msg = (err as { data?: { message?: string } })?.data?.message || "Failed to delete";
            toast.error(msg);
        }
    };

    return (
        <WithVendorAuth>
            <div className="space-y-6">
                <div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100 flex items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 mb-1">Shipping methods</h1>
                        <p className="text-sm text-gray-600">
                            Configure how you charge for delivery (flat rate, by weight, or by zone).
                        </p>
                    </div>
                    <button
                        onClick={openCreate}
                        className="inline-flex items-center px-4 py-2 rounded-lg bg-[#2d2a5d] text-white text-sm font-medium hover:bg-[#252947]"
                    >
                        Add method
                    </button>
                </div>

                <div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
                    {isLoading ? (
                        <p className="text-sm text-gray-500 py-8 text-center">Loading...</p>
                    ) : error ? (
                        <p className="text-sm text-red-600 py-8 text-center">Failed to load shipping methods.</p>
                    ) : methods.length === 0 ? (
                        <div className="py-12 text-center">
                            <p className="text-gray-500 mb-2">No shipping methods yet.</p>
                            <button onClick={openCreate} className="text-sm text-[#2d2a5d] hover:underline font-medium">
                                Add your first shipping method
                            </button>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full text-sm">
                                <thead className="bg-gray-50 text-gray-600">
                                    <tr>
                                        <th className="px-3 py-2 text-left font-medium">Name</th>
                                        <th className="px-3 py-2 text-left font-medium">Type</th>
                                        <th className="px-3 py-2 text-right font-medium">Rate</th>
                                        <th className="px-3 py-2 text-center font-medium">Default</th>
                                        <th className="px-3 py-2 text-center font-medium">Active</th>
                                        <th className="px-3 py-2 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {methods.map((m: VendorShippingMethod) => (
                                        <tr key={m.id} className="hover:bg-gray-50">
                                            <td className="px-3 py-2 font-medium">{m.name}</td>
                                            <td className="px-3 py-2 capitalize">{m.type}</td>
                                            <td className="px-3 py-2 text-right">
                                                ৳{Number(m.rate).toLocaleString()}
                                                {m.type === "weight" && m.per_kg_rate != null && (
                                                    <span className="text-gray-500 text-xs ml-1">+ ৳{Number(m.per_kg_rate).toLocaleString()}/kg</span>
                                                )}
                                            </td>
                                            <td className="px-3 py-2 text-center">
                                                {m.is_default ? <span className="text-[#2d2a5d] font-medium">Yes</span> : "—"}
                                            </td>
                                            <td className="px-3 py-2 text-center">
                                                <span className={m.is_active ? "text-green-600" : "text-gray-400"}>
                                                    {m.is_active ? "Yes" : "No"}
                                                </span>
                                            </td>
                                            <td className="px-3 py-2 text-right">
                                                <button
                                                    type="button"
                                                    onClick={() => openEdit(m)}
                                                    className="text-blue-600 hover:underline mr-2"
                                                >
                                                    Edit
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => handleDelete(m.id, m.name)}
                                                    className="text-red-600 hover:underline"
                                                >
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>

            {modal.open && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                    <div className="bg-white rounded-xl shadow-xl w-full max-w-md p-6 mx-4 max-h-[90vh] overflow-y-auto">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">
                            {modal.edit ? "Edit shipping method" : "Add shipping method"}
                        </h2>
                        <div className="space-y-3">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                <input
                                    type="text"
                                    value={form.name}
                                    onChange={(e) => setField("name", e.target.value)}
                                    className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    placeholder="e.g. Standard delivery"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Type</label>
                                <select
                                    value={form.type}
                                    onChange={(e) => setField("type", e.target.value as FormState["type"])}
                                    className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option value="flat">Flat rate</option>
                                    <option value="weight">By weight</option>
                                    <option value="zone">By zone</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Base rate (৳)
                                </label>
                                <input
                                    type="number"
                                    min={0}
                                    step={0.01}
                                    value={form.rate}
                                    onChange={(e) => setField("rate", e.target.value)}
                                    className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>
                            {form.type === "weight" && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Per kg rate (৳)
                                    </label>
                                    <input
                                        type="number"
                                        min={0}
                                        step={0.01}
                                        value={form.per_kg_rate}
                                        onChange={(e) => setField("per_kg_rate", e.target.value)}
                                        className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    />
                                </div>
                            )}
                            <div className="flex gap-4">
                                <div className="flex-1">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Min order (৳)</label>
                                    <input
                                        type="number"
                                        min={0}
                                        step={0.01}
                                        value={form.min_order_amount}
                                        onChange={(e) => setField("min_order_amount", e.target.value)}
                                        className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        placeholder="Optional"
                                    />
                                </div>
                                <div className="flex-1">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Max order (৳)</label>
                                    <input
                                        type="number"
                                        min={0}
                                        step={0.01}
                                        value={form.max_order_amount}
                                        onChange={(e) => setField("max_order_amount", e.target.value)}
                                        className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        placeholder="Optional"
                                    />
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                <input
                                    type="text"
                                    value={form.description}
                                    onChange={(e) => setField("description", e.target.value)}
                                    className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    placeholder="Optional"
                                />
                            </div>
                            <div className="flex items-center gap-4">
                                <label className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        checked={form.is_default}
                                        onChange={(e) => setField("is_default", e.target.checked)}
                                        className="rounded border-gray-300 text-[#2d2a5d] focus:ring-indigo-500"
                                    />
                                    <span className="text-sm text-gray-700">Default method</span>
                                </label>
                                <label className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        checked={form.is_active}
                                        onChange={(e) => setField("is_active", e.target.checked)}
                                        className="rounded border-gray-300 text-[#2d2a5d] focus:ring-indigo-500"
                                    />
                                    <span className="text-sm text-gray-700">Active</span>
                                </label>
                            </div>
                        </div>
                        <div className="flex justify-end gap-2 mt-5">
                            <button
                                onClick={() => setModal({ open: false, edit: null })}
                                className="px-4 py-2 text-sm border rounded-lg hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                disabled={creating || updating}
                                onClick={handleSubmit}
                                className="px-4 py-2 text-sm bg-[#2d2a5d] text-white rounded-lg hover:bg-[#252947] disabled:opacity-50"
                            >
                                {creating || updating ? "Saving…" : "Save"}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </WithVendorAuth>
    );
}
