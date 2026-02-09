/* eslint-disable @typescript-eslint/no-explicit-any */

import { baseApi } from "../api/baseApi";

const withdrawApi = baseApi.injectEndpoints({
	endpoints: (builder) => ({
		getAllWithdrawMethods: builder.query({
			query: () => {
				return {
					url: `/get-payment-types`,
					method: "GET",
				};
			},
			providesTags: ["withdrawApi"],
		}),
		getWithdrawList: builder.query({
			query: () => {
				return {
					url: `/withdraw-list`,
					method: "GET",
				};
			},
			providesTags: ["withdrawApi"],
		}),
		// getSingleCourse: builder.query({
		//   query: (course) => ({
		//     url: `/course-details/${course}`,
		//     method: "GET",
		//   }),
		//   providesTags: ["courseApi"],
		// }),

		createWithdrawRequest: builder.mutation({
			query: (data) => {
				return {
					url: "/give-withdraw-request",
					method: "POST",
					body: data,
				};
			},
			invalidatesTags: ["withdrawApi"],
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

export const {
	useGetAllWithdrawMethodsQuery,
	useGetWithdrawListQuery,
	useCreateWithdrawRequestMutation,
} = withdrawApi;
