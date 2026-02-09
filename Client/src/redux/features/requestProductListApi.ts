/* eslint-disable @typescript-eslint/no-explicit-any */

import { baseApi } from "../api/baseApi";

const requestProductListApi = baseApi.injectEndpoints({
	endpoints: (builder) => ({
		getAllRequestProducts: builder.query({
			query: () => {
				return {
					url: `/request-product-list`,
					method: "GET",
				};
			},
			providesTags: ["requestProductListApi"],
		}),
		// getSingleCourse: builder.query({
		//   query: (course) => ({
		//     url: `/course-details/${course}`,
		//     method: "GET",
		//   }),
		//   providesTags: ["courseApi"],
		// }),

		createRequestProduct: builder.mutation({
			query: (data) => {
				return {
					url: "/give-product-request",
					method: "POST",
					body: data,
				};
			},
			invalidatesTags: ["requestProductListApi"],
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
	useGetAllRequestProductsQuery,
	useCreateRequestProductMutation,
} = requestProductListApi;
