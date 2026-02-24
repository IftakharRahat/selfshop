/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";
import { useRouter } from "next/navigation";
import Image from "next/image";
import { type ReactNode, useEffect, useState } from "react";
// import { logout } from '@/redux/features/auth/authSlice';
import Swal from "sweetalert2";
import logo from "@/assets/icons/NavLogo.png";
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
		return (
			<div className="fixed inset-0 z-50 flex items-center justify-center bg-gray-50/80 backdrop-blur-sm">
				<div className="flex flex-col items-center gap-6">
					{/* Logo with pulse */}
					<div className="animate-pulse">
						<Image
							src={logo}
							alt="SelfShop"
							width={180}
							height={50}
							priority
							className="h-auto w-44"
						/>
					</div>

					{/* Spinner ring */}
					<div className="relative h-10 w-10">
						<div
							className="absolute inset-0 rounded-full border-[3px] border-gray-200"
						/>
						<div
							className="absolute inset-0 animate-spin rounded-full border-[3px] border-transparent"
							style={{
								borderTopColor: "#ec4899",
								borderRightColor: "#a855f7",
							}}
						/>
					</div>

					{/* Loading text */}
					<p className="text-sm font-medium text-gray-400 tracking-wide">
						Loading your dashboard…
					</p>
				</div>
			</div>
		);
	}

	return children;
};

export default WithAuthForAdmin;
