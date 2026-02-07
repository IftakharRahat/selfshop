// "use client";

// import Image from "next/image";
// import { Heart, ShoppingCart } from "lucide-react";
// import { TProduct } from "@/types/product";
// import StarRating from "../ui-library/star-rating";
// import { cn } from "@/lib/utils";

// interface ProductCardProps {
//   product: TProduct;
//   variant?: "featured" | "regular";
//   onAddToCart?: (productId: number) => void;
//   onToggleWishlist?: (productId: number) => void;
// }

// export default function ProductCard({ product, variant = "regular", onAddToCart, onToggleWishlist }: ProductCardProps) {
//   const handleAddToCart = () => {
//     onAddToCart?.(product.id);
//   };

//   const handleToggleWishlist = () => {
//     onToggleWishlist?.(product.id);
//   };

//   if (variant === "featured") {
//     return (
//       <div className="bg-white rounded-lg border border-gray-200 overflow-hidden">
//         <div className="flex flex-col sm:flex-row">
//           {/* Product Image */}
//           <div className={` flex-shrink-0 w-full sm:w-64 h-48 sm:h-auto flex items-center justify-center p-6 `}>
//             <Image
//               src={product.image || "/placeholder.svg"}
//               alt={product.alt}
//               width={200}
//               height={200}
//               className={cn(
//                 "w-full h-full object-fill rounded-md overflow-hidden",

               
//               )}
//             />
//           </div>

//           {/* Product Info */}
//           <div className="flex-1 p-4 sm:pl-0 sm:p-6 flex flex-col justify-center">
//             <h3 className="text-base sm:text-lg font-medium text-gray-900 mb-3 line-clamp-2">{product.name}</h3>

//             {/* Rating */}
//             <div className="mb-4">
//               <StarRating rating={product.rating} />
//             </div>

//             {/* Price */}
//             <div className="flex items-center space-x-3 mb-4">
//               <span className="text-xl sm:text-2xl font-bold text-gray-900">${product.currentPrice?.toFixed(2)}</span>
//               <span className="text-base sm:text-lg text-gray-500 line-through">${product.originalPrice?.toFixed(2)}</span>
//             </div>

//             {/* Add to Cart Button */}
//             <button
//               onClick={handleAddToCart}
//               className="cursor-pointer bg-[#E5005F] hover:bg-pink-700 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg flex items-center justify-center space-x-2 transition-colors font-medium text-sm sm:text-base"
//             >
//               <span>+</span>
//               <span>Add to cart</span>
//             </button>
//           </div>
//         </div>
//       </div>
//     );
//   }

//   return (
//     <div className="bg-white rounded-lg border border-gray-200 overflow-hidden group cursor-pointer">
//       <div className="flex items-center">
//         {/* Product Image Container */}
//         <div className="relative  w-20 h-20 sm:w-24 sm:h-24 flex-shrink-0 flex items-center justify-center p-2">
//           <Image
//             src={product.image || "/placeholder.svg"}
//             alt={product.alt}
//             width={80}
//             height={80}
//             className="w-full h-full object-fill group-hover:scale-105 transition-transform duration-300"
//           />
//           {/* Wishlist Heart Icon */}
//           <button
//             onClick={handleToggleWishlist}
//             className="absolute top-1 right-1 w-6 h-6 bg-white rounded-full flex items-center justify-center shadow-md hover:bg-gray-50 transition-colors"
//           >
//             <Heart className="w-3 h-3 text-gray-600 hover:text-pink-600" />
//           </button>
//         </div>

//         {/* Product Info */}
//         <div className="flex-1 p-3 sm:p-4">
//           <div className="flex items-center justify-between h-full">
//             <h3 className="text-gray-900 font-medium text-sm mb-2 line-clamp-1">{product.name}</h3>

//             {/* Rating */}
//             <div className="mb-2">
//               <StarRating rating={product.rating} />
//             </div>
//           </div>

//           <div className="flex items-center justify-between">
//             {/* Price */}
//             <div className="flex items-center space-x-2">
//               <span className="text-base font-bold text-gray-900">${product.currentPrice?.toFixed(2)}</span>
//               <span className="text-xs text-gray-500 line-through">${product.originalPrice?.toFixed(2)}</span>
//             </div>
//             {/* Add to Cart Button */}
//             <div className="">
//               <button
//                 onClick={handleAddToCart}
//                 className="p-2 bg-pink-600 hover:bg-pink-700 text-white rounded-full flex items-center justify-center transition-colors"
//               >
//                 <ShoppingCart className="w-3 h-3 sm:w-4 sm:h-4" />
//               </button>
//             </div>
//           </div>
//         </div>
//       </div>
//     </div>
//   );
// }
