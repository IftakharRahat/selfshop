"use client";

import money from "@/assets/images/dashboard/Group (3).png";
import balance from "@/assets/images/dashboard/Group 1321314506 (1).png";
import withdraw from "@/assets/images/dashboard/Group 1321314506 (2).png";
import profit from "@/assets/images/dashboard/Group 1321314506.png";
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
    <main className="flex-1 p-4 sm:p-5 ">

      {/* Mobile Welcome Section */}
      <div className="lg:hidden mb-6">
        <h1 className="text-lg font-semibold text-gray-900 mb-1">
          Welcome Back
        </h1>
        <p className="text-sm text-gray-600">
          Here’s what’s happening with your store today
        </p>
      </div>

      {/* Metrics Cards */}
      <div className="
        grid 
        grid-cols-1 
        sm:grid-cols-2 
        lg:grid-cols-4 
        gap-4 
        sm:gap-5 
        lg:gap-6 
        mb-6 
        lg:mb-8
      ">
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

      {/* Sales Insight + Orders */}
      <div className="my-6 lg:my-8 bg-white rounded-xl shadow-sm p-3 sm:p-4 lg:p-6">

        <OrderInsightCards />

        <div className="mt-6">
          <OrdersTable />
        </div>

      </div>
    </main>
  );
}
