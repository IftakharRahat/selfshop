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
		getPromotionalSections: builder.query({
			query: () => ({
				url: `/promotional-sections`,
				method: "GET",
			}),
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
		getFlashSale: builder.query({
			query: () => {
				return {
					url: `/flash-sale`,
					method: "GET",
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
			query: ({ slug, sort, page }: { slug: string; sort?: string; page?: number }) => ({
				url: `/products/${slug}`,
				method: "GET",
				params: { ...(sort ? { sort } : {}), ...(page ? { page } : {}) },
			}),
			providesTags: ["categories"],
		}),
		getSubcategoryProducts: builder.query({
			query: ({ slug, sort, page }: { slug: string; sort?: string; page?: number }) => ({
				url: `/subcategory-products/${slug}`,
				method: "GET",
				params: { ...(sort ? { sort } : {}), ...(page ? { page } : {}) },
			}),
			providesTags: ["categories"],
		}),
		getMinicategoryProducts: builder.query({
			query: ({ slug, sort, page }: { slug: string; sort?: string; page?: number }) => ({
				url: `/minicategory-products/${slug}`,
				method: "GET",
				params: { ...(sort ? { sort } : {}), ...(page ? { page } : {}) },
			}),
			providesTags: ["categories"],
		}),
		getPopularSuppliers: builder.query({
			query: (sort?: string) => ({
				url: `/popular-vendors`,
				method: "GET",
				params: sort ? { sort } : undefined,
			}),
			providesTags: ["categories"],
		}),
		getSupplierDetails: builder.query({
			query: ({ slug, category, page }: { slug: string; category?: number; page?: number }) => ({
				url: `/supplier/${slug}`,
				method: "GET",
				params: {
					...(category ? { category } : {}),
					...(page ? { page } : {}),
				},
			}),
			providesTags: ["categories"],
		}),
		getBrandProducts: builder.query({
			query: (slug: string) => ({
				url: `/brand-products/${slug}`,
				method: "GET",
			}),
			providesTags: ["categories"],
		}),

		// Vendor Follow
		followVendor: builder.mutation({
			query: (vendorId: number) => ({
				url: `/vendor-follow/${vendorId}/follow`,
				method: "POST",
			}),
			invalidatesTags: ["vendorFollow"],
		}),
		unfollowVendor: builder.mutation({
			query: (vendorId: number) => ({
				url: `/vendor-follow/${vendorId}/unfollow`,
				method: "POST",
			}),
			invalidatesTags: ["vendorFollow"],
		}),
		getFollowStatus: builder.query({
			query: (vendorId: number) => ({
				url: `/vendor-follow/${vendorId}/status`,
				method: "GET",
			}),
			providesTags: ["vendorFollow"],
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

		// ── Warranty / Exchange ──
		getWarrantyProducts: builder.query({
			query: () => ({ url: "warranty/products" }),
			providesTags: ["warrantyApi"],
		}),
		getWarrantyClaims: builder.query({
			query: () => ({ url: "warranty/claims" }),
			providesTags: ["warrantyApi"],
		}),
		submitWarrantyClaim: builder.mutation({
			query: (data: FormData) => ({
				url: "warranty/claims",
				method: "POST",
				body: data,
			}),
			invalidatesTags: ["warrantyApi"],
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
	useGetPromotionalSectionsQuery,
	useGetAllBigSellingQuery,
	useGetAllNewProductsQuery,
	useGetBasicInfoQuery,
	useGetAllFeaturedProductsQuery,
	useGetFlashSaleQuery,
	useGetCategoryProductsQuery,
	useGetSubcategoryProductsQuery,
	useGetMinicategoryProductsQuery,
	useGetPopularSuppliersQuery,
	useGetSupplierDetailsQuery,
	useGetBrandProductsQuery,
	useFollowVendorMutation,
	useUnfollowVendorMutation,
	useGetFollowStatusQuery,
	// Warranty / Exchange
	useGetWarrantyProductsQuery,
	useGetWarrantyClaimsQuery,
	useSubmitWarrantyClaimMutation,
} = homeApi;
