"use client";

import { useCallback, useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import BulkOrderMatrix, { MatrixVariant } from "@/components/vendor/BulkOrderMatrix";
import { getApiBaseUrl } from "@/lib/utils";
import { useAppSelector } from "@/redux/hooks";

type ProductApi = {
  id: number;
  ProductName: string;
  ViewProductImage?: string;
  ProductResellerPrice?: string;
  min_sell_price?: string;
  size?: string;
  varients?: Array<{ id: number; title?: string; price?: string; sizes?: string[]; qty?: number }>;
};

function buildVariantsFromProduct(p: ProductApi): MatrixVariant[] {
  const basePrice = parseFloat(p.ProductResellerPrice || p.min_sell_price || "0");
  const variants: MatrixVariant[] = [];

  if (p.varients && p.varients.length > 0) {
    p.varients.forEach((v, i) => {
      const price = v.price ? parseFloat(String(v.price)) : basePrice;
      const sizes = v.sizes ?? JSON.parse(p.size || "[]");
      if (Array.isArray(sizes) && sizes.length > 0) {
        sizes.forEach((s: string) => {
          variants.push({
            id: `v-${v.id}-${s}`,
            label: `${v.title || `Variant ${i + 1}`} / ${s}`,
            size: s,
            price,
            stock: v.qty,
          });
        });
      } else {
        variants.push({
          id: v.id,
          label: v.title || `Variant ${i + 1}`,
          price,
          stock: v.qty,
        });
      }
    });
  } else {
    const sizes = JSON.parse(p.size || "[]");
    if (sizes.length > 0) {
      sizes.forEach((s: string, i: number) => {
        variants.push({
          id: `size-${i}-${s}`,
          label: s,
          size: s,
          price: basePrice,
        });
      });
    } else {
      variants.push({
        id: "single",
        label: "Default",
        price: basePrice,
      });
    }
  }

  return variants;
}

export default function VendorProductPage() {
  const params = useParams();
  const router = useRouter();
  const token = useAppSelector((s) => s.auth.access_token) ?? (typeof window !== "undefined" ? localStorage.getItem("access_token") : null);
  const productId = params?.productId as string;
  const [product, setProduct] = useState<ProductApi | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!productId) return;
    const base = getApiBaseUrl();
    // Try vendor API first (product + price_tiers); on 401 use public product-details
    fetch(`${base}/vendor/product-details/${productId}`, { cache: "no-store", credentials: "include" })
      .then((r) => {
        if (r.ok) return r.json();
        if (r.status === 401) return fetch(`${base}/product-details/${productId}`).then((res) => res.json());
        throw new Error("Failed to load");
      })
      .then((data) => {
        const p = data?.data?.product_details;
        if (p) setProduct(p);
        else setError("Product not found");
      })
      .catch(() => setError("Failed to load product"))
      .finally(() => setLoading(false));
  }, [productId]);

  const handleConfirm = useCallback(
    async (payload: {
      variantQuantities: Record<string, number>;
      totalQty: number;
      unitPrice: number;
      total: number;
      variantLabels?: Record<string, string>;
    }) => {
      if (!product) return;
      const items = Object.entries(payload.variantQuantities)
        .filter(([, qty]) => qty > 0)
        .map(([id, qty]) => ({
          variant_id: id,
          variant_label: (payload.variantLabels as Record<string, string>)?.[id] ?? id,
          qty,
          unit_price: payload.unitPrice,
          size: id.includes("/") ? id.split(" / ").pop() : undefined,
        }));
      if (items.length === 0) return;
      try {
        const res = await fetch(`${getApiBaseUrl()}/vendor/bulk-add-to-cart`, {
          method: "POST",
          headers: { "Content-Type": "application/json", ...(token ? { Authorization: `Bearer ${token}` } : {}) },
          body: JSON.stringify({ product_id: product.id, items }),
        });
        const data = await res.json();
        if (data?.status) router.push("/vendor");
        else throw new Error(data?.message || "Failed to add to cart");
      } catch (e) {
        console.error(e);
        if (typeof window !== "undefined") alert((e as Error).message || "Failed to add to cart. Please log in.");
      }
    },
    [product, router, token]
  );

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[40vh]">
        <p className="text-gray-500">Loading product…</p>
      </div>
    );
  }
  if (error || !product) {
    return (
      <div className="rounded-xl bg-white p-6 text-center">
        <p className="text-red-600">{error || "Product not found"}</p>
        <button
          type="button"
          onClick={() => router.push("/vendor")}
          className="mt-4 text-[#E5005F] font-medium"
        >
          Back to vendor
        </button>
      </div>
    );
  }

  const variants = buildVariantsFromProduct(product);
  const basePrice = parseFloat(product.ProductResellerPrice || product.min_sell_price || "0");

  return (
    <BulkOrderMatrix
      productName={product.ProductName}
      productImage={product.ViewProductImage}
      basePrice={basePrice}
      variants={variants}
      onConfirm={handleConfirm}
      onClose={() => router.back()}
    />
  );
}
