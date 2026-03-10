"use client";
import CategoriesSection from "./CategoriesSection";
import FlashSaleSection from "./flash-sale-section";
import MostPopularBrands from "./most-popular-brands";
import NewProducts from "./new-products";
import PopularSuppliers from "./popular-suppliers";
import PromotionalSection from "./promotional-section";

const HomeComponent = () => {
	return (
		<div className="space-y-2 sm:space-y-4 lg:space-y-6">
			<CategoriesSection />
			<PopularSuppliers />
			<FlashSaleSection />
			<PromotionalSection />
			<NewProducts />
			<MostPopularBrands />
		</div>
	);
};

export default HomeComponent;
