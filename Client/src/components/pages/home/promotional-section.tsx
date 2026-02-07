/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";
import banner1 from "@/assets/images/newImages/3.png";
import banner2 from "@/assets/images/newImages/Ready for boost.png";
import banner3 from "@/assets/images/newImages/2.png";
import banner4 from "@/assets/images/newImages/subel112.png";
import banner5 from "@/assets/images/newImages/Profit.png";
import banner6 from "@/assets/images/newImages/4.png";
import { useGetAllCollectionsQuery } from "@/redux/features/home/homeApi";
import Image from "next/image";
import Link from "next/link";
import { useState } from "react";

export default function PromotionalSection() {
  const [objectQuery] = useState([
    { name: "page", value: 1 },
    { name: "limit", value: 2 },
  ]);

  const { data } = useGetAllCollectionsQuery({ objectQuery, slug: "hot_selling" });
  const { data: readyToBoostData } = useGetAllCollectionsQuery({ objectQuery, slug: "ready_to_bost" });
  const { data: limitedOffersData } = useGetAllCollectionsQuery({ objectQuery, slug: "limited_offer" });
  const { data: newArrivalsData } = useGetAllCollectionsQuery({ objectQuery, slug: "new_arrivel" });
  const { data: profitableProductData } = useGetAllCollectionsQuery({ objectQuery, slug: "profitable_product" });
  const { data: summerCollectionData } = useGetAllCollectionsQuery({ objectQuery, slug: "summer_collection" });

  return (
    <div className="w-full bg-gray-50 py-8 lg:py-12">
      <div className="container mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
          {/* category 1 */}
          <div className="bg-white rounded-lg border border-[#CFEDFF] overflow-hidden">
            {/* Banner Section */}
            <div className={` relative h-48 sm:h-52 lg:h-52 flex items-center justify-center p-4 `}>
              <Image
                src={banner1 || "/placeholder.svg"}
                alt={"image"}
                width={1200}
                height={800}
                className="w-full h-full object-cover rounded-lg overflow-hidden"
              />
            </div>

            {/* Title and Explore Button */}
            <div className="p-4 flex items-center justify-between">
              <h3 className={`text-lg lg:text-xl font-bold text-[#E5005F] `}>HOT SELLING</h3>
              <Link href={`/category?category=hot_selling`} scroll>
                <button className="bg-black text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-gray-800 transition-colors">Explore</button>
              </Link>
            </div>

            {/* Products Grid */}
            <div className="px-4 pb-4">
              <div className="grid grid-cols-2 gap-4">
                {data?.data?.data?.map((product: any) => (
                  <div key={product.id} className="text-center">
                    <Link href={`/product/${product?.ProductSlug}`}>
                      <div className="bg-gray-1 rounded-lg p-4 mb-2 hover:bg-gray-200 transition-colors cursor-pointer">
                        <Image
                          src={"https://selfshop.com.bd/" + product?.ViewProductImage || "/placeholder.svg"}
                          alt={product.name}
                          width={100}
                          height={100}
                          className="w-full h-20 object-contain"
                        />
                      </div>
                    </Link>
                    <Link href={`/product/${product?.ProductSlug}`}>
                      <p className="text-sm text-gray-700 font-medium">
                        {product?.ProductName?.length > 35 ? product?.ProductName?.slice(0, 35) + "..." : product?.ProductName}
                      </p>
                    </Link>
                  </div>
                ))}
              </div>
            </div>
          </div>
          {/* category 2 */}
          <div className="bg-white rounded-lg border border-[#CFEDFF] overflow-hidden">
            {/* Banner Section */}
            <div className={` relative h-48 sm:h-52 lg:h-52 flex items-center justify-center p-4 `}>
              <Image
                src={banner2 || "/placeholder.svg"}
                alt={"image"}
                width={1200}
                height={800}
                className="w-full h-full object-cover rounded-lg overflow-hidden"
              />
            </div>

            {/* Title and Explore Button */}
            <div className="p-4 flex items-center justify-between">
              <h3 className={`text-lg lg:text-xl font-bold text-[#E5005F] `}>READY TO BOOST</h3>
              <Link href={`/category?category=ready_to_bost`} scroll>
                <button className="bg-black text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-gray-800 transition-colors">Explore</button>
              </Link>
            </div>

            {/* Products Grid */}
            <div className="px-4 pb-4">
              <div className="grid grid-cols-2 gap-4">
                {readyToBoostData?.data?.data?.map((product: any) => (
                  <div key={product.id} className="text-center">
                    <Link href={`/product/${product?.ProductSlug}`}>
                      <div className="bg-gray-1 rounded-lg p-4 mb-2 hover:bg-gray-200 transition-colors cursor-pointer">
                        <Image
                          src={"https://selfshop.com.bd/" + product?.ViewProductImage || "/placeholder.svg"}
                          alt={product.name}
                          width={100}
                          height={100}
                          className="w-full h-20 object-contain"
                        />
                      </div>
                      <p className="text-sm text-gray-700 font-medium">
                        {product?.ProductName?.length > 35 ? product?.ProductName?.slice(0, 35) + "..." : product?.ProductName}
                      </p>
                    </Link>
                  </div>
                ))}
              </div>
            </div>
          </div>
          {/* category 3 */}
          <div className="bg-white rounded-lg border border-[#CFEDFF] overflow-hidden">
            {/* Banner Section */}
            <div className={` relative h-48 sm:h-52 lg:h-52 flex items-center justify-center p-4 `}>
              <Image
                src={banner3 || "/placeholder.svg"}
                alt={"image"}
                width={1200}
                height={800}
                className="w-full h-full object-cover rounded-lg overflow-hidden"
              />
            </div>

            {/* Title and Explore Button */}
            <div className="p-4 flex items-center justify-between">
              <h3 className={`text-lg lg:text-xl font-bold text-[#E5005F] `}>LIMITED OFFERS</h3>
              <Link href={`/category?category=limited_offer`} scroll>
                <button className="bg-black text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-gray-800 transition-colors">Explore</button>
              </Link>
            </div>

            {/* Products Grid */}
            <div className="px-4 pb-4">
              <div className="grid grid-cols-2 gap-4">
                {limitedOffersData?.data?.data?.map((product: any) => (
                  <div key={product.id} className="text-center">
                    <Link href={`/product/${product?.ProductSlug}`}>
                      <div className="bg-gray-1 rounded-lg p-4 mb-2 hover:bg-gray-200 transition-colors cursor-pointer">
                        <Image
                          src={"https://selfshop.com.bd/" + product?.ViewProductImage || "/placeholder.svg"}
                          alt={product.name}
                          width={100}
                          height={100}
                          className="w-full h-20 object-contain"
                        />
                      </div>
                      <p className="text-sm text-gray-700 font-medium">
                        {product?.ProductName?.length > 35 ? product?.ProductName?.slice(0, 35) + "..." : product?.ProductName}
                      </p>
                    </Link>
                  </div>
                ))}
              </div>
            </div>
          </div>
          {/* category 4 */}
          <div className="bg-white rounded-lg border border-[#CFEDFF] overflow-hidden">
            {/* Banner Section */}
            <div className={` relative h-48 sm:h-52 lg:h-52 flex items-center justify-center p-4 `}>
              <Image
                src={banner4 || "/placeholder.svg"}
                alt={"image"}
                width={1200}
                height={800}
                className="w-full h-full object-cover rounded-lg overflow-hidden"
              />
            </div>

            {/* Title and Explore Button */}
            <div className="p-4 flex items-center justify-between">
              <h3 className={`text-lg lg:text-xl font-bold text-[#E5005F] `}>NEW ARRIVALS</h3>
              <Link href={`/category?category=new_arrivel`} scroll>
                <button className="bg-black text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-gray-800 transition-colors">Explore</button>
              </Link>
            </div>

            {/* Products Grid */}
            <div className="px-4 pb-4">
              <div className="grid grid-cols-2 gap-4">
                {newArrivalsData?.data?.data?.map((product: any) => (
                  <div key={product.id} className="text-center">
                    <Link href={`/product/${product?.ProductSlug}`}>
                      <div className="bg-gray-1 rounded-lg p-4 mb-2 hover:bg-gray-200 transition-colors cursor-pointer">
                        <Image
                          src={"https://selfshop.com.bd/" + product?.ViewProductImage || "/placeholder.svg"}
                          alt={product.name}
                          width={100}
                          height={100}
                          className="w-full h-20 object-contain"
                        />
                      </div>
                      <p className="text-sm text-gray-700 font-medium">
                        {product?.ProductName?.length > 35 ? product?.ProductName?.slice(0, 35) + "..." : product?.ProductName}
                      </p>
                    </Link>
                  </div>
                ))}
              </div>
            </div>
          </div>
          {/* category 5 */}
          <div className="bg-white rounded-lg border border-[#CFEDFF] overflow-hidden">
            {/* Banner Section */}
            <div className={` relative h-48 sm:h-52 lg:h-52 flex items-center justify-center p-4 `}>
              <Image
                src={banner5 || "/placeholder.svg"}
                alt={"image"}
                width={1200}
                height={800}
                className="w-full h-full object-cover rounded-lg overflow-hidden"
              />
            </div>

            {/* Title and Explore Button */}
            <div className="p-4 flex items-center justify-between">
              <h3 className={`text-lg lg:text-xl font-bold text-[#E5005F] `}>BEST SELLERS</h3>
              <Link href={`/category?category=profitable_product`} scroll>
                <button className="bg-black text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-gray-800 transition-colors">Explore</button>
              </Link>
            </div>

            {/* Products Grid */}
            <div className="px-4 pb-4">
              <div className="grid grid-cols-2 gap-4">
                {profitableProductData?.data?.data?.map((product: any) => (
                  <div key={product.id} className="text-center">
                    <Link href={`/product/${product?.ProductSlug}`}>
                      <div className="bg-gray-1 rounded-lg p-4 mb-2 hover:bg-gray-200 transition-colors cursor-pointer">
                        <Image
                          src={"https://selfshop.com.bd/" + product?.ViewProductImage || "/placeholder.svg"}
                          alt={product.name}
                          width={100}
                          height={100}
                          className="w-full h-20 object-contain"
                        />
                      </div>
                      <p className="text-sm text-gray-700 font-medium">
                        {product?.ProductName?.length > 35 ? product?.ProductName?.slice(0, 35) + "..." : product?.ProductName}
                      </p>
                    </Link>
                  </div>
                ))}
              </div>
            </div>
          </div>
          {/* category 6 */}
          <div className="bg-white rounded-lg border border-[#CFEDFF] overflow-hidden">
            {/* Banner Section */}
            <div className={` relative h-48 sm:h-52 lg:h-52 flex items-center justify-center p-4 `}>
              <Image
                src={banner6 || "/placeholder.svg"}
                alt={"image"}
                width={1200}
                height={800}
                className="w-full h-full object-cover rounded-lg overflow-hidden"
              />
            </div>

            {/* Title and Explore Button */}
            <div className="p-4 flex items-center justify-between">
              <h3 className={`text-lg lg:text-xl font-bold text-[#E5005F] `}>TRENDING NOW</h3>
              <Link href={`/category?category=summer_collection`} scroll>
                <button className="bg-black text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-gray-800 transition-colors">Explore</button>
              </Link>
            </div>

            {/* Products Grid */}
            <div className="px-4 pb-4">
              <div className="grid grid-cols-2 gap-4">
                {summerCollectionData?.data?.data?.map((product: any) => (
                  <div key={product.id} className="text-center">
                    <Link href={`/product/${product?.ProductSlug}`}>
                      <div className="bg-gray-1 rounded-lg p-4 mb-2 hover:bg-gray-200 transition-colors cursor-pointer">
                        <Image
                          src={"https://selfshop.com.bd/" + product?.ViewProductImage || "/placeholder.svg"}
                          alt={product.name}
                          width={100}
                          height={100}
                          className="w-full h-20 object-contain"
                        />
                      </div>
                      <p className="text-sm text-gray-700 font-medium">
                        {product?.ProductName?.length > 35 ? product?.ProductName?.slice(0, 35) + "..." : product?.ProductName}
                      </p>
                    </Link>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
