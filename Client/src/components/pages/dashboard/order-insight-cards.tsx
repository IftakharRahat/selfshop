import order from "@/assets/images/dashboard/order.png";
import reseller from "@/assets/images/dashboard/reseller.png";
import shop from "@/assets/images/dashboard/shop.png";
import { useGetMeQuery } from "@/redux/features/auth/authApi";

export default function OrderInsightCards() {
	const { data } = useGetMeQuery(undefined);

	const insights = [
		{
			title: "My shop",
			value: data?.data?.shopproducts,
			icon: shop,
		},
		{
			title: "Total order",
			value: data?.data?.totalorders,
			icon: order,
		},
		{
			title: "Your sold amount",
			value: data?.data?.soldamount,
			icon: reseller,
		},
	];

	return (
		<div className="p-4">
			<h2 className="text-lg font-semibold text-gray-900 mb-4">
				Order insight
			</h2>
			<div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 max-w-3xl mx-auto">
				{insights.map((insight, index) => (
					<div
						key={index}
						className={`bg-[#F3F3F3] hover:shadow-md transition-shadow rounded-md p-4`}
					>
						<div className="flex items-start space-x-3">
							<div className="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
								<img
									src={insight.icon.src}
									alt={insight.title}
									className="w-8 h-8"
								/>
							</div>
							<div>
								<p className="text-sm font-medium text-gray-600">
									{insight.title}
								</p>
								<p className="text-2xl font-bold text-gray-900">
									{insight.value}
								</p>
							</div>
						</div>
					</div>
				))}
			</div>
		</div>
	);
}
