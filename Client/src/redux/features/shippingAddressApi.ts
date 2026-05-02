import { baseApi } from "../api/baseApi";

interface ShippingAddress {
    id: number;
    label: string | null;
    name: string;
    address: string;
    phone: string;
    is_default: boolean;
    city_id: number | null;
    zone_id: number | null;
    area_id: number | null;
}

const shippingAddressApi = baseApi.injectEndpoints({
    endpoints: (builder) => ({
        getShippingAddresses: builder.query<{ status: boolean; data: ShippingAddress[] }, void>({
            query: () => ({
                url: "/shipping-addresses",
                method: "GET",
            }),
            providesTags: ["shippingAddresses"],
        }),
        createShippingAddress: builder.mutation({
            query: (data: { label?: string; name: string; address: string; phone: string; is_default?: boolean; city_id?: number | null; zone_id?: number | null; area_id?: number | null }) => ({
                url: "/shipping-addresses",
                method: "POST",
                body: data,
            }),
            invalidatesTags: ["shippingAddresses"],
        }),
        updateShippingAddress: builder.mutation({
            query: ({ id, ...data }: { id: number; label?: string; name?: string; address?: string; phone?: string; is_default?: boolean }) => ({
                url: `/shipping-addresses/${id}`,
                method: "PUT",
                body: data,
            }),
            invalidatesTags: ["shippingAddresses"],
        }),
        deleteShippingAddress: builder.mutation({
            query: (id: number) => ({
                url: `/shipping-addresses/${id}`,
                method: "DELETE",
            }),
            invalidatesTags: ["shippingAddresses"],
        }),
    }),
});

export const {
    useGetShippingAddressesQuery,
    useCreateShippingAddressMutation,
    useUpdateShippingAddressMutation,
    useDeleteShippingAddressMutation,
} = shippingAddressApi;
