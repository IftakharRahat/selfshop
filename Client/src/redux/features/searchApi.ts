/* eslint-disable @typescript-eslint/no-explicit-any */

import { baseApi } from "../api/baseApi";


const searchApi = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    // getAllWithdrawMethods: builder.query({
    //   query: () => {

    //     return {
    //       url: `/get-payment-types`,
    //       method: "GET",
    //     };
    //   },
    //   providesTags: ["withdrawApi"],
    // }),
    // getWithdrawList: builder.query({
    //   query: () => {

    //     return {
    //       url: `/withdraw-list`,
    //       method: "GET",
    //     };
    //   },
    //   providesTags: ["withdrawApi"],
    // }),
    getSearchResults: builder.query({
      query: (search) => ({
        url: `/search?keywords=${search}`,
        method: "GET",
      }),
      providesTags: ["searchApi"],
    }),

    // createWithdrawRequest: builder.mutation({
    //   query: (data) => {
    //     return {
    //       url: "/give-withdraw-request",
    //       method: "POST",
    //       body: data,
    //     };
    //   },
    //   invalidatesTags: ["withdrawApi"],
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
    useGetSearchResultsQuery,
} = searchApi;
