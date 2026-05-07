"use client";
import { LogOut, User } from "lucide-react";
import Image from "next/image";
import Link from "next/link";
import { type ReactNode, useState } from "react";
import logo from "@/assets/icons/NavLogo.png";
import ResponsiveLayout from "@/components/pages/dashboard/responsive-layout";
import UserNotificationCenter from "@/components/pages/dashboard/UserNotificationCenter";
import FooterNavbar from "@/components/shared/FooterNavbar/FooterNavbar";
import { getImageUrl } from "@/lib/utils";
import { useGetMeQuery } from "@/redux/features/auth/authApi";
import { setUser } from "@/redux/features/auth/authSlice";
import { useAppDispatch } from "@/redux/hooks";

export default function DashboardLayout({ children }: { children: ReactNode }) {
	const { data } = useGetMeQuery(undefined);
	const dispatch = useAppDispatch();
	const [showLogoutModal, setShowLogoutModal] = useState(false);

	const handleLogout = () => setShowLogoutModal(true);
	const confirmLogout = async () => {
		setShowLogoutModal(false);
		await dispatch(setUser({ access_token: null }));
	};

	return (
		<div className="h-screen bg-gray-50 overflow-hidden flex flex-col">
			{/* Desktop Header - hidden on mobile */}
			<header className="hidden lg:block flex-shrink-0 bg-white border-b border-gray-200 px-6 py-3">
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
								Here&apos;s what&apos;s happening with your store today
							</p>
						</div>
					</div>
					<div className="flex items-center gap-3">
						<UserNotificationCenter />
						<button
							type="button"
							onClick={handleLogout}
							className="h-10 w-10 rounded-full flex items-center justify-center border border-gray-200 text-gray-600 hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition-colors"
							aria-label="Logout"
						>
							<LogOut className="w-4 h-4" />
						</button>
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

			{/* Logout Confirmation Modal */}
			{showLogoutModal && (
				<div className="fixed inset-0 z-[9999] flex items-center justify-center">
					<div
						className="absolute inset-0 bg-black/50 backdrop-blur-sm"
						onClick={() => setShowLogoutModal(false)}
					/>
					<div className="relative w-full max-w-sm mx-4 bg-white rounded-2xl shadow-2xl p-6">
						<div className="flex justify-center mb-4">
							<div className="flex h-14 w-14 items-center justify-center rounded-full bg-red-50">
								<LogOut className="h-7 w-7 text-red-500" />
							</div>
						</div>
						<div className="text-center mb-6">
							<h3 className="text-lg font-semibold text-gray-900 mb-1">Logout</h3>
							<p className="text-sm text-gray-500">Are you sure you want to log out of your account?</p>
						</div>
						<div className="flex gap-3">
							<button
								onClick={() => setShowLogoutModal(false)}
								className="flex-1 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
							>
								Cancel
							</button>
							<button
								onClick={confirmLogout}
								className="flex-1 rounded-lg bg-[#E5005F] px-4 py-2.5 text-sm font-medium text-white hover:bg-[#c80053] transition-colors shadow-sm"
							>
								Yes, Logout
							</button>
						</div>
					</div>
				</div>
			)}
		</div>
	);
}
