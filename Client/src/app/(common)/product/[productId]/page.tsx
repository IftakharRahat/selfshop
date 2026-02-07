import ProductDetailsComponent from "@/components/pages/productDetails/ProductDetailsComponent";
import React from "react";

interface PageProps {
  params: Promise<{
    productId: string;
  }>;
}

const page = async ({ params }: PageProps) => {
  const resolvedParams = await params;
  const { productId } = resolvedParams;
  return (
    <>
      <ProductDetailsComponent productId={productId} />
    </>
  );
};

export default page;
