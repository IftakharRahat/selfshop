/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import Image from "next/image";
import Link from "next/link";
import { Heart, ShoppingCart } from "lucide-react";
import { handleAsyncWithToast } from "@/utils/handleAsyncWithToast"; // adjust if needed
import React from "react";
import { useAddToCartMutation } from "@/redux/features/cartApi";

interface ProductCardProps {
  product: any;
}

const ProductCard: React.FC<ProductCardProps> = ({ product }) => {
  const [addToCart] = useAddToCartMutation();

  const handleAddToCart = async () => {
    const formData = new FormData();
    formData.append("product_id", product.id);
    formData.append("price", product.ProductRegularPrice.toString());
    formData.append("qty", "1");
    formData.append("size", product.sizes?.[0] || "");

    console.log("Added to cart:", {
      product_id: product.id,
      price: product.ProductRegularPrice,
      qty: 1,
      size: product.sizes?.[0] || "",
    });

    await handleAsyncWithToast(async () => {
      return addToCart(formData);
    });
  };

  // Optional simple star renderer
  const renderStars = (rating = 4) => {
    return Array(5)
      .fill(0)
      .map((_, i) => (
        <span key={i} className={i < rating ? "text-yellow-400" : "text-gray-300"}>
          ★
        </span>
      ));
  };

  return (
    <div className="group cursor-pointer shadow-lg p-3 rounded-lg hover:shadow-xl transition">
      {/* Image */}
      <div className="relative rounded-md overflow-hidden mb-4">
        <Link href={`/product/${product?.ProductSlug}`}>
          <Image
            src={product.ViewProductImage ? `https://api-v1.selfshop.com.bd/${product.ViewProductImage}` : "/placeholder.svg"}
            alt={product.ProductName}
            width={600}
            height={400}
            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
        </Link>
        <button className="absolute top-3 right-3 w-8 h-8 bg-white rounded-full flex items-center justify-center shadow-md hover:bg-gray-50">
          <Heart className="w-4 h-4 text-gray-600 hover:text-pink-600" />
        </button>
      </div>

      {/* Info */}
      <div className="space-y-1">
        <div className="flex items-center justify-between">
          <Link href={`/product/${product?.ProductSlug}`}>
            <h3 className="text-gray-900 font-medium text-[10px] xs:text-sm lg:text-base">
              {product.ProductName.length > 35 ? product.ProductName.slice(0, 35) + "..." : product.ProductName}
            </h3>
          </Link>
          <div className="hidden xs:flex items-center text-xl">{renderStars(product.rating)}</div>
        </div>

        <div className="flex items-center justify-between">
          <span className="text-[12px] xs:text-base md:text-lg font-bold text-gray-900">৳{product.ProductRegularPrice}</span>
          <button
            className="cursor-pointer p-2 bg-pink-600 hover:bg-pink-700 text-white rounded-full flex items-center justify-center"
            onClick={handleAddToCart}
          >
            <ShoppingCart className="w-3 h-3 xs:w-4 xs:h-4" />
          </button>
        </div>
      </div>
    </div>
  );
};

export default ProductCard;
