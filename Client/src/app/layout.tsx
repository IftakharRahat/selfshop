import type { Metadata } from "next";
import { Poppins } from "next/font/google";
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
