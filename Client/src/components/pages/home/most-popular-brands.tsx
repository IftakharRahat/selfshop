/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import React from "react";
import Image from "next/image";
import { getImageUrl } from "@/lib/utils";
import { useGetAllBrandsQuery } from "@/redux/features/home/homeApi";

// Swiper imports
import { Swiper, SwiperSlide } from "swiper/react";
import "swiper/css";
import "swiper/css/free-mode";
import { FreeMode } from "swiper/modules";

const MostPopularBrands = () => {
  const { data } = useGetAllBrandsQuery(undefined);

  const brands =
    data?.data?.map((brand: any) => ({
      id: brand.id,
      name: brand.brand_name,
      icon: brand.brand_icon,
      slug: brand.slug,
    })) || [];

  return (
    <div className="container mx-auto py-4 sm:py-6 lg:py-8">
      {/* Section title */}
      <h2 className="text-xl sm:text-2xl lg:text-3xl font-semibold text-[#322F35] mb-5 text-center">Most Popular Brands</h2>

      {/* ---------- MOBILE SWIPER ---------- */}
      <div className="block md:hidden">
        <Swiper slidesPerView={3.5} spaceBetween={5} freeMode={true} modules={[FreeMode]} className=" h-full flex items-center">
          {brands.map((brand: any) => (
            <SwiperSlide key={brand.id} className=" h-full my-auto">
              <div className="flex justify-center items-center p-4 bg-white rounded-lg cursor-pointer hover:shadow-md transition-shadow duration-300">
                <Image src={getImageUrl(brand.icon)} alt={brand?.name || "Brand"} width={120} height={180} className="object-contain my-auto" />
              </div>
            </SwiperSlide>
          ))}
        </Swiper>
      </div>

      {/* ---------- DESKTOP GRID / FLEX ---------- */}
      <div className="hidden md:grid grid-cols-3 lg:flex lg:items-center lg:justify-between gap-6 sm:gap-8">
        {brands.map((brand: any) => (
          <div
            key={brand.id}
            className="flex justify-center items-center p-4 bg-white rounded-lg cursor-pointer hover:shadow-md transition-shadow duration-300"
          >
            <Image src={getImageUrl(brand.icon)} alt={brand?.name || "Brand"} width={120} height={80} className="object-contain" />
          </div>
        ))}
      </div>
    </div>
  );
};

export default MostPopularBrands;
