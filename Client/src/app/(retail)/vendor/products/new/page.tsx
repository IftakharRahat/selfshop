"use client";

import { useState, useMemo } from "react";
import { useRouter } from "next/navigation";
import WithVendorAuth from "../../WithVendorAuth";
import { toast } from "sonner";
import {
	useCreateVendorProductMutation,
	useCreateVendorProductVariantMutation,
	useCreateVendorProductPriceTierMutation,
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
	type CatItem = { id: number; category_name: string; subcategories?: { id: number; sub_category_name: string; category_id: number }[] };
	const categories = (catData as { data?: CatItem[] })?.data ?? [];
	const brands = (brandData as { data?: Array<{ id: number; brand_name: string }> })?.data ?? [];

	const [selectedCategoryId, setSelectedCategoryId] = useState<string>("");
	const [createVariant] = useCreateVendorProductVariantMutation();
	const [createTier] = useCreateVendorProductPriceTierMutation();

	// Pending variants & tiers (added after product is created)
	type PendingVariant = { title: string; qty: string; price: string };
	type PendingTier = { min_qty: string; unit_price: string; tier_label: string };
	const [pendingVariants, setPendingVariants] = useState<PendingVariant[]>([]);
	const [pendingTiers, setPendingTiers] = useState<PendingTier[]>([]);
	const [newVariant, setNewVariant] = useState<PendingVariant>({ title: "", qty: "0", price: "0" });
	const [newTier, setNewTier] = useState<PendingTier>({ min_qty: "0", unit_price: "", tier_label: "Tier 1" });

	const subcategories = useMemo(() => {
		if (!selectedCategoryId) return [];
		const cat = categories.find((c) => c.id === Number(selectedCategoryId));
		return cat?.subcategories ?? [];
	}, [categories, selectedCategoryId]);

	const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
		e.preventDefault();
		setSaving(true);
		const form = e.currentTarget;
		const formData = new FormData();
		formData.append("ProductName", (form.querySelector('[name="name"]') as HTMLInputElement).value);
		formData.append("category_id", (form.querySelector('[name="category_id"]') as HTMLSelectElement).value);
		formData.append("subcategory_id", (form.querySelector('[name="subcategory_id"]') as HTMLSelectElement).value);
		formData.append("brand_id", (form.querySelector('[name="brand_id"]') as HTMLSelectElement).value);
		const brief = (form.querySelector('[name="short_description"]') as HTMLTextAreaElement).value;
		const details = (form.querySelector('[name="description"]') as HTMLTextAreaElement).value;
		if (brief) formData.append("ProductBreaf", brief);
		if (details) formData.append("ProductDetails", details);
		const basePrice = (form.querySelector('[name="base_price"]') as HTMLInputElement).value;
		const regularPrice = (form.querySelector('[name="regular_price"]') as HTMLInputElement)?.value ?? basePrice;
		formData.append("ProductResellerPrice", basePrice || "0");
		formData.append("ProductRegularPrice", regularPrice || "0");
		formData.append("qty", (form.querySelector('[name="qty"]') as HTMLInputElement).value || "0");
		formData.append("low_stock", (form.querySelector('[name="low_stock"]') as HTMLInputElement).value || "0");
		const sku = (form.querySelector('[name="sku"]') as HTMLInputElement).value;
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
		const allowDropship = (form.querySelector('[name="allow_dropship"]') as HTMLInputElement)?.checked;
		formData.append("allow_dropship", allowDropship ? "1" : "0");
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
				for (const v of pendingVariants) {
					if (!v.title.trim()) continue;
					try {
						await createVariant({
							id: productId,
							title: v.title.trim(),
							qty: parseInt(v.qty, 10) || 0,
							price: parseFloat(v.price) || 0,
						}).unwrap();
					} catch {
						toast.error(`Failed to add variant: ${v.title}`);
					}
				}
				for (const t of pendingTiers) {
					if (t.unit_price === "") continue;
					try {
						await createTier({
							id: productId,
							min_qty: parseInt(t.min_qty, 10) || 0,
							unit_price: parseFloat(t.unit_price) || 0,
							tier_label: t.tier_label || "Tier",
						}).unwrap();
					} catch {
						toast.error("Failed to add price tier");
					}
				}
			}
			toast.success("Product created.");
			router.push("/vendor/products");
		} catch (err: unknown) {
			const msg = err && typeof err === "object" && "data" in err && typeof (err as { data?: { message?: string } }).data?.message === "string"
				? (err as { data: { message: string } }).data.message
				: "Failed to create product.";
			toast.error(msg);
		} finally {
			setSaving(false);
		}
	};

	return (
		<WithVendorAuth>
			<div className="space-y-6">
				<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
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
					className="space-y-6 rounded-xl bg-white p-6 shadow-sm border border-gray-100"
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
										className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
									>
										<option value="">Select subcategory</option>
										{subcategories.map((s) => (
											<option key={s.id} value={s.id}>{s.sub_category_name}</option>
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
								<label className="inline-flex items-center gap-2 text-xs text-gray-700">
									<input type="checkbox" name="allow_dropship" className="rounded border-gray-300" />
									Allow dropship
								</label>
								<label className="inline-flex items-center gap-2 text-xs text-gray-700 ml-4">
									<input type="checkbox" className="rounded border-gray-300" />
									Published
								</label>
								<label className="inline-flex items-center gap-2 text-xs text-gray-700 ml-4">
									<input type="checkbox" className="rounded border-gray-300" />
									Featured
								</label>
							</div>
						</div>
					</div>

					{/* Variants */}
					<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
						<h2 className="text-sm font-semibold text-gray-900 mb-3">Variants</h2>
						<p className="text-xs text-gray-600 mb-3">Optional. Add after saving or here; they will be attached when you save the product.</p>
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
									{pendingVariants.map((v, i) => (
										<tr key={i} className="border-t border-gray-100">
											<td className="py-2 pr-3">{v.title}</td>
											<td className="py-2 pr-3">{v.qty}</td>
											<td className="py-2 pr-3">{v.price}</td>
											<td className="py-2">
												<button type="button" onClick={() => setPendingVariants((prev) => prev.filter((_, j) => j !== i))} className="text-xs text-red-600 hover:underline">Remove</button>
											</td>
										</tr>
									))}
									<tr className="border-t border-gray-200 bg-gray-50/50">
										<td className="py-2 pr-3 align-middle">
											<input placeholder="e.g. Red / S" value={newVariant.title} onChange={(e) => setNewVariant((p) => ({ ...p, title: e.target.value }))} className="mt-1 w-full max-w-[200px] rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
										</td>
										<td className="py-2 pr-3 align-middle">
											<input type="number" min={0} placeholder="0" value={newVariant.qty} onChange={(e) => setNewVariant((p) => ({ ...p, qty: e.target.value }))} className="mt-1 w-full max-w-[80px] rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
										</td>
										<td className="py-2 pr-3 align-middle">
											<input type="number" min={0} placeholder="0" value={newVariant.price} onChange={(e) => setNewVariant((p) => ({ ...p, price: e.target.value }))} className="mt-1 w-full max-w-[100px] rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
										</td>
										<td className="py-2 align-middle">
											<button type="button" onClick={() => { if (newVariant.title.trim()) { setPendingVariants((prev) => [...prev, { ...newVariant }]); setNewVariant({ title: "", qty: "0", price: "0" }); } }} className="rounded-md bg-gray-800 text-white px-3 py-1.5 text-xs">Add variant</button>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>

					{/* Wholesale price tiers */}
					<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
						<h2 className="text-sm font-semibold text-gray-900 mb-3">Wholesale price tiers</h2>
						<p className="text-xs text-gray-600 mb-3">Optional. Tiers will be attached when you save the product.</p>
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
									{pendingTiers.map((t, i) => (
										<tr key={i} className="border-t border-gray-100">
											<td className="py-2 pr-3">{t.min_qty}</td>
											<td className="py-2 pr-3">{t.unit_price}</td>
											<td className="py-2 pr-3">{t.tier_label}</td>
											<td className="py-2">
												<button type="button" onClick={() => setPendingTiers((prev) => prev.filter((_, j) => j !== i))} className="text-xs text-red-600 hover:underline">Remove</button>
											</td>
										</tr>
									))}
									<tr className="border-t border-gray-200 bg-gray-50/50">
										<td className="py-2 pr-3 align-middle">
											<input type="number" min={0} placeholder="0" value={newTier.min_qty} onChange={(e) => setNewTier((p) => ({ ...p, min_qty: e.target.value }))} className="mt-1 w-full max-w-[100px] rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
										</td>
										<td className="py-2 pr-3 align-middle">
											<input type="number" min={0} step="0.01" placeholder="0.00" value={newTier.unit_price} onChange={(e) => setNewTier((p) => ({ ...p, unit_price: e.target.value }))} className="mt-1 w-full max-w-[120px] rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
										</td>
										<td className="py-2 pr-3 align-middle">
											<input placeholder="e.g. Tier 1" value={newTier.tier_label} onChange={(e) => setNewTier((p) => ({ ...p, tier_label: e.target.value }))} className="mt-1 w-full max-w-[140px] rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
										</td>
										<td className="py-2 align-middle">
											<button type="button" onClick={() => { if (newTier.unit_price !== "") { setPendingTiers((prev) => [...prev, { ...newTier }]); setNewTier({ min_qty: "0", unit_price: "", tier_label: "Tier 1" }); } }} className="rounded-md bg-gray-800 text-white px-3 py-1.5 text-xs">Add tier</button>
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
							{saving ? "Saving..." : "Save product"}
						</button>
					</div>
				</form>
			</div>
		</WithVendorAuth>
	);
}

