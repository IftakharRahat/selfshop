/* eslint-disable @typescript-eslint/no-explicit-any */

import { baseApi } from "../api/baseApi";

const requestProductListApi = baseApi.injectEndpoints({
	endpoints: (builder) => ({
		getAllDashboardData: builder.query({
			query: () => {
				return {
					url: `/dashboard-data`,
					method: "GET",
				};
			},
			providesTags: ["dashboardApi"],
		}),
		participateSalesTarget: builder.mutation({
			query: (salesTargetId: number) => {
				return {
					url: `/sales-targets/participate`,
					method: "POST",
					body: { sales_target_id: salesTargetId },
				};
			},
			invalidatesTags: ["dashboardApi"],
		}),
		claimSalesTargetReward: builder.mutation({
			query: (salesTargetId: number) => {
				return {
					url: `/sales-targets/claim-reward`,
					method: "POST",
					body: { sales_target_id: salesTargetId },
				};
			},
			invalidatesTags: ["dashboardApi"],
		}),
		getAllFAQs: builder.query({
			query: () => {
				return {
					url: `/faqs`,
					method: "GET",
				};
			},
		}),
		getAllReferralData: builder.query({
			query: () => {
				return {
					url: `/referral/data`,
					method: "GET",
				};
			},
		}),
		getSingleOrder: builder.query({
			query: (invoiceID) => ({
				url: `/track-order?invoiceID=${encodeURIComponent(
					String(invoiceID ?? "").trim().replace(/^[^A-Za-z0-9]+/, "")
				)}`,
				method: "GET",
			}),
		}),
		getTeamMembers: builder.query({
			query: () => ({
				url: `/teams`,
				method: "GET",
			}),
		}),

		// createRequestProduct: builder.mutation({
		//   query: (data) => {
		//     return {
		//       url: "/give-product-request",
		//       method: "POST",
		//       body: data,
		//     };
		//   },
		//   invalidatesTags: ["requestProductListApi"],
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
	useGetAllDashboardDataQuery,
	useParticipateSalesTargetMutation,
	useClaimSalesTargetRewardMutation,
	useGetAllFAQsQuery,
	useGetAllReferralDataQuery,
	useGetSingleOrderQuery,
	useGetTeamMembersQuery,
} = requestProductListApi;
