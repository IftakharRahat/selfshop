/* eslint-disable @typescript-eslint/no-explicit-any */

import { baseApi } from "../api/baseApi";

const productDetailsApi = baseApi.injectEndpoints({
	endpoints: (builder) => ({
		// getAllWithdrawMethods: builder.query({
		//   query: () => {

		//     return {
		//       url: `/get-payment-types`,
		//       method: "GET",
		//     };
		//   },
		//   providesTags: ["withdrawApi"],
		// }),
		// getWithdrawList: builder.query({
		//   query: () => {

		//     return {
		//       url: `/withdraw-list`,
		//       method: "GET",
		//     };
		//   },
		//   providesTags: ["withdrawApi"],
		// }),
		getSingleProduct: builder.query({
			query: (product) => ({
				url: `/product-details/${product}`,
				method: "GET",
			}),
			providesTags: ["productDetailsApi"],
		}),

		// createWithdrawRequest: builder.mutation({
		//   query: (data) => {
		//     return {
		//       url: "/give-withdraw-request",
		//       method: "POST",
		//       body: data,
		//     };
		//   },
		//   invalidatesTags: ["withdrawApi"],
		// }),

		// updateExample: builder.mutation({
		//   query: (data) => {
		//     return {
		//       url: `example/${data?.id}`,
		//       method: "POST",
		//       body: data?.formData,
		//     };
		//   },
		//   invalidatesTags: ["example"],
		// }),
		// deleteExample: builder.mutation({
		//   query: (id) => {
		//     return {
		//       url: `example/${id}`,
		//       method: "DELETE",
		//     };
		//   },
		//   invalidatesTags: ["example"],
		// }),
	}),
});

export const { useGetSingleProductQuery } = productDetailsApi;
