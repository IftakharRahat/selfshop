"use client";

import { useSearchParams } from "next/navigation";
import Link from "next/link";

export default function CheckoutPage() {
	const searchParams = useSearchParams();
	const payment = searchParams.get("payment");

	if (payment === "canceled") {
		return (
			<div className="min-h-screen flex items-center justify-center bg-gray-50 px-4">
				<div className="text-center max-w-md">
					<div className="text-5xl mb-4">❌</div>
					<h1 className="text-2xl font-bold text-gray-900 mb-2">Payment Cancelled</h1>
					<p className="text-gray-600 mb-6">
						Your payment was cancelled. No charges were made. You can try again or continue shopping.
					</p>
					<div className="flex gap-3 justify-center">
						<Link href="/" className="px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
							Continue Shopping
						</Link>
					</div>
				</div>
			</div>
		);
	}

	if (payment === "failed") {
		return (
			<div className="min-h-screen flex items-center justify-center bg-gray-50 px-4">
				<div className="text-center max-w-md">
					<div className="text-5xl mb-4">⚠️</div>
					<h1 className="text-2xl font-bold text-gray-900 mb-2">Payment Failed</h1>
					<p className="text-gray-600 mb-6">
						Something went wrong with your payment. Please try again or choose a different payment method.
					</p>
					<div className="flex gap-3 justify-center">
						<Link href="/" className="px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
							Continue Shopping
						</Link>
					</div>
				</div>
			</div>
		);
	}

	// Default: redirect to home
	return (
		<div className="min-h-screen flex items-center justify-center bg-gray-50 px-4">
			<div className="text-center max-w-md">
				<h1 className="text-2xl font-bold text-gray-900 mb-4">Checkout</h1>
				<p className="text-gray-600 mb-6">Please proceed from your cart.</p>
				<Link href="/" className="px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
					Go to Home
				</Link>
			</div>
		</div>
	);
}

