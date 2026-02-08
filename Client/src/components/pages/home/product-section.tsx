/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";
import StarRating from "@/components/ui-library/star-rating";
import { cn, getImageUrl } from "@/lib/utils";
import { useAddToCartMutation } from "@/redux/features/cartApi";
import { useAppSelector } from "@/redux/hooks";
import { TProductSectionProps } from "@/types/product";
import { handleAsyncWithToast } from "@/utils/handleAsyncWithToast";
import { Heart, ShoppingCart } from "lucide-react";
import Image from "next/image";
import Link from "next/link";
import { toast } from "sonner";

export default function ProductSection({ title, featuredProducts = [], regularProducts = [], className = "" }: TProductSectionProps) {
  const token = useAppSelector((state) => state.auth.access_token);
  const [addToCart] = useAddToCartMutation();
  const handleAddToCart = async (product: any) => {
    if (!token) {
      toast.info("Please log in to add to cart");
      return;
    }
    const formData = new FormData();
    formData.append("product_id", product.id);
    formData.append("price", product.ProductRegularPrice.toString());
    formData.append("qty", "1");
    formData.append("size", product.sizes?.[0] || ""); // fallback to empty if no size

    await handleAsyncWithToast(async () => {
      return addToCart(formData);
    });
  };
  
  return (
    <div className={`w-full bg-white py-6 sm:py-8 lg:py-12 ${className}`}>
      <div className="container mx-auto px-4 sm:px-6 lg:px-8">
        {/* Section Title */}
        <h2 className="text-xl sm:text-2xl lg:text-3xl font-semibold text-[#322F35] mb-6 sm:mb-8">{title}</h2>

        {/* Featured Products */}
        {featuredProducts.length > 0 && (
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-6 sm:mb-8">
            {featuredProducts.map((product: any, i: number) => (
              <div key={i} className="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div className="flex flex-col sm:flex-row">
                  {/* Product Image */}
                  <div className={` flex-shrink-0 w-full sm:w-64 h-48 sm:h-auto flex items-center justify-center p-6 `}>
                    <Link href={`/product/${product?.ProductSlug}`}>
                    <Image
                      src={getImageUrl(product?.ViewProductImage)}
                      alt="image"
                      width={200}
                      height={200}
                      className={cn("w-full h-full object-fill rounded-md overflow-hidden")}
                      />
                      </Link>
                  </div>

                  {/* Product Info */}
                  <div className="flex-1 p-4 sm:pl-0 sm:p-6 flex flex-col justify-center">
                    <Link href={`/product/${product?.ProductSlug}`}>
                    <h3 className="text-base sm:text-lg font-medium text-gray-900 mb-3 line-clamp-2">{product?.ProductName}</h3>
                    </Link>

                    {/* Rating */}
                    <div className="mb-4">
                      <StarRating rating={product?.rating} />
                    </div>

                    {/* Price */}
                    <div className="flex items-center space-x-3 mb-4">
                      <span className="text-xl sm:text-2xl font-bold text-gray-900">৳ {product?.ProductSalePrice}</span>
                      <span className="text-base sm:text-lg text-gray-500 line-through">৳ {product?.ProductRegularPrice}</span>
                    </div>

                    {/* Add to Cart Button */}
                    <button
                      onClick={() => handleAddToCart(product)}
                      className="cursor-pointer bg-[#E5005F] hover:bg-pink-700 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg flex items-center justify-center space-x-2 transition-colors font-medium text-sm sm:text-base"
                    >
                      <span>+</span>
                      <span>Add to cart</span>
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}

        {/* Regular Products Grid */}
        {regularProducts.length > 0 && (
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3  gap-4 sm:gap-6">
            {regularProducts.map((product: any, i: number) => (
              <div key={i} className="bg-white rounded-lg border border-gray-200 overflow-hidden group cursor-pointer">
                <div className="flex items-center">
                  {/* Product Image Container */}
                  <div className="relative  w-20 h-20 sm:w-24 sm:h-24 flex-shrink-0 flex items-center justify-center p-2">
                    <Link href={`/product/${product?.ProductSlug}`}>
                    <Image
                      src={getImageUrl(product?.ViewProductImage)}
                      alt={product?.ProductName || product?.alt || "Product"}
                      width={80}
                      height={80}
                      className="w-full h-full object-fill group-hover:scale-105 transition-transform duration-300"
                      />
                      </Link>
                    {/* Wishlist Heart Icon */}
                    <button
                      // onClick={handleToggleWishlist}
                      className="absolute top-1 right-1 w-6 h-6 bg-white rounded-full flex items-center justify-center shadow-md hover:bg-gray-50 transition-colors"
                    >
                      <Heart className="w-3 h-3 text-gray-600 hover:text-pink-600" />
                    </button>
                  </div>

                  {/* Product Info */}
                  <div className="flex-1 p-3 sm:p-4">
                    <div className="flex items-center justify-between h-full">
                      <Link href={`/product/${product?.ProductSlug}`}>
                      <h3 className="text-gray-900 font-medium text-sm mb-2 line-clamp-1">{product?.ProductName}</h3>
                      </Link>

                      {/* Rating */}
                      <div className="mb-2">
                        <StarRating rating={product?.rating} />
                      </div>
                    </div>

                    <div className="flex items-center justify-between">
                      {/* Price */}
                      <div className="flex items-center space-x-2">
                        <span className="text-base font-bold text-gray-900">৳ {product?.ProductSalePrice}</span>
                        <span className="text-xs text-gray-500 line-through">৳ {product?.ProductRegularPrice}</span>
                      </div>
                      {/* Add to Cart Button */}
                      <div className="">
                        <button
                          onClick={() => handleAddToCart(product)}
                          className="cursor-pointer p-2 bg-pink-600 hover:bg-pink-700 text-white rounded-full flex items-center justify-center transition-colors"
                        >
                          <ShoppingCart className="w-3 h-3 sm:w-4 sm:h-4" />
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}

        {/* Empty State */}
        {featuredProducts.length === 0 && regularProducts.length === 0 && (
          <div className="text-center py-12">
            <p className="text-gray-500 text-lg">No products available</p>
          </div>
        )}
      </div>
    </div>
  );
}
