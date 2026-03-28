"use client";

import Script from "next/script";

export default function GoogleAnalytics() {
	return (
		<>
			<Script
				src="https://www.googletagmanager.com/gtag/js?id=G-FV9M04EC4C"
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
						gtag('config', 'G-FV9M04EC4C');
					`,
				}}
			/>
		</>
	);
}
