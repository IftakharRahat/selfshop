"use client";

import { useRouter } from "next/navigation";
import { useEffect, use } from "react";

/**
 * /registration/[code] → redirects to homepage with auth modal in register mode + campaign code
 * Example: selfshop.com.bd/registration/sahib → /?showAuth=register&campaign=sahib
 */
export default function CampaignRegistrationPage({ params }: { params: Promise<{ code: string }> }) {
	const router = useRouter();
	const { code } = use(params);

	useEffect(() => {
		router.replace(`/?showAuth=register&campaign=${encodeURIComponent(code)}`);
	}, [router, code]);

	return null;
}
