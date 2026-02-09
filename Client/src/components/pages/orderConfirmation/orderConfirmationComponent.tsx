import MostPopularBrands from "../home/most-popular-brands";
import OrderConfirmation from "./order-confirmation";

const OrderConfirmationComponent = () => {
	return (
		<>
			<OrderConfirmation />
			{/* <ProductShowSection title="FEATURED PRODUCTS" className="bg-white" /> */}
			<MostPopularBrands />
		</>
	);
};

export default OrderConfirmationComponent;
