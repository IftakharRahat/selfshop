"use client";

import { useState } from "react";
import Link from "next/link";
import WithVendorAuth from "../../vendor/WithVendorAuth";
import { useGetVendorReviewProductsQuery } from "@/redux/api/vendorApi";

function Stars({ rating }: { rating: number }) {
	return (
		<span className="inline-flex gap-0.5 text-amber-400">
			{[1, 2, 3, 4, 5].map((i) => (
				<svg
					key={i}
					className={`w-4 h-4 ${i <= Math.round(rating) ? "fill-current" : "text-gray-300 fill-current"}`}
					viewBox="0 0 20 20"
				>
					<path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.957a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.957c.3.921-.755 1.688-1.54 1.118l-3.37-2.448a1 1 0 00-1.176 0l-3.37 2.448c-.784.57-1.838-.197-1.539-1.118l1.287-3.957a1 1 0 00-.364-1.118L2.063 9.384c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 00.95-.69l1.286-3.957z" />
				</svg>
			))}
		</span>
	);
}

export default function VendorReviewsPage() {
	const [search, setSearch] = useState("");
	const [ratingFilter, setRatingFilter] = useState("");
	const { data, isLoading } = useGetVendorReviewProductsQuery(
		{
			...(search ? { search } : {}),
			...(ratingFilter ? { rating: ratingFilter } : {}),
		}
	);
	const products = data?.data?.products ?? [];

	return (
		<WithVendorAuth>
			<div className="space-y-6">
				{/* Header */}
				<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
					<h1 className="text-2xl font-bold text-gray-900 mb-1">
						All Rating &amp; Reviews
					</h1>
				</div>

				{/* Table */}
				<div className="rounded-xl bg-white shadow-sm border border-gray-100 overflow-hidden">
					<div className="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-b border-gray-100">
						<h2 className="text-lg font-semibold text-gray-900">
							Product Review &amp; Ratings
						</h2>
						<div className="flex items-center gap-3">
							<select
								value={ratingFilter}
								onChange={(e) => setRatingFilter(e.target.value)}
								className="rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
							>
								<option value="">Filter by Rating</option>
								<option value="5">5 Stars</option>
								<option value="4">4 Stars</option>
								<option value="3">3 Stars</option>
								<option value="2">2 Stars</option>
								<option value="1">1 Star</option>
							</select>
							<input
								type="text"
								placeholder="Type Product Name & Hit E..."
								value={search}
								onChange={(e) => setSearch(e.target.value)}
								className="rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 w-56"
							/>
						</div>
					</div>

					{isLoading ? (
						<p className="text-center text-gray-500 py-10 text-sm">Loading reviews...</p>
					) : products.length === 0 ? (
						<p className="text-center text-gray-500 py-10 text-sm">No product reviews found.</p>
					) : (
						<div className="overflow-x-auto">
							<table className="min-w-full text-sm">
								<thead>
									<tr className="border-b border-gray-100 bg-gray-50 text-gray-600 text-xs uppercase tracking-wider">
										<th className="px-6 py-3 text-left font-semibold w-12">#</th>
										<th className="px-6 py-3 text-left font-semibold">Product Name</th>
										<th className="px-6 py-3 text-center font-semibold">Rating</th>
										<th className="px-6 py-3 text-center font-semibold">Reviews</th>
										<th className="px-6 py-3 text-center font-semibold w-36">Options</th>
									</tr>
								</thead>
								<tbody>
									{products.map((p, idx) => (
										<tr key={p.id} className="border-b border-gray-50 hover:bg-gray-50/50">
											<td className="px-6 py-4 text-gray-700">{idx + 1}</td>
											<td className="px-6 py-4">
												<div className="flex items-center gap-3">
													{p.ViewProductImage ? (
														<img
															src={`${process.env.NEXT_PUBLIC_IMAGE_URL}/${p.ViewProductImage}`}
															alt=""
															className="w-10 h-10 rounded-md object-cover border border-gray-200"
														/>
													) : (
														<div className="w-10 h-10 rounded-md bg-gray-100" />
													)}
													<span className="font-medium text-gray-900">{p.ProductName}</span>
												</div>
											</td>
											<td className="px-6 py-4 text-center">
												<div className="flex items-center justify-center gap-1.5">
													<Stars rating={p.avg_rating} />
													<span className="text-gray-500 text-xs">({p.avg_rating})</span>
												</div>
											</td>
											<td className="px-6 py-4 text-center">
												<span className="text-gray-900 font-medium">{p.review_count}</span>
												{p.new_count > 0 && (
													<span className="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-red-100 text-red-700">
														{p.new_count} new
													</span>
												)}
											</td>
											<td className="px-6 py-4 text-center">
												<Link
													href={`/vendor/reviews/${p.id}`}
													className="inline-flex items-center px-4 py-1.5 rounded-md bg-gray-900 text-white text-xs font-medium hover:bg-gray-800 transition-colors"
												>
													View Reviews
												</Link>
											</td>
										</tr>
									))}
								</tbody>
							</table>
						</div>
					)}
				</div>
			</div>
		</WithVendorAuth>
	);
}
