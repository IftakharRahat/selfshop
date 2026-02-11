"use client";

import { useRouter } from "next/navigation";
import type { ReactNode } from "react";
import { useEffect, useState } from "react";
import { useAppSelector } from "@/redux/hooks";

const WithVendorAuth = ({ children }: { children: ReactNode }) => {
	const router = useRouter();
	const [loading, setLoading] = useState(true);
	const token = useAppSelector((state) => state.auth.access_token);

	useEffect(() => {
		if (!token) {
			router.replace("/vendor/login");
		} else {
			setLoading(false);
		}
	}, [router, token]);

	if (loading) {
		return <p>Loading...</p>;
	}

	return children;
};

export default WithVendorAuth;

