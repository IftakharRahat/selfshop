import { baseApi } from "../../api/baseApi";

const authApi = baseApi.injectEndpoints({
	endpoints: (builder) => ({
		login: builder.mutation({
			query: (userInfo) => {
				return {
					url: "login",
					method: "POST",
					body: userInfo,
				};
			},
			invalidatesTags: ["user"],
		}),
		register: builder.mutation({
			query: (userInfo) => {
				return {
					url: "/register",
					method: "POST",
					body: userInfo,
				};
			},
			invalidatesTags: ["user"],
		}),
		loginWithGoogle: builder.mutation({
			query: (userInfo) => {
				console.log({ userInfo });
				return {
					url: "google-login",
					method: "POST",
					body: userInfo,
				};
			},
			invalidatesTags: ["user"],
		}),
		forgotPassword: builder.mutation({
			query: (data: { phone: string }) => {
				return {
					url: "forgot-password",
					method: "POST",
					body: data,
				};
			},
		}),
		verifyOtp: builder.mutation({
			query: (data: { phone: string; otp: string }) => {
				return {
					url: "verify-otp",
					method: "POST",
					body: data,
				};
			},
		}),
		resetPassword: builder.mutation({
			query: (data: {
				phone: string;
				otp: string;
				password: string;
				password_confirmation: string;
			}) => {
				return {
					url: "reset-password",
					method: "POST",
					body: data,
				};
			},
		}),
		updateUser: builder.mutation({
			query: (userInfo) => {
				return {
					url: "/update-profile",
					method: "POST",
					body: userInfo,
				};
			},
			invalidatesTags: ["user"],
		}),
		updateBankInfo: builder.mutation({
			query: (userInfo) => {
				return {
					url: "/update-bank-info",
					method: "POST",
					body: userInfo,
				};
			},
			invalidatesTags: ["user"],
		}),
		otp: builder.mutation({
			query: (userInfo) => {
				return {
					url: "users/verify-otp",
					method: "POST",
					body: userInfo,
				};
			},
		}),
		getMe: builder.query({
			query: () => ({
				url: "/user-profile",
				method: "GET",
			}),
			providesTags: ["user"],
		}),
	}),
});

export const {
	useLoginMutation,
	useLoginWithGoogleMutation,
	useForgotPasswordMutation,
	useVerifyOtpMutation,
	useResetPasswordMutation,
	useRegisterMutation,
	useUpdateUserMutation,
	useUpdateBankInfoMutation,
	useOtpMutation,
	useGetMeQuery,
} = authApi;
