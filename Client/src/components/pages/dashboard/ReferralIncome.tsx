/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import orderIcon from "@/assets/images/dashboard/Group 1321314503 (1).png";
import myReferralIcon from "@/assets/images/dashboard/Group 1321314503 (2).png";
import activeMemberIcon from "@/assets/images/dashboard/Group 1321314504.png";
import paidMemberIcon from "@/assets/images/dashboard/Group 1321314505.png";
import { useGetAllReferralDataQuery } from "@/redux/features/dashboardApi";

const ReferralIncome = () => {
	const { data } = useGetAllReferralDataQuery(undefined);

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
		<div className="m-3 sm:m-4 lg:m-6 bg-white rounded-xl border border-gray-100 shadow-sm p-3 sm:p-5 lg:p-6 mb-24">
			{/* Header */}
			<h1 className="text-base sm:text-lg font-semibold text-gray-900 mb-4">
				My Referral
			</h1>

			{/* Stats Cards */}
			<div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
				{orderStats.map((stat, index) => (
					<div
						key={index}
						className="bg-gray-50/60 border border-gray-100 rounded-xl p-3 sm:p-4"
					>
						<div className="flex items-center gap-2.5">
							<div className="w-9 h-9 rounded-full bg-white border border-gray-100 flex items-center justify-center flex-shrink-0">
								<img
									src={stat.icon.src}
									alt={stat.title}
									className="w-5 h-5"
								/>
							</div>
							<div>
								<p className="text-xs text-gray-500">{stat.title}</p>
								<p className="text-lg sm:text-xl font-bold text-gray-900">
									{stat.value}
								</p>
							</div>
						</div>
					</div>
				))}
			</div>

			{/* Section Title */}
			<h2 className="text-sm sm:text-base font-semibold text-gray-900 mb-3">
				Referral Income
			</h2>

			{/* Mobile Card Layout */}
			<div className="md:hidden space-y-3">
				{history.length > 0 ? (
					history.map((row: any, index: number) => (
						<div
							key={row.id}
							className="bg-gray-50/60 border border-gray-100 rounded-xl p-3"
						>
							<div className="flex items-center justify-between mb-1">
								<p className="text-sm font-semibold text-gray-900 truncate mr-2">
									{row.message_for}
								</p>
								<span className="text-sm font-semibold text-green-600 flex-shrink-0">
									৳{row.amount}
								</span>
							</div>
							<p className="text-xs text-gray-500 mb-1.5 line-clamp-2">{row.message}</p>
							<div className="flex items-center justify-between text-xs text-gray-400">
								<span>#{index + 1}</span>
								<span>{row.date}</span>
							</div>
						</div>
					))
				) : (
					<div className="py-10 text-center text-gray-400 text-sm">
						No referral income data found.
					</div>
				)}
			</div>

			{/* Desktop Table Layout */}
			<div className="hidden md:block overflow-x-auto">
				<table className="w-full">
					<thead>
						<tr className="bg-gray-50/80">
							<th className="p-4 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">
								Serial No
							</th>
							<th className="p-4 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">
								Message For
							</th>
							<th className="p-4 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">
								Message
							</th>
							<th className="p-4 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">
								Date
							</th>
							<th className="p-4 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">
								Income
							</th>
						</tr>
					</thead>

					<tbody>
						{history.length > 0 ? (
							history.map((row: any, index: number) => (
								<tr
									key={row.id}
									className="border-b border-gray-50 hover:bg-gray-50/50 transition-colors"
								>
									<td className="p-4 text-sm text-gray-500">{index + 1}</td>
									<td className="p-4 text-sm font-medium text-gray-900">
										{row.message_for}
									</td>
									<td className="p-4 text-sm text-gray-600">{row.message}</td>
									<td className="p-4 text-sm text-gray-500">{row.date}</td>
									<td className="p-4 text-sm font-semibold text-green-600">
										৳{row.amount}
									</td>
								</tr>
							))
						) : (
							<tr>
								<td colSpan={5} className="text-center p-8 text-gray-400 text-sm">
									No referral income data found.
								</td>
							</tr>
						)}
					</tbody>
				</table>
			</div>
		</div>
	);
};

export default ReferralIncome;
