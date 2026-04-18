"use client";

import { useEffect, useState } from "react";
import Script from "next/script";

export default function GoogleAnalytics() {
	const [gaId, setGaId] = useState<string | null>(null);

	useEffect(() => {
		const fetchGaId = async () => {
			try {
				const res = await fetch(
					`${process.env.NEXT_PUBLIC_BASE_URL}/tracking-config`
				);
				const data = await res.json();
				if (data.google_analytics_id) {
					setGaId(data.google_analytics_id);
				}
			} catch (err) {
				// Silently fail — tracking should never break the app
			}
		};
		fetchGaId();
	}, []);

	if (!gaId) return null;

	return (
		<>
			<Script
				src={`https://www.googletagmanager.com/gtag/js?id=${gaId}`}
				strategy="afterInteractive"
			/>
			<Script
				id="google-analytics-init"
				strategy="afterInteractive"
				dangerouslySetInnerHTML={{
					__html: `
						window.dataLayer = window.dataLayer || [];
						function gtag(){dataLayer.push(arguments);}
						gtag('js', new Date());
						gtag('config', '${gaId}');
					`,
				}}
			/>
		</>
	);
}
