import { baseApi } from "@/redux/api/baseApi";

export type RefundStatus = "pending" | "in_progress" | "approved" | "rejected" | "closed";

export interface EligibleRefundItem {
	order_id: number;
	invoiceID: string;
	orderproduct_id: number;
	product_id: number;
	product_name: string;
	product_code?: string | null;
	product_image?: string | null;
	quantity: number;
	product_price: number | string;
	color?: string | null;
	size?: string | null;
	delivery_date: string;
	expires_at: string;
	warranty_days: number;
	days_remaining: number;
	customer?: {
		name?: string | null;
		phone?: string | null;
	};
}

export interface RefundClaimMessage {
	id: number;
	sender_type: "user" | "admin";
	message: string;
	attachment_path?: string | null;
	created_at?: string | null;
}

export interface RefundClaim {
	id: number;
	claim_number: string;
	status: RefundStatus;
	message: string;
	image_path?: string | null;
	delivery_date?: string | null;
	expires_at?: string | null;
	warranty_days: number;
	created_at?: string | null;
	updated_at?: string | null;
	order?: { id: number; invoiceID: string; status: string; deliveryDate?: string | null };
	orderproduct?: {
		id: number;
		productName: string;
		productCode?: string | null;
		quantity: number;
		productPrice: number | string;
		color?: string | null;
		size?: string | null;
	};
	product?: {
		id: number;
		ProductName: string;
		ProductSlug?: string | null;
		ProductSku?: string | null;
		ViewProductImage?: string | null;
	};
	messages: RefundClaimMessage[];
}

export const refundApi = baseApi.injectEndpoints({
	endpoints: (builder) => ({
		getEligibleRefundOrders: builder.query<{ data: { eligible_orders: EligibleRefundItem[] } }, void>({
			query: () => "/refund/eligible-orders",
			providesTags: ["refundApi"],
		}),
		getRefundClaims: builder.query<{ data: { claims: RefundClaim[] } }, void>({
			query: () => "/refund/claims",
			providesTags: ["refundApi"],
		}),
		getRefundClaim: builder.query<{ data: { claim: RefundClaim } }, number>({
			query: (id) => `/refund/claims/${id}`,
			providesTags: (_result, _error, id) => [{ type: "refundApi", id }],
		}),
		submitRefundClaim: builder.mutation<{ data: { claim: RefundClaim } }, FormData>({
			query: (body) => ({
				url: "/refund/claims",
				method: "POST",
				body,
			}),
			invalidatesTags: ["refundApi"],
		}),
		replyRefundClaim: builder.mutation<{ data: { claim: RefundClaim } }, { id: number; body: FormData }>({
			query: ({ id, body }) => ({
				url: `/refund/claims/${id}/messages`,
				method: "POST",
				body,
			}),
			invalidatesTags: (_result, _error, { id }) => ["refundApi", { type: "refundApi", id }],
		}),
	}),
});

export const {
	useGetEligibleRefundOrdersQuery,
	useGetRefundClaimsQuery,
	useGetRefundClaimQuery,
	useSubmitRefundClaimMutation,
	useReplyRefundClaimMutation,
} = refundApi;
