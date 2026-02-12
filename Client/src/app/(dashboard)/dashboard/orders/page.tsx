"use client";
import order from "@/assets/images/dashboard/Group 1321314503 (1).png";
import cancelled from "@/assets/images/dashboard/Group 1321314503 (2).png";
import returnIcon from "@/assets/images/dashboard/Group 1321314504.png";
import delivery from "@/assets/images/dashboard/Group 1321314505.png";
import delivered from "@/assets/images/dashboard/Group 1321314506 (3).png";
import OrdersTable from "@/components/pages/dashboard/orders-table";
import { useOrderCountQuery } from "@/redux/features/orderApi";

const OrderPage = () => {
	const { data, error, isLoading } = useOrderCountQuery(undefined);

	const stats = [
		{ title: "New order", value: data?.data?.pending ?? 0, icon: order },
		{ title: "Cancelled", value: data?.data?.canceled ?? 0, icon: cancelled },
		{ title: "Returned", value: data?.data?.return ?? 0, icon: returnIcon },
		{ title: "On delivery", value: data?.data?.ontheway ?? 0, icon: delivery },
		{ title: "Delivered", value: data?.data?.delivered ?? 0, icon: delivered },
	];

	return (
		<div className="m-3 sm:m-4 lg:m-6 bg-white rounded-xl border border-gray-100 shadow-sm p-3 sm:p-5 lg:p-6 mb-24">
			<h1 className="text-lg sm:text-2xl font-semibold text-gray-900 mb-3 sm:mb-4">
				Order Insight
			</h1>

			{/* Loading / Error */}
			{isLoading && (
				<p className="text-gray-600 mt-3">Loading order stats...</p>
			)}
			{error && <p className="text-red-500 mt-3">Failed to load stats.</p>}

			{/* Order Statistics */}
			{!isLoading && !error && (
				<div className="grid grid-cols-3 gap-2 sm:gap-3 lg:grid-cols-5 lg:border-b lg:pb-4 lg:mb-4 border-gray-100">
					{stats.map((stat, index) => (
						<div
							key={index}
							className="bg-gray-50/80 border border-gray-100 rounded-xl p-3 sm:p-4"
						>
							<div className="flex flex-col sm:flex-row items-center sm:items-center gap-1.5 sm:gap-3">
								<div className="w-8 h-8 sm:w-9 sm:h-9 rounded-lg bg-white border border-gray-100 flex items-center justify-center flex-shrink-0">
									<img
										src={stat.icon.src}
										alt={stat.title}
										className="w-5 h-5 sm:w-6 sm:h-6"
									/>
								</div>
								<div className="text-center sm:text-left">
									<p className="text-[10px] sm:text-sm font-medium text-gray-500 leading-tight">
										{stat.title}
									</p>
									<p className="text-lg sm:text-2xl font-bold text-gray-900">
										{stat.value}
									</p>
								</div>
							</div>
						</div>
					))}
				</div>
			)}

			<div className="mt-3 sm:mt-4">
				<h2 className="text-base sm:text-lg font-semibold text-gray-900 mb-3 sm:mb-4">
					Pending Orders
				</h2>
				<OrdersTable />
			</div>
		</div>
	);
};

export default OrderPage;
