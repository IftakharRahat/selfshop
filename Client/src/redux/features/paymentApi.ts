/* eslint-disable @typescript-eslint/no-explicit-any */

import { baseApi } from "../api/baseApi";

const paymentApi = baseApi.injectEndpoints({
	endpoints: (builder) => ({
		// getAllWithdrawMethods: builder.query({
		//   query: () => {

		//     return {
		//       url: `/get-payment-types`,
		//       method: "GET",
		//     };
		//   },
		//   providesTags: ["paymentApi"],
		// }),
		// getWithdrawList: builder.query({
		//   query: () => {

		//     return {
		//       url: `/withdraw-list`,
		//       method: "GET",
		//     };
		//   },
		//   providesTags: ["paymentApi"],
		// }),
		getSingleCourse: builder.query({
			query: () => ({
				url: `/invbkash/create-payment`,
				method: "GET",
			}),
			providesTags: ["paymentApi"],
		}),

		// createWithdrawRequest: builder.mutation({
		//   query: (data) => {
		//     return {
		//       url: "/give-withdraw-request",
		//       method: "POST",
		//       body: data,
		//     };
		//   },
		//   invalidatesTags: ["paymentApi"],
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

export const {} = paymentApi;
