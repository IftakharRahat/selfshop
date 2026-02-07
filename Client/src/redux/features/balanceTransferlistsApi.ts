/* eslint-disable @typescript-eslint/no-explicit-any */

import { baseApi } from "../api/baseApi";


const balanceTransferlistsApi = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    getAllBalanceTransfers: builder.query({
      query: () => {
        return {
          url: `/balance-transferlists`,
          method: "GET",
        };
      },
      providesTags: ["balanceTransferlistsApi"],
    }),
    // getSingleCourse: builder.query({
    //   query: (course) => ({
    //     url: `/course-details/${course}`,
    //     method: "GET",
    //   }),
    //   providesTags: ["courseApi"],
    // }),

    createBalanceTransfer: builder.mutation({
      query: (data) => {
        return {
          url: "/give-transfer-request",
          method: "POST",
          body: data,
        };
      },
      invalidatesTags: ["balanceTransferlistsApi"],
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
    useGetAllBalanceTransfersQuery,
    useCreateBalanceTransferMutation,
} = balanceTransferlistsApi;
