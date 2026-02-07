/* eslint-disable @typescript-eslint/no-explicit-any */
import { baseApi } from "../../api/baseApi";

const fraudCustomerApi = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    // getAllSupportTickets: builder.query({
    //   query: (data) => {
    //     const params = new URLSearchParams();
    //     if (data?.queryObj) {
    //       data?.queryObj.forEach((item: any) => {
    //         params.append(item.name, item.value as string);
    //       });
    //     }
    //     return {
    //       url: `get-supporttickets`,
    //       method: "GET",
    //       params: params,
    //     };
    //   },
    //   providesTags: ["supportTicket"],
    // }),
    getCheckFraud: builder.query({
      query: (id) => ({
        url: `/check-fraud?number=${id}`,
        method: "GET",
      }),
      providesTags: ["fraudCustomer"],
    }),

    createStoreFraudNumber: builder.mutation({
      query: (data) => {
        return {
          url: "/store-fraud-number",
          method: "POST",
          body: data,
        };
      },
      invalidatesTags: ["fraudCustomer"],
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
    useGetCheckFraudQuery,
    useCreateStoreFraudNumberMutation,
} = fraudCustomerApi;
