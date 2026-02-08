import Image from "next/image";
import { Heart, ShoppingCart } from "lucide-react";

export default function NewArrivalSection() {
  const products = [
    {
      id: 1,
      name: "Product Name Here",
      image: "/placeholder.svg?height=300&width=300&text=👞",
      currentPrice: 196.34,
      originalPrice: 330.69,
      rating: 5,
      alt: "Black dress shoes",
    },
    {
      id: 2,
      name: "Product Name Here",
      image: "/placeholder.svg?height=300&width=300&text=📱💻",
      currentPrice: 196.34,
      originalPrice: 330.69,
      rating: 5,
      alt: "Smartphones and tablets",
    },
    {
      id: 3,
      name: "Product Name Here",
      image: "/placeholder.svg?height=300&width=300&text=🛒🍎🥕",
      currentPrice: 196.34,
      originalPrice: 330.69,
      rating: 5,
      alt: "Fresh fruits and vegetables in basket",
    },
    {
      id: 4,
      name: "Product Name Here",
      image: "/placeholder.svg?height=300&width=300&text=📷🎥",
      currentPrice: 196.34,
      originalPrice: 330.69,
      rating: 5,
      alt: "Camera equipment and electronics",
    },
  ];

  const renderStars = (rating: number) => {
    return Array.from({ length: 5 }, (_, index) => (
      <span key={index} className={`${index < rating ? "text-yellow-400" : "text-gray-300"}`}>
        ★
      </span>
    ));
  };

  return (
    <div className="w-full bg-white py-8 lg:py-12">
      <div className="container mx-auto px-4 sm:px-6 lg:px-8">
        {/* Section Title */}
        <h2 className="text-2xl lg:text-3xl font-bold text-gray-900 mb-8">NEW ARRIVAL</h2>

        {/* Products Grid */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          {products.map((product) => (
            <div key={product.id} className="group cursor-pointer">
              {/* Product Image Container */}
              <div className="relative bg-gray-100 rounded-lg overflow-hidden mb-4 aspect-square">
                <Image
                  src={product.image || "/placeholder.svg"}
                  alt={product?.ProductName || product?.alt || "Product"}
                  width={300}
                  height={300}
                  className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                />
                {/* Wishlist Heart Icon */}
                <button className="absolute top-3 right-3 w-8 h-8 bg-white rounded-full flex items-center justify-center shadow-md hover:bg-gray-50 transition-colors">
                  <Heart className="w-4 h-4 text-gray-600 hover:text-pink-600" />
                </button>
              </div>

              {/* Product Info */}
              <div className="space-y-1">
                {/* Product Name */}
                <div className="flex items-center justify-between">
                  <h3 className="text-gray-900 font-medium text-sm lg:text-base">{product.name}</h3>

                  {/* Rating */}
                  <div className="flex items-center text-xl">{renderStars(product.rating)}</div>
                </div>

                {/* Price and Cart */}
                <div className="flex items-center justify-between">
                  <div className="flex items-center space-x-2">
                    <span className="text-lg font-bold text-gray-900">৳ {product.currentPrice.toFixed(2)}</span>
                    <span className="text-sm text-gray-500 line-through">৳ {product.originalPrice.toFixed(2)}</span>
                  </div>
                  {/* Add to Cart Button */}
                  <button className="p-2 bg-pink-600 hover:bg-pink-700 text-white rounded-full cursor-pointer flex items-center justify-center transition-colors">
                    <ShoppingCart className="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
