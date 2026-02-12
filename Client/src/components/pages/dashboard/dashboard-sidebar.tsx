"use client";

import {
	ArrowUpDown,
	BarChart3,
	CreditCard,
	DollarSign,
	Download,
	GraduationCap,
	Home,
	LogOut,
	Package,
	Settings,
	Shield,
	ShoppingBag,
	Ticket,
	TrainTrack,
	Users,
	Wallet,
} from "lucide-react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { FcSupport } from "react-icons/fc";
import { MdDeveloperBoard, MdFormatQuote } from "react-icons/md";
import Swal from "sweetalert2";
import { useGetMeQuery } from "@/redux/features/auth/authApi";
import { setUser } from "@/redux/features/auth/authSlice";
import { useAppDispatch } from "@/redux/hooks";

function getInitials(name: string | undefined): string {
	if (!name) return "U";
	return name
		.split(" ")
		.map((n) => n[0])
		.join("")
		.toUpperCase()
		.slice(0, 2);
}

const menuItems = [
	{ icon: Home, label: "Home", href: "/" },
	{ icon: BarChart3, label: "Dashboard", href: "/dashboard" },
	{ icon: ShoppingBag, label: "My orders", href: "/dashboard/orders" },
	{
		icon: DollarSign,
		label: "My Referral Income",
		href: "/dashboard/referral-income",
	},
	{ icon: CreditCard, label: "Order Income", href: "/dashboard/order-income" },
	{
		icon: ArrowUpDown,
		label: "Balance transfer",
		href: "/dashboard/balance-transfer",
	},
	{ icon: Download, label: "Withdraw", href: "/dashboard/withdraw" },
	{
		icon: Package,
		label: "Product request",
		href: "/dashboard/product-request",
	},
	{ icon: Users, label: "My team members", href: "/dashboard/team-members" },
	{ icon: GraduationCap, label: "Free course", href: "/dashboard/free-course" },
	{ icon: Shield, label: "Fraud checker", href: "/dashboard/fraud-checker" },
	{ icon: TrainTrack, label: "Track orders", href: "/dashboard/track-orders" },
	{ icon: Ticket, label: "Ticket", href: "/dashboard/ticket" },
	{
		icon: MdDeveloperBoard,
		label: "Developers API",
		href: "/dashboard/developers-api",
	},
	{ icon: MdFormatQuote, label: "FAQ", href: "/dashboard/faq" },
	{ icon: Settings, label: "Settings", href: "/dashboard/settings" },
	{ icon: FcSupport, label: "Help Center", href: "/support" },
];

interface DashboardSidebarProps {
	onItemClick?: () => void;
}

export default function DashboardSidebar({
	onItemClick,
}: DashboardSidebarProps) {
	const { data } = useGetMeQuery(undefined);

	const profile = data?.data?.profile;
	const pathname = usePathname();
	const dispatch = useAppDispatch();

	const handleLogout = async () => {
		const result = await Swal.fire({
			title: "Are you sure?",
			icon: "warning",
			showCancelButton: true,
			confirmButtonColor: "#3085d6",
			cancelButtonColor: "#d33",
			confirmButtonText: "Yes, Log out",
		});
		if (result.isConfirmed) {
			// await dispatch(logout());
			await dispatch(
				setUser({
					access_token: null,
				}),
			);
			Swal.fire({
				title: "Logged out!",
				icon: "success",
				timer: 1500,
				showConfirmButton: false,
			});
		}
	};

	return (
		<aside className="w-full bg-white  sticky top-0 h-full max-h-[calc(100vh-75px)] overflow-hidden">
			{/* User Info */}
			<div className="p-5 bg-gradient-to-br from-[#E5005F] to-[#b80050]">
				<div className="flex items-center gap-3 mb-3">
					{/* Avatar */}
					<div className="w-11 h-11 rounded-full bg-white/20 border-2 border-white/40 flex items-center justify-center flex-shrink-0">
						<span className="text-white font-bold text-sm">
							{getInitials(profile?.name)}
						</span>
					</div>
					{/* Name & Email */}
					<div className="min-w-0">
						<h3 className="font-semibold text-white text-sm truncate">
							{profile?.name || "Unknown"}
						</h3>
						<p className="text-[12px] text-white/70 truncate">
							{profile?.email || "Unknown"}
						</p>
					</div>
				</div>
				{/* ID & Income row */}
				<div className="flex items-center justify-between gap-2">
					<span className="text-[11px] text-white/60 bg-white/10 px-2.5 py-0.5 rounded-full truncate">
						ID: {profile?.id || "—"}
					</span>
					<div className="flex items-center gap-1 bg-white/15 px-2.5 py-1 rounded-full">
						<Wallet className="w-3.5 h-3.5 text-white/80" />
						<span className="text-[12px] font-semibold text-white">
							{(profile?.income || 0).toLocaleString()}TK
						</span>
					</div>
				</div>
			</div>

			{/* Navigation Menu */}
			<nav className="p-4 overflow-hidden overflow-y-auto  h-full max-h-[calc(100vh-300px)]">
				<ul className="space-y-2">
					{menuItems.map((item, index) => {
						const isActive = pathname === item.href;

						return (
							<li key={index}>
								<Link href={item.href} className="flex-1 text-inherit no-underline">
									<div
										onClick={onItemClick}
										className={`w-full flex items-center gap-3 px-4 py-3 rounded-lg text-left transition-colors cursor-pointer ${isActive
											? "bg-[#E5005F] text-white"
											: "text-gray-700 hover:bg-gray-100"
											}`}
									>
										<item.icon className="w-5 h-5" />
										<span className="font-medium">{item.label}</span>
									</div>
								</Link>
							</li>
						);
					})}
					<li>
						<button
							onClick={() => handleLogout()}
							className={`w-full flex items-center gap-3 px-4 py-3 rounded-lg text-left transition-colors cursor-pointer text-gray-700 hover:bg-gray-100 `}
						>
							<LogOut className="w-5 h-5" />
							<span className="font-medium">Logout</span>
						</button>
					</li>
				</ul>
			</nav>
		</aside>
	);
}
