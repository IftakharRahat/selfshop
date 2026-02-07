/* eslint-disable @typescript-eslint/no-explicit-any */

import { baseApi } from "../api/baseApi";


const courseApi = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    getAllCourse: builder.query({
      query: () => {
        // const params = new URLSearchParams();
        // if (data?.queryObj) {
        //   data?.queryObj.forEach((item: any) => {
        //     params.append(item.name, item.value as string);
        //   });
        // }
        return {
          url: `/view-course`,
          method: "GET",
        //   params: params,
        };
      },
      providesTags: ["courseApi"],
    }),
    getSingleCourse: builder.query({
      query: (course) => ({
        url: `/course-details/${course}`,
        method: "GET",
      }),
      providesTags: ["courseApi"],
    }),

    // createExample: builder.mutation({
    //   query: (data) => {
    //     return {
    //       url: "example",
    //       method: "POST",
    //       body: data,
    //     };
    //   },
    //   invalidatesTags: ["example"],
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
    useGetAllCourseQuery,
    useGetSingleCourseQuery,
} = courseApi;
