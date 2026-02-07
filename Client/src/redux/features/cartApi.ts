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
      query: (data) => {
        return {
          url: `/user-update-cart`,
          method: "POST",
          body: data?.formData,
        };
      },
      invalidatesTags: ["cartApi"],
    }),
    deleteCartItem: builder.mutation({
      query: (productId) => {
        return {
          url: `/user-destroy-cart`,
          method: "POST",
            body: { product_id: productId },
        };
      },
      invalidatesTags: ["cartApi"],
    }),
  }),
});

export const {
    useAddToCartMutation,
    useGetAllCartItemsQuery,
    useUpdateCartItemMutation,
    useDeleteCartItemMutation,
    useCreateOrderMutation,
} = courseApi;
