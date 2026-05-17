"use client";

import Link from "next/link";

export default function FailPage() {
	return (
		<div className="min-h-screen flex items-center justify-center bg-gray-50 px-4">
			<div className="text-center max-w-md">
				<div className="w-20 h-20 mx-auto mb-6 rounded-full bg-amber-100 flex items-center justify-center">
					<svg className="w-10 h-10 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
						<path strokeLinecap="round" strokeLinejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
					</svg>
				</div>
				<h1 className="text-2xl font-bold text-gray-900 mb-2">Payment Failed</h1>
				<p className="text-gray-600 mb-6">
					Something went wrong with your payment. Please try again or choose a different payment method.
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
