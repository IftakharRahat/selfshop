"use client";

import logo from "@/assets/icons/NavLogo.png";
import { useGetMeQuery } from "@/redux/features/auth/authApi";
import { Drawer } from "antd";
import { Menu, User, X } from "lucide-react";
import Image from "next/image";
import Link from "next/link";
import type React from "react";
import { useState } from "react";
import DashboardSidebar from "./dashboard-sidebar";

interface ResponsiveLayoutProps {
  children: React.ReactNode;
}

export default function ResponsiveLayout({ children }: ResponsiveLayoutProps) {
  const { data } = useGetMeQuery(undefined);
  const [drawerOpen, setDrawerOpen] = useState(false);

  return (
    <div className="flex min-h-screen">
      {/* Desktop Sidebar - hidden on mobile */}
      <div className="hidden lg:block">
        <DashboardSidebar />
      </div>

      {/* Mobile Drawer */}
      <Drawer
        title={null}
        placement="left"
        onClose={() => setDrawerOpen(false)}
        open={drawerOpen}
        width={280}
        styles={{
          body: { padding: 0 },
          header: { display: "none" },
        }}
        className="lg:hidden"
        zIndex={1000}
      >
        <div className="flex justify-end p-4 border-b bg-white">
          <button onClick={() => setDrawerOpen(false)} className="h-8 w-8 hover:bg-gray-100">
            <X className="h-4 w-4" />
          </button>
        </div>
        <DashboardSidebar onItemClick={() => setDrawerOpen(false)} />
      </Drawer>

      {/* Main Content Area */}
      <div className="flex-1 flex flex-col min-w-0">
        {/* Mobile Header with Hamburger */}
        <div className="lg:hidden bg-white border-b border-gray-200 px-4 py-3 sticky top-0 z-10">
          <div className="flex items-center justify-between">
            <button onClick={() => setDrawerOpen(true)} className="h-8 w-8 hover:bg-gray-100">
              <Menu className="h-4 w-4" />
            </button>
            {/* <div className="flex items-center gap-2">
              <div className="w-6 h-6 bg-pink-500 rounded-lg flex items-center justify-center">
                <div className="w-3 h-3 bg-white rounded-full"></div>
              </div>
              <span className="text-lg font-bold text-pink-500">SELFSHOP</span>
            </div> */}

            <Link href="/">
              <img src={logo.src} alt="SelfShop Logo" className="w-44 " />
            </Link>
            <div className="flex items-center gap-2">
              {/* <button className="h-8 w-8 hover:bg-gray-100">
                <Bell className="w-4 h-4 text-gray-600" />
              </button> */}
              <div className="w-10 h-10 rounded-full overflow-hidden border">
                {data?.data?.profile?.profile ? (
                  <Image
                    src={`https://api-v1.selfshop.com.bd/${data?.data?.profile?.profile}`}
                    alt="Profile"
                    width={96}
                    height={96}
                    className="w-full h-full object-fill"
                  />
                ) : (
                  <User className="w-12 h-12" />
                )}
              </div>
            </div>
          </div>
        </div>

        {/* Main Content */}
        <div className=" h-full max-h-[calc(100vh-75px)] overflow-hidden overflow-y-auto">{children}</div>
      </div>
    </div>
  );
}
