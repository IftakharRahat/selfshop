"use client";

import money from "@/assets/images/dashboard/Group (3).png";
import profit from "@/assets/images/dashboard/Group 1321314506.png";
import balance from "@/assets/images/dashboard/Group 1321314506 (1).png";
import withdraw from "@/assets/images/dashboard/Group 1321314506 (2).png";
import MetricCard from "@/components/pages/dashboard/metric-card";
import OrderInsightCards from "@/components/pages/dashboard/order-insight-cards";
import OrdersTable from "@/components/pages/dashboard/orders-table";
import { useGetAllDashboardDataQuery } from "@/redux/features/dashboardApi";

export default function Dashboard() {
	const { data } = useGetAllDashboardDataQuery(undefined);

	// 🛠 FIXING API TYPO: blance → balance
	const metrics = {
		...data?.data,
		balance: data?.data?.balance ?? data?.data?.blance ?? 0,
	};

	return (
		<main className="flex-1 p-3 sm:p-5 lg:p-6 pb-24">
			{/* Metrics Cards */}
			<div className="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-5 mb-4 lg:mb-8">
				<MetricCard
					title="Total Sale"
					value={`৳ ${metrics.total_sales ?? 0}`}
					icon={money}
					iconColor="bg-white"
				/>

				<MetricCard
					title="Total Profit"
					value={`৳ ${metrics.total_profit ?? 0}`}
					icon={profit}
					iconColor="bg-pink-500"
				/>

				<MetricCard
					title="Balance"
					value={`৳ ${metrics.balance ?? 0}`}
					subtitle={`Last withdraw: ৳ ${metrics.withdraw ?? 0}`}
					icon={balance}
					iconColor="bg-pink-500"
				/>

				<MetricCard
					title="Total Withdraw"
					value={`৳ ${metrics.withdraw ?? 0}`}
					icon={withdraw}
					iconColor="bg-pink-500"
				/>
			</div>

			{/* Order Insight + Orders Table */}
			<div className="bg-white rounded-xl border border-gray-100 shadow-sm p-3 sm:p-5 lg:p-6">
				<OrderInsightCards />

				<div className="mt-4 sm:mt-6 pt-4 sm:pt-6 border-t border-gray-100">
					<h2 className="text-base sm:text-lg font-semibold text-gray-900 mb-3 sm:mb-4">
						Pending Orders
					</h2>
					<OrdersTable />
				</div>
			</div>
		</main>
	);
}
