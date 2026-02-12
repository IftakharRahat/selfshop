"use client";
import { useState } from "react";
import order from "@/assets/images/dashboard/Group 1321314503 (1).png";
import cancelled from "@/assets/images/dashboard/Group 1321314503 (2).png";
import returnIcon from "@/assets/images/dashboard/Group 1321314504.png";
import delivery from "@/assets/images/dashboard/Group 1321314505.png";
import delivered from "@/assets/images/dashboard/Group 1321314506 (3).png";
import OrdersTable from "@/components/pages/dashboard/orders-table";
import { cn } from "@/lib/utils";
import { useOrderCountQuery } from "@/redux/features/orderApi";

const statusTabs = [
	{ label: "All", value: "all" },
	{ label: "Pending", value: "Pending" },
	{ label: "Confirmed", value: "Confirmed" },
	{ label: "Processing", value: "Processing" },
	{ label: "On delivery", value: "Ontheway" },
	{ label: "Delivered", value: "Delivered" },
	{ label: "Cancelled", value: "Canceled" },
	{ label: "Returned", value: "Return" },
];

const OrderPage = () => {
	const { data, error, isLoading } = useOrderCountQuery(undefined);
	const [activeStatus, setActiveStatus] = useState("all");

	const stats = [
		{ title: "New order", value: data?.data?.pending ?? 0, icon: order },
		{ title: "Cancelled", value: data?.data?.canceled ?? 0, icon: cancelled },
		{ title: "Returned", value: data?.data?.return ?? 0, icon: returnIcon },
		{ title: "On delivery", value: data?.data?.ontheway ?? 0, icon: delivery },
		{ title: "Delivered", value: data?.data?.delivered ?? 0, icon: delivered },
	];

	return (
		<div className="m-4 lg:m-6 bg-white rounded-md p-4">
			<div className="flex items-center justify-between">
				<h1 className="text-2xl font-semibold text-gray-900">Order insight</h1>
			</div>

			{/* Loading / Error */}
			{isLoading && (
				<p className="text-gray-600 mt-3">Loading order stats...</p>
			)}
			{error && <p className="text-red-500 mt-3">Failed to load stats.</p>}

			{/* Order Statistics */}
			{!isLoading && !error && (
				<div className="grid grid-cols-1 gap-4 xs:grid-cols-2 lg:grid-cols-5 xl:border-b xl:pb-3 xl:mb-3 border-gray-200">
					{stats.map((stat, index) => (
						<div key={index} className="bg-white">
							<div className="p-6 flex items-center justify-between">
								<div className="flex items-start space-x-3">
									<div className="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center">
										<img
											src={stat.icon.src}
											alt={stat.title}
											className="w-6 h-6"
										/>
									</div>
									<div>
										<p className="text-sm font-medium text-gray-600">
											{stat.title}
										</p>
										<p className="text-2xl font-bold text-gray-900">
											{stat.value}
										</p>
									</div>
								</div>

								<div
									className={cn(
										index === stats.length - 1
											? ""
											: "hidden 2xl:block h-12 w-[0.2px] bg-gray-300",
									)}
								></div>
							</div>
						</div>
					))}
				</div>
			)}

			{/* Status Tabs */}
			<div className="flex flex-wrap gap-2 mb-4 border-b border-gray-100 pb-3">
				{statusTabs.map((tab) => (
					<button
						key={tab.value}
						onClick={() => setActiveStatus(tab.value)}
						className={cn(
							"px-3 py-1.5 text-sm font-medium rounded-lg transition-colors cursor-pointer",
							activeStatus === tab.value
								? "bg-[#E5005F] text-white"
								: "bg-gray-50 text-gray-600 hover:bg-gray-100"
						)}
					>
						{tab.label}
					</button>
				))}
			</div>

			<OrdersTable status={activeStatus} />
		</div>
	);
};

export default OrderPage;
