/* eslint-disable @typescript-eslint/no-explicit-any */
import { useGetAllNewProductsQuery } from "@/redux/features/home/homeApi";
import { useState } from "react";
import ProductSection from "./product-section";

export default function NewProducts() {
  const [objectQuery] = useState([
    { name: "page", value: 1 },
    { name: "limit", value: 8 },
  ]);
  const { data: newArrivalsData } = useGetAllNewProductsQuery({ objectQuery });
  const firstItem = newArrivalsData?.data?.data?.[0];
  const secondItem = newArrivalsData?.data?.data?.[1];
  const featuredProducts: any[] = firstItem ? [firstItem, secondItem] : [];
  const regularProducts: any[] = newArrivalsData?.data?.data?.slice(2) || [];

  return <ProductSection title="NEW PRODUCTS" featuredProducts={featuredProducts} regularProducts={regularProducts} />;
}
