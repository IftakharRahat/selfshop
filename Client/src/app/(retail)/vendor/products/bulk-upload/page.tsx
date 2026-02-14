"use client";

import { useState, useCallback } from "react";
import Link from "next/link";
import WithVendorAuth from "../../WithVendorAuth";
import { toast } from "sonner";
import { useCreateVendorBulkUploadMutation } from "@/redux/api/vendorApi";
import { useAppSelector } from "@/redux/hooks";

const inputCls = "mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500";

export default function VendorBulkUploadPage() {
	const token = useAppSelector((s) => s.auth?.access_token);
	const [file, setFile] = useState<File | null>(null);
	const [upload, { isLoading }] = useCreateVendorBulkUploadMutation();
	const [result, setResult] = useState<{ created: number; errors: { row: number; message: string }[] } | null>(null);

	const downloadTemplate = useCallback(async () => {
		if (!token) {
			toast.error("Please log in first.");
			return;
		}
		const base = process.env.NEXT_PUBLIC_BASE_URL ?? "";
		const url = `${base}/vendor/products/bulk-template`;
		try {
			const res = await fetch(url, {
				headers: { Authorization: `Bearer ${token}`, Accept: "text/csv" },
			});
			if (!res.ok) throw new Error("Download failed");
			const blob = await res.blob();
			const a = document.createElement("a");
			a.href = URL.createObjectURL(blob);
			a.download = "vendor-products-template.csv";
			a.click();
			URL.revokeObjectURL(a.href);
			toast.success("Template downloaded");
		} catch {
			toast.error("Failed to download template");
		}
	}, [token]);

	const handleSubmit = async (e: React.FormEvent) => {
		e.preventDefault();
		if (!file) {
			toast.error("Choose a CSV file first.");
			return;
		}
		setResult(null);
		const formData = new FormData();
		formData.append("file", file);
		try {
			const data = await upload(formData).unwrap();
			setResult(data.data ?? { created: 0, errors: [] });
			toast.success(data.message ?? "Upload complete.");
		} catch (err: unknown) {
			const msg = err && typeof err === "object" && "data" in err && typeof (err as { data?: { message?: string } }).data?.message === "string"
				? (err as { data: { message: string } }).data.message
				: "Upload failed.";
			toast.error(msg);
		}
	};

	return (
		<WithVendorAuth>
			<div className="space-y-6">
				<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100 flex items-center justify-between gap-4">
					<div>
						<h1 className="text-2xl font-bold text-gray-900 mb-1">Bulk product upload</h1>
						<p className="text-sm text-gray-600">Upload a CSV to create multiple products at once.</p>
					</div>
					<Link href="/vendor/products" className="text-sm font-medium text-gray-600 hover:text-gray-900">Back to products</Link>
				</div>

				<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100 space-y-4">
					<div>
						<h2 className="text-sm font-semibold text-gray-900 mb-2">1. Download template</h2>
						<p className="text-xs text-gray-600 mb-2">Use the template to fill in product names, category IDs, brand ID, prices, qty, SKU, minimum qty, etc.</p>
						<button type="button" onClick={downloadTemplate} className="rounded-md bg-gray-800 text-white px-4 py-2 text-sm font-medium hover:bg-gray-700">
							Download CSV template
						</button>
					</div>
					<form onSubmit={handleSubmit} className="space-y-4">
						<h2 className="text-sm font-semibold text-gray-900">2. Upload CSV</h2>
						<label className="block">
							<span className="text-sm font-medium text-gray-700">CSV file</span>
							<input type="file" accept=".csv,.txt" onChange={(e) => setFile(e.target.files?.[0] ?? null)} className={inputCls} />
						</label>
						<button type="submit" disabled={isLoading || !file} className="rounded-lg bg-[#2d2a5d] text-white px-4 py-2 text-sm font-medium hover:bg-[#252947] disabled:opacity-50">
							{isLoading ? "Uploading…" : "Upload"}
						</button>
					</form>
					{result && (
						<div className="pt-4 border-t border-gray-200">
							<h3 className="text-sm font-semibold text-gray-900 mb-2">Result</h3>
							<p className="text-sm text-gray-700">Created: <strong>{result.created}</strong> product(s).</p>
							{result.errors.length > 0 && (
								<div className="mt-2">
									<p className="text-sm text-amber-700 font-medium">{result.errors.length} row(s) had errors:</p>
									<ul className="mt-1 text-xs text-gray-600 list-disc list-inside max-h-40 overflow-y-auto">
										{result.errors.map((e, i) => (
											<li key={i}>Row {e.row}: {e.message}</li>
										))}
									</ul>
								</div>
							)}
						</div>
					)}
				</div>
			</div>
		</WithVendorAuth>
	);
}
