/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { ChevronRight, Minus, Plus } from "lucide-react";
import Image from "next/image";
import { useState } from "react";
import type { Swiper as SwiperType } from "swiper";
import { FreeMode, Navigation, Pagination, Thumbs } from "swiper/modules";
import { Swiper, SwiperSlide } from "swiper/react";
import { TbCurrencyTaka } from "react-icons/tb";
// Import Swiper styles
import { useAddToCartMutation } from "@/redux/features/cartApi";
import { handleAsyncWithToast } from "@/utils/handleAsyncWithToast";
import "swiper/css";
import "swiper/css/free-mode";
import "swiper/css/navigation";
import "swiper/css/pagination";
import "swiper/css/thumbs";
import { z } from "zod";
import { useAppSelector } from "@/redux/hooks";
import Swal from "sweetalert2";
import OrderNowModal from "./OrderNowModal";
import { MdOutlineFileDownload } from "react-icons/md";
import { cn } from "@/lib/utils";

type ColorOption = {
  id: string | number;
  name?: string;
  color: string;
};

export default function ProductDetailPage({ product }: any) {
  const [orderOpen, setOrderOpen] = useState(false);
  const token = useAppSelector((state) => state.auth.access_token);

  const [addToCart, { isLoading }] = useAddToCartMutation();

  // ---- Transform Backend Data ----
  const images = [product.ViewProductImage, ...JSON.parse(product.PostImage || "[]").map((img: string) => `${img}`)];

  const productData = {
    name: product.ProductName,
    category: `Category #${product.category_id}`,
    quantity: product.qty,
    sku: product.ProductSku,
    minimumPrice: parseFloat(product.min_sell_price),
    currentPrice: parseFloat(product.ProductResellerPrice),
    sizes: JSON.parse(product.size || "[]"),
    description: product.ProductDetails,
    images: {
      main: images,
      thumbnails: images,
    },
    varients: product.varients || [],
  };
  const sellingPriceSchema = z
    .number({ required_error: "Selling price is required" })
    .min(productData.minimumPrice, `Price must be at least ${productData.minimumPrice} taka.`);

  // ---- UI States ----
  const [selectedSize, setSelectedSize] = useState(productData.sizes[0] || "");
  const [selectedVarient, setSelectedVarient] = useState<any>(product?.varients?.[0] ?? null);
  const [quantity, setQuantity] = useState(1);
  const [sellingPrice, setSellingPrice] = useState("");
  const [priceError, setPriceError] = useState<string | null>(null);

  const [thumbsSwiper, setThumbsSwiper] = useState<SwiperType | null>(null);

  const handleQuantityChange = (type: "increase" | "decrease") => {
    if (type === "increase") {
      setQuantity((prev) => prev + 1);
    } else if (type === "decrease" && quantity > 1) {
      setQuantity((prev) => prev - 1);
    }
  };

  const validateSellingPrice = () => {
    try {
      const parsedPrice = sellingPriceSchema.parse(Number(sellingPrice));
      setPriceError(null);
      return parsedPrice;
    } catch (err: any) {
      setPriceError(err.errors?.[0]?.message || "Invalid price");
      return null;
    }
  };

  const handleAddToCart = async () => {
    if (!token) {
      Swal.fire({
        icon: "error",
        title: "Unauthorized",
        text: "Please log in to add items to your cart.",
      });
      return;
    }
    const validPrice = validateSellingPrice();
    if (!validPrice) return;

    const formData = new FormData();
    formData.append("product_id", product.id);
    formData.append("price", validPrice.toString());
    formData.append("qty", quantity.toString());
    formData.append("size", selectedSize);

    console.log("Added to cart:", {
      // product: productData.name,
      product_id: product.id,
      price: validPrice,
      qty: quantity,
      size: selectedSize,
    });

    await handleAsyncWithToast(async () => {
      return addToCart(formData);
    });
  };

  const handleBuyNow = async () => {
    if (!token) {
      Swal.fire({
        icon: "error",
        title: "Unauthorized",
        text: "Please log in to add items to your cart.",
      });
      return;
    }
    const validPrice = validateSellingPrice();
    if (!validPrice) return;

    const formData = new FormData();
    formData.append("product_id", product.id);
    formData.append("price", validPrice.toString());
    formData.append("qty", quantity.toString());
    formData.append("size", selectedSize);

    const result = await handleAsyncWithToast(async () => {
      return addToCart(formData);
    }, false);

    if (result.data?.status) {
      window.location.href = "/order-confirmation";
    }
  };
  console.log(selectedVarient);
  const handleOrderNow = async () => {
    if (!token) {
      Swal.fire({
        icon: "error",
        title: "Unauthorized",
        text: "Please log in to add items to your cart.",
      });
      return;
    }
    setOrderOpen(true);
  };

  const derivedColors: ColorOption[] = [
    { id: 1, name: "Red", color: "red" },
    { id: 2, name: "Blue", color: "blue" },
    { id: 3, name: "Green", color: "green" },
    { id: 4, name: "Black", color: "black" },
  ];

  const [selectedColor, setSelectedColor] = useState<ColorOption>(derivedColors[0]);

  const handleDownloadImage = (imgPath: string) => {
    const imageUrl = `https://api-v1.selfshop.com.bd/${imgPath}`;
    const link = document.createElement("a");
    link.href = imageUrl;
    // Optional: extract filename from URL
    link.download = imgPath.split("/").pop() || "product-image.jpg";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  return (
    <div className="min-h-screen bg-white">
      <div className="container mx-auto px-4 sm:px-6 lg:px-8 py-6">
        {/* Breadcrumb */}
        <nav className="flex items-center space-x-2 text-sm mb-8">
          <span className="text-gray-600">Home</span>
          <ChevronRight className="w-4 h-4 text-gray-400" />
          <span className="text-pink-600 font-medium">product details</span>
        </nav>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 border-b border-gray-200 pb-8 lg:pb-12">
          {/* Product Images */}
          <div className="space-y-4">
            {/* Main Image Swiper */}
            <div className="aspect-[4/4] bg-gradient-to-br from-purple-300 to-purple-400 rounded-lg overflow-hidden">
              <Swiper
                modules={[Navigation, Pagination, Thumbs]}
                thumbs={{ swiper: thumbsSwiper && !thumbsSwiper.destroyed ? thumbsSwiper : null }}
                navigation={{
                  nextEl: ".swiper-button-next-custom",
                  prevEl: ".swiper-button-prev-custom",
                }}
                // pagination={{
                //   clickable: false,
                //   bulletClass: "swiper-pagination-bullet-custom",
                //   bulletActiveClass: "swiper-pagination-bullet-active-custom",
                // }}
                className="w-full h-full"
                spaceBetween={10}
                slidesPerView={1}
              >
                {productData.images.main.map((image, index) => (
                  <SwiperSlide key={index} className="relative">
                    <div className="w-full h-full flex items-center justify-center relative">
                      <Image
                        src={`https://api-v1.selfshop.com.bd/${image || "/placeholder.svg"}`}
                        alt={`${productData.name} - View ${index + 1}`}
                        width={500}
                        height={600}
                        className="w-full h-full object-cover"
                        priority={index === 0}
                      />
                        </div>
                      <div
                        className="absolute bottom-0 right-0 z-50 bg-[#CCFF8D] p-1 text-xs rounded-tl py-1 px-5 flex items-center gap-1 cursor-pointer hover:opacity-90 transition-opacity"
                        onClick={() => handleDownloadImage(image)}
                      >
                        <MdOutlineFileDownload size={20} />
                        Download image
                      </div>
                  </SwiperSlide>
                ))}

                {/* Custom Navigation Buttons */}
                <div className="swiper-button-prev-custom absolute left-2 top-1/2 -translate-y-1/2 z-10 w-8 h-8 bg-white/80 rounded-full flex items-center justify-center cursor-pointer hover:bg-white transition-colors">
                  <ChevronRight className="w-4 h-4 rotate-180" />
                </div>
                <div className="swiper-button-next-custom absolute right-2 top-1/2 -translate-y-1/2 z-10 w-8 h-8 bg-white/80 rounded-full flex items-center justify-center cursor-pointer hover:bg-white transition-colors">
                  <ChevronRight className="w-4 h-4" />
                </div>
              </Swiper>
            </div>

            {/* Thumbnail Swiper */}
            <div className="w-full">
              <Swiper
                modules={[FreeMode, Thumbs]}
                onSwiper={setThumbsSwiper}
                spaceBetween={15}
                slidesPerView={5}
                freeMode={true}
                watchSlidesProgress={true}
                loop={true}
                className="thumbnail-swiper"
              >
                {productData.images.thumbnails.map((thumbnail, index) => (
                  <SwiperSlide key={index}>
                    <div className=" rounded-lg overflow-hidden border-2 border-gray-200 cursor-pointer hover:border-pink-500 transition-colors">
                      <Image
                        src={`https://api-v1.selfshop.com.bd/${thumbnail || "/placeholder.svg"}`}
                        alt={`Product thumbnail ${index + 1}`}
                        width={80}
                        height={80}
                        className="w-full h-full object-cover"
                      />
                    </div>
                  </SwiperSlide>
                ))}
              </Swiper>
            </div>
          </div>

          {/* Product Details */}
          <div className="space-y-6">
            <h1 className="text-2xl sm:text-3xl font-bold text-gray-900">{productData.name}</h1>

            <div className="space-y-3 text-sm">
              <div className="flex items-center">
                <span className="font-medium text-gray-900">Category :</span>
                <span className="ml-2 text-gray-600">{productData.category}</span>
              </div>
              <div className="flex items-center">
                <span className="font-medium text-gray-900">Quantity :</span>
                <span className="ml-2 text-gray-600">{productData.quantity}</span>
              </div>
              <div className="flex items-center">
                <span className="font-medium text-gray-900">SKU :</span>
                <span className="ml-2 text-gray-600">{productData.sku}</span>
              </div>
              <div className="flex items-center">
                <span className="font-medium text-gray-900">Minimum Sell Price :</span>
                <span className="ml-2 text-gray-600 flex items-center">
                  <TbCurrencyTaka size={20} />
                  {productData.minimumPrice.toFixed(2)}
                </span>
              </div>
            </div>

            {/* Bulk */}
            {/* Bulk (Variants) */}
            <OrderNowModal open={orderOpen} onClose={() => setOrderOpen(false)} variant={selectedVarient} />

            {productData.varients && productData.varients.length > 0 && (
              <div className="bg-[#E5005F0F] p-4 space-y-4">
                <div className="space-y-3">
                  <h3 className="font-medium text-gray-900">Bulk Options</h3>
                  <div className="flex flex-wrap gap-3">
                    {productData.varients.map((variant: any, i: number) => (
                      <button
                        key={i}
                        onClick={() => setSelectedVarient(variant)}
                        className={`px-4 py-2 border rounded-lg text-sm font-medium transition-colors bg-white ${
                          selectedVarient?.id === variant.id
                            ? "border-pink-500 text-pink-600 bg-pink-50"
                            : "border-gray-300 text-gray-700 hover:border-gray-400"
                        }`}
                      >
                        {variant.title || `${variant.qty} pcs`}
                        <p className="flex items-center gap-1">
                          <TbCurrencyTaka size={15} />
                          {parseFloat(variant.price).toFixed(2)}
                        </p>
                      </button>
                    ))}
                  </div>

                  <button onClick={handleOrderNow} className="bg-black rounded text-white py-2 px-3 cursor-pointer">
                    Order Now
                  </button>
                </div>
              </div>
            )}

            {/* Size Selection */}
            <div className="bg-[#F4F4F4] p-4 space-y-4">
              {/* Color Select */}
              <div className="mb-3">
                <h3 className="font-medium mb-2 text-sm">Color</h3>
                <div className="flex gap-3 overflow-x-auto p-1">
                  {derivedColors.map((c) => (
                    <button
                      key={c.id}
                      onClick={() => setSelectedColor(c)}
                      className={`flex flex-col items-center p-1 rounded-md transition min-w-12 ${
                        selectedColor.id === c.id ? "ring-2 ring-pink-500" : "hover:ring-1 hover:ring-gray-200"
                      }`}
                    >
                      <div className="w-5 h-5 rounded-full border" style={{ backgroundColor: c.color }}></div>
                      <span className="text-[10px] mt-1">{c.name}</span>
                    </button>
                  ))}
                </div>
              </div>
              <div className="space-y-3">
                <h3 className="font-medium text-gray-900">Size</h3>
                <div className="flex space-x-3 flex-wrap gap-2">
                  {productData.sizes.map((size: any) => (
                    <button
                      key={size}
                      onClick={() => setSelectedSize(size)}
                      className={`px-4 py-2 border rounded-lg text-sm font-medium transition-colors bg-white ${
                        selectedSize === size ? "border-pink-500 text-pink-600 bg-pink-50" : "border-gray-300 text-gray-700 hover:border-gray-400"
                      }`}
                    >
                      {size}
                    </button>
                  ))}
                </div>
              </div>

              {/* Quantity Selection */}
              <div className="space-y-3">
                <h3 className="font-medium text-gray-900">Quantity</h3>
                <div className="flex items-center space-x-3">
                  <button
                    onClick={() => handleQuantityChange("decrease")}
                    className="w-10 h-10 border border-gray-300 rounded-lg flex items-center justify-center hover:bg-gray-50 transition-colors bg-white"
                    disabled={quantity <= 1}
                  >
                    <Minus className="w-4 h-4" />
                  </button>
                  <span className="w-10 h-10 rounded-lg flex items-center justify-center bg-white">{quantity}</span>
                  <button
                    onClick={() => handleQuantityChange("increase")}
                    className="w-10 h-10 border border-gray-300 rounded-lg flex items-center justify-center hover:bg-pink-50 transition-colors bg-white"
                  >
                    <Plus className="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>

            <div className="text-3xl font-bold text-gray-900 flex items-center">
              <TbCurrencyTaka size={35} />
              {productData.currentPrice.toFixed(2)}
            </div>

            <div className="space-y-3">
              <h3 className="font-medium text-gray-900">Your selling price</h3>
              <input
                type="number"
                placeholder="Enter your selling price"
                value={sellingPrice}
                onChange={(e) => setSellingPrice(e.target.value)}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
              />
              {priceError && <p className="text-red-500 text-sm mt-1">{priceError}</p>}{" "}
              {priceError == null && (
                <p
                  className={cn(
                    "text-green-600 text-sm mt-1",
                    Number(((Number(sellingPrice) - productData.currentPrice) * quantity).toFixed(2)) > 0 ? "" : "hidden"
                  )}
                >
                  Your total earn {((Number(sellingPrice) - productData.currentPrice) * quantity).toFixed(2)} TK
                </p>
              )}
            </div>

            <div className="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-4 ">
              <button
                onClick={handleAddToCart}
                className="flex-1 px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium cursor-pointer"
              >
                Add to cart
              </button>
              <button
                onClick={handleBuyNow}
                className="flex-1 px-6 py-3 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition-colors font-medium cursor-pointer"
              >
                {isLoading ? "Processing..." : "Buy Now"}
              </button>
            </div>
          </div>
        </div>

        {/* Description */}
        <div className="mt-12">
          <h2 className="text-xl font-semibold text-pink-600 border-b w-fit">Description</h2>
          {/* <div className=" h-0.5 bg-pink-600 mb-4"></div> */}
          <div dangerouslySetInnerHTML={{ __html: productData.description }} className="text-gray-700 leading-relaxed w-full overflow-hidden" />
        </div>
      </div>

      {/* Swiper Styles */}
      <style jsx global>{`
        .swiper-pagination-bullet-custom {
          width: 8px;
          height: 8px;
          background: rgba(255, 255, 255, 0.5);
          opacity: 1;
          margin: 0 4px;
        }
        .swiper-pagination-bullet-active-custom {
          background: white;
        }
        .thumbnail-swiper .swiper-slide-thumb-active div {
          border-color: #ec4899;
        }
        .swiper-pagination {
          bottom: 16px;
        }
      `}</style>
    </div>
  );
}
