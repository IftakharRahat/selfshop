"use client";

import { useGetMeQuery } from "@/redux/features/auth/authApi";
import { setUser } from "@/redux/features/auth/authSlice";
import { useAppDispatch } from "@/redux/hooks";
import {
  ArrowUpDown,
  BarChart3,
  CreditCard,
  DollarSign,
  Download,
  GraduationCap,
  LogOut,
  Package,
  Settings,
  Shield,
  ShoppingBag,
  Ticket,
  TrainTrack,
  Users,
  Home,
} from "lucide-react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { FcSupport } from "react-icons/fc";
import { MdDeveloperBoard, MdFormatQuote } from "react-icons/md";
import Swal from "sweetalert2";

const menuItems = [
  { icon: Home, label: "Home", href: "/" },
  { icon: BarChart3, label: "Dashboard", href: "/dashboard" },
  { icon: ShoppingBag, label: "My orders", href: "/dashboard/orders" },
  { icon: DollarSign, label: "My Referral Income", href: "/dashboard/referral-income" },
  { icon: CreditCard, label: "Order Income", href: "/dashboard/order-income" },
  { icon: ArrowUpDown, label: "Balance transfer", href: "/dashboard/balance-transfer" },
  { icon: Download, label: "Withdraw", href: "/dashboard/withdraw" },
  { icon: Package, label: "Product request", href: "/dashboard/product-request" },
  { icon: Users, label: "My team members", href: "/dashboard/team-members" },
  { icon: GraduationCap, label: "Free course", href: "/dashboard/free-course" },
  { icon: Shield, label: "Fraud checker", href: "/dashboard/fraud-checker" },
  { icon: TrainTrack, label: "Track orders", href: "/dashboard/track-orders" },
  { icon: Ticket, label: "Ticket", href: "/dashboard/ticket" },
  { icon: MdDeveloperBoard, label: "Developers API", href: "/dashboard/developers-api" },
  { icon: MdFormatQuote, label: "FAQ", href: "/dashboard/faq" },
  { icon: Settings, label: "Settings", href: "/dashboard/settings" },
  { icon: FcSupport, label: "Help Center", href: "/support" },
];

interface DashboardSidebarProps {
  onItemClick?: () => void;
}

export default function DashboardSidebar({ onItemClick }: DashboardSidebarProps) {
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
        })
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
    <aside className="w-full bg-whit border-r border-gray-200  sticky top-0 h-full max-h-[calc(100vh-75px)]  overflow-hidden">
      {/* User Info */}
      <div className="p-6 bg-gray-100 border-b border-gray-200">
        <h3 className="font-semibold text-gray-900 mb-1">{profile?.name || "Unknown"}</h3>
        <p className="text-sm text-gray-600 mb-1">{profile?.email || "Unknown"}</p>
        <p className="text-sm text-gray-600 mb-3">ID: {profile?.id || "Unknown"}</p>
        <p className="text-sm font-medium text-green-600">Your Income {profile?.income || 0}TK</p>
      </div>

      {/* Navigation Menu */}
      <nav className="p-4 overflow-hidden overflow-y-auto  h-full max-h-[calc(100vh-300px)]">
        <ul className="space-y-2">
          {menuItems.map((item, index) => {
            const isActive = pathname === item.href;

            return (
              <li key={index}>
                <Link href={item.href}>
                  <button
                    onClick={onItemClick}
                    className={`w-full flex items-center gap-3 px-4 py-3 rounded-lg text-left transition-colors cursor-pointer ${
                      isActive ? "bg-[#E5005F] !text-white" : "text-gray-700 hover:bg-gray-100"
                    }`}
                  >
                    <item.icon className="w-5 h-5" />
                    <span className="font-medium">{item.label}</span>
                  </button>
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
