/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import order from "@/assets/images/dashboard/order.png";
import reseller from "@/assets/images/dashboard/reseller.png";
import shop from "@/assets/images/dashboard/shop.png";
import { useGetMeQuery } from "@/redux/features/auth/authApi";
import { useIncomeHistoryQuery } from "@/redux/features/orderApi";

const OrderIncomePage = () => {
  const { data, isLoading, isError } = useIncomeHistoryQuery(undefined);
  const { data: getMe } = useGetMeQuery(undefined);

  const incomeList = data?.data || [];

  const insights = [
    {
      title: "My shop",
      value: getMe?.data?.shopproducts,
      icon: shop,
    },
    {
      title: "Total order",
      value: getMe?.data?.totalorders,
      icon: order,
    },
    { 
      title: "Your sold amount",
      value: getMe?.data?.soldamount,
      icon: reseller,
    },
  ];

  return (
    <div className="m-4 lg:m-6 bg-white rounded-md">
      <div className="p-4">
        <h2 className="text-lg font-semibold text-gray-900 mb-4">Order income</h2>

        {/* insight cards */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 max-w-3xl mx-auto">
          {insights.map((insight, index) => (
            <div key={index} className="bg-[#F3F3F3] hover:shadow-md transition-shadow rounded-md p-4">
              <div className="flex items-start space-x-3">
                <div className="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center">
                  <img src={insight.icon.src} alt={insight.title} className="w-6 h-6" />
                </div>
                <div>
                  <p className="text-sm font-medium text-gray-600">{insight.title}</p>
                  <p className="text-2xl font-bold text-gray-900">{insight.value}</p>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* table */}
      <div className="bg-white">
        <div className="p-0">
          <div className="flex items-center justify-between p-4 border border-gray-200">
            <h2 className="text-md font-semibold text-gray-900">All incomes</h2>
          </div>

          {/* loading state */}
          {isLoading && <div className="p-4 text-center text-gray-500">Loading income history...</div>}

          {/* error state */}
          {isError && <div className="p-4 text-center text-red-500">Failed to load income history.</div>}

          {/* Desktop Table */}
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr>
                  <th className="text-left p-4 text-sm font-medium text-gray-600">Order ID</th>
                  <th className="text-left p-4 text-sm font-medium text-gray-600">Product price</th>
                  <th className="text-left p-4 text-sm font-medium text-gray-600">Income amount</th>
                  <th className="text-left p-4 text-sm font-medium text-gray-600">Date</th>
                  <th className="text-left p-4 text-sm font-medium text-gray-600">Status</th>
                </tr>
              </thead>

              <tbody>
                {incomeList.map((item: any, index: number) => (
                  <tr key={index} className="border-b border-gray-100 hover:bg-gray-50">
                    <td className="p-4 text-sm text-gray-900">{item.invoice_id}</td>

                    <td className="p-4 text-sm text-gray-900">৳ {item.product_price}</td>

                    <td className="p-4 text-sm text-gray-600">৳ {item.amount}</td>

                    <td className="p-4 text-sm text-gray-600">{new Date(item.created_at).toLocaleDateString()}</td>

                    <td className="p-4">
                      <div
                        className={`py-1 w-min rounded-md px-5 text-xs ${
                          item.status === "Canceled" ? "bg-red-200 text-red-800" : "bg-green-200 text-green-800"
                        }`}
                      >
                        {item.status}
                      </div>
                    </td>
                  </tr>
                ))}

                {incomeList.length === 0 && !isLoading && (
                  <tr>
                    <td colSpan={5} className="text-center p-4 text-gray-500">
                      No income records found.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
};

export default OrderIncomePage;
