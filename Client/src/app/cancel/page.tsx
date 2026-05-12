"use client";

import Link from "next/link";

export default function CancelPage() {
	return (
		<div className="min-h-screen flex items-center justify-center bg-gray-50 px-4">
			<div className="text-center max-w-md">
				<div className="w-20 h-20 mx-auto mb-6 rounded-full bg-red-100 flex items-center justify-center">
					<svg className="w-10 h-10 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
						<path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
					</svg>
				</div>
				<h1 className="text-2xl font-bold text-gray-900 mb-2">Payment Cancelled</h1>
				<p className="text-gray-600 mb-6">
					Your payment was cancelled. No charges were made. You can try again or continue shopping.
				</p>
				<div className="space-y-3">
					<Link
						href="/dashboard/orders"
						className="block w-full px-6 py-3 bg-[#2d2a5d] text-white rounded-lg font-medium hover:bg-[#252947] transition-colors"
					>
						View My Orders
					</Link>
					<Link
						href="/"
						className="block w-full px-6 py-3 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-colors"
					>
						Continue Shopping
					</Link>
				</div>
			</div>
		</div>
	);
}
