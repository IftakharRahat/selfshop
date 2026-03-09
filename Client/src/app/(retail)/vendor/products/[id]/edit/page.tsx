"use client";

import { useState, useEffect, useMemo } from "react";
import { useRouter, useParams } from "next/navigation";
import Image from "next/image";
import Link from "next/link";
import WithVendorAuth from "../../../WithVendorAuth";
import { toast } from "sonner";
import { getImageUrl } from "@/lib/utils";
import R2ImageUploader from "@/components/shared/r2-image-uploader";
import R2MultiImageUploader from "@/components/shared/r2-multi-image-uploader";
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
	useCreateVendorProductVariantSizeMutation,
	useUpdateVendorProductVariantSizeMutation,
	useDeleteVendorProductVariantSizeMutation,
	useCreateVendorProductVariantSizeBulkPriceMutation,
	useDeleteVendorProductVariantSizeBulkPriceMutation,
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
	});
	const [sellingType, setSellingType] = useState<'wholesale' | 'dropshipping' | 'both'>('wholesale');
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
	const [createVariantSize] = useCreateVendorProductVariantSizeMutation();
	const [updateVariantSize] = useUpdateVendorProductVariantSizeMutation();
	const [deleteVariantSize] = useDeleteVendorProductVariantSizeMutation();
	const [createBulkPrice] = useCreateVendorProductVariantSizeBulkPriceMutation();
	const [deleteBulkPrice] = useDeleteVendorProductVariantSizeBulkPriceMutation();

	const variants = variantsData?.data?.variants ?? [];
	const priceTiers = tiersData?.data?.price_tiers ?? [];

	const [newVariant, setNewVariant] = useState({
		title: "",
		color_name: "",
		color_code: "#000000",
		imageFile: undefined as File | undefined,
		imagePreview: undefined as string | undefined,
	});

	// For inline add-size forms per variant
	const [newSizeState, setNewSizeState] = useState<Record<number, { size_name: string; price: string; qty: string }>>({});

	// Image state for R2 uploaders
	const [thumbnailFile, setThumbnailFile] = useState<File | null>(null);
	const [galleryFiles, setGalleryFiles] = useState<File[]>([]);

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
			});

			const hasBulk = Boolean((p as { is_wholesale?: boolean | number }).is_wholesale);
			const hasDropship = Boolean((p as { allow_dropship?: boolean | number }).allow_dropship);

			if (hasBulk && hasDropship) setSellingType('both');
			else if (hasDropship) setSellingType('dropshipping');
			else setSellingType('wholesale');

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
		if (!newVariant.color_name) return toast.error("Color name is required.");
		try {
			const varFormData = new FormData();
			const title = newVariant.title || newVariant.color_name || "Variant";
			varFormData.append("title", title);
			if (newVariant.color_name) varFormData.append("color_name", newVariant.color_name);
			if (newVariant.color_code) varFormData.append("color_code", newVariant.color_code);
			varFormData.append("qty", "0");
			varFormData.append("price", "0");
			if (newVariant.imageFile) varFormData.append("image", newVariant.imageFile);

			// Instead of manual fetch, try using the RTK mutation which handles tokens/headers
			await createVariant({ id, body: varFormData }).unwrap();

			toast.success("Variant added");
			setNewVariant({ title: "", color_name: "", color_code: "#000000", imageFile: undefined, imagePreview: undefined });
		} catch (err: any) {
			console.error("Add variant error:", err);
			const msg = err?.data?.message || err?.message || "Failed to add variant";
			toast.error(msg);
		}
	};

	const handleAddSize = async (variantId: number) => {
		const sz = newSizeState[variantId];
		if (!sz || !sz.size_name.trim()) return toast.error("Size name required");
		try {
			await createVariantSize({
				id,
				variantId,
				size_name: sz.size_name.trim(),
				qty: parseInt(sz.qty, 10) || 0,
				price: sz.price ? parseFloat(sz.price) : null,
				status: "Active"
			}).unwrap();
			toast.success("Size added");
			setNewSizeState(prev => ({ ...prev, [variantId]: { size_name: "", price: "", qty: "0" } }));
		} catch {
			toast.error("Failed to add size");
		}
	};

	const handleRemoveSize = async (variantId: number, sizeId: number) => {
		try {
			await deleteVariantSize({ id, variantId, sizeId }).unwrap();
			toast.success("Size removed");
		} catch {
			toast.error("Failed to delete size");
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
		formData.append("selling_type", sellingType);
		formData.append("allow_dropship", sellingType === 'dropshipping' || sellingType === 'both' ? "1" : "0");
		formData.append("_method", "PUT");

		if (thumbnailFile) formData.append("ProductImage", thumbnailFile);
		if (galleryFiles.length > 0) {
			for (let i = 0; i < galleryFiles.length; i++) {
				formData.append(`PostImage[${i}]`, galleryFiles[i]);
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

								<div className="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
									<label className={`flex items-center gap-3 rounded-xl border-2 p-4 cursor-pointer transition-all ${sellingType === 'wholesale'
										? 'border-green-500 bg-green-50 shadow-sm'
										: 'border-gray-200 hover:border-gray-300 bg-white'
										}`}>
										<input
											type="radio"
											name="selling_type_radio"
											value="wholesale"
											checked={sellingType === 'wholesale'}
											onChange={() => setSellingType('wholesale')}
											className="w-4 h-4 accent-green-600"
										/>
										<div className="flex flex-col">
											<span className="text-sm font-bold text-gray-900">🏭 Wholesale</span>
											<span className="text-[10px] text-gray-500 leading-tight mt-0.5">Bulk pricing tiers</span>
										</div>
									</label>
									<label className={`flex items-center gap-3 rounded-xl border-2 p-4 cursor-pointer transition-all ${sellingType === 'dropshipping'
										? 'border-blue-500 bg-blue-50 shadow-sm'
										: 'border-gray-200 hover:border-gray-300 bg-white'
										}`}>
										<input
											type="radio"
											name="selling_type_radio"
											value="dropshipping"
											checked={sellingType === 'dropshipping'}
											onChange={() => setSellingType('dropshipping')}
											className="w-4 h-4 accent-blue-600"
										/>
										<div className="flex flex-col">
											<span className="text-sm font-bold text-gray-900">🚀 Dropshipping</span>
											<span className="text-[10px] text-gray-500 leading-tight mt-0.5">Single price & stock</span>
										</div>
									</label>
									<label className={`flex items-center gap-3 rounded-xl border-2 p-4 cursor-pointer transition-all ${sellingType === 'both'
										? 'border-amber-500 bg-amber-50 shadow-sm'
										: 'border-gray-200 hover:border-gray-300 bg-white'
										}`}>
										<input
											type="radio"
											name="selling_type_radio"
											value="both"
											checked={sellingType === 'both'}
											onChange={() => setSellingType('both')}
											className="w-4 h-4 accent-amber-600"
										/>
										<div className="flex flex-col">
											<span className="text-sm font-bold text-gray-900">🔄 Both</span>
											<span className="text-[10px] text-gray-500 leading-tight mt-0.5">Wholesale + Dropship</span>
										</div>
									</label>
								</div>

								<label className="flex flex-col text-sm font-medium text-gray-700">
									Product name
									<input required value={f.name} onChange={set("name")} className={inputCls} />
								</label>
							</div>

							{/* Price & stock */}
							{(sellingType === 'dropshipping' || sellingType === 'both') && (
								<div className="space-y-3">
									<h2 className="text-sm font-semibold text-gray-900">Product price &amp; stock</h2>
									<div className="grid grid-cols-1 md:grid-cols-2 gap-3">
										<label className="flex flex-col text-sm font-medium text-gray-700">
											Base price (reseller)
											<input type="number" min={0} step="0.01" value={f.base_price} onChange={set("base_price")} className={inputCls} />
											{f.base_price && selectedCategoryCommission !== null && !isNaN(Number(f.base_price)) && (
												<p className="text-xs text-green-600 mt-1 font-semibold">
													Storefront price: ৳{(Number(f.base_price) * (1 + Number(selectedCategoryCommission) / 100)).toFixed(2)}
													<span className="text-[10px] ml-1 font-normal">(after {selectedCategoryCommission}% admin commission)</span>
												</p>
											)}
										</label>
										<label className="flex flex-col text-sm font-medium text-gray-700">
											Regular price (MSRP)
											<input
												type="number"
												step="0.01"
												value={f.regular_price}
												onChange={set("regular_price")}
												placeholder="Manual entry (optional)"
												className={inputCls}
											/>
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

								<R2ImageUploader
									label="Thumbnail image"
									value={thumbnailFile}
									existingImageUrl={product.ViewProductImage ? getImageUrl(product.ViewProductImage as string) : undefined}
									onChange={setThumbnailFile}
									compact
								/>

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
								<R2MultiImageUploader
									label="New gallery images (optional, replaces existing)"
									value={galleryFiles}
									onChange={setGalleryFiles}
								/>
							</div>
						</div>
					</div>

					{/* Product Variants (Colors & Sizes) */}
					<div className="rounded-xl bg-indigo-50/30 p-4 sm:p-6 shadow-sm border border-indigo-100">
						<h2 className="text-sm font-bold text-indigo-900 mb-3 flex items-center gap-2">
							🎨 Product Variants (Colors & Sizes)
						</h2>
						<p className="text-xs text-indigo-700 mb-4">
							Manage color variants and their available sizes.
						</p>

						{/* New Variant (Color) Form */}
						<div className="bg-white p-4 rounded-lg border border-gray-200 mb-6 flex flex-wrap gap-4 items-end">
							<div className="flex-1 min-w-[150px]">
								<label className="block text-xs font-semibold text-gray-700 mb-1">Color Name *</label>
								<input
									placeholder="e.g. Red"
									value={newVariant.color_name}
									onChange={(e) => setNewVariant({ ...newVariant, color_name: e.target.value })}
									className="w-full rounded-md border-gray-300 text-sm py-1.5 focus:ring-indigo-500 focus:border-indigo-500"
								/>
							</div>
							<div className="w-[80px]">
								<label className="block text-xs font-semibold text-gray-700 mb-1">Color Picker</label>
								<input
									type="color"
									value={newVariant.color_code}
									onChange={(e) => setNewVariant({ ...newVariant, color_code: e.target.value })}
									className="h-8 w-full rounded-md border border-gray-300 p-0.5 cursor-pointer"
								/>
							</div>
							<div className="flex-1 min-w-[200px]">
								<label className="block text-xs font-semibold text-gray-700 mb-1">Variant Title (Optional)</label>
								<input
									placeholder="defaults to color name"
									value={newVariant.title}
									onChange={(e) => setNewVariant({ ...newVariant, title: e.target.value })}
									className="w-full rounded-md border-gray-300 text-sm py-1.5 focus:ring-indigo-500 focus:border-indigo-500"
								/>
							</div>
							<div className="flex-1 min-w-[180px]">
								<label className="block text-xs font-semibold text-gray-700 mb-1">Color Image (Optional)</label>
								<input
									type="file"
									accept="image/*"
									onChange={(e) => {
										const file = e.target.files?.[0];
										if (file) {
											setNewVariant({ ...newVariant, imageFile: file, imagePreview: URL.createObjectURL(file) });
										}
									}}
									className="w-full text-xs text-gray-600 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-gray-100 file:text-gray-700"
								/>
							</div>
							<div>
								<button
									type="button"
									onClick={handleAddVariant}
									disabled={addingVariant}
									className="h-8 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-md transition-colors disabled:opacity-50"
								>
									{addingVariant ? 'Adding...' : 'Add Color'}
								</button>
							</div>
						</div>

						{/* Variants List */}
						<div className="space-y-6">
							{variants.map((v: any) => (
								<div key={v.id} className="bg-white border border-gray-200 rounded-lg p-5 relative shadow-sm">
									<button
										type="button"
										onClick={() => handleRemoveVariant(v.id)}
										className="absolute top-4 right-4 text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 rounded-md p-1.5 transition-colors"
										title="Remove variant"
									>
										<svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
									</button>

									<div className="flex gap-5 items-start pr-12">
										<div className="w-20 h-20 rounded-lg border border-gray-200 overflow-hidden bg-gray-50 flex-shrink-0 flex items-center justify-center">
											{v.image ? (
												<img src={getImageUrl(v.image)} alt="Preview" className="w-full h-full object-cover" />
											) : (
												<div className="w-10 h-10 rounded-full border border-gray-300 shadow-inner" style={{ backgroundColor: v.color_code || '#fff' }} />
											)}
										</div>
										<div className="flex-1">
											<h3 className="text-lg font-bold text-gray-900 leading-tight">
												{v.color_name || 'No Color Name'} <span className="text-sm font-normal text-gray-500 ml-2">({v.title})</span>
											</h3>
											<p className="text-xs text-gray-500 mb-4 mt-1">Manage sizes and bulk pricing tiers for this color.</p>

											{/* Sizes Section */}
											<div className="space-y-4">
												{v.sizes && v.sizes.map((sz: any) => (
													<div key={sz.id} className="bg-gray-50 border border-gray-100 rounded-lg p-3">
														<div className="flex items-center gap-4 mb-3">
															<div className="flex-1 grid grid-cols-3 gap-3">
																<div>
																	<label className="text-[10px] font-bold text-gray-500 uppercase">Size Name</label>
																	<div className="font-semibold text-gray-800">{sz.size_name}</div>
																</div>
																<div>
																	<label className="text-[10px] font-bold text-gray-500 uppercase">Price</label>
																	<div className="font-semibold text-indigo-600">৳{sz.price || 'Inherited'}</div>
																	{sz.price && selectedCategoryCommission !== null && (
																		<div className="text-[9px] text-green-600 font-medium">
																			Store: ৳{(Number(sz.price) * (1 + Number(selectedCategoryCommission) / 100)).toFixed(2)}
																		</div>
																	)}
																</div>
																<div>
																	<label className="text-[10px] font-bold text-gray-500 uppercase">Qty</label>
																	<div className="font-semibold text-gray-800">{sz.qty}</div>
																</div>
															</div>
															<button
																type="button"
																onClick={() => handleRemoveSize(v.id, sz.id)}
																className="text-red-500 hover:text-red-700 bg-white border border-red-100 p-1 rounded transition-colors"
															>
																<svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" /></svg>
															</button>
														</div>

														{/* Bulk Tiers for this size */}
														<div className="pl-4 border-l-2 border-indigo-100 space-y-2">
															<h4 className="text-[10px] font-bold text-indigo-900 uppercase tracking-wider mb-1">Bulk Pricing Tiers</h4>
															{sz.bulk_prices && sz.bulk_prices.map((bt: any) => (
																<div key={bt.id} className="flex items-center gap-3 text-xs bg-white p-1.5 rounded border border-indigo-50">
																	<span className="flex-1 font-medium">Qty: {bt.min_qty} - {bt.max_qty || '∞'}</span>
																	<div className="text-right">
																		<span className="font-bold text-indigo-600">৳{bt.bulk_price}</span>
																		{selectedCategoryCommission !== null && (
																			<div className="text-[9px] text-green-600 font-medium">
																				Store: ৳{(Number(bt.bulk_price) * (1 + Number(selectedCategoryCommission) / 100)).toFixed(2)}
																			</div>
																		)}
																	</div>
																	<button
																		type="button"
																		onClick={async () => {
																			try {
																				await deleteBulkPrice({ id, variantId: v.id, sizeId: sz.id, bulkId: bt.id }).unwrap();
																				toast.success("Tier removed");
																			} catch { toast.error("Failed to remove tier"); }
																		}}
																		className="text-red-400 hover:text-red-600 transition-colors"
																	>
																		<svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" /></svg>
																	</button>
																</div>
															))}
															{/* Add Bulk Tier Inline */}
															<div className="flex gap-2 items-end pt-1">
																<div className="w-16">
																	<input id={`bt_min_${sz.id}`} type="number" placeholder="Min" className="w-full text-[10px] p-1 border rounded" />
																</div>
																<div className="w-16">
																	<input id={`bt_max_${sz.id}`} type="number" placeholder="Max" className="w-full text-[10px] p-1 border rounded" />
																</div>
																<div className="w-20">
																	<input id={`bt_price_${sz.id}`} type="number" step="0.01" placeholder="Price" className="w-full text-[10px] p-1 border rounded" />
																</div>
																<button
																	type="button"
																	onClick={async () => {
																		const minEl = document.getElementById(`bt_min_${sz.id}`) as HTMLInputElement;
																		const maxEl = document.getElementById(`bt_max_${sz.id}`) as HTMLInputElement;
																		const prEl = document.getElementById(`bt_price_${sz.id}`) as HTMLInputElement;
																		if (!minEl.value || !prEl.value) return toast.error("Min qty and price required");
																		try {
																			await createBulkPrice({
																				id, variantId: v.id, sizeId: sz.id,
																				min_qty: parseInt(minEl.value, 10),
																				max_qty: maxEl.value ? parseInt(maxEl.value, 10) : null,
																				bulk_price: parseFloat(prEl.value)
																			}).unwrap();
																			toast.success("Tier added");
																			minEl.value = ""; maxEl.value = ""; prEl.value = "";
																		} catch { toast.error("Failed to add tier"); }
																	}}
																	className="bg-indigo-600 text-white text-[10px] px-2 py-1 rounded font-bold uppercase hover:bg-indigo-700 transition-colors"
																>
																	Add Tier
																</button>
															</div>
														</div>
													</div>
												))}

												{/* Add Size Form (Inline) */}
												<div className="flex gap-3 items-end bg-indigo-50/50 p-3 rounded-lg border border-indigo-100 mt-4">
													<div className="flex-1">
														<label className="block text-[10px] font-bold text-indigo-900 mb-1 uppercase">Size Name</label>
														<input
															type="text" placeholder="e.g. S, 40, Free"
															value={newSizeState[v.id]?.size_name || ""}
															onChange={(e) => setNewSizeState(p => ({ ...p, [v.id]: { ...(p[v.id] || { price: "", qty: "0" }), size_name: e.target.value } }))}
															className="w-full text-xs p-1.5 border rounded focus:ring-1 focus:ring-indigo-500"
														/>
													</div>
													<div className="w-24">
														<label className="block text-[10px] font-bold text-indigo-900 mb-1 uppercase">Price</label>
														<input
															type="number" step="0.01" placeholder="Price"
															value={newSizeState[v.id]?.price || ""}
															onChange={(e) => setNewSizeState(p => ({ ...p, [v.id]: { ...(p[v.id] || { size_name: "", qty: "0" }), price: e.target.value } }))}
															className="w-full text-xs p-1.5 border rounded focus:ring-1 focus:ring-indigo-500"
														/>
													</div>
													<div className="w-20">
														<label className="block text-[10px] font-bold text-indigo-900 mb-1 uppercase">Qty</label>
														<input
															type="number" placeholder="Qty"
															value={newSizeState[v.id]?.qty || "0"}
															onChange={(e) => setNewSizeState(p => ({ ...p, [v.id]: { ...(p[v.id] || { size_name: "", price: "" }), qty: e.target.value } }))}
															className="w-full text-xs p-1.5 border rounded focus:ring-1 focus:ring-indigo-500"
														/>
													</div>
													<button
														type="button"
														onClick={() => handleAddSize(v.id)}
														className="bg-indigo-600 text-white h-[32px] px-4 rounded font-bold text-xs uppercase hover:bg-indigo-700 transition-all shadow-sm"
													>
														Add Size
													</button>
												</div>
											</div>
										</div>
									</div>
								</div>
							))}
							{variants.length === 0 && (
								<p className="text-sm text-gray-500 text-center py-4 bg-white rounded border border-gray-100">No variants defined yet.</p>
							)}
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

