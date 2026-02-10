"use client";
import { useState } from "react";
import {
	useGetAllCollectionsQuery,
	useGetAllFeaturedProductsQuery,
} from "@/redux/features/home/homeApi";
import BigSellingSection from "./big-selling-section";
import CategoriesSection from "./CategoriesSection";
import FeaturesSection from "./features-section";
import MostPopularBrands from "./most-popular-brands";
import NewProducts from "./new-products";
import ProductShowSection from "./product-show-section";
import PromotionalSection from "./promotional-section";

const HomeComponent = () => {
	const [objectQuery] = useState([
		{ name: "page", value: 1 },
		{ name: "limit", value: 12 },
	]);
	const { data: newArrivalsData } = useGetAllCollectionsQuery({
		objectQuery,
		slug: "new_arrivel",
	});
	const { data: featuredProductsData } =
		useGetAllFeaturedProductsQuery(undefined);
	return (
		<div className="space-y-2 sm:space-y-4 lg:space-y-6">
			<CategoriesSection />
			<FeaturesSection />
			<PromotionalSection />
			<ProductShowSection
				title="NEW ARRIVALS"
				className="bg-white"
				productData={newArrivalsData?.data?.data}
			/>
			<ProductShowSection
				title="FEATURED PRODUCTS"
				className="bg-[#FDF0F6]"
				productData={featuredProductsData?.data?.data}
			/>
			<BigSellingSection />
			<NewProducts />
			<MostPopularBrands />
		</div>
	);
};

export default HomeComponent;
