"use client";

import { useState } from "react";
import Link from "next/link";
import WithVendorAuth from "../WithVendorAuth";
import {
	useGetVendorProductsQuery,
	useDeleteVendorProductMutation,
	useUpdateVendorProductStatusMutation,
	useUpdateVendorProductStockStatusMutation,
} from "@/redux/api/vendorApi";
import { toast } from "sonner";

export default function VendorProductsPage() {
	const [search, setSearch] = useState("");
	const { data, isLoading, error } = useGetVendorProductsQuery(
		search ? { search } : undefined
	);
	const [deleteProduct, { isLoading: deleting }] = useDeleteVendorProductMutation();
	const [updateStatus, { isLoading: updatingStatus }] = useUpdateVendorProductStatusMutation();
	const [updateStockStatus, { isLoading: updatingStockStatus }] = useUpdateVendorProductStockStatusMutation();
	const products = data?.data?.products ?? [];

	const handleDelete = async (id: number, name: string) => {
		if (!confirm(`Delete "${name}"? This cannot be undone.`)) return;
		try {
			await deleteProduct(id).unwrap();
			toast.success("Product deleted");
		} catch {
			toast.error("Failed to delete product");
		}
	};

	const handleToggleStatus = async (id: number, current: string) => {
		const next = current === "Active" ? "Inactive" : "Active";
		try {
			await updateStatus({ id, status: next }).unwrap();
			toast.success(next === "Active" ? "Published" : "Unpublished");
		} catch {
			toast.error("Failed to update status");
		}
	};

	const handleToggleStock = async (id: number, current: number | undefined) => {
		const next = current ? 0 : 1;
		try {
			await updateStockStatus({ id, in_stock: next as 0 | 1 }).unwrap();
			toast.success(next ? "Marked as In Stock" : "Marked as Stock Out");
		} catch {
			toast.error("Failed to update stock status");
		}
	};

	return (
		<WithVendorAuth>
			<div className="space-y-6">
				<div className="rounded-xl bg-white p-4 sm:p-6 shadow-sm border border-gray-100 flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
					<div>
						<h1 className="text-2xl font-bold text-gray-900 mb-1">
							Products
						</h1>
						<p className="text-sm text-gray-600">
							Manage your catalog. You can add, edit, and publish products here.
						</p>
					</div>
					<div className="flex w-full flex-wrap items-center gap-2 sm:w-auto sm:justify-end">
						<Link
							href="/vendor/products/bulk-upload"
							className="inline-flex w-full items-center justify-center px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 sm:w-auto"
						>
							Bulk upload
						</Link>
						<Link
							href="/vendor/products/new"
							className="inline-flex w-full items-center justify-center px-4 py-2 rounded-lg bg-[#2d2a5d] text-white text-sm font-medium hover:bg-[#252947] sm:w-auto"
						>
							Add new product
						</Link>
					</div>
				</div>

				<div className="rounded-xl bg-white p-4 sm:p-6 shadow-sm border border-gray-100">
					<div className="mb-4 flex flex-col items-stretch gap-3 sm:flex-row sm:items-center sm:justify-between">
						<p className="text-sm font-medium text-gray-800">
							All products
						</p>
						<div className="flex w-full items-center gap-3 sm:w-auto">
							<input
								type="text"
								placeholder="Search product"
								value={search}
								onChange={(e) => setSearch(e.target.value)}
								className="w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:w-64"
							/>
						</div>
					</div>

					{isLoading ? (
						<p className="text-sm text-gray-500 py-4">Loading products...</p>
					) : error ? (
						<p className="text-sm text-red-600 py-4">Failed to load products.</p>
					) : (
						<div className="overflow-x-auto">
							<table className="min-w-full text-sm">
								<thead className="bg-gray-50 text-gray-600">
									<tr>
										<th className="px-3 py-2 text-left font-medium">Name</th>
										<th className="px-3 py-2 text-center font-medium">
											Approval
										</th>
										<th className="px-3 py-2 text-right font-medium">
											Current qty
										</th>
										<th className="px-3 py-2 text-right font-medium">
											Base price
										</th>
										<th className="px-3 py-2 text-center font-medium">
											Published
										</th>
										<th className="px-3 py-2 text-center font-medium">
											Stock
										</th>
										<th className="px-3 py-2 text-center font-medium">
											Options
										</th>
									</tr>
								</thead>
								<tbody className="divide-y divide-gray-100">
									{products.length === 0 ? (
										<tr>
											<td colSpan={7} className="px-3 py-6 text-center text-gray-500">
												No products yet. Add your first product above.
											</td>
										</tr>
									) : (
										products.map((p) => (
											<tr key={p.id} className="hover:bg-gray-50">
												<td className="px-3 py-2 align-middle text-gray-900">
													{p.ProductName}
												</td>
												<td className="px-3 py-2 align-middle text-center">
													<span
														className={`inline-flex px-2 py-0.5 rounded text-xs font-medium ${p.vendor_approval_status === "approved"
																? "bg-green-100 text-green-800"
																: p.vendor_approval_status === "rejected"
																	? "bg-red-100 text-red-800"
																	: "bg-amber-100 text-amber-800"
															}`}
													>
														{p.vendor_approval_status === "approved"
															? "Approved"
															: p.vendor_approval_status === "rejected"
																? "Rejected"
																: "Pending"}
													</span>
												</td>
												<td className="px-3 py-2 align-middle text-right text-gray-700">
													{p.qty ?? 0}
												</td>
												<td className="px-3 py-2 align-middle text-right text-gray-700">
													{p.ProductResellerPrice ?? 0}
												</td>
												<td className="px-3 py-2 align-middle text-center">
													<button
														type="button"
														disabled={updatingStatus}
														onClick={() => handleToggleStatus(p.id, p.status ?? "Inactive")}
														className={`inline-flex h-5 w-10 items-center rounded-full border focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 disabled:opacity-50 ${p.status === "Active"
																? "bg-emerald-500 border-emerald-500"
																: "bg-gray-200 border-gray-300"
															}`}
													>
														<span
															className={`inline-block h-4 w-4 rounded-full bg-white transform transition-transform ${p.status === "Active" ? "translate-x-5" : "translate-x-1"
																}`}
														/>
													</button>
												</td>
												<td className="px-3 py-2 align-middle text-center">
													<button
														type="button"
														disabled={updatingStockStatus}
														onClick={() => handleToggleStock(p.id, p.frature as number | undefined)}
														className={`inline-flex h-5 w-10 items-center rounded-full border focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 disabled:opacity-50 ${p.frature ? "bg-emerald-500 border-emerald-500" : "bg-red-500 border-red-500"
															}`}
													>
														<span
															className={`inline-block h-4 w-4 rounded-full bg-white transform transition-transform ${p.frature ? "translate-x-5" : "translate-x-1"
																}`}
														/>
													</button>
													<p className={`text-[10px] mt-0.5 font-bold ${p.frature ? "text-emerald-600" : "text-red-600"}`}>
														{p.frature ? "IN STOCK" : "STOCK OUT"}
													</p>
												</td>
												<td className="px-3 py-2 align-middle text-center">
													<div className="inline-flex items-center gap-2">
														<Link
															href={`/vendor/products/${p.id}/edit`}
															className="text-xs font-medium text-blue-600 hover:underline"
														>
															Edit
														</Link>
														<button
															type="button"
															disabled={deleting}
															onClick={() => handleDelete(p.id, p.ProductName)}
															className="text-xs font-medium text-red-600 hover:underline disabled:opacity-50"
														>
															Delete
														</button>
													</div>
												</td>
											</tr>
										))
									)}
								</tbody>
							</table>
						</div>
					)}
				</div>
			</div>
		</WithVendorAuth>
	);
}


