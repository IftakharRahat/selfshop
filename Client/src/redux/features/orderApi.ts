/* eslint-disable @typescript-eslint/no-explicit-any */

import { baseApi } from "../api/baseApi";

const orderApi = baseApi.injectEndpoints({
	endpoints: (builder) => ({
		trackOrder: builder.query({
		query: (params: string | { invoiceID: string; id?: string }) => {
			let normalized = "";
			let id = "";
			if (typeof params === "string") {
				normalized = String(params ?? "")
					.trim()
					.replace(/^[^A-Za-z0-9]+/, "");
			} else {
				normalized = String(params.invoiceID ?? "")
					.trim()
					.replace(/^[^A-Za-z0-9]+/, "");
				id = params.id || "";
			}

			const searchParams = new URLSearchParams();
			if (normalized) searchParams.set("invoiceID", normalized);
			if (id) searchParams.set("id", id);

			return {
				url: `/track-order?${searchParams.toString()}`,
				method: "GET",
			};
		},
		providesTags: ["orderApi"],
	}),
		orderCount: builder.query({
			query: () => {
				return {
					url: `/order-count`,
					method: "GET",
				};
			},
			providesTags: ["orderApi"],
		}),
		ordersByStatus: builder.query({
			query: ({ status = "Pending", page = 1 }: { status?: string; page?: number }) => {
				return {
					url: `/order-data/${status}?page=${page}`,
					method: "GET",
				};
			},
			providesTags: ["orderApi"],
		}),
		orderDataByStatus: builder.query({
			query: ({ status, page = 1 }: { status: string; page?: number }) => {
				return {
					url: `/order-data/${status}?page=${page}`,
					method: "GET",
				};
			},
			providesTags: ["orderApi"],
		}),
		incomeHistory: builder.query({
			query: () => {
				return {
					url: `/income-history`,
					method: "GET",
				};
			},
			providesTags: ["orderApi"],
		}),
		// getWithdrawList: builder.query({
		//   query: () => {

		//     return {
		//       url: `/withdraw-list`,
		//       method: "GET",
		//     };
		//   },
		//   providesTags: ["orderApi"],
		// }),
		// getSingleCourse: builder.query({
		//   query: (course) => ({
		//     url: `/course-details/${course}`,
		//     method: "GET",
		//   }),
		//   providesTags: ["courseApi"],
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

export const {
	useTrackOrderQuery,
	useOrderCountQuery,
	useOrdersByStatusQuery,
	useOrderDataByStatusQuery,
	useIncomeHistoryQuery,
} = orderApi;
