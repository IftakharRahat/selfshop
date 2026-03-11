"use client";

import { useEffect } from "react";
import { useSearchParams, useRouter } from "next/navigation";
import { useAppDispatch } from "@/redux/hooks";
import { setUser } from "@/redux/features/auth/authSlice";

export default function ImpersonatePage() {
	const searchParams = useSearchParams();
	const router = useRouter();
	const dispatch = useAppDispatch();

	useEffect(() => {
		const token = searchParams.get("token");
		if (token) {
			// Set the token in Redux (same as normal login)
			dispatch(setUser({ access_token: token }));
			// Redirect to dashboard
			router.replace("/dashboard");
		} else {
			router.replace("/");
		}
	}, [searchParams, dispatch, router]);

	return (
		<div style={{ display: "flex", justifyContent: "center", alignItems: "center", height: "100vh" }}>
			<p>Logging in...</p>
		</div>
	);
}
