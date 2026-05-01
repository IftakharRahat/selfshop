"use client";

import { useEffect, useState } from "react";
import Script from "next/script";
import { usePathname } from "next/navigation";

export default function FacebookPixel() {
	const [pixelId, setPixelId] = useState<string | null>(null);
	const pathname = usePathname();

	useEffect(() => {
		const fetchPixelId = async () => {
			try {
				const res = await fetch(
					`${process.env.NEXT_PUBLIC_BASE_URL}/tracking-config`
				);
				const data = await res.json();
				if (data.facebook_pixel_id) {
					setPixelId(data.facebook_pixel_id);
				}
			} catch (err) {
				// Silently fail — tracking should never break the app
			}
		};
		fetchPixelId();
	}, []);

	// Track page views on route change
	useEffect(() => {
		if (pixelId && typeof window !== "undefined" && (window as any).fbq) {
			(window as any).fbq("track", "PageView");
		}
	}, [pathname, pixelId]);

	if (!pixelId) return null;

	return (
		<>
			<Script
				id="facebook-pixel-init"
				strategy="afterInteractive"
				dangerouslySetInnerHTML={{
					__html: `
						!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
						n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
						n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
						t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
						document,'script','https://connect.facebook.net/en_US/fbevents.js');
						fbq('init', '${pixelId}');
					`,
				}}
			/>
			<noscript>
				<img
					height="1"
					width="1"
					style={{ display: "none" }}
					src={`https://www.facebook.com/tr?id=${pixelId}&ev=PageView&noscript=1`}
					alt=""
				/>
			</noscript>
		</>
	);
}
