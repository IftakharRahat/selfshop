/* eslint-disable @typescript-eslint/no-explicit-any */
import React, { useState } from "react";
import { Swiper, SwiperSlide } from "swiper/react";
import { Navigation } from "swiper/modules";

import "swiper/css";
import "swiper/css/navigation";

import { getImageUrl } from "@/lib/utils";
import { useGetAllMenusQuery } from "@/redux/features/home/homeApi";
import Image from "next/image";
import { useRouter } from "next/navigation";
import Link from "next/link";

export default function CategoryCarousel() {
  const router = useRouter();
  const { data: menuOptions } = useGetAllMenusQuery(undefined);

  const [showMore] = useState(false);

  const categories =
    menuOptions?.data?.map((menu: any) => ({
      name: menu?.category_name,
      icon: menu?.category_icon,
      slug: menu?.slug,
    })) || [];

  // ⚡ Only first 6 items if showMore = false (2 rows of 3)
  const mobileCategories = showMore ? categories : categories.slice(0, 6);

  return (
    <div>
      {/* ✔ MOBILE VIEW */}
      <div className="lg:hidden  mb-2">
        {/* ✔ SEE MORE BUTTON */}
        <div className="flex items-center justify-between my-4">
          <h4 className="font-semibold">Popular Categories</h4>
          {categories.length > 6 && (
          <div className="text-end">
            <Link href="/categories" passHref>
            <button className="px-3 py-[2px] text-xs font-medium text-pink-600 border border-pink-600 rounded-full hover:bg-pink-50 transition-colors">
              See All
            </button>
            </Link>
          </div>
        )}
        </div>

        <div className="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-4">
          {mobileCategories.map((category: any, index: number) => (
            <div
              key={index}
              className="flex flex-col items-center text-center group cursor-pointer"
              onClick={() => router.push(`/product-filter?category=${category?.slug}`)}
            >
              <div className="w-16 h-16 mb-2 bg-gray-100 rounded-full flex items-center justify-center group-hover:bg-gray-200 transition-colors">
                <Image src={getImageUrl(category?.icon)} alt={category?.name || "Category"} width={40} height={40} className="w-8 h-8" />
              </div>
              <span className="text-xs text-gray-700 font-medium group-hover:text-pink-600 transition-colors">{category.name}</span>
            </div>
          ))}
        </div>
      </div>

      {/* ✔ DESKTOP VIEW */}
      <div className="hidden lg:block w-full mx-auto py-6">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-lg font-semibold">All popular categories</h2>
        </div>

        <div className="rounded-2xl bg-white shadow-md p-4 relative">
          <Swiper
            spaceBetween={20}
            slidesPerView={6}
            navigation={true}
            loop={true}
            modules={[Navigation]}
            breakpoints={{
              320: { slidesPerView: 2 },
              480: { slidesPerView: 3 },
              640: { slidesPerView: 4 },
              1024: { slidesPerView: 6 },
              1280: { slidesPerView: 8 },
            }}
            className="relative"
          >
            {categories.map((cat: any, i: number) => (
              <SwiperSlide key={cat.slug || cat.name + i}>
                <a href={`/${cat.slug || "category"}`} className="flex flex-col items-center gap-3 p-3 hover:bg-gray-50 rounded-lg">
                  <div className="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center shadow-sm overflow-hidden">
                    <img
                      src={getImageUrl(cat.icon)}
                      alt={cat?.name || "Category"}
                      className="w-full h-full object-contain"
                      loading="lazy"
                      onError={(e) => {
                        e.currentTarget.src =
                          "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='200' height='200'><rect fill='%23f3f4f6' width='100%25' height='100%25'/><text x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' fill='%239ca3af' font-size='20'>No image</text></svg>";
                      }}
                    />
                  </div>

                  <div className="text-xs text-center text-gray-700 max-w-[80px] truncate">{cat.name}</div>
                </a>
              </SwiperSlide>
            ))}
          </Swiper>
        </div>
      </div>
    </div>
  );
}
