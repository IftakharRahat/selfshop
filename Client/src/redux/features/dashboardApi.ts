/* eslint-disable @typescript-eslint/no-explicit-any */

import { baseApi } from "../api/baseApi";

export interface UserNotificationItem {
	id: string;
	title: string;
	description?: string;
	message?: string;
	image?: string | null;
	image_url?: string | null;
	link?: string | null;
	url?: string | null;
	type?: string;
	is_read: boolean;
	read_at?: string | null;
	created_at?: string | null;
	meta?: Record<string, unknown>;
}

type UserNotificationsResponse = {
	status: boolean;
	message: string;
	data: UserNotificationItem[];
	unread_count: number;
	pagination?: {
		current_page: number;
		last_page: number;
		per_page: number;
		total: number;
	};
};

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
		getUserNotifications: builder.query<
			UserNotificationsResponse,
			{ per_page?: number; page?: number; unread_only?: boolean } | void
		>({
			query: (params) => {
				const queryParams = params ?? {};
				return {
					url: "/user-notification",
					method: "GET",
					params: queryParams,
				};
			},
			providesTags: ["userNotifications"],
		}),
		markUserNotificationRead: builder.mutation<
			{ status: boolean; message: string; unread_count: number },
			{ id: string }
		>({
			query: ({ id }) => ({
				url: `/user-notification/${id}/read`,
				method: "POST",
			}),
			invalidatesTags: ["userNotifications"],
		}),
		markAllUserNotificationsRead: builder.mutation<
			{ status: boolean; message: string; unread_count: number },
			void
		>({
			query: () => ({
				url: "/user-notification/read-all",
				method: "POST",
			}),
			invalidatesTags: ["userNotifications"],
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
	useGetUserNotificationsQuery,
	useMarkUserNotificationReadMutation,
	useMarkAllUserNotificationsReadMutation,
} = requestProductListApi;
