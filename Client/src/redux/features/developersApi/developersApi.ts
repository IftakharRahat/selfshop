/* eslint-disable @typescript-eslint/no-explicit-any */
import { baseApi } from "../../api/baseApi";

const developersApi = baseApi.injectEndpoints({
	endpoints: (builder) => ({
		getDeveloperApi: builder.query({
			query: () => {
				return {
					url: `/developers-api`,
					method: "GET",
				};
			},
			providesTags: ["developersApi"],
		}),
		generateDeveloperApi: builder.mutation({
			query: () => {
				return {
					url: `/generate-developers-api`,
					method: "GET",
				};
			},
			invalidatesTags: ["developersApi"],
		}),
	}),
});

export const { useGetDeveloperApiQuery, useGenerateDeveloperApiMutation } =
	developersApi;
