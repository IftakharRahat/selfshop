"use client";
import { User } from "lucide-react";
import Image from "next/image";
import Link from "next/link";
import type { ReactNode } from "react";
import logo from "@/assets/icons/NavLogo.png";
import ResponsiveLayout from "@/components/pages/dashboard/responsive-layout";
import FooterNavbar from "@/components/shared/FooterNavbar/FooterNavbar";
import { getImageUrl } from "@/lib/utils";
import { useGetMeQuery } from "@/redux/features/auth/authApi";

export default function DashboardLayout({ children }: { children: ReactNode }) {
	const { data } = useGetMeQuery(undefined);
	return (
		<div className="h-screen bg-gray-50  overflow-hidden">
			{/* Desktop Header - hidden on mobile */}
			<header className="hidden lg:block bg-white border-b border-gray-200 px-6 py-3">
				<div className="flex items-center justify-between">
					<div className="flex items-center gap-8">
						<Link href="/" className="w-[240px] flex-shrink-0 pr-6">
							<img src={logo.src} alt="SelfShop Logo" className="w-48" />
						</Link>
						<div>
							<h1 className="text-lg font-semibold text-gray-900">
								Welcome{data?.data?.profile?.name ? `, ${data.data.profile.name}` : ""}
							</h1>
							<p className="text-sm text-gray-500">
								Here's what's happening with your store today
							</p>
						</div>
					</div>
					<div className="flex items-center gap-6">
						<div className="w-10 h-10 rounded-full overflow-hidden border border-gray-200 flex items-center justify-center bg-gray-50">
							{data?.data?.profile?.profile ? (
								<Image
									src={getImageUrl(data?.data?.profile?.profile)}
									alt="Profile"
									width={96}
									height={96}
									className="w-full h-full object-cover"
								/>
							) : (
								<User className="w-5 h-5 text-gray-400" />
							)}
						</div>
					</div>
				</div>
			</header>

			<ResponsiveLayout>{children}</ResponsiveLayout>
			<FooterNavbar />
		</div>
	);
}
