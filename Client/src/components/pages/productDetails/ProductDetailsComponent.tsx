// app/products/[productId]/ProductDetailsComponent.tsx
"use client";

import { Loader2 } from "lucide-react";
import MostPopularBrands from "../home/most-popular-brands";
import ProductShowSection from "../home/product-show-section";
import { useGetSingleProductQuery } from "@/redux/features/productDetails";
import ProductDetailPage from "./product-detail-page";

interface ProductDetailsComponentProps {
	productId: string;
}

export default function ProductDetailsComponent({
	productId,
}: ProductDetailsComponentProps) {
	const { data: product, isLoading, isFetching } = useGetSingleProductQuery(
		productId,
		{
			skip: !productId,
			refetchOnMountOrArgChange: true,
		},
	);

	if (!product && (isLoading || isFetching)) {
		return (
			<div className="min-h-[50vh] flex items-center justify-center">
				<Loader2 className="h-8 w-8 animate-spin text-pink-600" />
			</div>
		);
	}

	return (
		<div>
			<ProductDetailPage
				product={product?.data?.product_details}
				flashSale={product?.data?.flash_sale}
				commissionPercent={product?.data?.commission_percent}
			/>
			<ProductShowSection
				title="NEW ARRIVALS"
				className="bg-white"
				productData={product?.data?.relatedproducts}
			/>
			<MostPopularBrands />
		</div>
	);
}
