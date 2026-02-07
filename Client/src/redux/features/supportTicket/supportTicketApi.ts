/* eslint-disable @typescript-eslint/no-unused-vars */
/* eslint-disable @typescript-eslint/no-explicit-any */
import { baseApi } from "../../api/baseApi";

const supportTicketApi = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    getAllSupportTickets: builder.query({
      query: (data) => {
        // const params = new URLSearchParams();
        // if (data?.queryObj) {
        //   data?.queryObj.forEach((item: any) => {
        //     params.append(item.name, item.value as string);
        //   });
        // }
        return {
          url: `get-supporttickets`,
          method: "GET",
          // params: params,
        };
      },
      providesTags: ["supportTicket"],
    }),
    // getSingleExample: builder.query({
    //   query: (id) => ({
    //     url: `example/${id}`,
    //     method: "GET",
    //   }),
    //   providesTags: ["example"],
    // }),

    createSupportTicket: builder.mutation({
      query: (data) => {
        return {
          url: "/create-supportticket",
          method: "POST",
          body: data,
        };
      },
      invalidatesTags: ["supportTicket"],
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
    useCreateSupportTicketMutation,
    useGetAllSupportTicketsQuery,
} = supportTicketApi;
