/* eslint-disable @typescript-eslint/no-unused-vars */
"use client";
import { usePathname, useRouter } from "next/navigation";
import { useState } from "react";
import { HiOutlineSquares2X2 } from "react-icons/hi2";
import { IoMdHome } from "react-icons/io";
import { IoPersonOutline } from "react-icons/io5";
import { LuShoppingCart } from "react-icons/lu";
import { cn } from "@/lib/utils";
import { useAppSelector } from "@/redux/hooks";
import AuthModal from "../AuthModal";
import CartDrawer from "../CartDrawer/CartDrawer";

const mobileBottomNavbar = [
	{ icon: <IoMdHome size={28} />, label: "Home", path: "/" },
	{
		icon: <HiOutlineSquares2X2 size={28} />,
		label: "Dashboard",
		path: "/categories",
	},
	{ icon: <LuShoppingCart size={28} />, label: "Cart" },
	// { icon: <LiaFileInvoiceSolid />, label: "Invoice" },
	{
		icon: <IoPersonOutline size={28} />,
		label: "Profile",
		path: "/dashboard/settings",
	},
];

const FooterNavbar = () => {
	const router = useRouter();
	const pathname = usePathname();
	const [isCartOpen, setIsCartOpen] = useState(false);
	const [isLoginModalOpen, setIsLoginModalOpen] = useState(false);
	const [isPricingModalOpen, setIsPricingModalOpen] = useState(false);
	const token = useAppSelector((state) => state.auth.access_token);
	const handleTabClick = (item: (typeof mobileBottomNavbar)[0]) => {
		if (item.label === "Cart") {
			setIsCartOpen(true);
		} else if (item.label === "Profile") {
			if (token) {
				router.push(item.path || "/dashboard/settings");
			} else {
				setIsLoginModalOpen(true);
			}
		} else if (item.path) {
			router.push(item.path);
		}
	};

	return (
		<div className="lg:hidden fixed bottom-0 flex items-center justify-around bg-white px-4 py-2 w-full z-50">
			{mobileBottomNavbar.map((item, index) => {
				const isActive = item.path ? pathname === item.path : false;

				return (
					<div
						key={index}
						onClick={() => handleTabClick(item)}
						className={cn(
							"flex flex-col items-center rounded-full p-2",
							isActive ? "text-white bg-[#E5005F]" : "text-gray-500",
						)}
					>
						{item.icon}
					</div>
				);
			})}
			<CartDrawer isOpen={isCartOpen} onClose={() => setIsCartOpen(false)} />
			<AuthModal
				open={isLoginModalOpen}
				onClose={() => setIsLoginModalOpen(false)}
				setIsPricingModalOpen={setIsPricingModalOpen}
			/>
		</div>
	);
};

export default FooterNavbar;
