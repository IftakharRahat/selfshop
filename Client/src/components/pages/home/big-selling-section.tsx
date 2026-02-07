/* eslint-disable @typescript-eslint/no-explicit-any */
" use client";
import { useGetAllBigSellingQuery } from "@/redux/features/home/homeApi";
import { useState } from "react";
import ProductSection from "./product-section";

export default function BigSellingSection() {
  const [objectQuery] = useState([
    { name: "page", value: 1 },
    { name: "limit", value: 8 },
  ]);
  const { data: newArrivalsData } = useGetAllBigSellingQuery({ objectQuery });
  const firstItem = newArrivalsData?.data?.data?.[0];
  const secondItem = newArrivalsData?.data?.data?.[1];
  const featuredProducts: any[] = firstItem ? [firstItem, secondItem] : [];
  const regularProducts: any[] = newArrivalsData?.data?.data?.slice(2) || [];
  
  return <ProductSection title="BIG SELLING" featuredProducts={featuredProducts} regularProducts={regularProducts} />;
}
