"use client";

import { useState, useEffect, useMemo } from "react";
import { useRouter, useParams } from "next/navigation";
import Image from "next/image";
import Link from "next/link";
import WithVendorAuth from "../../../WithVendorAuth";
import { toast } from "sonner";
import { getImageUrl } from "@/lib/utils";
import {
	useGetVendorProductQuery,
	useUpdateVendorProductMutation,
	useGetVendorProductVariantsQuery,
	useCreateVendorProductVariantMutation,
	useDeleteVendorProductVariantMutation,
	useGetVendorProductPriceTiersQuery,
	useCreateVendorProductPriceTierMutation,
	useDeleteVendorProductPriceTierMutation,
	useGetVendorCategoryCommissionsQuery,
} from "@/redux/api/vendorApi";
import {
	useGetAllNavbarCategoryDropdownOptionsQuery,
	useGetAllBrandsQuery,
} from "@/redux/features/home/homeApi";

type CatItem = { id: number; category_name: string; subcategories?: { id: number; sub_category_name: string; category_id: number }[] };

export default function VendorEditProductPage() {
	const router = useRouter();
	const params = useParams();
	const id = Number(params?.id);
	const [saving, setSaving] = useState(false);
	const { data, isLoading, error } = useGetVendorProductQuery(id, {
		skip: !id || isNaN(id),
	});
	const [updateProduct] = useUpdateVendorProductMutation();
	const product = data?.data?.product;

	// Category / brand data
	const { data: catData } = useGetAllNavbarCategoryDropdownOptionsQuery(undefined);
	const { data: brandData } = useGetAllBrandsQuery(undefined);
	const { data: commissionData } = useGetVendorCategoryCommissionsQuery();
	const categories = (catData as { data?: CatItem[] })?.data ?? [];
	const brands = (brandData as { data?: Array<{ id: number; brand_name: string }> })?.data ?? [];
	const commissionRows = commissionData?.data?.categories ?? [];

	// ── Controlled form state ──
	const [f, setF] = useState({
		name: "",
		base_price: "",
		regular_price: "",
		qty: "",
		low_stock: "",
		sku: "",
		discount: "",
		unit: "",
		product_weight: "",
		minimum_qty: "",
		tags: "",
		status: "Active",
		short_description: "",
		description: "",
		stock_visibility: "quantity",
		category_id: "",
		subcategory_id: "",
		brand_id: "",
		allow_dropship: false as boolean,
	});
	const selectedCategoryCommission = f.category_id
		? commissionRows.find((row) => row.category_id === Number(f.category_id))
			?.commission_percent
		: null;
	const [initialized, setInitialized] = useState(false);

	// Variants & price tiers
	const { data: variantsData } = useGetVendorProductVariantsQuery(id, { skip: !id || isNaN(id) });
	const { data: tiersData } = useGetVendorProductPriceTiersQuery(id, { skip: !id || isNaN(id) });
	const [createVariant, { isLoading: addingVariant }] = useCreateVendorProductVariantMutation();
	const [deleteVariant] = useDeleteVendorProductVariantMutation();
	const [createTier, { isLoading: addingTier }] = useCreateVendorProductPriceTierMutation();
	const [deleteTier] = useDeleteVendorProductPriceTierMutation();
	const variants = variantsData?.data?.variants ?? [];
	const priceTiers = tiersData?.data?.price_tiers ?? [];
	// Unified Bulk Pricing Variant-Wise
	const [newBulkRow, setNewBulkRow] = useState({
		variant_title: "",
		color_name: "",
		color_code: "",
		min_qty: "1",
		max_qty: "",
		price: "",
		delivery_charge: "",
	});

	// Populate state once product arrives
	useEffect(() => {
		if (product && !initialized) {
			const p = product as unknown as Record<string, unknown>;
			setF({
				name: String(p.ProductName ?? ""),
				base_price: String(p.ProductResellerPrice ?? ""),
				regular_price: String(p.ProductRegularPrice ?? ""),
				qty: String(p.qty ?? ""),
				low_stock: String(p.low_stock ?? ""),
				sku: String(p.ProductSku ?? ""),
				discount: String(p.Discount ?? "0"),
				unit: String(p.weight ?? ""),
				product_weight: String(p.product_weight ?? "0"),
				minimum_qty: String(p.minimum_qty ?? "1"),
				tags: String(p.MetaKey ?? ""),
				status: String(p.status ?? "Active"),
				short_description: String(p.ProductBreaf ?? ""),
				description: String(p.ProductDetails ?? ""),
				stock_visibility:
					p.show_stock === "On" ? "quantity" : p.show_stock_text === "On" ? "text" : "hide",
				category_id: String(p.category_id ?? ""),
				subcategory_id: String(p.subcategory_id ?? ""),
				brand_id: String(p.brand_id ?? ""),
				allow_dropship: Boolean((p as { allow_dropship?: boolean }).allow_dropship),
			});
			setInitialized(true);
		}
	}, [product, initialized]);

	const subcategories = useMemo(() => {
		if (!f.category_id) return [];
		const cat = categories.find((c) => c.id === Number(f.category_id));
		return cat?.subcategories ?? [];
	}, [categories, f.category_id]);

	const set = (key: keyof typeof f) => (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
		const v = e.target.type === "checkbox" ? (e.target as HTMLInputElement).checked : e.target.value;
		setF((prev) => ({ ...prev, [key]: v }));
	};

	const handleRemoveVariant = async (variantId: number) => {
		try {
			await deleteVariant({ id, variantId }).unwrap();
			toast.success("Variant removed");
		} catch {
			toast.error("Failed to delete");
		}
	};
	const handleAddBulkRow = async () => {
		if (!newBulkRow.price) {
			toast.error("Price is required");
			return;
		}

		try {
			// 1. If title exists, ensure variant exists in 'varients' table (if new title)
			if (newBulkRow.variant_title.trim()) {
				const title = newBulkRow.variant_title.trim();
				const colorName = newBulkRow.color_name.trim();
				const colorCode = newBulkRow.color_code.trim().toLowerCase();
				const exists = variants.some((v: any) =>
					v.title === title &&
					(v.color_name ?? "").toLowerCase() === colorName.toLowerCase() &&
					(v.color_code ?? "").toLowerCase() === colorCode
				);
				if (!exists) {
					await createVariant({
						id,
						title,
						color_name: colorName || undefined,
						color_code: colorCode || undefined,
						qty: parseInt(newBulkRow.max_qty, 10) || 0,
						price: parseFloat(newBulkRow.price) || 0,
					}).unwrap();
				}
			}

			// 2. Add to price tiers
			await createTier({
				id,
				min_qty: parseInt(newBulkRow.min_qty, 10) || 0,
				max_qty: parseInt(newBulkRow.max_qty, 10) || null,
				unit_price: parseFloat(newBulkRow.price) || 0,
				delivery_charge: newBulkRow.delivery_charge ? parseFloat(newBulkRow.delivery_charge) : null,
				tier_label: newBulkRow.variant_title || "Bulk",
				variant_title: newBulkRow.variant_title || null,
			}).unwrap();

			toast.success("Bulk pricing added");
			setNewBulkRow({
				variant_title: "",
				color_name: "",
				color_code: "",
				min_qty: (parseInt(newBulkRow.max_qty || newBulkRow.min_qty, 10) + 1).toString(),
				max_qty: "",
				price: "",
				delivery_charge: ""
			});
		} catch {
			toast.error("Failed to add bulk pricing");
		}
	};
	const handleRemoveTier = async (tierId: number) => {
		try {
			await deleteTier({ id, tierId }).unwrap();
			toast.success("Tier removed");
		} catch {
			toast.error("Failed to delete");
		}
	};

	const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
		e.preventDefault();
		if (!id || isNaN(id)) return;
		setSaving(true);
		const formData = new FormData();
		formData.append("ProductName", f.name);
		formData.append("ProductBreaf", f.short_description);
		formData.append("ProductDetails", f.description);
		formData.append("ProductResellerPrice", f.base_price || "0");
		formData.append("ProductRegularPrice", f.regular_price || "0");
		formData.append("qty", f.qty || "0");
		formData.append("low_stock", f.low_stock || "0");
		formData.append("ProductSku", f.sku);
		formData.append("status", f.status);
		formData.append("category_id", f.category_id);
		formData.append("subcategory_id", f.subcategory_id);
		formData.append("brand_id", f.brand_id);
		formData.append("show_stock", f.stock_visibility === "quantity" ? "On" : "Off");
		formData.append("show_stock_text", f.stock_visibility === "text" ? "On" : "Off");
		formData.append("product_weight", f.product_weight || "0");
		formData.append("minimum_qty", f.minimum_qty || "1");
		if (f.unit) formData.append("unit", f.unit);
		if (f.tags) formData.append("MetaKey", f.tags);
		formData.append("Discount", f.discount || "0");
		formData.append("allow_dropship", f.allow_dropship ? "1" : "0");
		formData.append("_method", "PUT");

		const form = e.currentTarget;
		const thumb = (form.querySelector('[name="thumbnail"]') as HTMLInputElement)?.files?.[0];
		if (thumb) formData.append("ProductImage", thumb);
		const galleryInput = form.querySelector('[name="gallery_images"]') as HTMLInputElement;
		if (galleryInput?.files?.length) {
			for (let i = 0; i < galleryInput.files.length; i++) {
				formData.append(`PostImage[${i}]`, galleryInput.files[i]);
			}
		}
		try {
			await updateProduct({ id, body: formData }).unwrap();
			toast.success("Product updated.");
			router.push("/vendor/products");
		} catch (err: unknown) {
			const msg =
				err && typeof err === "object" && "data" in err && typeof (err as { data?: { message?: string } }).data?.message === "string"
					? (err as { data: { message: string } }).data.message
					: "Failed to update product.";
			toast.error(msg);
		} finally {
			setSaving(false);
		}
	};

	const inputCls = "mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500";

	if (!id || isNaN(id)) {
		return <WithVendorAuth><p className="text-red-600">Invalid product ID.</p></WithVendorAuth>;
	}
	if (isLoading) {
		return <WithVendorAuth><p className="text-gray-600 p-6">Loading product...</p></WithVendorAuth>;
	}
	if (error || !product) {
		return (
			<WithVendorAuth>
				<p className="text-red-600">Product not found.</p>
				<Link href="/vendor/products" className="text-blue-600 hover:underline mt-2 inline-block">Back to products</Link>
			</WithVendorAuth>
		);
	}

	return (
		<WithVendorAuth>
			<div className="space-y-6">
				{/* Header */}
				<div className="rounded-xl bg-white p-4 sm:p-6 shadow-sm border border-gray-100 flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
					<div>
						<h1 className="text-2xl font-bold text-gray-900 mb-1">Edit product</h1>
						<p className="text-sm text-gray-600">Update product details below.</p>
					</div>
					<Link href="/vendor/products" className="text-sm font-medium text-gray-600 hover:text-gray-900">Back to products</Link>
				</div>

				<form onSubmit={handleSubmit} className="space-y-6 rounded-xl bg-white p-4 sm:p-6 shadow-sm border border-gray-100">
					<div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
						{/* ── Left column ── */}
						<div className="space-y-4 lg:col-span-2">
							{/* Basic info */}
							<div className="space-y-3">
								<h2 className="text-sm font-semibold text-gray-900">Basic information</h2>
								<label className="flex flex-col text-sm font-medium text-gray-700">
									Product name
									<input required value={f.name} onChange={set("name")} className={inputCls} />
								</label>
							</div>

							{/* Price & stock */}
							{f.allow_dropship && (
								<div className="space-y-3">
									<h2 className="text-sm font-semibold text-gray-900">Product price &amp; stock</h2>
									<div className="grid grid-cols-1 md:grid-cols-2 gap-3">
										<label className="flex flex-col text-sm font-medium text-gray-700">
											Base price (reseller)
											<input type="number" min={0} step="0.01" value={f.base_price} onChange={set("base_price")} className={inputCls} />
										</label>
										<label className="flex flex-col text-sm font-medium text-gray-700">
											Regular price
											<input type="number" min={0} step="0.01" value={f.regular_price} onChange={set("regular_price")} className={inputCls} />
										</label>
										<label className="flex flex-col text-sm font-medium text-gray-700">
											Quantity
											<input type="number" min={0} value={f.qty} onChange={set("qty")} className={inputCls} />
										</label>
										<label className="flex flex-col text-sm font-medium text-gray-700">
											Low stock warning at
											<input type="number" min={0} value={f.low_stock} onChange={set("low_stock")} className={inputCls} />
										</label>
										<label className="flex flex-col text-sm font-medium text-gray-700">
											SKU
											<input value={f.sku} onChange={set("sku")} className={inputCls} />
										</label>
										<label className="flex flex-col text-sm font-medium text-gray-700">
											Discount
											<input type="number" min={0} step="0.01" value={f.discount} onChange={set("discount")} className={inputCls} />
										</label>
										<label className="flex flex-col text-sm font-medium text-gray-700">
											Unit (e.g. Pc)
											<input value={f.unit} onChange={set("unit")} placeholder="Pc" className={inputCls} />
										</label>
										<label className="flex flex-col text-sm font-medium text-gray-700">
											Weight (kg)
											<input type="number" min={0} step="0.01" value={f.product_weight} onChange={set("product_weight")} className={inputCls} />
										</label>
										<label className="flex flex-col text-sm font-medium text-gray-700">
											Minimum purchase qty
											<input type="number" min={1} value={f.minimum_qty} onChange={set("minimum_qty")} className={inputCls} />
										</label>
										<label className="flex flex-col text-sm font-medium text-gray-700">
											Tags (comma separated)
											<input value={f.tags} onChange={set("tags")} placeholder="tag1, tag2" className={inputCls} />
										</label>
									</div>

									<p className="text-xs text-gray-600 font-medium">Stock visibility</p>
									<div className="flex flex-wrap gap-4 text-sm">
										{(["quantity", "text", "hide"] as const).map((v) => (
											<label key={v} className="inline-flex items-center gap-2">
												<input
													type="radio"
													name="stock_visibility"
													value={v}
													checked={f.stock_visibility === v}
													onChange={set("stock_visibility")}
													className="rounded-full border-gray-300"
												/>
												{v === "quantity" ? "Show quantity" : v === "text" ? "Show text only" : "Hide"}
											</label>
										))}
									</div>

									<label className="flex flex-col text-sm font-medium text-gray-700">
										Status
										<select value={f.status} onChange={set("status")} className={inputCls}>
											<option value="Active">Active</option>
											<option value="Inactive">Inactive</option>
										</select>
									</label>
								</div>
							)}

							{/* Description */}
							<div className="space-y-3">
								<h2 className="text-sm font-semibold text-gray-900">Product description</h2>
								<label className="flex flex-col text-sm font-medium text-gray-700">
									Short description
									<textarea rows={2} value={f.short_description} onChange={set("short_description")} className={inputCls} />
								</label>
								<label className="flex flex-col text-sm font-medium text-gray-700">
									Description
									<textarea rows={4} value={f.description} onChange={set("description")} className={inputCls} />
								</label>
							</div>
						</div>

						{/* ── Right column ── */}
						<div className="space-y-4 lg:col-span-1">
							{/* Product category */}
							<div className="space-y-2">
								<h2 className="text-sm font-semibold text-gray-900">Product category</h2>
								<p className="text-xs text-gray-500">
									Vendors must use existing categories from main website.
								</p>
								<label className="flex flex-col text-xs font-medium text-gray-700">
									Category
									<select
										required
										value={f.category_id}
										onChange={(e) => setF((prev) => ({ ...prev, category_id: e.target.value, subcategory_id: "" }))}
										className={inputCls}
									>
										<option value="">Select category</option>
										{categories.map((c) => (
											<option key={c.id} value={c.id}>{c.category_name}</option>
										))}
									</select>
								</label>
								<label className="flex flex-col text-xs font-medium text-gray-700">
									Subcategory
									<select required value={f.subcategory_id} onChange={set("subcategory_id")} className={inputCls}>
										<option value="">Select subcategory</option>
										{subcategories.map((s) => (
											<option key={s.id} value={s.id}>{s.sub_category_name}</option>
										))}
									</select>
								</label>
								<label className="flex flex-col text-xs font-medium text-gray-700">
									Brand
									<select required value={f.brand_id} onChange={set("brand_id")} className={inputCls}>
										<option value="">Select brand</option>
										{brands.map((b) => (
											<option key={b.id} value={b.id}>{b.brand_name}</option>
										))}
									</select>
								</label>

								<div className="rounded-md border border-indigo-100 bg-indigo-50 p-3">
									<p className="text-xs font-semibold text-indigo-900 mb-1">
										Category-wise commission (admin set)
									</p>
									{selectedCategoryCommission !== null && selectedCategoryCommission !== undefined && (
										<p className="text-xs text-indigo-700 mb-2">
											Selected category commission: <span className="font-semibold">{selectedCategoryCommission}%</span>
										</p>
									)}
									<div className="max-h-36 overflow-y-auto space-y-1">
										{commissionRows.length === 0 ? (
											<p className="text-xs text-indigo-700">No commission rules found.</p>
										) : (
											commissionRows.map((row) => (
												<div
													key={row.category_id}
													className={`text-[11px] flex items-center justify-between rounded px-2 py-1 ${Number(f.category_id) === row.category_id
														? "bg-indigo-200 text-indigo-900"
														: "bg-white text-gray-700"
														}`}
												>
													<span>{row.category_name}</span>
													<span className="font-semibold">{row.commission_percent}%</span>
												</div>
											))
										)}
									</div>
								</div>
							</div>

							{/* Product images */}
							<div className="space-y-3">
								<h2 className="text-sm font-semibold text-gray-900">Product images</h2>

								{/* Current thumbnail preview */}
								{product.ViewProductImage && (
									<div>
										<p className="text-xs text-gray-500 mb-1">Current thumbnail</p>
										<img
											src={getImageUrl(product.ViewProductImage as string)}
											alt="Current thumbnail"
											className="w-28 h-28 object-cover rounded-lg border border-gray-200"
											onError={(e) => { (e.target as HTMLImageElement).style.display = "none"; }}
										/>
									</div>
								)}
								<label className="flex flex-col text-xs font-medium text-gray-700">
									New thumbnail (optional)
									<input name="thumbnail" type="file" accept="image/*" className="mt-1 block w-full text-xs text-gray-700 file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-gray-900 file:text-white hover:file:bg-black" />
								</label>

								{/* Current gallery preview */}
								{(() => {
									let gallery: string[] = [];
									try {
										const raw = (product as unknown as Record<string, unknown>).PostImage;
										if (typeof raw === "string" && raw.startsWith("[")) gallery = JSON.parse(raw);
										else if (Array.isArray(raw)) gallery = raw as string[];
									} catch { /* ignore */ }
									return gallery.length > 0 ? (
										<div>
											<p className="text-xs text-gray-500 mb-1">Current gallery ({gallery.length} image{gallery.length > 1 ? "s" : ""})</p>
											<div className="flex flex-wrap gap-2">
												{gallery.map((g, idx) => (
													<img
														key={idx}
														src={getImageUrl(g)}
														alt={`Gallery ${idx + 1}`}
														className="w-20 h-20 object-cover rounded-lg border border-gray-200"
														onError={(e) => { (e.target as HTMLImageElement).style.display = "none"; }}
													/>
												))}
											</div>
										</div>
									) : null;
								})()}
								<label className="flex flex-col text-xs font-medium text-gray-700">
									Gallery images (optional, replaces existing)
									<input name="gallery_images" type="file" multiple accept="image/*" className="mt-1 block w-full text-xs text-gray-700 file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-gray-900 file:text-white hover:file:bg-black" />
								</label>
								<label className="flex items-center gap-2 text-xs font-medium text-gray-700">
									<input type="checkbox" checked={f.allow_dropship} onChange={set("allow_dropship")} className="rounded border-gray-300" />
									Allow dropship
								</label>
							</div>
						</div>
					</div>

					{/* Bulk Pricing Variant-Wise */}
					<div className="rounded-xl bg-indigo-50/30 p-4 sm:p-6 shadow-sm border border-indigo-100">
						<h2 className="text-sm font-bold text-indigo-900 mb-3 flex items-center gap-2">
							📦 Bulk Pricing Variant-Wise
						</h2>
						<p className="text-xs text-indigo-700 mb-4">
							Quantity-based pricing. Variant is optional. Deleting a row removes that specific pricing rule.
						</p>
						<div className="overflow-x-auto">
							<table className="min-w-full text-sm">
								<thead>
									<tr className="text-left text-gray-600 border-b border-gray-200">
										<th className="py-2 pr-3 font-semibold w-[20%] text-indigo-900">Variant (Optional)</th>
										<th className="py-2 pr-2 font-semibold w-[14%] text-indigo-900">Color Name</th>
										<th className="py-2 pr-2 font-semibold w-[8%] text-indigo-900 text-center">Color</th>
										<th className="py-2 pr-2 font-semibold w-[10%] text-indigo-900 text-center">Min Qty</th>
										<th className="py-2 pr-2 font-semibold w-[10%] text-indigo-900 text-center">Max Qty</th>
										<th className="py-2 pr-2 font-semibold w-[14%] text-indigo-900">Price</th>
										<th className="py-2 pr-2 font-semibold w-[14%] text-indigo-900">Deliv. Charge</th>
										<th className="py-2 w-[10%]"></th>
									</tr>
								</thead>
								<tbody className="divide-y divide-gray-100">
									{priceTiers.map((t: any) => (
										<tr key={t.id} className="hover:bg-white/50 transition-colors">
											<td className="py-3 pr-3 text-gray-700 italic">{t.variant_title || "Base Product"}</td>
											<td className="py-3 pr-2 text-gray-700">
												{variants.find((v: any) => v.title === t.variant_title)?.color_name || "-"}
											</td>
											<td className="py-3 pr-2 text-center">
												{(() => {
													const color = variants.find((v: any) => v.title === t.variant_title)?.color_code;
													return color ? (
														<span
															className="inline-block h-5 w-5 rounded-full border border-gray-300"
															style={{ backgroundColor: color }}
															title={color}
														/>
													) : (
														<span className="text-gray-400">-</span>
													);
												})()}
											</td>
											<td className="py-3 pr-2 text-center font-medium text-gray-900">{t.min_qty}</td>
											<td className="py-3 pr-2 text-center font-medium text-gray-900">{t.max_qty || "∞"}</td>
											<td className="py-3 pr-2 font-bold text-gray-900">৳{t.unit_price}</td>
											<td className="py-3 pr-2 text-gray-600">{t.delivery_charge ? `৳${t.delivery_charge}` : "Default"}</td>
											<td className="py-3 text-right">
												<button
													type="button"
													onClick={() => handleRemoveTier(t.id)}
													className="text-xs text-red-600 hover:text-red-800 font-medium underline"
												>
													Remove
												</button>
											</td>
										</tr>
									))}
									<tr className="bg-white/70">
										<td className="py-3 pr-3">
											<input
												placeholder="e.g. Red / S"
												value={newBulkRow.variant_title}
												onChange={(e) => setNewBulkRow(p => ({ ...p, variant_title: e.target.value }))}
												className="w-full rounded-lg border-gray-300 px-3 py-2 text-xs focus:ring-2 focus:ring-indigo-500"
											/>
										</td>
										<td className="py-3 pr-2">
											<input
												placeholder="e.g. Red"
												value={newBulkRow.color_name}
												onChange={(e) => setNewBulkRow(p => ({ ...p, color_name: e.target.value }))}
												className="w-full rounded-lg border-gray-300 px-3 py-2 text-xs focus:ring-2 focus:ring-indigo-500"
											/>
										</td>
										<td className="py-3 pr-2">
											<input
												type="color"
												value={newBulkRow.color_code || "#000000"}
												onChange={(e) => setNewBulkRow(p => ({ ...p, color_code: e.target.value }))}
												className="h-9 w-full rounded-lg border border-gray-300 p-1"
												title="Pick variant color"
											/>
										</td>
										<td className="py-3 pr-2">
											<input
												type="number" min={1}
												value={newBulkRow.min_qty}
												onChange={(e) => setNewBulkRow(p => ({ ...p, min_qty: e.target.value }))}
												className="w-full rounded-lg border-gray-300 px-2 py-2 text-xs text-center focus:ring-2 focus:ring-indigo-500"
											/>
										</td>
										<td className="py-3 pr-2">
											<input
												type="number" min={1}
												placeholder="Max"
												value={newBulkRow.max_qty}
												onChange={(e) => setNewBulkRow(p => ({ ...p, max_qty: e.target.value }))}
												className="w-full rounded-lg border-gray-300 px-2 py-2 text-xs text-center focus:ring-2 focus:ring-indigo-500"
											/>
										</td>
										<td className="py-3 pr-2">
											<input
												type="number" min={0}
												placeholder="Price"
												value={newBulkRow.price}
												onChange={(e) => setNewBulkRow(p => ({ ...p, price: e.target.value }))}
												className="w-full rounded-lg border-gray-300 px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-indigo-500"
											/>
										</td>
										<td className="py-3 pr-2">
											<input
												type="number" min={0}
												placeholder="Optional"
												value={newBulkRow.delivery_charge}
												onChange={(e) => setNewBulkRow(p => ({ ...p, delivery_charge: e.target.value }))}
												className="w-full rounded-lg border-gray-300 px-3 py-2 text-xs focus:ring-2 focus:ring-indigo-500"
											/>
										</td>
										<td className="py-3">
											<button
												type="button"
												disabled={addingTier}
												onClick={handleAddBulkRow}
												className="w-full rounded-lg bg-indigo-600 text-white px-3 py-2 text-xs font-bold hover:bg-indigo-700 transition-colors shadow-sm disabled:opacity-50"
											>
												+ Add
											</button>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>

					<div className="flex justify-end">
						<button
							type="submit"
							disabled={saving}
							className="inline-flex items-center px-5 py-2.5 rounded-lg bg-[#2d2a5d] text-white text-sm font-medium hover:bg-[#252947] disabled:opacity-60"
						>
							{saving ? "Saving..." : "Save changes"}
						</button>
					</div>
				</form >
			</div >
		</WithVendorAuth >
	);
}

