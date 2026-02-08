"use client";

import { ReactNode } from "react";
import Link from "next/link";

/**
 * Vendor (Wholesale) layout for verified wholesalers.
 * Served when visiting vendor.selfshop.com (middleware rewrites to /vendor).
 */
export default function VendorLayout({ children }: { children: ReactNode }) {
  return (
    <div className="min-h-screen bg-gray-50">
      <header className="sticky top-0 z-10 bg-white border-b border-gray-200 px-4 py-3 shadow-sm">
        <div className="container mx-auto flex items-center justify-between">
          <Link href="/vendor" className="text-xl font-bold text-[#E5005F]">
            SelfShop Vendor
          </Link>
          <span className="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">Wholesale</span>
        </div>
      </header>
      <main className="container mx-auto px-4 py-6 pb-24">{children}</main>
    </div>
  );
}
