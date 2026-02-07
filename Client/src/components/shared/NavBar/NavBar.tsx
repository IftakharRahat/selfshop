/* eslint-disable @typescript-eslint/no-unused-vars */
/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import BecomeADropshiiper from "@/assets/icons/Become a dropshiiper.png";
import logo from "@/assets/icons/NavLogo.png";
import shopLogo from "@/assets/icons/shoplogo.png";
import TrackYourOrder from "@/assets/icons/Track your order.png";
import { Menu as MenuIcon, Search, ShoppingCart, User, X } from "lucide-react";

import { useGetMeQuery } from "@/redux/features/auth/authApi";
import { setUser } from "@/redux/features/auth/authSlice";
import { useGetAllCartItemsQuery } from "@/redux/features/cartApi";
import { useGetAllMenusQuery, useGetAllNavbarCategoryDropdownOptionsQuery } from "@/redux/features/home/homeApi";
import { useAppDispatch, useAppSelector } from "@/redux/hooks";
import { handleAsyncWithToast } from "@/utils/handleAsyncWithToast";
import { Dropdown, Menu } from "antd";
import Link from "next/link";
import { useEffect, useState } from "react";
import Swal from "sweetalert2";
import DropDownBtn from "./DropDownBtn";

import { PricingPage } from "@/components/pages/dashboard/pricing-page";
import { useLoginMutation, useRegisterMutation } from "@/redux/features/auth/authApi";
import { Button, ConfigProvider, Divider, Form, Input, Modal, Tabs } from "antd";
import { useRouter } from "next/navigation";
import { FaApple } from "react-icons/fa";
import { FcGoogle } from "react-icons/fc";
import CartDrawer from "../CartDrawer/CartDrawer";
import AuthModal from "../AuthModal";

export default function Navbar() {
  const dispatch = useAppDispatch();
  const token = useAppSelector((state) => state.auth.access_token);
  const { data: user } = useGetMeQuery(token);
  const [isCartOpen, setIsCartOpen] = useState(false);
  const { data: cartItems } = useGetAllCartItemsQuery(undefined);

  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [isLoginModalOpen, setIsLoginModalOpen] = useState(false);
  const [isPricingModalOpen, setIsPricingModalOpen] = useState(false);
  const [isInvoiceModalOpen, setIsInvoiceModalOpen] = useState(false);
  const [searchValue, setSearchValue] = useState("");

  const router = useRouter();

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
      await dispatch(
        setUser({
          access_token: null,
        })
      );
      await localStorage.removeItem("access_token");
      // await dispatch(logout());
      Swal.fire({
        title: "Logged out!",
        icon: "success",
        timer: 1500,
        showConfirmButton: false,
      });
    }
  };

  const { data: categoryDropdownOptions } = useGetAllNavbarCategoryDropdownOptionsQuery(undefined);
  const { data: menuOptions } = useGetAllMenusQuery(undefined);

  // Map API data to DropdownMenu format
  const mappedMenuData =
    categoryDropdownOptions?.data?.map((cat: any, index: number) => ({
      id: index,
      name: cat.category_name,
      href: `/product-filter?category=${cat.slug}`, // ✅ optional
      sub_items: cat.subcategories?.map((sub: any, index: number) => ({
        id: index,
        name: sub.sub_category_name,
        href: `/product-filter?category=${sub.slug}`, // ✅ optional
        sub_sub_items: [], // If you don’t have deeper levels
      })),
    })) || [];

  const categories = menuOptions?.data?.map((menu: any) => ({ item: menu?.category_name, icon: menu?.category_icon, slug: menu?.slug })) || [];

  const userMenu = (
    <Menu>
      <Menu.Item key="profile">
        <Link href="/dashboard/settings">Profile</Link>
      </Menu.Item>
      <Menu.Item key="logout" onClick={handleLogout} danger>
        Logout
      </Menu.Item>
    </Menu>
  );

  const [form] = Form.useForm();
  const [activeTab, setActiveTab] = useState("reseller");
  const [isLogin, setIsLogin] = useState(true);
  const [isRegistration, setIsRegistration] = useState(false);
  const [expandedCategory, setExpandedCategory] = useState<number | null>(null);



  return (
    <>
      {/* <LoginModal open={isLoginModalOpen} onCancel={() => setIsLoginModalOpen(false)} /> */}
      {/* Top notification bar */}
      <div className="hidden lg:block bg-gradient-to-r from-[#B9006E] to-[#E7005E] py-2 text-white px-4 sm:px-6 lg:px-8">
        <div className="container flex items-center justify-between  ">
          <div className="flex items-center">
            <img src={shopLogo.src} alt="Shop Logo" className="w-6 h-6 mr-2" />
            <p>Explore Mega offer winter for getting hottest drops.</p>
          </div>
          <div className="flex items-center gap-6">
            <div className="flex items-center">
              <img src={TrackYourOrder.src} alt="Track Your Order" className="w-6 h-6 mr-2" />
              <p>Track your order</p>
            </div>
            <div className="flex items-center">
              <img src={BecomeADropshiiper.src} alt="Become a Dropshipper" className="w-6 h-6 mr-2" />
              <p>Become a dropshipper</p>
            </div>
          </div>
        </div>
      </div>
      <div className="bg-gradient-to-r from-[#D701640F] to-[#D701640F] ">
        {/* Main navbar */}
        <div className="container  py-3 lg:py-5 border-b border-b-[#4E4E4E17] ">
          <div className="flex items-center justify-between h-16">
            {/* Logo */}
            <Link href="/">
              <img src={logo.src} alt="SelfShop Logo" className="w-44 " />
            </Link>

            {/* Search bar - hidden on mobile */}
            <div className="hidden lg:flex flex-1 max-w-2xl mx-8">
              <div className="relative w-full">
                <input
                  type="text"
                  placeholder="Search product or Store"
                  value={searchValue}
                  onChange={(e) => setSearchValue(e.target.value)}
                  className="w-full pl-4 pr-12 py-2 border bg-white border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-[#E7005E] "
                />
                <button
                  onClick={() => router.push(`/search?keywords=${searchValue}`)}
                  className="absolute right-0 top-0 h-full px-4 bg-[#E7005E] hover:bg-pink-600 text-white rounded-r-full rounded-l-none"
                >
                  <Search className="h-4 w-4" />
                </button>
              </div>
            </div>

            {/* Right side - User and Cart */}
            <div className="flex items-center space-x-6">
              {token && user?.data?.profile?.name ? (
                <Dropdown overlay={userMenu} placement="bottomRight" trigger={["click"]}>
                  <div className="hidden sm:flex items-center space-x-2 text-gray-700 cursor-pointer">
                    <User className="h-5 w-5" />
                    <span className="hidden sm:block">Hello, {user?.data?.profile?.name}</span>
                  </div>
                </Dropdown>
              ) : (
                <div onClick={() => setIsLoginModalOpen(true)} className="hidden sm:flex items-center space-x-2 text-gray-700 cursor-pointer">
                  <User className="h-5 w-5" />
                  <span>Hello, Sign in</span>
                </div>
              )}

              {/* <div className="flex items-center space-x-2 text-gray-700">
                <ShoppingCart className="h-5 w-5" />
                <span>Cart</span>
              </div> */}
              {/* mobile */}
              <div>
                <div className="flex items-center gap-2">
                  <div
                    className="hidden md:flex items-center space-x-0.5 md:space-x-2 text-gray-700 cursor-pointer"
                    onClick={() => setIsCartOpen(true)}
                  >
                    <ShoppingCart className="h-4 w-4 md:h-5 md:w-5" />
                    <span className="text-xs md:text-base">Cart ({cartItems?.data?.length || 0})</span>
                  </div>
                  <div className="sm:hidden">
                    {/* mobile */}
                    {token && user?.data?.profile?.name ? (
                      <Dropdown overlay={userMenu} placement="bottomRight" trigger={["click"]}>
                        <div className="flex items-center space-x-2 text-gray-700 cursor-pointer">
                          <User className="h-5 w-5" />
                        </div>
                      </Dropdown>
                    ) : (
                      <div onClick={() => setIsLoginModalOpen(true)} className="flex items-center space-x-2 text-gray-700 cursor-pointer">
                        <User className="h-5 w-5" />
                      </div>
                    )}
                  </div>
                </div>
                <CartDrawer isOpen={isCartOpen} onClose={() => setIsCartOpen(false)} />
              </div>
              {/* Mobile menu button */}
              <button className="lg:hidden" onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}>
                {isMobileMenuOpen ? <X className="h-5 w-5" /> : <MenuIcon className="h-5 w-5" />}
              </button>
            </div>
          </div>

          {/* Mobile search bar */}
          <div className="lg:hidden pb-4">
            <div className="relative">
              <input
                type="text"
                value={searchValue}
                onChange={(e) => setSearchValue(e.target.value)}
                placeholder="Search product or Store"
                className="w-full pl-4 pr-12 py-2 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-[#E7005E] focus:border-transparent"
              />
              <button
                onClick={() => router.push(`/search?keywords=${searchValue}`)}
                className="absolute right-0 top-0 h-full px-4 bg-[#E7005E] hover:bg-pink-600 text-white rounded-r-full rounded-l-none"
              >
                <Search className="h-4 w-4" />
              </button>
            </div>
          </div>
        </div>

        {/* Categories navigation */}
        <div className="">
          <div className="container px-4 sm:px-6 lg:px-8">
            {/* Desktop categories */}
            <div className="hidden lg:flex items-center gap-5 space-x-1 py-3 ">
              {/* All Categories Dropdown Menu */}
              <DropDownBtn title="All Categories" menuData={mappedMenuData} />
              <div>
                {/* {categories.map((category: any) => (
                  <button
                    key={category?.id}
                    onClick={() => router.push(`/product-filter?category=${category?.slug}`)}
                    className="text-gray-700 hover:text-pink-600 hover:bg-pink-50 rounded-full px-4 py-2 whitespace-nowrap font-[18px] cursor-pointer"
                  >
                    {category?.item}
                  </button>
                ))} */}
              </div>
            </div>

            {/* Mobile categories */}
            {/* {isMobileMenuOpen && (
              <div className=" lg:hidden py-3 space-y-2">
              
                {categories.map((category: any) => (
                  <button key={category?.id} className="w-full justify-start text-gray-700 hover:text-pink-600 hover:bg-pink-50 rounded-lg">
                    {category?.item}
                  </button>
                ))}
              </div>
            )} */}
            {isMobileMenuOpen && (
              <div className="lg:hidden py-3 space-y-2 bg-white shadow-md rounded-lg">
                {mappedMenuData.map((category: any) => (
                  <div key={category.id} className="space-y-1">
                    {/* Main category button */}
                    <button
                      onClick={() =>
                        category.sub_items && category.sub_items.length === 0
                          ? router.push(category.href)
                          : setExpandedCategory(expandedCategory === category.id ? null : category.id)
                      }
                      className="w-full flex text-xs justify-between items-center text-gray-800 hover:text-pink-600 hover:bg-pink-50 rounded-lg px-4 py-3 font-semibold transition-all duration-200"
                    >
                      <span className="text-sm">{category.name}</span>
                      {category.sub_items && category.sub_items.length > 0 && (
                        <svg
                          className={`w-5 h-5 ml-2 text-gray-400 transition-transform duration-300 ${
                            expandedCategory === category.id ? "rotate-180" : ""
                          }`}
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                          xmlns="http://www.w3.org/2000/svg"
                        >
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                        </svg>
                      )}
                    </button>

                    {/* Subcategories */}
                    {category.sub_items && category.sub_items.length > 0 && expandedCategory === category.id && (
                      <div className="pl-6 space-y-1 border-l border-gray-200">
                        {category.sub_items.map((sub: any) => (
                          <button
                            key={sub.id}
                            onClick={() => router.push(sub.href)}
                            className="w-full text-gray-600 hover:text-pink-500 hover:bg-pink-50 rounded-lg px-4 py-2 text-xs font-medium transition-colors duration-200"
                          >
                            <span className="text-xs"> {sub.name}</span>
                          </button>
                        ))}
                      </div>
                    )}
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      </div>

      <ConfigProvider
        theme={{
          token: {
            colorPrimary: "#e91e63",
            colorLink: "#e91e63",
          },
        }}
      >
        <AuthModal
          open={isLoginModalOpen}
          onClose={() => setIsLoginModalOpen(false)}
          setIsPricingModalOpen={setIsPricingModalOpen}
        />

        <Modal
          open={isPricingModalOpen}
          // onCancel={() => setIsPricingModalOpen(false)}
          footer={null}
          width={900}
          centered
          // styles={{
          //   body: { padding: "40px 40px 20px 40px" },
          // }}
          closeIcon={null}
        >
          {/* Logo + Title */}
          <div className="text-center mb-6 py-[40px] pt-[20px] pb-[40px]">
            <div className="flex items-center justify-center mb-4">
              <img src={logo.src} alt="SelfShop Logo" className="w-60" />
            </div>

            <PricingPage />
          </div>
        </Modal>
      </ConfigProvider>
    </>
  );
}
