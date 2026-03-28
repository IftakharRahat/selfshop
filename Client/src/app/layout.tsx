import type { Metadata } from "next";
import { Poppins } from "next/font/google";
import MicrosoftClarity from "@/components/providers/MicrosoftClarity";
import { Toaster } from "sonner";
import MyContextProvider from "@/lib/MyContextProvider";
import SessionProviderForNextAuth from "@/nextAuth/SessionProviderForNextAuth";
import ReduxStoreProvider from "@/redux/ReduxStoreProvider";
import StyledComponentsRegistry from "@/lib/AntdRegistry";
import FcmProvider from "@/components/providers/FcmProvider";
import NotificationProvider from "@/components/providers/NotificationProvider";
import "./globals.css";

const poppins = Poppins({
	variable: "--font-poppins",
	weight: ["100", "200", "300", "400", "500", "600", "700", "800", "900"],
	subsets: ["latin"],
});

export const metadata: Metadata = {
	title: "SelfShop Limited | Bangladesh's Leading B2B Wholesale & Dropshipping Marketplace",
	description:
		"Empowering Bangladesh's entrepreneurs with reliable wholesale & dropshipping solutions. Source quality products, automate fulfillment, and scale your reselling business with SelfShop Limited.",
	icons: {
		icon: [
			{ url: "/favicon.png", sizes: "any" },
			{ url: "/favicon.png", type: "image/png", sizes: "32x32" },
			{ url: "/favicon.png", type: "image/png", sizes: "192x192" },
		],
		apple: "/favicon.png",
		shortcut: "/favicon.png",
	},
	openGraph: {
		title: "SelfShop Limited | Bangladesh's Leading B2B Wholesale & Dropshipping Marketplace",
		description:
			"Empowering Bangladesh's entrepreneurs with reliable wholesale & dropshipping solutions. Source quality products, automate fulfillment, and scale your reselling business with SelfShop Limited.",
		siteName: "SelfShop Limited",
		locale: "en_US",
		type: "website",
		images: [
			{
				url: "/icon/main_site_icon_with_label.png",
				width: 512,
				height: 512,
				alt: "SelfShop Limited",
			},
		],
	},
	twitter: {
		card: "summary",
		title: "SelfShop Limited | B2B Wholesale & Dropshipping Marketplace",
		description:
			"Bangladesh's premier B2B wholesale and dropshipping marketplace. Source quality products and scale your reselling business.",
		images: ["/icon/main_site_icon_with_label.png"],
	},
	metadataBase: new URL("https://selfshop.com.bd"),
	manifest: "/manifest.json",
};

export default function RootLayout({
	children,
}: Readonly<{
	children: React.ReactNode;
}>) {
	return (
		<html lang="en">
			<head>
				<MicrosoftClarity />
			</head>
			<body
				suppressHydrationWarning={true}
				className={`${poppins.variable} antialiased font-poppins`}
			>
				<MyContextProvider>
					<SessionProviderForNextAuth>
						<ReduxStoreProvider>
							<StyledComponentsRegistry>
								<FcmProvider>
									<NotificationProvider>
										<Toaster position="top-right" richColors toastOptions={{ style: { zIndex: 99999 } }} />
										{children}
									</NotificationProvider>
								</FcmProvider>
							</StyledComponentsRegistry>
						</ReduxStoreProvider>
					</SessionProviderForNextAuth>
				</MyContextProvider>
			</body>
		</html>
	);
}
