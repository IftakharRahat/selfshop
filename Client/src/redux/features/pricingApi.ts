/* eslint-disable @typescript-eslint/no-explicit-any */

import { baseApi } from "../api/baseApi";

export interface PackagePlan {
	id: number;
	package_name: string;
	price: number;
	discount_price?: number;
	validity?: number | string;
	status?: string;
}

export interface PackageInvoice {
	id: number;
	invoiceID: string;
	package_id?: number;
	amount?: number;
	payable_amount?: number;
	status?: string;
}

export interface PricingPayload {
	invoice: PackageInvoice | null;
	packages: PackagePlan[];
}

export interface PricingResponse {
	status: boolean;
	message: string;
	data: PricingPayload;
}

interface CreatePurchasePayload {
	package_id: number;
	amount?: number;
}

interface CreatePurchaseResponse {
	status: boolean;
	message: string;
	data: {
		invoice: PackageInvoice;
		package?: PackagePlan;
	};
}

interface InitiatePackagePaymentPayload {
	invoice_id: number;
}

interface InitiatePackagePaymentResponse {
	status: boolean;
	message: string;
	data: {
		gateway_url: string;
		tran_id: string;
		invoice: PackageInvoice;
	};
}

const pricingApi = baseApi.injectEndpoints({
	endpoints: (builder) => ({
		getPricing: builder.query<PricingResponse, void>({
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

		createPurchase: builder.mutation<CreatePurchaseResponse, CreatePurchasePayload>(
			{
				query: (data) => {
					return {
						url: "/purchese-package",
						method: "POST",
						body: data,
					};
				},
				invalidatesTags: ["pricingApi"],
			},
		),

		initiatePackagePayment: builder.mutation<
			InitiatePackagePaymentResponse,
			InitiatePackagePaymentPayload
		>({
			query: (data) => {
				return {
					url: "/package-payment/initiate",
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

export const {
	useGetPricingQuery,
	useCreatePurchaseMutation,
	useInitiatePackagePaymentMutation,
} = pricingApi;
