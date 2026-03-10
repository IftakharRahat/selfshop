import { baseApi } from "./baseApi";

export interface ShopProduct {
    id: number;
    product_id: number;
    status: string;
    added_at: string;
    product: {
        id: number;
        name: string;
        slug: string;
        sku: string;
        image: string | null;
        reseller_price: number | string;
        regular_price: number | string;
        qty: number;
        product_status: string;
    } | null;
}

export interface PublicShopProduct {
    id: number;
    name: string;
    slug: string;
    image: string | null;
    regular_price: number | string;
    qty: number;
}

export interface PublicShopData {
    shop_name: string;
    user_id: number;
    products: PublicShopProduct[];
}

const shopApi = baseApi.injectEndpoints({
    endpoints: (builder) => ({
        getShopProducts: builder.query<{ status: boolean; data: ShopProduct[] }, void>({
            query: () => "/shop-products",
            providesTags: ["shopProducts"],
        }),

        checkInShop: builder.query<{ status: boolean; in_shop: boolean }, number>({
            query: (productId) => `/check-in-shop/${productId}`,
            providesTags: (_result, _err, productId) => [
                { type: "shopProducts", id: productId },
            ],
        }),

        addToShop: builder.mutation<{ status: boolean; message: string }, number>({
            query: (productId) => `/add-to-shop/${productId}`,
            invalidatesTags: ["shopProducts"],
        }),

        removeFromShop: builder.mutation<{ status: boolean; message: string }, number>({
            query: (productId) => `/remove-from-shop/${productId}`,
            invalidatesTags: ["shopProducts"],
        }),

        getPublicShop: builder.query<{ status: boolean; data: PublicShopData }, number>({
            query: (userId) => `/reseller-shop/${userId}`,
        }),
    }),
});

export const {
    useGetShopProductsQuery,
    useCheckInShopQuery,
    useAddToShopMutation,
    useRemoveFromShopMutation,
    useGetPublicShopQuery,
} = shopApi;
