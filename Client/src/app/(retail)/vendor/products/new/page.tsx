"use client";

import { useEffect, useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import WithVendorAuth from "../../WithVendorAuth";
import { toast } from "sonner";
import R2ImageUploader from "@/components/shared/r2-image-uploader";
import R2MultiImageUploader from "@/components/shared/r2-multi-image-uploader";
import {
	useCreateVendorProductMutation,
	useCreateVendorProductVariantMutation,
	useCreateVendorProductVariantSizeMutation,
	useCreateVendorProductVariantSizeBulkPriceMutation,
	useCreateVendorProductPriceTierMutation,
	useGetVendorCategoryCommissionsQuery,
	vendorApi,
} from "@/redux/api/vendorApi";
import {
	useGetAllNavbarCategoryDropdownOptionsQuery,
	useGetAllBrandsQuery,
} from "@/redux/features/home/homeApi";
import { formatBDT } from "@/lib/format-currency";

export default function VendorNewProductPage() {
	const router = useRouter();
	const [saving, setSaving] = useState(false);
	const [createProduct] = useCreateVendorProductMutation();
	const { data: catData } = useGetAllNavbarCategoryDropdownOptionsQuery(undefined);
	const { data: brandData } = useGetAllBrandsQuery(undefined);
	const { data: commissionData } = useGetVendorCategoryCommissionsQuery();

	type MiniCategoryItem = {
		id: number;
		mini_category_name: string;
		subcategory_id: number;
	};
	type SubCategoryItem = {
		id: number;
		sub_category_name: string;
		category_id: number;
		minicategories?: MiniCategoryItem[];
	};
	type CatItem = {
		id: number;
		category_name: string;
		subcategories?: SubCategoryItem[];
	};
	const categories = (catData as { data?: CatItem[] })?.data ?? [];
	const brands = (brandData as { data?: Array<{ id: number; brand_name: string }> })?.data ?? [];
	const commissionRows = commissionData?.data?.categories ?? [];


	const [selectedCategoryId, setSelectedCategoryId] = useState<string>("");
	const [selectedSubcategoryId, setSelectedSubcategoryId] = useState<string>("");
	const [selectedMinicategoryId, setSelectedMinicategoryId] = useState<string>("");
	const [basePrice, setBasePrice] = useState<string>("");
	const [regularPrice, setRegularPrice] = useState<string>("");
	const selectedCategoryCommission = selectedCategoryId
		? commissionRows.find((r) => r.category_id === Number(selectedCategoryId))
			?.commission_percent
		: null;

	const [createVariant] = useCreateVendorProductVariantMutation();
	const [createTier] = useCreateVendorProductPriceTierMutation();

	const [createVariantSize] = useCreateVendorProductVariantSizeMutation();
	const [createBulkPrice] = useCreateVendorProductVariantSizeBulkPriceMutation();

	// Variants & Sizes State
	type BulkPriceRow = {
		min_qty: string;
		max_qty: string;
		bulk_price: string;
	};

	type VariantSizeRow = {
		id: string; // temp frontend id
		size_name: string;
		qty: string;
		price: string;
		bulkTiers: BulkPriceRow[];
	};

	type VariantRow = {
		id: string; // temporary frontend id
		title: string;
		color_name: string;
		color_code: string;
		imageFile?: File;
		imagePreview?: string;
		sizes: VariantSizeRow[];
	};

	const [variants, setVariants] = useState<VariantRow[]>([]);
	const [newVariant, setNewVariant] = useState<Omit<VariantRow, 'id' | 'sizes'>>({
		title: "",
		color_name: "",
		color_code: "#000000",
	});

	// Image state for R2 uploaders
	const [thumbnailFile, setThumbnailFile] = useState<File | null>(null);
	const [galleryFiles, setGalleryFiles] = useState<File[]>([]);

	const subcategories = useMemo(() => {
		if (!selectedCategoryId) return [];
		const cat = categories.find((c) => c.id === Number(selectedCategoryId));
		return cat?.subcategories ?? [];
	}, [categories, selectedCategoryId]);

	const miniCategories = useMemo(() => {
		if (!selectedSubcategoryId) return [];
		const sub = subcategories.find(
			(item) => item.id === Number(selectedSubcategoryId),
		);
		return sub?.minicategories ?? [];
	}, [selectedSubcategoryId, subcategories]);

	useEffect(() => {
		setSelectedSubcategoryId("");
		setSelectedMinicategoryId("");
	}, [selectedCategoryId]);

	useEffect(() => {
		setSelectedMinicategoryId("");
	}, [selectedSubcategoryId]);

	const [sellingType, setSellingType] = useState<'wholesale' | 'dropshipping' | 'both'>('wholesale');

	// Clear variants when switching to dropshipping (dropshipping has no variants)
	useEffect(() => {
		if (sellingType === 'dropshipping') {
			setVariants([]);
		}
	}, [sellingType]);

	const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
		e.preventDefault();
		setSaving(true);
		const form = e.currentTarget;
		const formData = new FormData();
		formData.append("ProductName", (form.querySelector('[name="name"]') as HTMLInputElement).value);
		formData.append("category_id", (form.querySelector('[name="category_id"]') as HTMLSelectElement).value);
		formData.append("subcategory_id", (form.querySelector('[name="subcategory_id"]') as HTMLSelectElement).value);
		const minicategoryId = (form.querySelector('[name="minicategory_id"]') as HTMLSelectElement | null)?.value;
		if (minicategoryId) {
			formData.append("minicategory_id", minicategoryId);
		}
		formData.append("brand_id", (form.querySelector('[name="brand_id"]') as HTMLSelectElement).value);
		const brief = (form.querySelector('[name="short_description"]') as HTMLTextAreaElement).value;
		const details = (form.querySelector('[name="description"]') as HTMLTextAreaElement).value;
		if (brief) formData.append("ProductBreaf", brief);
		if (details) formData.append("ProductDetails", details);
		const basePrice = (form.querySelector('[name="base_price"]') as HTMLInputElement)?.value;
		formData.append("ProductResellerPrice", basePrice || "0");
		const regularPrice = (form.querySelector('[name="regular_price"]') as HTMLInputElement)?.value;
		formData.append("ProductRegularPrice", regularPrice || "0");
		formData.append("qty", (form.querySelector('[name="qty"]') as HTMLInputElement)?.value || "0");
		formData.append("low_stock", (form.querySelector('[name="low_stock"]') as HTMLInputElement)?.value || "0");
		const sku = (form.querySelector('[name="sku"]') as HTMLInputElement)?.value;
		if (sku) formData.append("ProductSku", sku);
		const stockVis = (form.querySelector('[name="stock_visibility"]:checked') as HTMLInputElement)?.value ?? "quantity";
		formData.append("show_stock", stockVis === "quantity" ? "On" : "Off");
		formData.append("show_stock_text", stockVis === "text" ? "On" : "Off");
		formData.append("product_weight", (form.querySelector('[name="product_weight"]') as HTMLInputElement)?.value || "0");
		formData.append("minimum_qty", (form.querySelector('[name="minimum_qty"]') as HTMLInputElement)?.value || "1");
		const unit = (form.querySelector('[name="unit"]') as HTMLInputElement)?.value;
		if (unit) formData.append("unit", unit);
		const tags = (form.querySelector('[name="tags"]') as HTMLInputElement)?.value;
		if (tags) formData.append("MetaKey", tags);
		const discount = (form.querySelector('[name="discount"]') as HTMLInputElement)?.value;
		if (discount !== undefined && discount !== "") formData.append("Discount", discount);
		formData.append("selling_type", sellingType);
		formData.append("allow_dropship", sellingType === 'dropshipping' || sellingType === 'both' ? "1" : "0");
		if (thumbnailFile) formData.append("ProductImage", thumbnailFile);
		if (galleryFiles.length > 0) {
			for (let i = 0; i < galleryFiles.length; i++) {
				formData.append("PostImage[]", galleryFiles[i]);
			}
		}
		try {
			const res = await createProduct(formData).unwrap();
			const productId = res?.data?.product?.id;
			if (productId != null) {
				// 2. Create Variants
				for (const v of variants) {
					try {
						const varFormData = new FormData();
						const title = v.title || v.color_name || "Variant";
						varFormData.append("title", title);
						if (v.color_name) varFormData.append("color_name", v.color_name);
						if (v.color_code) varFormData.append("color_code", v.color_code);
						varFormData.append("qty", "0"); // Default for variant-level, actual qty in sizes
						varFormData.append("price", "0"); // Default for variant-level
						if (v.imageFile) varFormData.append("image", v.imageFile);

						// Use RTK mutation instead of manual fetch
						const variantResult = await createVariant({ id: productId, body: varFormData }).unwrap();
						const newVariantId = variantResult.data?.variant?.id;

						if (!newVariantId) throw new Error("Failed to get variant ID");

						// 3. Create Sizes for this variant
						for (const s of v.sizes) {
							if (!s.size_name) continue;

							const sizeResult = await createVariantSize({
								id: productId,
								variantId: newVariantId,
								size_name: s.size_name,
								qty: parseInt(s.qty, 10) || 0,
								price: s.price ? parseFloat(s.price) : null,
								status: "Active"
							}).unwrap();

							const newSizeId = (sizeResult.data as any)?.size?.id;
							if (!newSizeId) continue;

							// 4. Create Bulk Prices for this size
							for (const bt of s.bulkTiers) {
								if (!bt.min_qty || !bt.bulk_price) continue;

								await createBulkPrice({
									id: productId,
									variantId: newVariantId,
									sizeId: newSizeId,
									min_qty: parseInt(bt.min_qty, 10),
									max_qty: bt.max_qty ? parseInt(bt.max_qty, 10) : null,
									bulk_price: parseFloat(bt.bulk_price),
								}).unwrap();
							}
						}
					} catch (err) {
						console.error("Variant creation failed:", err);
						toast.error(`Failed to add variant: ${v.color_name || v.title}`);
					}
				}
			}
			toast.success("Product created.");
			router.push("/vendor/products");
		} catch (err: any) {
			console.error("Product creation error:", JSON.stringify(err, null, 2));
			let msg = "Failed to create product.";
			if (err?.data?.errors) {
				const errors = err.data.errors;
				const firstError = Object.values(errors)[0];
				if (Array.isArray(firstError) && firstError.length > 0) {
					msg = firstError[0];
				}
			} else if (err?.data?.message) {
				msg = err.data.message;
			} else if (err?.message) {
				msg = err.message;
			} else if (err?.status) {
				msg = `Server error (status ${err.status}). Check the Laravel log for details.`;
			}
			toast.error(msg);
		} finally {
			setSaving(false);
		}
	};

	return (
		<WithVendorAuth>
			<div className="space-y-6">
				<div className="rounded-xl bg-white p-4 sm:p-6 shadow-sm border border-gray-100">
					<h1 className="text-2xl font-bold text-gray-900 mb-1">
						Add new product
					</h1>
					<p className="text-sm text-gray-600">
						Fill in the required fields to create a product. Multi-language and
						video sections are intentionally omitted for now.
					</p>
				</div>

				<form
					onSubmit={handleSubmit}
					className="space-y-6 rounded-xl bg-white p-4 sm:p-6 shadow-sm border border-gray-100"
				>
					<div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
						<div className="space-y-4 lg:col-span-2">
							<div className="space-y-3">
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
											<span className="text-sm font-bold text-gray-900 flex items-center gap-1.5">
												🏭 Wholesale
											</span>
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
											<span className="text-sm font-bold text-gray-900 flex items-center gap-1.5">
												🚀 Dropshipping
											</span>
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
											<span className="text-sm font-bold text-gray-900 flex items-center gap-1.5">
												🔄 Both
											</span>
											<span className="text-[10px] text-gray-500 leading-tight mt-0.5">Wholesale + Dropship</span>
										</div>
									</label>
								</div>

								<label className="flex flex-col text-sm font-medium text-gray-700">
									Product name
									<input
										required
										name="name"
										className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
									/>
								</label>
								<div className="grid grid-cols-1 md:grid-cols-2 gap-3">
									<label className="flex flex-col text-sm font-medium text-gray-700">
										Unit (e.g. Pc, Kg)
										<input name="unit" placeholder="Pc" className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
									</label>
									<label className="flex flex-col text-sm font-medium text-gray-700">
										Weight (kg)
										<input type="number" min={0} step="0.01" name="product_weight" defaultValue={0} className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
									</label>
									<label className="flex flex-col text-sm font-medium text-gray-700">
										Minimum purchase qty
										<input type="number" min={1} name="minimum_qty" defaultValue={1} className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
									</label>
									<label className="flex flex-col text-sm font-medium text-gray-700">
										Tags (comma separated)
										<input name="tags" placeholder="tag1, tag2" className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
									</label>
								</div>
							</div>

							{(sellingType === 'dropshipping' || sellingType === 'both') && (
								<div className="space-y-3">
									<h2 className="text-sm font-semibold text-gray-900">
										Product price &amp; stock
									</h2>
									<div className="grid grid-cols-1 md:grid-cols-2 gap-3">
										<label className="flex flex-col text-sm font-medium text-gray-700">
											Base price (reseller)
											<input
												type="number"
												min={0}
												step="0.01"
												name="base_price"
												value={basePrice}
												onChange={(e) => setBasePrice(e.target.value)}
												className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
											/>
											{basePrice && selectedCategoryCommission !== null && !isNaN(Number(basePrice)) && (
												<p className="text-xs text-green-600 mt-1 font-semibold">
													Storefront price: ৳{formatBDT(Number(basePrice) * (1 + Number(selectedCategoryCommission) / 100))}
													<span className="text-[10px] ml-1 font-normal">(after {selectedCategoryCommission}% admin commission)</span>
												</p>
											)}

										</label>
										<label className="flex flex-col text-sm font-medium text-gray-700">
											Regular price (MSRP)
											<input
												type="number"
												step="0.01"
												value={regularPrice}
												onChange={(e) => setRegularPrice(e.target.value)}
												name="regular_price"
												placeholder="Manual entry (optional)"
												className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
											/>
										</label>
										<label className="flex flex-col text-sm font-medium text-gray-700">
											Quantity
											<input
												type="number"
												min={0}
												name="qty"
												className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
											/>
										</label>
										<label className="flex flex-col text-sm font-medium text-gray-700">
											Low stock warning at
											<input
												type="number"
												min={0}
												name="low_stock"
												className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
											/>
										</label>
										<label className="flex flex-col text-sm font-medium text-gray-700">
											SKU
											<input
												name="sku"
												className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
											/>
										</label>
										<label className="flex flex-col text-sm font-medium text-gray-700">
											Discount
											<input type="number" min={0} step="0.01" name="discount" defaultValue={0} className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
										</label>
									</div>
									<p className="text-xs text-gray-600 font-medium mt-1">Stock visibility</p>
									<div className="flex flex-wrap gap-4 text-sm text-gray-700">
										<label className="inline-flex items-center gap-2">
											<input type="radio" name="stock_visibility" value="quantity" defaultChecked className="rounded-full border-gray-300" />
											Show stock quantity
										</label>
										<label className="inline-flex items-center gap-2">
											<input type="radio" name="stock_visibility" value="text" className="rounded-full border-gray-300" />
											Show stock text only
										</label>
										<label className="inline-flex items-center gap-2">
											<input type="radio" name="stock_visibility" value="hide" className="rounded-full border-gray-300" />
											Hide stock
										</label>
									</div>
								</div>
							)}

							<div className="space-y-3">
								<h2 className="text-sm font-semibold text-gray-900">
									Product description
								</h2>
								<label className="flex flex-col text-sm font-medium text-gray-700">
									Short description
									<textarea
										name="short_description"
										rows={2}
										className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
									/>
								</label>
								<label className="flex flex-col text-sm font-medium text-gray-700">
									Description
									<textarea
										name="description"
										rows={4}
										className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
									/>
								</label>
							</div>
						</div>

						<div className="space-y-4 lg:col-span-1">
							<div className="space-y-2">
								<h2 className="text-sm font-semibold text-gray-900">
									Product category
								</h2>
								<p className="text-xs text-gray-500">
									Vendors must use existing categories from main website.
								</p>
								<label className="flex flex-col text-xs font-medium text-gray-700">
									Category
									<select
										name="category_id"
										required
										value={selectedCategoryId}
										onChange={(e) => setSelectedCategoryId(e.target.value)}
										className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
									>
										<option value="">Select category</option>
										{categories.map((c) => (
											<option key={c.id} value={c.id}>{c.category_name}</option>
										))}
									</select>
								</label>
								<label className="flex flex-col text-xs font-medium text-gray-700">
									Subcategory
									<select
										name="subcategory_id"
										required
										value={selectedSubcategoryId}
										onChange={(e) => setSelectedSubcategoryId(e.target.value)}
										className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
									>
										<option value="">Select subcategory</option>
										{subcategories.map((s) => (
											<option key={s.id} value={s.id}>{s.sub_category_name}</option>
										))}
									</select>
								</label>
								<label className="flex flex-col text-xs font-medium text-gray-700">
									Child category
									<select
										name="minicategory_id"
										value={selectedMinicategoryId}
										onChange={(e) => setSelectedMinicategoryId(e.target.value)}
										required={miniCategories.length > 0}
										disabled={!selectedSubcategoryId}
										className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100 disabled:text-gray-500"
									>
										<option value="">
											{!selectedSubcategoryId
												? "Select subcategory first"
												: miniCategories.length > 0
													? "Select child category"
													: "No child category"}
										</option>
										{miniCategories.map((m) => (
											<option key={m.id} value={m.id}>
												{m.mini_category_name}
											</option>
										))}
									</select>
								</label>
								<label className="flex flex-col text-xs font-medium text-gray-700">
									Brand
									<select
										name="brand_id"
										required
										className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
									>
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
													className={`text-[11px] flex items-center justify-between rounded px-2 py-1 ${Number(selectedCategoryId) === row.category_id
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
							<div className="space-y-2">
								<h2 className="text-sm font-semibold text-gray-900">
									Product images
								</h2>
								<R2MultiImageUploader
									label="Gallery images"
									value={galleryFiles}
									onChange={setGalleryFiles}
								/>
								<R2ImageUploader
									label="Thumbnail image"
									value={thumbnailFile}
									onChange={setThumbnailFile}
									compact
								/>
							</div>

						</div>
					</div>

					{/* Product Variants (Colors & Sizes) */}
					{sellingType !== 'dropshipping' && (
					<div className="rounded-xl bg-indigo-50/30 p-4 sm:p-6 shadow-sm border border-indigo-100">
						<h2 className="text-sm font-bold text-indigo-900 mb-3 flex items-center gap-2">
							🎨 Product Variants (Colors & Sizes)
						</h2>
						<p className="text-xs text-indigo-700 mb-4">
							Add color variants first, then attach available sizes to each color.
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
								<R2ImageUploader
									value={newVariant.imageFile ?? null}
									onChange={(file) => {
										setNewVariant({
											...newVariant,
											imageFile: file ?? undefined,
											imagePreview: file ? URL.createObjectURL(file) : undefined,
										});
									}}
									compact
								/>
							</div>
							<div>
								<button
									type="button"
									onClick={() => {
										if (!newVariant.color_name) return toast.error("Color name is required.");
										setVariants([...variants, {
											...newVariant,
											id: Math.random().toString(36).substring(7),
											sizes: []
										}]);
										setNewVariant({ title: "", color_name: "", color_code: "#000000", imageFile: undefined, imagePreview: undefined });
									}}
									className="h-8 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-md transition-colors"
								>
									Add Color
								</button>
							</div>
						</div>

						{/* Variants List */}
						<div className="space-y-6">
							{variants.map((v, vIdx) => (
								<div key={v.id} className="bg-white border border-gray-200 rounded-lg p-5 relative shadow-sm">
									<button
										type="button"
										onClick={() => setVariants(variants.filter((_, idx) => idx !== vIdx))}
										className="absolute top-4 right-4 text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 rounded-md p-1.5 transition-colors"
									>
										<svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
									</button>

									<div className="flex gap-5 items-start pr-12">
										<div className="w-20 h-20 rounded-lg border border-gray-200 overflow-hidden bg-gray-50 flex-shrink-0 flex items-center justify-center">
											{v.imagePreview ? (
												<img src={v.imagePreview} alt="Preview" className="w-full h-full object-cover" />
											) : (
												<div className="w-10 h-10 rounded-full border border-gray-300 shadow-inner" style={{ backgroundColor: v.color_code || '#fff' }} />
											)}
										</div>
										<div className="flex-1">
											<h3 className="text-lg font-bold text-gray-900 leading-tight">
												{v.color_name} <span className="text-sm font-normal text-gray-500 ml-2">({v.title || 'no title'})</span>
											</h3>
											<p className="text-xs text-gray-500 mb-4 mt-1">Add sizes and bulk pricing tiers for this color.</p>

											{/* Sizes Section */}
											<div className="space-y-4">
												{v.sizes.map((sz, szIdx) => (
													<div key={sz.id} className="bg-gray-50 border border-gray-100 rounded-lg p-3">
														<div className="flex items-center gap-4 mb-3">
															<div className="flex-1 grid grid-cols-3 gap-3">
																<div>
																	<label className="text-[10px] font-bold text-gray-500 uppercase">Size Name</label>
																	<div className="font-semibold text-gray-800">{sz.size_name}</div>
																</div>
																<div>
																	<label className="text-[10px] font-bold text-gray-500 uppercase">Price</label>
																	<div className="font-semibold text-indigo-600">৳{formatBDT(Number(sz.price))}</div>
																	{sz.price && selectedCategoryCommission !== null && (
																		<div className="text-[9px] text-green-600 font-medium">
																			Store: ৳{formatBDT(Number(sz.price) * (1 + Number(selectedCategoryCommission) / 100))}
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
																onClick={() => {
																	const newVars = [...variants];
																	newVars[vIdx].sizes = newVars[vIdx].sizes.filter((_, idx) => idx !== szIdx);
																	setVariants(newVars);
																}}
																className="text-red-500 hover:text-red-700 bg-white border border-red-100 p-1 rounded"
															>
																<svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" /></svg>
															</button>
														</div>

														{/* Bulk Tiers for this size */}
														<div className="pl-4 border-l-2 border-indigo-100 space-y-2">
															<h5 className="text-[10px] font-bold text-indigo-900 uppercase tracking-wider mb-1">Bulk Pricing Tiers</h5>
															{sz.bulkTiers.map((bt, btIdx) => (
																<div key={btIdx} className="flex items-center gap-3 text-xs bg-white p-1.5 rounded border border-indigo-50">
																	<span className="flex-1 font-medium">Qty: {bt.min_qty} - {bt.max_qty || '∞'}</span>
																	<div className="text-right">
																		<span className="font-bold text-indigo-600">৳{formatBDT(Number(bt.bulk_price))}</span>
																		{selectedCategoryCommission !== null && (
																			<div className="text-[9px] text-green-600 font-medium">
																				Store: ৳{formatBDT(Number(bt.bulk_price) * (1 + Number(selectedCategoryCommission) / 100))}
																			</div>
																		)}

																	</div>
																	<button
																		type="button"
																		onClick={() => {
																			const newVars = [...variants];
																			newVars[vIdx].sizes[szIdx].bulkTiers = newVars[vIdx].sizes[szIdx].bulkTiers.filter((_, idx) => idx !== btIdx);
																			setVariants(newVars);
																		}}
																		className="text-red-400 hover:text-red-600"
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
																	<input id={`bt_price_${sz.id}`} type="number" step="0.01" placeholder="Bulk Price" className="w-full text-[10px] p-1 border rounded" />
																</div>
																<button
																	type="button"
																	onClick={() => {
																		const minEl = document.getElementById(`bt_min_${sz.id}`) as HTMLInputElement;
																		const maxEl = document.getElementById(`bt_max_${sz.id}`) as HTMLInputElement;
																		const prEl = document.getElementById(`bt_price_${sz.id}`) as HTMLInputElement;
																		if (!minEl.value || !prEl.value) return toast.error("Min qty and bulk price required");

																		const newVars = [...variants];
																		newVars[vIdx].sizes[szIdx].bulkTiers.push({
																			min_qty: minEl.value,
																			max_qty: maxEl.value,
																			bulk_price: prEl.value
																		});
																		setVariants(newVars);
																		minEl.value = ""; maxEl.value = ""; prEl.value = "";
																	}}
																	className="bg-indigo-600 text-white text-[10px] px-2 py-1 rounded font-bold uppercase"
																>
																	Add Tier
																</button>
															</div>
														</div>
													</div>
												))}

												{/* Add Size Form (Inline) */}
												<div className="flex gap-3 items-end bg-indigo-50/50 p-3 rounded-lg border border-indigo-100">
													<div className="flex-1">
														<label className="block text-[10px] font-bold text-indigo-900 mb-1 uppercase">Size Name</label>
														<input id={`sz_name_${v.id}`} type="text" placeholder="e.g. S, 40, Free" className="w-full text-xs p-1.5 border rounded" />
													</div>
													<div className="w-24">
														<label className="block text-[10px] font-bold text-indigo-900 mb-1 uppercase">Price</label>
														<input id={`sz_price_${v.id}`} type="number" step="0.01" placeholder="Price" className="w-full text-xs p-1.5 border rounded" />
													</div>
													<div className="w-20">
														<label className="block text-[10px] font-bold text-indigo-900 mb-1 uppercase">Qty</label>
														<input id={`sz_qty_${v.id}`} type="number" placeholder="Qty" defaultValue="0" className="w-full text-xs p-1.5 border rounded" />
													</div>
													<button
														type="button"
														onClick={() => {
															const nEl = document.getElementById(`sz_name_${v.id}`) as HTMLInputElement;
															const pEl = document.getElementById(`sz_price_${v.id}`) as HTMLInputElement;
															const qEl = document.getElementById(`sz_qty_${v.id}`) as HTMLInputElement;
															if (!nEl.value.trim() || !pEl.value) return toast.error("Size name and price required");

															const newVars = [...variants];
															newVars[vIdx].sizes.push({
																id: Math.random().toString(36).substring(7),
																size_name: nEl.value.trim(),
																price: pEl.value,
																qty: qEl.value || "0",
																bulkTiers: []
															});
															setVariants(newVars);
															nEl.value = ""; pEl.value = ""; qEl.value = "0";
														}}
														className="bg-indigo-600 text-white h-[30px] px-4 rounded font-bold text-xs uppercase transition-all hover:bg-indigo-700"
													>
														Add Size
													</button>
												</div>
											</div>
										</div>
									</div>
								</div>
							))}
						</div>
					</div>
					)}

					<div className="flex justify-end">
						<button
							type="submit"
							disabled={saving}
							className="inline-flex items-center px-5 py-2.5 rounded-lg bg-[#2d2a5d] text-white text-sm font-medium hover:bg-[#252947] disabled:opacity-60"
						>
							{saving ? "Saving..." : "Save product"}
						</button>
					</div>
				</form>
			</div>
		</WithVendorAuth>
	);
}


