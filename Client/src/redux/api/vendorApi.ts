import { baseApi } from "./baseApi";

export interface VendorProfile {
	id: number;
	user_id: number;
	company_name: string;
	slug: string;
	business_type?: string | null;
	contact_name?: string | null;
	contact_email?: string | null;
	contact_phone?: string | null;
	country?: string | null;
	state?: string | null;
	city?: string | null;
	postcode?: string | null;
	address_line_1?: string | null;
	address_line_2?: string | null;
	pickup_location_label?: string | null;
	status: "pending" | "approved" | "rejected" | "suspended";
}

export interface VendorKycDocument {
	id: number;
	vendor_id: number;
	document_type: string;
	document_number?: string | null;
	status: "pending" | "approved" | "rejected";
	created_at: string;
}

export const vendorApi = baseApi.injectEndpoints({
	endpoints: (build) => ({
		registerVendor: build.mutation<
			{ status: boolean; message: string },
			{
				name: string;
				email: string;
				password: string;
				company_name: string;
				business_type?: string;
				country?: string;
				city?: string;
			}
		>({
			query: (body) => ({
				url: "/vendor/register",
				method: "POST",
				body,
			}),
		}),
		getVendorProfile: build.query<
			{ user: unknown; vendor: VendorProfile | null },
			void
		>({
			query: () => ({
				url: "/vendor/profile",
				method: "GET",
			}),
			providesTags: ["user"],
		}),
		upsertVendorProfile: build.mutation<
			{ vendor: VendorProfile },
			Partial<VendorProfile> & { company_name: string }
		>({
			query: (body) => ({
				url: "/vendor/profile",
				method: "POST",
				body,
			}),
			invalidatesTags: ["user"],
		}),
		getVendorKycDocuments: build.query<
			{ documents: VendorKycDocument[] },
			void
		>({
			query: () => ({
				url: "/vendor/kyc-documents",
				method: "GET",
			}),
			providesTags: ["user"],
		}),
		createVendorKycDocument: build.mutation<
			{ document: VendorKycDocument },
			{ document_type: string; document_number?: string }
		>({
			query: (body) => ({
				url: "/vendor/kyc-documents",
				method: "POST",
				body,
			}),
			invalidatesTags: ["user"],
		}),
	}),
});

export const {
	useRegisterVendorMutation,
	useGetVendorProfileQuery,
	useUpsertVendorProfileMutation,
	useGetVendorKycDocumentsQuery,
	useCreateVendorKycDocumentMutation,
} = vendorApi;

