/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";
import Image from "next/image";
import { useGetAllSliderBottomBannersQuery } from "@/redux/features/home/homeApi";

export default function FeaturesSection() {
  const { data } = useGetAllSliderBottomBannersQuery(undefined);

  const defaultIcons = [
    "/images/default/feature1.png",
    "/images/default/feature2.png",
    "/images/default/feature3.png",
  ];

  const features = data?.data?.length
    ? data.data.map((item: any, index: number) => ({
        icon: item.icon ? `${item.icon}` : defaultIcons[index] || defaultIcons[0],
      }))
    : defaultIcons.map((icon) => ({ icon }));

  return (
    <div className="container">
      <div className="px-4 sm:px-6 lg:px-8 py-4 sm:py-6 lg:py-8 relative rounded-xl bg-[#FDF0F6]">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-8 lg:gap-12">
          {features.map((feature: any, index: number) => (
            <div key={index} className="relative text-center rounded-md overflow-hidden">
              {/* Icon Only */}
              <div className="flex justify-center py-3 px-4">
                <div className="h-full w-full flex items-center justify-center">
                  <Image
                    src={`https://api-v1.selfshop.com.bd/${feature.icon}`}
                    alt={`Feature ${index + 1}`}
                    width={400}
                    height={400}
                    className="w-full h-full object-contain"
                  />
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
