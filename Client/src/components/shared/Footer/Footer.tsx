/* eslint-disable @typescript-eslint/no-unused-vars */
"use client";

import type React from "react";
import { HiOutlineSquares2X2 } from "react-icons/hi2";
import { IoMdHome } from "react-icons/io";
import { IoPersonOutline } from "react-icons/io5";
import { LiaFileInvoiceSolid } from "react-icons/lia";
import { LuShoppingCart } from "react-icons/lu";

import footerLofo from "@/assets/icons/footerLogo.png";
import { cn } from "@/lib/utils";
import { Facebook, Instagram, Linkedin, Twitter } from "lucide-react";
import { useState } from "react";
import FooterNavbar from "../FooterNavbar/FooterNavbar";
import Link from "next/link";
// import home from "@/assets/images/home-01.png";
// import square from "@/assets/images/dashboard-square-01.png";
// import cart from "@/assets/images/shopping-cart-01.png";
// import invoice from "@/assets/images/invoice.png";
// import user from "@/assets/images/user.png";

const mobileBottomNavbar = [
  {
    icon: <IoMdHome />,
    label: "Home",
  },
  {
    icon: <HiOutlineSquares2X2 />,
    label: "Dashboard",
  },
  {
    icon: <LuShoppingCart />,
    label: "Cart",
  },
  {
    icon: <LiaFileInvoiceSolid />,
    label: "Invoice",
  },
  {
    icon: <IoPersonOutline />,
    label: "Profile",
  },
];
export default function Footer() {
  const [email, setEmail] = useState("");
  const [selectedTab, setSelectedTab] = useState("Home");

  const handleNewsletterSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    // Handle newsletter signup
    console.log("Newsletter signup:", email);
    setEmail("");
  };

  return (
    <>
      <FooterNavbar />
      <footer className="bg-[#2D2D2D] text-white">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8 lg:gap-12">
            {/* Logo and Description */}
            <div className="lg:col-span-2">
              <img src={footerLofo.src} alt="SelfShop Logo" className="w-44 mb-4" />
              <p className="text-gray-300 leading-relaxed mb-8">
                SelfShop is a B2B platform created for modern entrepreneurs and dropshippers. Here, you can purchase single wholesale products or buy
                in bulk—giving you the flexibility to grow your business your way. From trending items to essential goods, we make sourcing and
                scaling simple.
              </p>

              {/* Social Media */}
              <div>
                <h3 className="text-lg font-semibold mb-4">SOCIAL MEDIA</h3>
                <div className="flex space-x-3">
                  <a
                    href="#"
                    className="w-10 h-10 bg-gray-200 text-black rounded-full flex items-center justify-center hover:bg-pink-600 transition-colors"
                  >
                    <Facebook className="w-5 h-5" />
                  </a>
                  <a
                    href="#"
                    className="w-10 h-10 bg-gray-200 text-black rounded-full flex items-center justify-center hover:bg-pink-600 transition-colors"
                  >
                    <Twitter className="w-5 h-5" />
                  </a>
                  <a
                    href="#"
                    className="w-10 h-10 bg-gray-200 text-black rounded-full flex items-center justify-center hover:bg-pink-600 transition-colors"
                  >
                    <Linkedin className="w-5 h-5" />
                  </a>
                  <a
                    href="#"
                    className="w-10 h-10 bg-gray-200 text-black rounded-full flex items-center justify-center hover:bg-pink-600 transition-colors"
                  >
                    <Instagram className="w-5 h-5" />
                  </a>
                </div>
              </div>
            </div>

            {/* Information */}
            <div>
              <h3 className="text-lg font-semibold mb-6">INFORMATION</h3>
              <ul className="space-y-3">
                <li>
                  <Link href={"/"} className="text-gray-300 hover:text-white transition-colors">
                    Home
                  </Link>
                </li>
                <li>
                  <Link href={"/about-us"} className="text-gray-300 hover:text-white transition-colors">
                    About Us
                  </Link>
                </li>
                <li>
                  <Link href={"/contact"} className="text-gray-300 hover:text-white transition-colors">
                    Contact Us
                  </Link>
                </li>
                <li>
                  <Link href={"/return-policy"} className="text-gray-300 hover:text-white transition-colors">
                    Returns & Refunds
                  </Link>
                </li>
                {/* <li>
                  <a href="#" className="text-gray-300 hover:text-white transition-colors">
                    Terms & Conditions
                  </a>
                </li> */}
              </ul>
            </div>

            {/* Pages */}
            <div>
              <h3 className="text-lg font-semibold mb-6">PAGES</h3>
              <ul className="space-y-3">
                <li>
                  <a href="#" className="text-gray-300 hover:text-white transition-colors">
                    Live Chat
                  </a>
                </li>
                <li>
                  <a href="#" className="text-gray-300 hover:text-white transition-colors">
                    Live Tracking
                  </a>
                </li>
                <li>
                  <a href="#" className="text-gray-300 hover:text-white transition-colors">
                    Become an Supplier
                  </a>
                </li>
                <li>
                  <a href="#" className="text-gray-300 hover:text-white transition-colors">
                    Become an Reseller
                  </a>
                </li>
              </ul>
            </div>

            {/* Help Center & Newsletter */}
            <div>
              <h3 className="text-lg font-semibold mb-6">HELP CENTER</h3>
              <ul className="space-y-3 mb-8">
                <li>
                  <Link href={"/faq"} className="text-gray-300 hover:text-white transition-colors">
                    Faq
                  </Link>
                </li>
                <li>
                  <Link href={"/support"} className="text-gray-300 hover:text-white transition-colors">
                    Help & Support
                  </Link>
                </li>
                <li>
                  <Link href={"/return-policy"} className="text-gray-300 hover:text-white transition-colors">
                    Return policy
                  </Link>
                </li>
                <li>
                  <a href="#" className="text-gray-300 hover:text-white transition-colors">
                    Track Order
                  </a>
                </li>
              </ul>

              {/* Newsletter */}
              {/* <div className="">
                <h3 className="text-lg font-semibold mb-4">NEWSLETTER</h3>
                <p className="text-gray-300 text-sm mb-4">*Only valuable resource no bullshit</p>
                <form onSubmit={handleNewsletterSubmit} className="flex">
                  <input
                    type="email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    placeholder="Enter your e-mail"
                    className="flex-1 px-4 py-2 rounded-l-full bg-white text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-pink-500"
                    required
                  />
                  <button type="submit" className="px-4 py-2 bg-[#CC0168] hover:bg-pink-600 rounded-r-full transition-colors">
                    <ArrowRight className="w-5 h-5" />
                  </button>
                </form>
              </div> */}
            </div>
          </div>
        </div>
      </footer>
    </>
  );
}
