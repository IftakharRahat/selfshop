/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";
import { useRouter } from "next/navigation";
import { type ReactNode, useEffect, useState } from "react";
// import { logout } from '@/redux/features/auth/authSlice';
import Swal from "sweetalert2";
import { useGetMeQuery } from "@/redux/features/auth/authApi";
import { useGetPricingQuery } from "@/redux/features/pricingApi";
import { useAppDispatch, useAppSelector } from "@/redux/hooks";

const WithAuthForAdmin = ({ children }: { children: ReactNode }) => {
	const dispatch = useAppDispatch();
	const router = useRouter();
	const [loading, setLoading] = useState(true); // Loading state
	const token = useAppSelector((state) => state.auth.access_token); // Check for token
	const {
		data: profileData,
		isFetching: isProfileFetching,
		isError: isProfileError,
	} = useGetMeQuery(token, { skip: !token });
	const { data: pricingData, isFetching: isPricingFetching } = useGetPricingQuery(
		undefined,
		{ skip: !token },
	);

	useEffect(() => {
		if (!token) {
			// dispatch(logout());
			router.replace("/");

			Swal.fire({
				icon: "error",
				title: "Unauthorized",
				text: "You must be logged in to access this page.",
			});
		}
	}, [router, token, dispatch]);

	useEffect(() => {
		if (!token || isProfileFetching) return;

		if (isProfileError) {
			router.replace("/");
			return;
		}

		const membershipStatus = String(
			profileData?.data?.profile?.membership_status ?? "",
		).toLowerCase();
		const accountStatus = String(
			profileData?.data?.profile?.status ?? "",
		).toLowerCase();
		const isPaidUser =
			membershipStatus === "paid" || accountStatus === "active";

		if (isPaidUser) {
			setLoading(false);
			return;
		}

		if (isPricingFetching) return;

		const pendingInvoice = pricingData?.data?.invoice;
		const invoiceStatus = String(pendingInvoice?.status ?? "").toLowerCase();

		if (pendingInvoice?.invoiceID && invoiceStatus !== "paid") {
			const query = new URLSearchParams();
			if (pendingInvoice?.id) query.set("invoice_id", String(pendingInvoice.id));
			query.set("invoiceID", String(pendingInvoice.invoiceID));
			if (pendingInvoice?.package_id) {
				query.set("package_id", String(pendingInvoice.package_id));
			}
			router.replace(`/invoice?${query.toString()}`);
			return;
		}

		router.replace("/pricing");
	}, [
		token,
		router,
		isProfileError,
		profileData?.data?.profile?.membership_status,
		profileData?.data?.profile?.status,
		isProfileFetching,
		pricingData?.data?.invoice,
		isPricingFetching,
	]);

	if (loading) {
		return <p>Loading...</p>;
	}

	return children;
};

export default WithAuthForAdmin;
