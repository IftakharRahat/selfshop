import {
	type BaseQueryFn,
	createApi,
	type FetchArgs,
	type FetchBaseQueryError,
	fetchBaseQuery,
} from "@reduxjs/toolkit/query/react";
import type { RootState } from "../store";

// import { logout } from "../features/auth/authSlice";

const baseQuery = fetchBaseQuery({
	baseUrl: process.env.NEXT_PUBLIC_BASE_URL,
	// credentials: "include",
	prepareHeaders: (headers, { getState }) => {
		const access_token = (getState() as RootState).auth.access_token;
		headers.set("accept", "application/json");
		if (access_token) {
			headers.set("authorization", `Bearer ${access_token}`);
		}
		return headers;
	},
});

const baseQueryWithRefreshToken: BaseQueryFn<
	string | FetchArgs,
	unknown,
	FetchBaseQueryError
> = async (args, api, extraOptions) => {
	const result = await baseQuery(args, api, extraOptions);

	if (result.error?.status === 401) {
		try {
			//  api.dispatch(logout());
		} catch (error) {
			console.error("Error during token refresh:", error);
		}
	}

	return result;
};

export const baseApi = createApi({
	reducerPath: "baseApi",
	baseQuery: baseQueryWithRefreshToken,
	// Vendor panel frequently navigates between sibling pages.
	// Keep query cache longer to avoid repeated refetches and faster tab/page switches.
	keepUnusedDataFor: 300,
	refetchOnFocus: false,
	refetchOnReconnect: false,
	refetchOnMountOrArgChange: false,
	tagTypes: [
		"user",
		"vendorProducts",
		"vendorOrders",
		"vendorCategoryDiscounts",
		"vendorReviews",
		"example",
		"navbarCategoryDropdownOptions",
		"supportTicket",
		"fraudCustomer",
		"developersApi",
		"courseApi",
		"withdrawApi",
		"balanceTransferlistsApi",
		"requestProductListApi",
		"dashboardApi",
		"productDetailsApi",
		"cartApi",
		"categories",
		"pricingApi",
		"orderApi",
		"paymentApi",
		"searchApi",
		"vendorInventory",
		"vendorWarehouses",
		"vendorShippingMethods",
		"vendorEarnings",
		"vendorPayoutAccounts",
		"vendorPayoutRequests",
		"vendorReports",
		"vendorDashboard",
	],
	endpoints: () => ({}),
});
