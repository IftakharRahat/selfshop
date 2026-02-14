"use client";

import { useState } from "react";
import WithVendorAuth from "../WithVendorAuth";
import {
	useGetVendorReportsSalesQuery,
	useGetVendorReportsTopProductsQuery,
	useGetVendorReportsSalesBreakdownQuery,
} from "@/redux/api/vendorApi";

const defaultFrom = () => {
	const d = new Date();
	d.setDate(d.getDate() - 30);
	return d.toISOString().slice(0, 10);
};
const defaultTo = () => new Date().toISOString().slice(0, 10);

export default function VendorReportsPage() {
	const [period, setPeriod] = useState<"day" | "month">("day");
	const [from, setFrom] = useState(defaultFrom);
	const [to, setTo] = useState(defaultTo);

	const { data: salesData, isLoading: salesLoading } = useGetVendorReportsSalesQuery({
		period,
		from,
		to,
	});
	const { data: topData, isLoading: topLoading } = useGetVendorReportsTopProductsQuery({ limit: 10 });
	const { data: breakdownData, isLoading: breakdownLoading } = useGetVendorReportsSalesBreakdownQuery({
		from,
		to,
	});

	const sales = salesData?.data;
	const topProducts = topData?.data?.top_products ?? [];
	const breakdown = breakdownData?.data?.by_fulfillment_type ?? {};

	return (
		<WithVendorAuth>
			<div className="space-y-6">
				<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
					<h1 className="text-2xl font-bold text-gray-900 mb-1">Reports &amp; analytics</h1>
					<p className="text-sm text-gray-600">
						Sales over time, top products, and wholesale vs dropship breakdown.
					</p>
				</div>

				{/* Sales over time */}
				<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
					<h2 className="text-sm font-semibold text-gray-800 mb-3">Sales report</h2>
					<div className="flex flex-wrap gap-2 mb-4">
						<select
							value={period}
							onChange={(e) => setPeriod(e.target.value as "day" | "month")}
							className="border border-gray-300 rounded-md px-3 py-1.5 text-sm"
						>
							<option value="day">Daily</option>
							<option value="month">Monthly</option>
						</select>
						<input
							type="date"
							value={from}
							onChange={(e) => setFrom(e.target.value)}
							className="border border-gray-300 rounded-md px-3 py-1.5 text-sm"
						/>
						<span className="self-center text-gray-500">to</span>
						<input
							type="date"
							value={to}
							onChange={(e) => setTo(e.target.value)}
							className="border border-gray-300 rounded-md px-3 py-1.5 text-sm"
						/>
					</div>
					{salesLoading ? (
						<p className="text-sm text-gray-500 py-6">Loading...</p>
					) : sales?.series?.length ? (
						<div className="overflow-x-auto">
							<table className="min-w-full text-sm">
								<thead className="bg-gray-50 text-gray-600">
									<tr>
										<th className="px-3 py-2 text-left font-medium">Period</th>
										<th className="px-3 py-2 text-right font-medium">Sales</th>
										<th className="px-3 py-2 text-right font-medium">Commission</th>
										<th className="px-3 py-2 text-right font-medium">Net</th>
										<th className="px-3 py-2 text-right font-medium">Orders</th>
									</tr>
								</thead>
								<tbody className="divide-y divide-gray-100">
									{sales.series.map((row: { period: string; total_sales: number; total_commission: number; net_earnings: number; order_count: number }) => (
										<tr key={row.period} className="hover:bg-gray-50">
											<td className="px-3 py-2">{row.period}</td>
											<td className="px-3 py-2 text-right">৳{Number(row.total_sales).toLocaleString()}</td>
											<td className="px-3 py-2 text-right text-amber-600">-৳{Number(row.total_commission).toLocaleString()}</td>
											<td className="px-3 py-2 text-right font-medium">৳{Number(row.net_earnings).toLocaleString()}</td>
											<td className="px-3 py-2 text-right">{row.order_count}</td>
										</tr>
									))}
								</tbody>
							</table>
						</div>
					) : (
						<p className="text-sm text-gray-500 py-6">No sales data in this range.</p>
					)}
				</div>

				{/* Top products */}
				<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
					<h2 className="text-sm font-semibold text-gray-800 mb-3">Top selling products</h2>
					{topLoading ? (
						<p className="text-sm text-gray-500 py-4">Loading...</p>
					) : topProducts.length ? (
						<div className="overflow-x-auto">
							<table className="min-w-full text-sm">
								<thead className="bg-gray-50 text-gray-600">
									<tr>
										<th className="px-3 py-2 text-left font-medium">Product</th>
										<th className="px-3 py-2 text-right font-medium">Quantity sold</th>
										<th className="px-3 py-2 text-right font-medium">Sales</th>
										<th className="px-3 py-2 text-right font-medium">Orders</th>
									</tr>
								</thead>
								<tbody className="divide-y divide-gray-100">
									{topProducts.map((p: { product_id: number; product_name: string; total_sales: number; total_quantity: number; order_count: number }) => (
										<tr key={p.product_id} className="hover:bg-gray-50">
											<td className="px-3 py-2">{p.product_name}</td>
											<td className="px-3 py-2 text-right">{p.total_quantity}</td>
											<td className="px-3 py-2 text-right font-medium">৳{Number(p.total_sales).toLocaleString()}</td>
											<td className="px-3 py-2 text-right">{p.order_count}</td>
										</tr>
									))}
								</tbody>
							</table>
						</div>
					) : (
						<p className="text-sm text-gray-500 py-4">No product sales yet.</p>
					)}
				</div>

				{/* Wholesale vs dropship */}
				<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
					<h2 className="text-sm font-semibold text-gray-800 mb-3">Sales by fulfillment type</h2>
					<p className="text-xs text-gray-500 mb-3">From {from} to {to}</p>
					{breakdownLoading ? (
						<p className="text-sm text-gray-500 py-4">Loading...</p>
					) : Object.keys(breakdown).length ? (
						<div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
							{Object.entries(breakdown).map(([type, row]: [string, { total_sales: number; net_earnings: number; order_count: number }]) => (
								<div key={type} className="border rounded-lg p-4 bg-gray-50 border-gray-200">
									<p className="text-sm font-medium text-gray-700 capitalize">{type}</p>
									<p className="text-lg font-bold text-gray-900 mt-1">৳{Number(row.total_sales).toLocaleString()}</p>
									<p className="text-xs text-gray-500">Net: ৳{Number(row.net_earnings).toLocaleString()} · {row.order_count} orders</p>
								</div>
							))}
						</div>
					) : (
						<p className="text-sm text-gray-500 py-4">No breakdown in this range.</p>
					)}
				</div>
			</div>
		</WithVendorAuth>
	);
}
