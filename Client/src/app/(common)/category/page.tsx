/* eslint-disable @typescript-eslint/no-unused-vars */
"use client";

import ProductCard from "@/components/shared/ProductCard/ProductCard";
import { useEffect, useState, useRef, useCallback } from "react";

interface Product {
  id: number;
  ProductSlug: string;
  ProductName: string;
  ViewProductImage: string;
  ProductRegularPrice: string;
  ProductSalePrice: string;
  ProductResellerPrice: string;
  Discount: string;
}

// Get category from URL query params
const getCategoryFromURL = () => {
  if (typeof window === "undefined") return "";
  const params = new URLSearchParams(window.location.search);
  return params.get("category") || "hot_selling";
};

const CategoryPage = () => {
  const [category, setCategory] = useState(getCategoryFromURL());

  const [page, setPage] = useState(1);
  const [products, setProducts] = useState<Product[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [hasMore, setHasMore] = useState(true);
  const [error, setError] = useState(false);

  const observerRef = useRef<IntersectionObserver | null>(null);
  const loadMoreRef = useRef<HTMLDivElement | null>(null);

  const limit = 15; // Reasonable limit per page

  // Fetch products function
  const fetchProducts = useCallback(
    async (pageNum: number) => {
      if (isLoading || !hasMore) return;

      setIsLoading(true);
      setError(false);

      try {
        const params = new URLSearchParams({
          page: pageNum.toString(),
          limit: limit.toString(),
        });

        const response = await fetch(`https://api-v1.selfshop.com.bd/api/collection/${category}?${params}`);

        const result = await response.json();

        if (result.status && result.data?.data) {
          const newProducts = result.data.data;

          // Append new products
          setProducts((prev) => (pageNum === 1 ? newProducts : [...prev, ...newProducts]));

          // Check if there are more pages
          const currentPage = result.data.current_page;
          const totalPages = result.data.last_page;
          setHasMore(currentPage < totalPages);
        } else {
          setError(true);
        }
      } catch (err) {
        setError(true);
        console.error("Error fetching products:", err);
      } finally {
        setIsLoading(false);
      }
    },
    [category, hasMore, isLoading, limit]
  );

  // Reset when category changes
  useEffect(() => {
    setProducts([]);
    setPage(1);
    setHasMore(true);
    setError(false);
    fetchProducts(1);
  }, [category]);

  // Intersection Observer for infinite scroll
  useEffect(() => {
    if (isLoading || !hasMore) return;

    const options = {
      root: null,
      rootMargin: "200px",
      threshold: 0.1,
    };

    observerRef.current = new IntersectionObserver((entries) => {
      if (entries[0].isIntersecting && !isLoading && hasMore) {
        setPage((prev) => {
          const nextPage = prev + 1;
          fetchProducts(nextPage);
          return nextPage;
        });
      }
    }, options);

    if (loadMoreRef.current) {
      observerRef.current.observe(loadMoreRef.current);
    }

    return () => {
      if (observerRef.current) {
        observerRef.current.disconnect();
      }
    };
  }, [isLoading, hasMore, fetchProducts]);

  return (
    <section className="container mx-auto px-4 md:px-8 lg:px-16 py-10">
      <h1 className="text-2xl md:text-3xl font-bold text-gray-900 mb-6 capitalize">{category?.replace(/_/g, " ") || "All Products"}</h1>

      {/* Error State */}
      {error && products.length === 0 ? (
        <div className="flex flex-col items-center justify-center py-10 text-center">
          <p className="text-red-500 text-lg font-medium mb-2">Failed to fetch products</p>
          <p className="text-gray-500">Please try again later.</p>
        </div>
      ) : (
        <>
          {/* Product Grid */}
          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-5">
            {products.map((product) => (
              <ProductCard key={product.id} product={product} />
            ))}
          </div>

          {/* Loading Indicator */}
          {isLoading && (
            <div className="flex justify-center py-8">
              <div className="flex items-center gap-2 text-gray-600">
                <div className="w-6 h-6 border-3 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                <span>Loading more products...</span>
              </div>
            </div>
          )}

          {/* Load More Trigger */}
          {hasMore && !isLoading && (
            <div ref={loadMoreRef} className="h-20 flex items-center justify-center">
              <p className="text-gray-400 text-sm">Scroll for more</p>
            </div>
          )}

          {/* No More Products */}
          {!hasMore && products.length > 0 && (
            <div className="text-center py-8">
              <p className="text-gray-500">No more products to load</p>
            </div>
          )}

          {/* Empty State */}
          {!isLoading && products.length === 0 && !error && <p className="text-gray-500 text-center py-10">No products found.</p>}
        </>
      )}
    </section>
  );
};

export default CategoryPage;
