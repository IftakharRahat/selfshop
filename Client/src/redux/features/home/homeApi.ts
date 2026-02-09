/* eslint-disable @typescript-eslint/no-explicit-any */
import { baseApi } from "../../api/baseApi";

const homeApi = baseApi.injectEndpoints({
	endpoints: (builder) => ({
		getAllNavbarCategoryDropdownOptions: builder.query({
			query: () => {
				return {
					url: `/categories`,
					method: "GET",
				};
			},
			providesTags: ["categories"],
		}),
		getAllMenus: builder.query({
			query: () => {
				return {
					url: `/header-categories`,
					method: "GET",
				};
			},
			providesTags: ["categories"],
		}),
		getAllSliders: builder.query({
			query: () => {
				return {
					url: `/sliders`,
					method: "GET",
				};
			},
			providesTags: ["categories"],
		}),
		getAllSliderBottomBanners: builder.query({
			query: () => {
				return {
					url: `/slider-bottom-banners`,
					method: "GET",
				};
			},
			providesTags: ["categories"],
		}),
		getAllBrands: builder.query({
			query: () => {
				return {
					url: `/brands`,
					method: "GET",
				};
			},
			providesTags: ["categories"],
		}),
		getBasicInfo: builder.query({
			query: () => {
				return {
					url: `/basic-info`,
					method: "GET",
				};
			},
			providesTags: ["categories"],
		}),
		getAllCollections: builder.query({
			query: (data) => {
				const params = new URLSearchParams();
				if (data?.objectQuery) {
					data?.objectQuery.forEach((item: any) => {
						params.append(item.name, item.value as string);
					});
				}
				return {
					url: `/collection/${data?.slug}`,
					method: "GET",
					params: params,
				};
			},
			providesTags: ["categories"],
		}),
		getAllFeaturedProducts: builder.query({
			query: () => {
				// const params = new URLSearchParams();
				// if (data?.objectQuery) {
				//   data?.objectQuery.forEach((item: any) => {
				//     params.append(item.name, item.value as string);
				//   });
				// }
				return {
					url: `/featured-products`,
					method: "GET",
					// params: params,
				};
			},
			providesTags: ["categories"],
		}),
		getAllBigSelling: builder.query({
			query: (data) => {
				const params = new URLSearchParams();
				if (data?.objectQuery) {
					data?.objectQuery.forEach((item: any) => {
						params.append(item.name, item.value as string);
					});
				}
				return {
					url: `big-selling`,
					method: "GET",
					params: params,
				};
			},
			providesTags: ["categories"],
		}),
		getAllNewProducts: builder.query({
			query: (data) => {
				const params = new URLSearchParams();
				if (data?.objectQuery) {
					data?.objectQuery.forEach((item: any) => {
						params.append(item.name, item.value as string);
					});
				}
				return {
					url: `new-products`,
					method: "GET",
					params: params,
				};
			},
			providesTags: ["categories"],
		}),

		getCategoryProducts: builder.query({
			query: (slug) => ({
				url: `/products/${slug}`,
				method: "GET",
			}),
			providesTags: ["categories"],
		}),
		getSubcategoryProducts: builder.query({
			query: (slug) => ({
				url: `/subcategory-products/${slug}`,
				method: "GET",
			}),
			providesTags: ["categories"],
		}),

		createExample: builder.mutation({
			query: (data) => {
				return {
					url: "example",
					method: "POST",
					body: data,
				};
			},
			invalidatesTags: ["example"],
		}),

		updateExample: builder.mutation({
			query: (data) => {
				return {
					url: `example/${data?.id}`,
					method: "POST",
					body: data?.formData,
				};
			},
			invalidatesTags: ["example"],
		}),
		deleteExample: builder.mutation({
			query: (id) => {
				return {
					url: `example/${id}`,
					method: "DELETE",
				};
			},
			invalidatesTags: ["example"],
		}),
	}),
});

export const {
	useGetAllNavbarCategoryDropdownOptionsQuery,
	useGetAllMenusQuery,
	useGetAllSlidersQuery,
	useGetAllSliderBottomBannersQuery,
	useGetAllBrandsQuery,
	useGetAllCollectionsQuery,
	useGetAllBigSellingQuery,
	useGetAllNewProductsQuery,
	useGetBasicInfoQuery,
	useGetAllFeaturedProductsQuery,
	useGetCategoryProductsQuery,
	useGetSubcategoryProductsQuery,
} = homeApi;
