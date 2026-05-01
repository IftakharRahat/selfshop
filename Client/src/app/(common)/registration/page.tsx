"use client";

import { useRouter } from "next/navigation";
import { useEffect } from "react";

/**
 * /registration → redirects to homepage with auth modal in register mode
 * Production URL: https://selfshop.com.bd/registration
 */
export default function RegistrationPage() {
	const router = useRouter();

	useEffect(() => {
		router.replace("/?showAuth=register");
	}, [router]);

	return null;
}
