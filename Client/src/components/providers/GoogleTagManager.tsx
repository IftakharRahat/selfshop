"use client";

import { useEffect, useState } from "react";
import Script from "next/script";

export default function GoogleTagManager() {
	const [gtmId, setGtmId] = useState<string | null>(null);

	useEffect(() => {
		const fetchGtmId = async () => {
			try {
				const res = await fetch(
					`${process.env.NEXT_PUBLIC_BASE_URL}/tracking-config`
				);
				const data = await res.json();
				if (data.gtm_id) {
					setGtmId(data.gtm_id);
				}
			} catch (err) {
				// Silently fail — tracking should never break the app
			}
		};
		fetchGtmId();
	}, []);

	if (!gtmId) return null;

	return (
		<>
			{/* GTM Head Script */}
			<Script
				id="gtm-init"
				strategy="afterInteractive"
				dangerouslySetInnerHTML={{
					__html: `
						(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
						new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
						j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
						'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
						})(window,document,'script','dataLayer','${gtmId}');
					`,
				}}
			/>
			{/* GTM noscript iframe — placed in body via portal won't work, so we render it here */}
			<noscript>
				<iframe
					src={`https://www.googletagmanager.com/ns.html?id=${gtmId}`}
					height="0"
					width="0"
					style={{ display: "none", visibility: "hidden" }}
				/>
			</noscript>
		</>
	);
}
