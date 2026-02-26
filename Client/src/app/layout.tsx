import type { Metadata } from "next";
import { Poppins } from "next/font/google";
import Script from "next/script";
import { Toaster } from "sonner";
import MyContextProvider from "@/lib/MyContextProvider";
import SessionProviderForNextAuth from "@/nextAuth/SessionProviderForNextAuth";
import ReduxStoreProvider from "@/redux/ReduxStoreProvider";
import StyledComponentsRegistry from "@/lib/AntdRegistry";
import "./globals.css";

const poppins = Poppins({
	variable: "--font-poppins",
	weight: ["100", "200", "300", "400", "500", "600", "700", "800", "900"],
	subsets: ["latin"],
});

export const metadata: Metadata = {
	title: "SelfShop",
	description: "Your one-stop online shop for all your needs.",
};

export default function RootLayout({
	children,
}: Readonly<{
	children: React.ReactNode;
}>) {
	return (
		<html lang="en">
			<body
				suppressHydrationWarning={true}
				className={`${poppins.variable} antialiased font-poppins`}
			>
				<Script id="onesignal-deferred" strategy="afterInteractive">
					{`window.OneSignalDeferred = window.OneSignalDeferred || [];`}
				</Script>
				<Script
					src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js"
					strategy="afterInteractive"
				/>
				<MyContextProvider>
					<SessionProviderForNextAuth>
						<ReduxStoreProvider>
							<StyledComponentsRegistry>
								<Toaster />
								{children}
							</StyledComponentsRegistry>
						</ReduxStoreProvider>
					</SessionProviderForNextAuth>
				</MyContextProvider>
			</body>
		</html>
	);
}
