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
	const categories = (catData as { data?: CatItem[] })?.data ?? [];
	const brands = (brandData as { data?: Array<{ id: number; brand_name: string }> })?.data ?? [];

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
	const [newVariant, setNewVariant] = useState({ title: "", qty: "0", price: "0" });
	const [newTier, setNewTier] = useState({ min_qty: "0", unit_price: "", tier_label: "Tier 1" });

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
	const handleAddVariant = async () => {
		try {
			await createVariant({
				id,
				title: newVariant.title.trim(),
				qty: parseInt(newVariant.qty, 10) || 0,
				price: parseFloat(newVariant.price) || 0,
			}).unwrap();
			toast.success("Variant added");
			setNewVariant({ title: "", qty: "0", price: "0" });
		} catch {
			toast.error("Failed to add variant");
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
	const handleAddTier = async () => {
		try {
			await createTier({
				id,
				min_qty: parseInt(newTier.min_qty, 10) || 0,
				unit_price: parseFloat(newTier.unit_price) || 0,
				tier_label: newTier.tier_label || "Tier",
			}).unwrap();
			toast.success("Tier added");
			setNewTier({ min_qty: "0", unit_price: "", tier_label: "Tier 1" });
		} catch {
			toast.error("Failed to add tier");
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
				<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100 flex items-center justify-between gap-4">
					<div>
						<h1 className="text-2xl font-bold text-gray-900 mb-1">Edit product</h1>
						<p className="text-sm text-gray-600">Update product details below.</p>
					</div>
					<Link href="/vendor/products" className="text-sm font-medium text-gray-600 hover:text-gray-900">Back to products</Link>
				</div>

				<form onSubmit={handleSubmit} className="space-y-6 rounded-xl bg-white p-6 shadow-sm border border-gray-100">
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

					{/* Variants */}
					<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
						<h2 className="text-sm font-semibold text-gray-900 mb-3">Variants</h2>
						<div className="overflow-x-auto">
							<table className="min-w-full text-sm table-fixed">
								<thead>
									<tr className="text-left text-gray-600 border-b border-gray-200">
										<th className="py-2 pr-3 w-[40%]">Title</th>
										<th className="py-2 pr-3 w-[15%]">Qty</th>
										<th className="py-2 pr-3 w-[20%]">Price</th>
										<th className="py-2 w-[25%]"></th>
									</tr>
								</thead>
								<tbody>
									{variants.map((v) => (
										<tr key={v.id} className="border-t border-gray-100">
											<td className="py-2 pr-3">{v.title}</td>
											<td className="py-2 pr-3">{v.qty}</td>
											<td className="py-2 pr-3">{v.price}</td>
											<td className="py-2">
												<button type="button" onClick={() => handleRemoveVariant(v.id)} className="text-xs text-red-600 hover:underline">Remove</button>
											</td>
										</tr>
									))}
									<tr className="border-t border-gray-200 bg-gray-50/50">
										<td className="py-2 pr-3 align-middle">
											<input placeholder="e.g. Red / S" value={newVariant.title} onChange={(e) => setNewVariant((p) => ({ ...p, title: e.target.value }))} className={`${inputCls} w-full max-w-[200px]`} />
										</td>
										<td className="py-2 pr-3 align-middle">
											<input type="number" min={0} placeholder="0" value={newVariant.qty} onChange={(e) => setNewVariant((p) => ({ ...p, qty: e.target.value }))} className={`${inputCls} w-full max-w-[80px]`} />
										</td>
										<td className="py-2 pr-3 align-middle">
											<input type="number" min={0} placeholder="0" value={newVariant.price} onChange={(e) => setNewVariant((p) => ({ ...p, price: e.target.value }))} className={`${inputCls} w-full max-w-[100px]`} />
										</td>
										<td className="py-2 align-middle">
											<button type="button" disabled={addingVariant || !newVariant.title.trim()} onClick={handleAddVariant} className="rounded-md bg-gray-800 text-white px-3 py-1.5 text-xs disabled:opacity-50">Add variant</button>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>

					{/* Wholesale price tiers */}
					<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
						<h2 className="text-sm font-semibold text-gray-900 mb-3">Wholesale price tiers</h2>
						<div className="overflow-x-auto">
							<table className="min-w-full text-sm table-fixed">
								<thead>
									<tr className="text-left text-gray-600 border-b border-gray-200">
										<th className="py-2 pr-3 w-[20%]">Min qty</th>
										<th className="py-2 pr-3 w-[25%]">Unit price</th>
										<th className="py-2 pr-3 w-[30%]">Label</th>
										<th className="py-2 w-[25%]"></th>
									</tr>
								</thead>
								<tbody>
									{priceTiers.map((t) => (
										<tr key={t.id} className="border-t border-gray-100">
											<td className="py-2 pr-3">{t.min_qty}</td>
											<td className="py-2 pr-3">{t.unit_price}</td>
											<td className="py-2 pr-3">{t.tier_label}</td>
											<td className="py-2">
												<button type="button" onClick={() => handleRemoveTier(t.id)} className="text-xs text-red-600 hover:underline">Remove</button>
											</td>
										</tr>
									))}
									<tr className="border-t border-gray-200 bg-gray-50/50">
										<td className="py-2 pr-3 align-middle">
											<input type="number" min={0} placeholder="0" value={newTier.min_qty} onChange={(e) => setNewTier((p) => ({ ...p, min_qty: e.target.value }))} className={`${inputCls} w-full max-w-[100px]`} />
										</td>
										<td className="py-2 pr-3 align-middle">
											<input type="number" min={0} step="0.01" placeholder="0.00" value={newTier.unit_price} onChange={(e) => setNewTier((p) => ({ ...p, unit_price: e.target.value }))} className={`${inputCls} w-full max-w-[120px]`} />
										</td>
										<td className="py-2 pr-3 align-middle">
											<input placeholder="e.g. Tier 1" value={newTier.tier_label} onChange={(e) => setNewTier((p) => ({ ...p, tier_label: e.target.value }))} className={`${inputCls} w-full max-w-[140px]`} />
										</td>
										<td className="py-2 align-middle">
											<button type="button" disabled={addingTier || newTier.unit_price === ""} onClick={handleAddTier} className="rounded-md bg-gray-800 text-white px-3 py-1.5 text-xs disabled:opacity-50">Add tier</button>
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
				</form>
			</div>
		</WithVendorAuth>
	);
}
