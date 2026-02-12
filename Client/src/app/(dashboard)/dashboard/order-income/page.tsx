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
		<div className="m-3 sm:m-4 lg:m-6 bg-white rounded-xl border border-gray-100 shadow-sm p-3 sm:p-5 lg:p-6 mb-24">
			<h2 className="text-lg sm:text-2xl font-semibold text-gray-900 mb-3 sm:mb-4">
				Order Income
			</h2>

			{/* Insight cards */}
			<div className="grid grid-cols-3 gap-2 sm:gap-4">
				{insights.map((insight, index) => (
					<div
						key={index}
						className="bg-gray-50/80 border border-gray-100 hover:border-gray-200 hover:shadow-sm transition-all rounded-xl p-2.5 sm:p-4"
					>
						<div className="flex flex-col sm:flex-row items-center sm:items-center gap-1.5 sm:gap-3">
							<div className="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-white border border-gray-100 flex items-center justify-center flex-shrink-0">
								<img
									src={insight.icon.src}
									alt={insight.title}
									className="w-4 h-4 sm:w-6 sm:h-6"
								/>
							</div>
							<div className="text-center sm:text-left">
								<p className="text-[10px] sm:text-sm text-gray-500 leading-tight">
									{insight.title}
								</p>
								<p className="text-base sm:text-xl font-bold text-gray-900">
									{insight.value}
								</p>
							</div>
						</div>
					</div>
				))}
			</div>

			{/* Income Table */}
			<div className="mt-4 sm:mt-6 pt-4 sm:pt-6 border-t border-gray-100">
				<h3 className="text-base sm:text-lg font-semibold text-gray-900 mb-3 sm:mb-4">
					All Incomes
				</h3>

				{/* Loading / Error */}
				{isLoading && (
					<div className="py-10 text-center text-gray-500 text-sm">
						Loading income history...
					</div>
				)}
				{isError && (
					<div className="py-10 text-center text-red-500 text-sm">
						Failed to load income history.
					</div>
				)}

				{/* Mobile Card Layout */}
				{!isLoading && !isError && (
					<div className="md:hidden space-y-3">
						{incomeList.map((item: any, index: number) => (
							<div
								key={index}
								className="bg-gray-50/60 border border-gray-100 rounded-xl p-3"
							>
								<div className="flex items-center justify-between mb-1">
									<p className="text-xs text-gray-500">
										{item.invoice_id}
									</p>
									<span
										className={`px-2 py-0.5 rounded-full text-[10px] font-medium ${item.status === "Canceled"
											? "bg-red-50 text-red-700 border border-red-200"
											: "bg-green-50 text-green-700 border border-green-200"
											}`}
									>
										{item.status}
									</span>
								</div>

								<p className="text-lg font-bold text-green-700 mb-1.5">৳ {item.amount}</p>

								<div className="flex items-center justify-between text-xs text-gray-400">
									<span>Product: ৳ {item.product_price}</span>
									<span>{new Date(item.created_at).toLocaleDateString()}</span>
								</div>
							</div>
						))}

						{incomeList.length === 0 && (
							<div className="py-10 text-center text-gray-400 text-sm">
								No income records found.
							</div>
						)}
					</div>
				)}

				{/* Desktop Table Layout */}
				{!isLoading && !isError && (
					<div className="hidden md:block overflow-x-auto">
						<table className="w-full">
							<thead>
								<tr className="bg-gray-50/80">
									<th className="p-4 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">
										Order ID
									</th>
									<th className="p-4 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">
										Product Price
									</th>
									<th className="p-4 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">
										Income Amount
									</th>
									<th className="p-4 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">
										Date
									</th>
									<th className="p-4 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">
										Status
									</th>
								</tr>
							</thead>

							<tbody>
								{incomeList.map((item: any, index: number) => (
									<tr
										key={index}
										className="border-b border-gray-50 hover:bg-gray-50/50 transition-colors"
									>
										<td className="p-4 text-sm font-medium text-gray-900">
											{item.invoice_id}
										</td>
										<td className="p-4 text-sm text-gray-700">
											৳ {item.product_price}
										</td>
										<td className="p-4 text-sm font-bold text-green-700">
											৳ {item.amount}
										</td>
										<td className="p-4 text-sm text-gray-500">
											{new Date(item.created_at).toLocaleDateString()}
										</td>
										<td className="p-4">
											<span
												className={`px-2.5 py-1 rounded-full text-xs font-medium ${item.status === "Canceled"
													? "bg-red-50 text-red-700 border border-red-200"
													: "bg-green-50 text-green-700 border border-green-200"
													}`}
											>
												{item.status}
											</span>
										</td>
									</tr>
								))}

								{incomeList.length === 0 && (
									<tr>
										<td colSpan={5} className="py-12 text-center text-gray-400 text-sm">
											No income records found.
										</td>
									</tr>
								)}
							</tbody>
						</table>
					</div>
				)}
			</div>
		</div>
	);
};

export default OrderIncomePage;
