/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { useGetAllSlidersQuery } from "@/redux/features/home/homeApi";
import Image from "next/image";
import { Autoplay, Navigation, Pagination } from "swiper/modules";
import { Swiper, SwiperSlide } from "swiper/react";

import "swiper/css";
import "swiper/css/navigation";
import "swiper/css/pagination";
import CategoryCarousel from "./CategoryCarousel";

export default function CategoriesSection() {
  const { data: sliderOptions } = useGetAllSlidersQuery(undefined);

  const sliders =
    sliderOptions?.data?.map((slider: any) => ({
      title: slider?.slider_title,
      image: slider?.slider_image,
      link: slider?.slider_btn_link,
    })) || [];

  return (
    <div className="bg-white">
      {/* Categories Section */}
      <div className="container mx-auto px-4 sm:px-6 lg:px-8 ">
        <CategoryCarousel />
      </div>

      {/* Promotional Banner Slider */}
      <div className="container mx-auto px-4 sm:px-6 lg:px-8 pb-6 lg:pb-8">
        <Swiper
          modules={[Autoplay, Pagination, Navigation]}
          spaceBetween={20}
          slidesPerView={1}
          loop={true}
          autoplay={{
            delay: 4000,
            disableOnInteraction: false,
          }}
          pagination={{ clickable: true }}
          navigation={false}
          className="rounded-lg overflow-hidden"
        >
          {sliders.map((slider: any, index: number) => (
            <SwiperSlide key={index}>
              <div className="relative w-full h-[180px] sm:h-[250px] md:h-[350px] lg:h-[500px]">
                <Image
                  src={"https://selfshop.com.bd/" + slider.image}
                  alt={slider.title}
                  fill
                  priority={index === 0}
                  className="object-cover rounded-lg"
                />
              </div>
            </SwiperSlide>
          ))}
        </Swiper>
      </div>
    </div>
  );
}
