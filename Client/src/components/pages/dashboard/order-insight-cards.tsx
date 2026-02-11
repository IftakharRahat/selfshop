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
		<div>
			<h2 className="text-lg font-semibold text-gray-900 mb-4">
				Order Insight
			</h2>
			<div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
				{insights.map((insight, index) => (
					<div
						key={index}
						className="bg-gray-50/80 border border-gray-100 hover:border-gray-200 hover:shadow-sm transition-all rounded-xl p-4"
					>
						<div className="flex items-center gap-3">
							<div className="w-10 h-10 rounded-xl bg-white border border-gray-100 flex items-center justify-center flex-shrink-0">
								<img
									src={insight.icon.src}
									alt={insight.title}
									className="w-6 h-6"
								/>
							</div>
							<div>
								<p className="text-sm text-gray-500">
									{insight.title}
								</p>
								<p className="text-xl font-bold text-gray-900">
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
