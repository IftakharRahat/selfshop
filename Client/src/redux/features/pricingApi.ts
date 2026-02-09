/* eslint-disable @typescript-eslint/no-explicit-any */

import { baseApi } from "../api/baseApi";

const pricingApi = baseApi.injectEndpoints({
	endpoints: (builder) => ({
		getPricing: builder.query({
			query: () => {
				return {
					url: `/our-packages`,
					method: "GET",
				};
			},
			providesTags: ["pricingApi"],
		}),
		// getWithdrawList: builder.query({
		//   query: () => {

		//     return {
		//       url: `/withdraw-list`,
		//       method: "GET",
		//     };
		//   },
		//   providesTags: ["pricingApi"],
		// }),
		// getSingleCourse: builder.query({
		//   query: (course) => ({
		//     url: `/course-details/${course}`,
		//     method: "GET",
		//   }),
		//   providesTags: ["courseApi"],
		// }),

		createPurchase: builder.mutation({
			query: (data) => {
				return {
					url: "/purchese-package",
					method: "POST",
					body: data,
				};
			},
			invalidatesTags: ["pricingApi"],
		}),

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

export const { useGetPricingQuery, useCreatePurchaseMutation } = pricingApi;
