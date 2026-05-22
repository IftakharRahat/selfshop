/* eslint-disable @typescript-eslint/no-explicit-any */

import { baseApi } from "../api/baseApi";

const courseApi = baseApi.injectEndpoints({
	endpoints: (builder) => ({
		getAllCartItems: builder.query({
			query: () => {
				return {
					url: `/user-cart-content`,
					method: "GET",
					//   params: params,
				};
			},
			providesTags: ["cartApi"],
		}),
		// getSingleCourse: builder.query({
		//   query: (course) => ({
		//     url: `/course-details/${course}`,
		//     method: "GET",
		//   }),
		//   providesTags: ["courseApi"],
		// }),

		addToCart: builder.mutation({
			query: (data) => {
				return {
					url: "/user-add-to-cart",
					method: "POST",
					body: data,
				};
			},
			invalidatesTags: ["cartApi"],
		}),
		createOrder: builder.mutation({
			query: (data) => {
				return {
					url: "/order-now",
					method: "POST",
					body: data,
				};
			},
			invalidatesTags: ["cartApi"],
		}),

		updateCartItem: builder.mutation({
			query: ({ cartId, qty }: { cartId: number; qty: number }) => {
				return {
					url: `/user-update-cart`,
					method: "POST",
					body: { cart_id: cartId, qty },
				};
			},
			invalidatesTags: ["cartApi"],
		}),
		deleteCartItem: builder.mutation({
			query: (cartId: number) => {
				return {
					url: `/user-destroy-cart`,
					method: "POST",
					body: { cart_id: cartId },
				};
			},
			invalidatesTags: ["cartApi"],
		}),
		getDeliveryCharges: builder.query<
			{
				same_city_charge: number;
				inter_city_charge: number;
				vendors?: Array<{
					vendor_id: number | null;
					charge: number;
					zone: string;
					zone_label: string;
					vendor_name: string;
				}>;
				total_charge?: number;
			},
			{ cityId: number }
		>({
			query: ({ cityId }) => ({
				url: `/delivery-charges`,
				method: "GET",
				params: { city_id: cityId },
			}),
			transformResponse: (response: any) => response?.data ?? response,
		}),
	}),
});

export const {
	useAddToCartMutation,
	useGetAllCartItemsQuery,
	useUpdateCartItemMutation,
	useDeleteCartItemMutation,
	useCreateOrderMutation,
	useGetDeliveryChargesQuery,
} = courseApi;
