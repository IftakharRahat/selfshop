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

		// ── Product Reviews ──
		submitReview: builder.mutation<
			{ status: boolean; message: string; data?: unknown },
			FormData
		>({
			query: (formData) => ({
				url: "/review/store",
				method: "POST",
				body: formData,
			}),
			invalidatesTags: ["reviews", "orderApi"],
		}),
		getReviewableProducts: builder.query<
			{
				status: boolean;
				message: string;
				data: Array<{
					product_id: number;
					product_name: string;
					product_slug: string | null;
					product_image: string | null;
					order_id: number;
					invoice_id: string;
					delivery_date: string | null;
				}>;
			},
			void
		>({
			query: () => ({
				url: "/reviewable-products",
				method: "GET",
			}),
			providesTags: ["reviews"],
		}),
		checkUserReview: builder.query<
			{
				status: boolean;
				data: {
					has_reviewed: boolean;
					can_review: boolean;
					review: {
						id: number;
						rating: number;
						messages: string | null;
						created_at: string;
					} | null;
				};
			},
			number
		>({
			query: (productId) => ({
				url: `/review/check/${productId}`,
				method: "GET",
			}),
			providesTags: (_r, _e, id) => [{ type: "reviews", id }],
		}),
		getProductReviews: builder.query<
			{
				status: boolean;
				message: string;
				data: {
					reviews: Array<{
						id: number;
						product_id: number;
						user_id: number;
						rating: number;
						messages: string | null;
						file: string | null;
						status: string;
						created_at: string;
						user?: {
							id: number;
							name: string;
							email: string;
							profile: string | null;
						};
					}>;
					review_count: number;
					average_rating: number;
				};
			},
			number
		>({
			query: (productId) => ({
				url: `/review/product/${productId}`,
				method: "GET",
			}),
			providesTags: (_r, _e, id) => [{ type: "reviews", id }],
		}),
		updateReview: builder.mutation<
			{ status: boolean; message: string; data?: unknown },
			{ reviewId: number; rating: number; messages?: string | null }
		>({
			query: ({ reviewId, ...body }) => ({
				url: `/review/update/${reviewId}`,
				method: "POST",
				body,
			}),
			invalidatesTags: ["reviews"],
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
	useSubmitReviewMutation,
	useGetReviewableProductsQuery,
	useCheckUserReviewQuery,
	useGetProductReviewsQuery,
	useUpdateReviewMutation,
} = requestProductListApi;

