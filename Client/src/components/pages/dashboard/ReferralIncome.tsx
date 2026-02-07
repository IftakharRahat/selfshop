/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import orderIcon from "@/assets/images/dashboard/Group 1321314503 (1).png";
import myReferralIcon from "@/assets/images/dashboard/Group 1321314503 (2).png";
import activeMemberIcon from "@/assets/images/dashboard/Group 1321314504.png";
import paidMemberIcon from "@/assets/images/dashboard/Group 1321314505.png";
import { cn } from "@/lib/utils";
import { useGetAllReferralDataQuery } from "@/redux/features/dashboardApi";

const ReferralIncome = () => {
  const { data } = useGetAllReferralDataQuery(undefined);

  // FIX API DATA FORMAT
  const statsData = data?.data || {};

  const history = statsData?.history?.data || [];

  const orderStats = [
    {
      title: "Referral Bonus",
      value: statsData.referal_bonus ?? 0,
      icon: orderIcon,
    },
    {
      title: "My Referral",
      value: statsData.my_referral ?? 0,
      icon: myReferralIcon,
    },
    {
      title: "Active Member",
      value: statsData.active_member ?? 0,
      icon: activeMemberIcon,
    },
    {
      title: "Paid Member",
      value: statsData.paid_member ?? 0,
      icon: paidMemberIcon,
    },
  ];

  return (
    <div className="m-4 lg:m-6 bg-white rounded-md p-4">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-xl sm:text-2xl font-semibold text-gray-900">
          My Referral
        </h1>
      </div>

      {/* Referral Statistics Cards */}
      <div className="
        grid 
        grid-cols-2
        xs:grid-cols-2 
        lg:grid-cols-4 
        gap-4 
        xl:border-b 
        xl:pb-3 
        xl:mb-3 
        border-gray-200
      ">
        {orderStats.map((stat, index) => (
          <div key={index} className="bg-white">
            <div className="p-4 sm:p-6 flex items-center justify-between">
              <div className="flex items-start space-x-3">
                <div className="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-gray-100 flex items-center justify-center">
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
                  <p className="text-xl sm:text-2xl font-bold text-gray-900">
                    {stat.value}
                  </p>
                </div>
              </div>

              {/* Vertical Line on Large Screens */}
              <div
                className={cn(
                  index === orderStats.length - 1
                    ? ""
                    : "hidden 2xl:block h-12 w-[1px] bg-gray-300"
                )}
              ></div>
            </div>
          </div>
        ))}
      </div>

      {/* Referral History Table */}
      <div className="bg-white mt-6 rounded-md border border-gray-200">
        <div className="flex items-center justify-between p-4 border-b border-gray-200">
          <h2 className="text-lg font-semibold text-gray-900">
            Referral Income
          </h2>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full min-w-[700px]">
            <thead>
              <tr>
                <th className="text-left p-4 text-sm font-medium text-gray-600">
                  Serial No
                </th>
                <th className="text-left p-4 text-sm font-medium text-gray-600">
                  Message For
                </th>
                <th className="text-left p-4 text-sm font-medium text-gray-600">
                  Message
                </th>
                <th className="text-left p-4 text-sm font-medium text-gray-600">
                  Date
                </th>
                <th className="text-left p-4 text-sm font-medium text-gray-600">
                  Income
                </th>
              </tr>
            </thead>

            <tbody>
              {history.length > 0 ? (
                history.map((row: any, index: number) => (
                  <tr
                    key={row.id}
                    className="border-b border-gray-100 hover:bg-gray-50"
                  >
                    <td className="p-4 text-sm text-gray-900">{index + 1}</td>

                    <td className="p-4 text-sm text-gray-900">
                      {row.message_for}
                    </td>

                    <td className="p-4 text-sm text-gray-700">
                      {row.message}
                    </td>

                    <td className="p-4 text-sm text-gray-600">
                      {row.date}
                    </td>

                    <td className="p-4 text-sm text-gray-900">
                      ৳{row.amount}
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={5} className="text-center p-4 text-gray-500">
                    No referral income data found.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};

export default ReferralIncome;
