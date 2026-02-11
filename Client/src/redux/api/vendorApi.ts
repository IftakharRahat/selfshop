import { baseApi } from "./baseApi";
import type { RootState } from "../store";

export interface VendorProfile {
	id: number;
	user_id: number;
	company_name: string;
	slug: string;
	business_type?: string | null;
	contact_name?: string | null;
	contact_email?: string | null;
	contact_phone?: string | null;
	country?: string | null;
	state?: string | null;
	city?: string | null;
	postcode?: string | null;
	address_line_1?: string | null;
	address_line_2?: string | null;
	pickup_location_label?: string | null;
	status: "pending" | "approved" | "rejected" | "suspended";
}

export interface VendorKycDocument {
	id: number;
	vendor_id: number;
	document_type: string;
	document_number?: string | null;
	status: "pending" | "approved" | "rejected";
	created_at: string;
}

export interface VendorProduct {
	id: number;
	ProductName: string;
	ProductSlug: string;
	ProductSku: string;
	qty: number;
	ProductResellerPrice: number;
	ProductRegularPrice: number;
	status: string;
	frature?: string | number;
	ViewProductImage?: string | null;
	ProductImage?: string | null;
	ProductBreaf?: string | null;
	ProductDetails?: string | null;
	low_stock?: number;
	show_stock?: string;
	show_stock_text?: string;
	category_id?: number;
	subcategory_id?: number;
	brand_id?: number;
	minicategory_id?: number | null;
	created_at?: string;
	product_weight?: number;
	minimum_qty?: number;
	weight?: string | null;
	MetaKey?: string | null;
	Discount?: number | string;
	vendor_approval_status?: "pending" | "approved" | "rejected" | null;
	PostImage?: string | null;
}

export interface VendorOrderListItem {
	id: number;
	invoiceID: string;
	orderDate: string | null;
	status: string;
	Payment: string | null;
	paymentAmount: number | null;
	subTotal: number;
	customer_name: string | null;
	customer_phone: string | null;
	vendor_item_count: number;
	vendor_subtotal: number;
}

export interface VendorOrderDetail {
	order: {
		id: number;
		invoiceID: string;
		orderDate: string | null;
		deliveryDate: string | null;
		status: string;
		Payment: string | null;
		paymentAmount: number | null;
		subTotal: number;
		deliveryCharge: number | null;
		discountCharge: number | null;
		customerNote: string | null;
	};
	customer: { customerName: string; customerPhone: string; customerAddress: string } | null;
	line_items: Array<{
		id: number;
		product_id: number;
		productName: string;
		productCode: string;
		productPrice: number;
		quantity: number;
		line_total: number;
		product: { id: number; ProductName: string; ViewProductImage: string | null } | null;
	}>;
	vendor_subtotal: number;
}

export interface VendorCategoryDiscountItem {
	id: number;
	category_name: string;
	category_icon: string | null;
	slug: string;
	discount_percent: number;
	start_date: string | null;
	end_date: string | null;
}

export interface VendorReviewProduct {
	id: number;
	ProductName: string;
	ProductSlug: string;
	ViewProductImage: string | null;
	avg_rating: number;
	review_count: number;
	new_count: number;
}

export interface VendorReview {
	id: number;
	user_id: number;
	product_id: number;
	messages: string | null;
	rating: number;
	file: string | null;
	status: string;
	created_at: string;
	updated_at: string;
	user?: { id: number; name: string; email: string; profile?: string | null };
}

export const vendorApi = baseApi.injectEndpoints({
	endpoints: (build) => ({
		registerVendor: build.mutation<
			{ status: boolean; message: string },
			{
				name: string;
				email: string;
				password: string;
				company_name: string;
				business_type?: string;
				country?: string;
				city?: string;
			}
		>({
			query: (body) => ({
				url: "/vendor/register",
				method: "POST",
				body,
			}),
		}),
		getVendorProfile: build.query<
			{ status: boolean; data?: { user: unknown; vendor: VendorProfile | null } },
			void
		>({
			query: () => ({
				url: "/vendor/profile",
				method: "GET",
			}),
			providesTags: ["user"],
		}),
		upsertVendorProfile: build.mutation<
			{ status: boolean; data?: { vendor: VendorProfile } },
			Partial<VendorProfile> & { company_name: string }
		>({
			query: (body) => ({
				url: "/vendor/profile",
				method: "POST",
				body,
			}),
			invalidatesTags: ["user"],
		}),
		getVendorKycDocuments: build.query<
			{ status: boolean; data?: { documents: VendorKycDocument[] } },
			void
		>({
			query: () => ({
				url: "/vendor/kyc-documents",
				method: "GET",
			}),
			providesTags: ["user"],
		}),
		createVendorKycDocument: build.mutation<
			{ status: boolean; data?: { document: VendorKycDocument } },
			{ document_type: string; document_number?: string; file?: File }
		>({
			query: (payload) => {
				const formData = new FormData();
				formData.append("document_type", payload.document_type);
				if (payload.document_number) {
					formData.append("document_number", payload.document_number);
				}
				if (payload.file) {
					formData.append("file", payload.file);
				}

				return {
					url: "/vendor/kyc-documents",
					method: "POST",
					body: formData,
				};
			},
			invalidatesTags: ["user"],
		}),

		getVendorProducts: build.query<
			{ status: boolean; data?: { products: VendorProduct[] } },
			{ search?: string } | void
		>({
			query: (params) => ({
				url: "/vendor/products",
				params: params ?? {},
			}),
			providesTags: (result) =>
				result?.data?.products
					? [
							...result.data.products.map((p) => ({
								type: "vendorProducts" as const,
								id: p.id,
							})),
							{ type: "vendorProducts", id: "LIST" },
						]
					: [{ type: "vendorProducts", id: "LIST" }],
		}),
		getVendorProduct: build.query<
			{ status: boolean; data?: { product: VendorProduct } },
			number
		>({
			query: (id) => ({ url: `/vendor/products/${id}` }),
			providesTags: (_result, _err, id) => [{ type: "vendorProducts", id }],
		}),
		createVendorProduct: build.mutation<
			{ status: boolean; message?: string; data?: { product: VendorProduct } },
			FormData
		>({
			query: (body) => ({
				url: "/vendor/products",
				method: "POST",
				body,
			}),
			invalidatesTags: [{ type: "vendorProducts", id: "LIST" }],
		}),
		updateVendorProduct: build.mutation<
			{ status: boolean; message?: string; data?: { product: VendorProduct } },
			{ id: number; body: FormData }
		>({
			// Use custom fetch so FormData is sent without Content-Type (browser sets multipart boundary).
			// RTK Query's fetchBaseQuery can set application/json for some body types; FormData must be sent raw.
			queryFn: async ({ id, body }, api) => {
				const state = api.getState() as RootState;
				const token = state.auth?.access_token;
				const baseUrl = process.env.NEXT_PUBLIC_BASE_URL ?? "";
				const res = await fetch(`${baseUrl}/vendor/products/${id}`, {
					method: "POST",
					headers: {
						Accept: "application/json",
						...(token ? { Authorization: `Bearer ${token}` } : {}),
					},
					body,
				});
				const data = await res.json().catch(() => ({}));
				if (!res.ok) {
					return {
						error: {
							status: res.status,
							data: data?.message ?? data,
						},
					};
				}
				return { data };
			},
			invalidatesTags: (_result, _err, { id }) => [
				{ type: "vendorProducts", id },
				{ type: "vendorProducts", id: "LIST" },
			],
		}),
		deleteVendorProduct: build.mutation<
			{ status: boolean; message?: string },
			number
		>({
			query: (id) => ({
				url: `/vendor/products/${id}`,
				method: "DELETE",
			}),
			invalidatesTags: (_result, _err, id) => [
				{ type: "vendorProducts", id },
				{ type: "vendorProducts", id: "LIST" },
			],
		}),
		updateVendorProductStatus: build.mutation<
			{ status: boolean; message?: string; data?: { product: VendorProduct } },
			{ id: number; status: "Active" | "Inactive" }
		>({
			query: ({ id, status }) => ({
				url: `/vendor/products/${id}/status`,
				method: "PUT",
				body: { status },
			}),
			invalidatesTags: (_result, _err, { id }) => [
				{ type: "vendorProducts", id },
				{ type: "vendorProducts", id: "LIST" },
			],
		}),
		updateVendorProductFeatured: build.mutation<
			{ status: boolean; message?: string; data?: { product: VendorProduct } },
			{ id: number; featured: 0 | 1 }
		>({
			query: ({ id, featured }) => ({
				url: `/vendor/products/${id}/featured`,
				method: "PUT",
				body: { featured },
			}),
			invalidatesTags: (_result, _err, { id }) => [
				{ type: "vendorProducts", id },
				{ type: "vendorProducts", id: "LIST" },
			],
		}),

		// ── Variants ──
		getVendorProductVariants: build.query<
			{ status: boolean; data?: { variants: { id: number; product_id: number; title: string; qty: number; price: number; status: string }[] } },
			number
		>({
			query: (id) => ({ url: `/vendor/products/${id}/variants` }),
			providesTags: (_r, _e, id) => [{ type: "vendorProducts", id }],
		}),
		createVendorProductVariant: build.mutation<
			{ status: boolean; data?: { variant: { id: number; title: string; qty: number; price: number } } },
			{ id: number; title: string; qty: number; price: number }
		>({
			query: ({ id, ...body }) => ({
				url: `/vendor/products/${id}/variants`,
				method: "POST",
				body,
			}),
			invalidatesTags: (_r, _e, { id }) => [{ type: "vendorProducts", id }],
		}),
		updateVendorProductVariant: build.mutation<
			{ status: boolean; data?: { variant: unknown } },
			{ id: number; variantId: number; title?: string; qty?: number; price?: number; status?: string }
		>({
			query: ({ id, variantId, ...body }) => ({
				url: `/vendor/products/${id}/variants/${variantId}`,
				method: "PUT",
				body,
			}),
			invalidatesTags: (_r, _e, { id }) => [{ type: "vendorProducts", id }],
		}),
		deleteVendorProductVariant: build.mutation<
			{ status: boolean },
			{ id: number; variantId: number }
		>({
			query: ({ id, variantId }) => ({
				url: `/vendor/products/${id}/variants/${variantId}`,
				method: "DELETE",
			}),
			invalidatesTags: (_r, _e, { id }) => [{ type: "vendorProducts", id }],
		}),

		// ── Bulk upload ──
		createVendorBulkUpload: build.mutation<
			{ status: boolean; message?: string; data?: { created: number; errors: { row: number; message: string }[] } },
			FormData
		>({
			query: (body) => ({
				url: "/vendor/products/bulk-upload",
				method: "POST",
				body,
			}),
			invalidatesTags: [{ type: "vendorProducts", id: "LIST" }],
		}),

		// ── Price tiers ──
		getVendorProductPriceTiers: build.query<
			{ status: boolean; data?: { price_tiers: { id: number; product_id: number; min_qty: number; unit_price: number; tier_label: string }[] } },
			number
		>({
			query: (id) => ({ url: `/vendor/products/${id}/price-tiers` }),
			providesTags: (_r, _e, id) => [{ type: "vendorProducts", id }],
		}),
		createVendorProductPriceTier: build.mutation<
			{ status: boolean; data?: { price_tier: unknown } },
			{ id: number; min_qty: number; unit_price: number; tier_label?: string }
		>({
			query: ({ id, ...body }) => ({
				url: `/vendor/products/${id}/price-tiers`,
				method: "POST",
				body: { ...body, tier_label: body.tier_label ?? "Tier" },
			}),
			invalidatesTags: (_r, _e, { id }) => [{ type: "vendorProducts", id }],
		}),
		deleteVendorProductPriceTier: build.mutation<
			{ status: boolean },
			{ id: number; tierId: number }
		>({
			query: ({ id, tierId }) => ({
				url: `/vendor/products/${id}/price-tiers/${tierId}`,
				method: "DELETE",
			}),
			invalidatesTags: (_r, _e, { id }) => [{ type: "vendorProducts", id }],
		}),

		// ── Orders (Phase 4) ──
		getVendorOrders: build.query<
			{
				status: boolean;
				data?: {
					orders: VendorOrderListItem[];
					pagination: { current_page: number; last_page: number; per_page: number; total: number };
				};
			},
			{ search?: string; status?: string; payment?: string; page?: number; per_page?: number } | void
		>({
			query: (params) => ({
				url: "/vendor/orders",
				params: params ? { ...params, page: params.page ?? 1 } : {},
			}),
			providesTags: (result) =>
				result?.data?.orders
					? [
							...result.data.orders.map((o) => ({ type: "vendorOrders" as const, id: o.id })),
							{ type: "vendorOrders", id: "LIST" },
						]
					: [{ type: "vendorOrders", id: "LIST" }],
		}),
		getVendorOrder: build.query<
			{ status: boolean; data?: VendorOrderDetail },
			number
		>({
			query: (id) => ({ url: `/vendor/orders/${id}` }),
			providesTags: (_r, _e, id) => [{ type: "vendorOrders", id }],
		}),

		// ── Category-wise discounts ──
		getVendorCategoryDiscounts: build.query<
			{ status: boolean; data?: { categories: VendorCategoryDiscountItem[] } },
			void
		>({
			query: () => ({ url: "/vendor/category-discounts" }),
			providesTags: ["vendorCategoryDiscounts"],
		}),
		setVendorCategoryDiscount: build.mutation<
			{ status: boolean; message?: string },
			{ categoryId: number; discount_percent: number; start_date?: string | null; end_date?: string | null }
		>({
			query: ({ categoryId, ...body }) => ({
				url: `/vendor/category-discounts/${categoryId}`,
				method: "POST",
				body,
			}),
			invalidatesTags: ["vendorCategoryDiscounts"],
		}),

		// ── Product reviews ──
		getVendorReviewProducts: build.query<
			{ status: boolean; data?: { products: VendorReviewProduct[] } },
			{ search?: string; rating?: string } | void
		>({
			query: (params) => ({ url: "/vendor/reviews", params: params ?? {} }),
			providesTags: ["vendorReviews"],
		}),
		getVendorProductReviews: build.query<
			{
				status: boolean;
				data?: {
					product: { id: number; ProductName: string; ViewProductImage: string | null };
					avg_rating: number;
					review_count: number;
					reviews: VendorReview[];
				};
			},
			number
		>({
			query: (productId) => ({ url: `/vendor/reviews/${productId}` }),
			providesTags: (_r, _e, id) => [{ type: "vendorReviews", id }],
		}),
	}),
});

export const {
	useRegisterVendorMutation,
	useGetVendorProfileQuery,
	useUpsertVendorProfileMutation,
	useGetVendorKycDocumentsQuery,
	useCreateVendorKycDocumentMutation,
	useGetVendorProductsQuery,
	useGetVendorProductQuery,
	useCreateVendorProductMutation,
	useUpdateVendorProductMutation,
	useDeleteVendorProductMutation,
	useUpdateVendorProductStatusMutation,
	useUpdateVendorProductFeaturedMutation,
	useGetVendorProductVariantsQuery,
	useCreateVendorProductVariantMutation,
	useUpdateVendorProductVariantMutation,
	useDeleteVendorProductVariantMutation,
	useCreateVendorBulkUploadMutation,
	useGetVendorProductPriceTiersQuery,
	useCreateVendorProductPriceTierMutation,
	useDeleteVendorProductPriceTierMutation,
	useGetVendorOrdersQuery,
	useGetVendorOrderQuery,
	useGetVendorCategoryDiscountsQuery,
	useSetVendorCategoryDiscountMutation,
	useGetVendorReviewProductsQuery,
	useGetVendorProductReviewsQuery,
} = vendorApi;

