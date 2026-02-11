"use client";

import { useState } from "react";
import WithVendorAuth from "../../vendor/WithVendorAuth";
import { toast } from "sonner";
import {
	useGetVendorCategoryDiscountsQuery,
	useSetVendorCategoryDiscountMutation,
} from "@/redux/api/vendorApi";
import { getImageUrl } from "@/lib/utils";

/** True if value looks like an image path (file path or URL), not HTML/SVG. */
function isImagePath(value: string | null | undefined): boolean {
	if (!value || typeof value !== "string") return false;
	const v = value.trim();
	return (
		v.startsWith("http") ||
		/\.(webp|png|jpg|jpeg|gif|svg)$/i.test(v) ||
		/^(public\/|\.\/|\/)/.test(v) ||
		v.includes("/images/")
	);
}

export default function VendorCategoryDiscountPage() {
	const { data, isLoading } = useGetVendorCategoryDiscountsQuery();
	const [setDiscount] = useSetVendorCategoryDiscountMutation();
	const categories = data?.data?.categories ?? [];

	// Local editing state per category
	const [edits, setEdits] = useState<
		Record<number, { discount_percent: string; start_date: string; end_date: string }>
	>({});
	const [savingId, setSavingId] = useState<number | null>(null);
	const [search, setSearch] = useState("");

	const getEdit = (catId: number) => {
		const cat = categories.find((c) => c.id === catId);
		return (
			edits[catId] ?? {
				discount_percent: String(cat?.discount_percent ?? 0),
				start_date: cat?.start_date ?? "",
				end_date: cat?.end_date ?? "",
			}
		);
	};

	const updateEdit = (catId: number, field: string, value: string) => {
		setEdits((prev) => ({
			...prev,
			[catId]: { ...getEdit(catId), [field]: value },
		}));
	};

	const handleSet = async (catId: number) => {
		const e = getEdit(catId);
		setSavingId(catId);
		try {
			await setDiscount({
				categoryId: catId,
				discount_percent: parseFloat(e.discount_percent) || 0,
				start_date: e.start_date || null,
				end_date: e.end_date || null,
			}).unwrap();
			toast.success("Discount updated");
		} catch {
			toast.error("Failed to update discount");
		} finally {
			setSavingId(null);
		}
	};

	const filtered = search
		? categories.filter((c) =>
				c.category_name.toLowerCase().includes(search.toLowerCase())
			)
		: categories;

	return (
		<WithVendorAuth>
			<div className="space-y-6">
				{/* Header */}
				<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
					<h1 className="text-2xl font-bold text-gray-900 mb-1">
						Set Category Base Product Discount
					</h1>
				</div>

				{/* Table */}
				<div className="rounded-xl bg-white shadow-sm border border-gray-100 overflow-hidden">
					<div className="flex items-center justify-between px-6 py-4 border-b border-gray-100">
						<h2 className="text-lg font-semibold text-gray-900">Categories</h2>
						<input
							type="text"
							placeholder="Type name & Enter"
							value={search}
							onChange={(e) => setSearch(e.target.value)}
							className="rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-pink-500 w-56"
						/>
					</div>

					{isLoading ? (
						<p className="text-center text-gray-500 py-10 text-sm">Loading categories...</p>
					) : filtered.length === 0 ? (
						<p className="text-center text-gray-500 py-10 text-sm">No categories found.</p>
					) : (
						<div className="overflow-x-auto">
							<table className="min-w-full text-sm">
								<thead>
									<tr className="border-b border-gray-100 bg-gray-50 text-gray-600 text-xs uppercase tracking-wider">
										<th className="px-6 py-3 text-left font-semibold w-12">#</th>
										<th className="px-6 py-3 text-left font-semibold w-[72px]">Icon</th>
										<th className="px-6 py-3 text-left font-semibold">Name</th>
										<th className="px-6 py-3 text-center font-semibold" colSpan={2}>
											Discount
										</th>
										<th className="px-6 py-3 text-left font-semibold">
											Discount Date Range
										</th>
										<th className="px-6 py-3 text-center font-semibold w-28">
											Action
										</th>
									</tr>
								</thead>
								<tbody>
									{filtered.map((cat, idx) => {
										const e = getEdit(cat.id);
										return (
											<tr
												key={cat.id}
												className="border-b border-gray-50 hover:bg-gray-50/50 align-top"
											>
												<td className="px-6 py-4 text-gray-700 align-middle">{idx + 1}</td>
												<td className="px-6 py-4 align-middle w-[72px]">
													<div className="w-10 h-10 flex items-center justify-center rounded-md bg-gray-50 border border-gray-100 overflow-hidden shrink-0">
														{cat.category_icon ? (
															isImagePath(cat.category_icon) ? (
																<img
																	src={getImageUrl(cat.category_icon)}
																	alt=""
																	className="w-full h-full object-contain"
																	onError={(ev) => {
																		(ev.target as HTMLImageElement).style.display = "none";
																	}}
																/>
															) : (
																<span
																	className="text-lg text-gray-600 [&>svg]:w-6 [&>svg]:h-6"
																	dangerouslySetInnerHTML={{
																		__html: cat.category_icon,
																	}}
																/>
															)
														) : (
															<span className="text-gray-400 text-xs">--</span>
														)}
													</div>
												</td>
												<td className="px-6 py-4 font-medium text-gray-900 align-middle">{cat.category_name}</td>
												<td className="px-2 py-4 text-center align-middle">
													<input
														type="number"
														min={0}
														max={100}
														step="0.01"
														value={e.discount_percent}
														onChange={(ev) =>
															updateEdit(cat.id, "discount_percent", ev.target.value)
														}
														className="w-20 rounded-md border border-gray-300 px-2 py-1.5 text-sm text-center focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-pink-500"
													/>
												</td>
												<td className="px-1 py-4 text-gray-500 font-medium align-middle">%</td>
												<td className="px-6 py-4 align-middle">
													<div className="flex flex-wrap items-center gap-2">
														<input
															type="date"
															value={e.start_date}
															onChange={(ev) =>
																updateEdit(cat.id, "start_date", ev.target.value)
															}
															className="rounded-md border border-gray-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-pink-500 min-w-0"
														/>
														<span className="text-gray-400 shrink-0">-</span>
														<input
															type="date"
															value={e.end_date}
															onChange={(ev) =>
																updateEdit(cat.id, "end_date", ev.target.value)
															}
															className="rounded-md border border-gray-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-pink-500 min-w-0"
														/>
													</div>
												</td>
												<td className="px-6 py-4 text-center align-middle">
													<button
														type="button"
														onClick={() => handleSet(cat.id)}
														disabled={savingId === cat.id}
														className="inline-flex items-center justify-center min-w-[60px] px-4 py-1.5 rounded-md bg-indigo-600 text-white text-xs font-medium hover:bg-indigo-700 disabled:opacity-60 transition-colors"
													>
														{savingId === cat.id ? "Saving..." : "Set"}
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
			</div>
		</WithVendorAuth>
	);
}
