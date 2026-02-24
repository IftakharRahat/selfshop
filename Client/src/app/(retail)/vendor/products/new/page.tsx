"use client";

import { useEffect, useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import WithVendorAuth from "../../WithVendorAuth";
import { toast } from "sonner";
import {
	useCreateVendorProductMutation,
	useCreateVendorProductVariantMutation,
	useCreateVendorProductPriceTierMutation,
	useGetVendorCategoryCommissionsQuery,
} from "@/redux/api/vendorApi";
import {
	useGetAllNavbarCategoryDropdownOptionsQuery,
	useGetAllBrandsQuery,
} from "@/redux/features/home/homeApi";

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
	const selectedCategoryCommission = selectedCategoryId
		? commissionRows.find((r) => r.category_id === Number(selectedCategoryId))
			?.commission_percent
		: null;
	const [createVariant] = useCreateVendorProductVariantMutation();
	const [createTier] = useCreateVendorProductPriceTierMutation();

	// Unified Bulk Pricing Variant-Wise
	type BulkPricingRow = {
		variant_title: string;
		color_name: string;
		color_code: string;
		min_qty: string;
		max_qty: string;
		price: string;
		delivery_charge: string;
	};
	const [bulkPricing, setBulkPricing] = useState<BulkPricingRow[]>([]);
	const [newBulkRow, setNewBulkRow] = useState<BulkPricingRow>({
		variant_title: "",
		color_name: "",
		color_code: "",
		min_qty: "1",
		max_qty: "",
		price: "",
		delivery_charge: "",
	});

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
		const regularPrice = (form.querySelector('[name="regular_price"]') as HTMLInputElement)?.value ?? basePrice;
		formData.append("ProductResellerPrice", basePrice || "0");
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
		const thumb = (form.querySelector('[name="thumbnail"]') as HTMLInputElement)?.files?.[0];
		if (thumb) formData.append("ProductImage", thumb);
		const galleryInput = form.querySelector('[name="gallery_images"]') as HTMLInputElement;
		if (galleryInput?.files?.length) {
			for (let i = 0; i < galleryInput.files.length; i++) {
				formData.append("PostImage[]", galleryInput.files[i]);
			}
		}
		try {
			const res = await createProduct(formData).unwrap();
			const productId = res?.data?.product?.id;
			if (productId != null) {
				// 1. Collect unique variant titles and their max qty/price if needed
				const uniqueVariants = new Map<
					string,
					{
						title: string;
						color_name?: string;
						color_code?: string;
						qty: number;
						price: number;
					}
				>();
				bulkPricing.forEach(row => {
					const title = row.variant_title.trim();
					if (title) {
						const colorName = row.color_name.trim();
						const colorCode = row.color_code.trim();
						const variantKey = `${title.toLowerCase()}|${colorName.toLowerCase()}|${colorCode.toLowerCase()}`;
						const currentMax = uniqueVariants.get(variantKey)?.qty ?? 0;
						const rowMax = parseInt(row.max_qty, 10) || 0;
						const rowPrice = parseFloat(row.price) || 0;
						if (rowMax > currentMax || !uniqueVariants.has(variantKey)) {
							uniqueVariants.set(variantKey, {
								title,
								color_name: colorName || undefined,
								color_code: colorCode || undefined,
								qty: rowMax,
								price: rowPrice,
							});
						}
					}
				});

				// 2. Create Variants
				for (const v of uniqueVariants.values()) {
					try {
						await createVariant({
							id: productId,
							title: v.title,
							color_name: v.color_name,
							color_code: v.color_code,
							qty: v.qty,
							price: v.price,
						}).unwrap();
					} catch {
						toast.error(`Failed to add variant: ${v.title}`);
					}
				}

				// 3. Create Price Tiers
				for (const row of bulkPricing) {
					if (!row.price) continue;
					try {
						await createTier({
							id: productId,
							min_qty: parseInt(row.min_qty, 10) || 0,
							max_qty: parseInt(row.max_qty, 10) || null,
							unit_price: parseFloat(row.price) || 0,
							delivery_charge: row.delivery_charge ? parseFloat(row.delivery_charge) : null,
							tier_label: row.variant_title || "Bulk",
							variant_title: row.variant_title || null,
						}).unwrap();
					} catch {
						toast.error("Failed to add bulk pricing tier");
					}
				}
			}
			toast.success("Product created.");
			router.push("/vendor/products");
		} catch (err: any) {
			console.error("Product creation error:", err);
			let msg = "Failed to create product.";
			if (err?.data?.errors) {
				const errors = err.data.errors;
				const firstError = Object.values(errors)[0];
				if (Array.isArray(firstError) && firstError.length > 0) {
					msg = firstError[0];
				}
			} else if (err?.data?.message) {
				msg = err.data.message;
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
								<h2 className="text-sm font-semibold text-gray-900">
									Basic information
								</h2>
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
												className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
											/>
										</label>
										<label className="flex flex-col text-sm font-medium text-gray-700">
											Regular price
											<input
												type="number"
												min={0}
												step="0.01"
												name="regular_price"
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
								<label className="flex flex-col text-xs font-medium text-gray-700">
									Gallery images
									<input
										name="gallery_images"
										type="file"
										multiple
										accept="image/*"
										className="mt-1 block w-full text-xs text-gray-700 file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-gray-900 file:text-white hover:file:bg-black"
									/>
									<span className="text-xs text-gray-500 mt-0.5">Multiple images for product gallery.</span>
								</label>
								<label className="flex flex-col text-xs font-medium text-gray-700">
									Thumbnail image
									<input
										name="thumbnail"
										type="file"
										accept="image/*"
										className="mt-1 block w-full text-xs text-gray-700 file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-gray-900 file:text-white hover:file:bg-black"
									/>
								</label>
							</div>

							<div className="space-y-2">
								<h2 className="text-sm font-semibold text-gray-900">
									Visibility &amp; options
								</h2>
								<p className="text-xs font-semibold text-gray-900 mb-2">Selling Type</p>
								<div className="flex flex-col gap-2">
									<label className={`flex items-center gap-3 rounded-lg border-2 p-3 cursor-pointer transition-all ${sellingType === 'wholesale'
											? 'border-green-500 bg-green-50'
											: 'border-gray-200 hover:border-gray-300'
										}`}>
										<input
											type="radio"
											name="selling_type_radio"
											value="wholesale"
											checked={sellingType === 'wholesale'}
											onChange={() => setSellingType('wholesale')}
											className="accent-green-600"
										/>
										<div>
											<span className="text-xs font-bold text-gray-900">🏭 Wholesale</span>
											<p className="text-[10px] text-gray-500">Tier-based bulk pricing</p>
										</div>
									</label>
									<label className={`flex items-center gap-3 rounded-lg border-2 p-3 cursor-pointer transition-all ${sellingType === 'dropshipping'
											? 'border-blue-500 bg-blue-50'
											: 'border-gray-200 hover:border-gray-300'
										}`}>
										<input
											type="radio"
											name="selling_type_radio"
											value="dropshipping"
											checked={sellingType === 'dropshipping'}
											onChange={() => setSellingType('dropshipping')}
											className="accent-blue-600"
										/>
										<div>
											<span className="text-xs font-bold text-gray-900">🚀 Dropshipping</span>
											<p className="text-[10px] text-gray-500">Single price + stock</p>
										</div>
									</label>
									<label className={`flex items-center gap-3 rounded-lg border-2 p-3 cursor-pointer transition-all ${sellingType === 'both'
											? 'border-amber-500 bg-amber-50'
											: 'border-gray-200 hover:border-gray-300'
										}`}>
										<input
											type="radio"
											name="selling_type_radio"
											value="both"
											checked={sellingType === 'both'}
											onChange={() => setSellingType('both')}
											className="accent-amber-600"
										/>
										<div>
											<span className="text-xs font-bold text-gray-900">🔄 Both</span>
											<p className="text-[10px] text-gray-500">Wholesale + Dropshipping</p>
										</div>
									</label>
								</div>
							</div>
						</div>
					</div>

					{/* Bulk Pricing Variant-Wise — visible for wholesale or both */}
					{(sellingType === 'wholesale' || sellingType === 'both') && (
						<div className="rounded-xl bg-indigo-50/30 p-4 sm:p-6 shadow-sm border border-indigo-100">
							<h2 className="text-sm font-bold text-indigo-900 mb-3 flex items-center gap-2">
								📦 Bulk Pricing Variant-Wise
							</h2>
							<p className="text-xs text-indigo-700 mb-4">
								Define quantity-based pricing. Variant name is optional. If left blank, pricing applies to the base product.
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
										{bulkPricing.map((row, i) => (
											<tr key={i} className="hover:bg-white/50 transition-colors">
												<td className="py-3 pr-3 text-gray-700 italic">{row.variant_title || "Base Product"}</td>
												<td className="py-3 pr-2 text-gray-700">{row.color_name || "-"}</td>
												<td className="py-3 pr-2 text-center">
													{row.color_code ? (
														<span
															className="inline-block h-5 w-5 rounded-full border border-gray-300"
															style={{ backgroundColor: row.color_code }}
															title={row.color_code}
														/>
													) : (
														<span className="text-gray-400">-</span>
													)}
												</td>
												<td className="py-3 pr-2 text-center font-medium text-gray-900">{row.min_qty}</td>
												<td className="py-3 pr-2 text-center font-medium text-gray-900">{row.max_qty || "∞"}</td>
												<td className="py-3 pr-2 font-bold text-gray-900">৳{row.price}</td>
												<td className="py-3 pr-2 text-gray-600">{row.delivery_charge ? `৳${row.delivery_charge}` : "Default"}</td>
												<td className="py-3 text-right">
													<button
														type="button"
														onClick={() => setBulkPricing(prev => prev.filter((_, j) => j !== i))}
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
													onClick={() => {
														if (newBulkRow.price) {
															setBulkPricing(prev => [...prev, { ...newBulkRow }]);
															setNewBulkRow({
																variant_title: "",
																color_name: "",
																color_code: "",
																min_qty: (parseInt(newBulkRow.max_qty || newBulkRow.min_qty) + 1).toString(),
																max_qty: "",
																price: "",
																delivery_charge: ""
															});
														} else {
															toast.error("Price is required");
														}
													}}
													className="w-full rounded-lg bg-indigo-600 text-white px-3 py-2 text-xs font-bold hover:bg-indigo-700 transition-colors shadow-sm"
												>
													+ Add
												</button>
											</td>
										</tr>
									</tbody>
								</table>
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


