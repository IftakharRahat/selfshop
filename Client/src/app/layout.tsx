import MyContextProvider from "@/lib/MyContextProvider";
import SessionProviderForNextAuth from "@/nextAuth/SessionProviderForNextAuth";
import ReduxStoreProvider from "@/redux/ReduxStoreProvider";
import type { Metadata } from "next";
import { Poppins } from "next/font/google";
import { Toaster } from "sonner";
import "./globals.css";

const poppins = Poppins({
  variable: "--font-poppins",
  weight: ["100", "200", "300", "400", "500", "600", "700", "800", "900"],
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: "Self Shop",
  description: "Your one-stop online shop for all your needs.",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en">
      <body suppressHydrationWarning={true} className={`${poppins.variable} antialiased font-poppins`}>
        <MyContextProvider>
          <SessionProviderForNextAuth>
            <ReduxStoreProvider>
              <Toaster />
              {children}
            </ReduxStoreProvider>
          </SessionProviderForNextAuth>
        </MyContextProvider>
      </body>
    </html>
  );
}
