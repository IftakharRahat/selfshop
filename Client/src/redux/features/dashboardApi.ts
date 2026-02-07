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
        url: `/track-order?invoiceID=${invoiceID}`,
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
  useGetAllFAQsQuery,
  useGetAllReferralDataQuery,
  useGetSingleOrderQuery,
} = requestProductListApi;
