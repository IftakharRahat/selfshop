/* eslint-disable @typescript-eslint/no-explicit-any */
import { baseApi } from "./baseApi";

// ── Types ──────────────────────────────────────────────────────────────
export interface Campaign {
    id: number;
    title: string;
    start_time: string;
    end_time: string;
    registration_deadline: string | null;
    vendor_registration: boolean;
    status: string;
    banner_image: string | null;
    products_count: number;
    vendor_product_count?: number;
    created_at: string;
    updated_at: string;
}

export interface CampaignProduct {
    id: number;
    flash_sale_id: number;
    product_id: number;
    vendor_id: number;
    discount_percentage: number;
    campaign_price: number;
    seller_sku: string | null;
    product: {
        id: number;
        ProductName: string;
        ViewProductImage: string | null;
        ProductSalePrice: number;
        ProductRegularPrice: number;
        qty: number;
        ProductSku: string;
    } | null;
    created_at: string;
}

// ── API Slice ──────────────────────────────────────────────────────────
const campaignApi = baseApi.injectEndpoints({
    endpoints: (builder) => ({
        vendorCampaigns: builder.query<Campaign[], void>({
            query: () => ({
                url: "/vendor/campaigns",
                method: "GET",
            }),
            transformResponse: (res: any) => res?.data?.campaigns ?? [],
            providesTags: ["vendorCampaigns"],
        }),

        vendorCampaign: builder.query<
            { campaign: Campaign; vendor_products: CampaignProduct[] },
            number
        >({
            query: (id) => ({
                url: `/vendor/campaigns/${id}`,
                method: "GET",
            }),
            transformResponse: (res: any) => res?.data ?? {},
            providesTags: (_r, _e, id) => [
                { type: "vendorCampaigns", id },
            ],
        }),

        submitCampaignProduct: builder.mutation<
            CampaignProduct,
            {
                campaignId: number;
                product_id: number;
                campaign_price: number;
                seller_sku?: string;
            }
        >({
            query: ({ campaignId, ...body }) => ({
                url: `/vendor/campaigns/${campaignId}/products`,
                method: "POST",
                body,
            }),
            invalidatesTags: ["vendorCampaigns"],
        }),

        removeCampaignProduct: builder.mutation<
            void,
            { campaignId: number; fspId: number }
        >({
            query: ({ campaignId, fspId }) => ({
                url: `/vendor/campaigns/${campaignId}/products/${fspId}`,
                method: "DELETE",
            }),
            invalidatesTags: ["vendorCampaigns"],
        }),

        // Vendor's own products for the product picker
        vendorProductsForCampaign: builder.query<any[], string | void>({
            query: (search) => ({
                url: `/vendor/products${search ? `?search=${search}` : ""}`,
                method: "GET",
            }),
            transformResponse: (res: any) =>
                res?.data?.products ?? [],
        }),
    }),
});

export const {
    useVendorCampaignsQuery,
    useVendorCampaignQuery,
    useSubmitCampaignProductMutation,
    useRemoveCampaignProductMutation,
    useVendorProductsForCampaignQuery,
} = campaignApi;
