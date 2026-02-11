"use client";

import { useParams } from "next/navigation";
import Link from "next/link";
import WithVendorAuth from "../../../WithVendorAuth";
import { useGetVendorProductReviewsQuery } from "@/redux/api/vendorApi";

function Stars({ rating, size = "sm" }: { rating: number; size?: "sm" | "md" }) {
	const w = size === "md" ? "w-5 h-5" : "w-4 h-4";
	return (
		<span className="inline-flex gap-0.5 text-amber-400">
			{[1, 2, 3, 4, 5].map((i) => (
				<svg
					key={i}
					className={`${w} ${i <= Math.round(rating) ? "fill-current" : "text-gray-300 fill-current"}`}
					viewBox="0 0 20 20"
				>
					<path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.957a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.957c.3.921-.755 1.688-1.54 1.118l-3.37-2.448a1 1 0 00-1.176 0l-3.37 2.448c-.784.57-1.838-.197-1.539-1.118l1.287-3.957a1 1 0 00-.364-1.118L2.063 9.384c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 00.95-.69l1.286-3.957z" />
				</svg>
			))}
		</span>
	);
}

export default function VendorProductReviewsPage() {
	const params = useParams();
	const productId = Number(params?.id);
	const { data, isLoading, error } = useGetVendorProductReviewsQuery(productId, {
		skip: !productId || isNaN(productId),
	});

	const product = data?.data?.product;
	const reviews = data?.data?.reviews ?? [];
	const avgRating = data?.data?.avg_rating ?? 0;
	const reviewCount = data?.data?.review_count ?? 0;

	if (isLoading) {
		return (
			<WithVendorAuth>
				<p className="text-gray-500 p-6">Loading reviews...</p>
			</WithVendorAuth>
		);
	}

	if (error || !product) {
		return (
			<WithVendorAuth>
				<p className="text-red-600 p-6">Product not found or no reviews.</p>
				<Link href="/vendor/reviews" className="text-blue-600 hover:underline ml-6">Back to reviews</Link>
			</WithVendorAuth>
		);
	}

	return (
		<WithVendorAuth>
			<div className="space-y-6">
				{/* Header */}
				<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100 flex flex-wrap items-center justify-between gap-4">
					<div className="flex items-center gap-4">
						{product.ViewProductImage ? (
							<img
								src={`${process.env.NEXT_PUBLIC_IMAGE_URL}/${product.ViewProductImage}`}
								alt=""
								className="w-14 h-14 rounded-lg object-cover border border-gray-200"
							/>
						) : (
							<div className="w-14 h-14 rounded-lg bg-gray-100" />
						)}
						<div>
							<h1 className="text-xl font-bold text-gray-900">{product.ProductName}</h1>
							<div className="flex items-center gap-2 mt-1">
								<Stars rating={avgRating} size="md" />
								<span className="text-sm text-gray-500">
									{avgRating} avg / {reviewCount} review{reviewCount !== 1 ? "s" : ""}
								</span>
							</div>
						</div>
					</div>
					<Link
						href="/vendor/reviews"
						className="text-sm font-medium text-gray-600 hover:text-gray-900"
					>
						Back to reviews
					</Link>
				</div>

				{/* Reviews list */}
				{reviews.length === 0 ? (
					<div className="rounded-xl bg-white p-8 shadow-sm border border-gray-100 text-center text-gray-500">
						No reviews yet for this product.
					</div>
				) : (
					<div className="space-y-4">
						{reviews.map((review) => (
							<div
								key={review.id}
								className="rounded-xl bg-white p-5 shadow-sm border border-gray-100"
							>
								<div className="flex flex-wrap items-start justify-between gap-3">
									<div className="flex items-center gap-3">
										{review.user?.profile ? (
											<img
												src={`${process.env.NEXT_PUBLIC_IMAGE_URL}/${review.user.profile}`}
												alt=""
												className="w-10 h-10 rounded-full object-cover border border-gray-200"
											/>
										) : (
											<div className="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 text-sm font-semibold">
												{(review.user?.name ?? "?").charAt(0).toUpperCase()}
											</div>
										)}
										<div>
											<p className="font-medium text-gray-900 text-sm">
												{review.user?.name ?? "Anonymous"}
											</p>
											<p className="text-xs text-gray-500">{review.user?.email}</p>
										</div>
									</div>
									<div className="flex items-center gap-2">
										<Stars rating={review.rating} />
										<span
											className={`text-[10px] font-semibold px-1.5 py-0.5 rounded ${
												review.status === "Active"
													? "bg-green-100 text-green-700"
													: "bg-yellow-100 text-yellow-700"
											}`}
										>
											{review.status}
										</span>
									</div>
								</div>

								{review.messages && (
									<p className="mt-3 text-sm text-gray-700 leading-relaxed">
										{review.messages}
									</p>
								)}

								{review.file && (
									<div className="mt-3">
										<img
											src={
												review.file.startsWith("http")
													? review.file
													: `${process.env.NEXT_PUBLIC_IMAGE_URL}/${review.file}`
											}
											alt="Review attachment"
											className="max-w-xs rounded-lg border border-gray-200"
										/>
									</div>
								)}

								<p className="mt-3 text-[11px] text-gray-400">
									{new Date(review.created_at).toLocaleDateString("en-US", {
										year: "numeric",
										month: "long",
										day: "numeric",
									})}
								</p>
							</div>
						))}
					</div>
				)}
			</div>
		</WithVendorAuth>
	);
}
