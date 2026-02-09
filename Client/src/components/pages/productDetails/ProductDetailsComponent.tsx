// app/products/[productId]/ProductDetailsComponent.tsx

import MostPopularBrands from "../home/most-popular-brands";
import ProductShowSection from "../home/product-show-section";
import ProductDetailPage from "./product-detail-page";

interface ProductDetailsComponentProps {
	productId: string;
}

async function getSingleProduct(productId: string) {
	const res = await fetch(
		`${process.env.NEXT_PUBLIC_BASE_URL}/product-details/${productId}`,
		{
			method: "GET",
			cache: "no-store", // or "force-cache" if you want caching
		},
	);

	if (!res.ok) {
		throw new Error("Failed to fetch product details");
	}

	return res.json();
}

export default async function ProductDetailsComponent({
	productId,
}: ProductDetailsComponentProps) {
	const product = await getSingleProduct(productId);

	return (
		<div>
			<ProductDetailPage product={product?.data?.product_details} />
			<ProductShowSection
				title="NEW ARRIVALS"
				className="bg-white"
				productData={product?.data?.relatedproducts}
			/>
			<MostPopularBrands />
		</div>
	);
}
