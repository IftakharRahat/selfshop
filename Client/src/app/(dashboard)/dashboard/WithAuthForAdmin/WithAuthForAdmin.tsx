/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";
import { useRouter } from "next/navigation";
import { type ReactNode, useEffect, useState } from "react";
// import { logout } from '@/redux/features/auth/authSlice';
import Swal from "sweetalert2";
import { useAppDispatch, useAppSelector } from "@/redux/hooks";

const WithAuthForAdmin = ({ children }: { children: ReactNode }) => {
	const dispatch = useAppDispatch();
	const router = useRouter();
	const [loading, setLoading] = useState(true); // Loading state
	const token = useAppSelector((state) => state.auth.access_token); // Check for token

	useEffect(() => {
		if (!token) {
			// dispatch(logout());
			router.replace("/");

			Swal.fire({
				icon: "error",
				title: "Unauthorized",
				text: "You must be logged in to access this page.",
			});
		} else {
			setLoading(false); // Stop loading once authenticated
		}
	}, [router, token, dispatch]);

	if (loading) {
		return <p>Loading...</p>;
	}

	return children;
};

export default WithAuthForAdmin;
