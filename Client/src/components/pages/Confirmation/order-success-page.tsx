"use client";

import { Check, Copy } from "lucide-react";
import { useState } from "react";

interface OrderSuccessPageProps {
	orderId?: string;
	customerPhone?: string;
	onSaveProduct?: () => void;
}

export default function OrderSuccessPage({
	orderId = "SS00142",
	customerPhone = "01976367981",
	onSaveProduct,
}: OrderSuccessPageProps) {
	const [copied, setCopied] = useState(false);

	const handleCopyOrderId = async () => {
		try {
			await navigator.clipboard.writeText(orderId);
			setCopied(true);
			setTimeout(() => setCopied(false), 2000);
		} catch (err) {
			console.error("Failed to copy order ID:", err);
		}
	};

	const handleSaveProduct = () => {
		if (onSaveProduct) {
			onSaveProduct();
		}
	};

	return (
		<div className="min-h-screen bg-gray-50 flex items-center justify-center px-4 py-8">
			<div className="max-w-2xl w-full bg-white rounded-lg shadow-sm p-8 md:p-12 text-center">
				{/* Success Icon */}
				<div className="flex justify-center mb-8">
					<div className="w-20 h-20 bg-green-500 rounded-full flex items-center justify-center relative">
						{/* Star-like border effect */}
						<div
							className="absolute inset-0 bg-green-500 rounded-full transform rotate-12"
							style={{
								clipPath:
									"polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%)",
							}}
						></div>
						<Check
							className="w-10 h-10 text-white relative z-10"
							strokeWidth={3}
						/>
					</div>
				</div>

				{/* Main Message */}
				<h1 className="text-2xl md:text-3xl font-bold text-gray-900 mb-6">
					Thank you! Your order has been successfully completed.
				</h1>

				{/* Customer Greeting */}
				<p className="text-lg text-gray-700 mb-6">Dear customer</p>

				{/* Order Details */}
				<p className="text-gray-600 mb-6 leading-relaxed">
					Your order has been received and we are working to process it as soon
					as possible. Our team will update you on your order shortly.
				</p>

				{/* Support Message */}
				<p className="text-gray-600 mb-6 leading-relaxed">
					We appreciate your trust and support. If you have any questions or
					need assistance, please contact our customer support.
				</p>

				{/* Closing Message */}
				<p className="text-gray-700 font-medium mb-6">
					Thanks for staying with SelfShop!
				</p>

				{/* Contact Information */}
				<p className="text-gray-600 mb-8">
					For any needs, call:{" "}
					<span className="text-pink-600 font-medium">{customerPhone}</span>
				</p>

				{/* Order ID */}
				<div className="flex items-center justify-center gap-2 mb-8">
					<span className="text-gray-600">ORDER ID:</span>
					<span className="font-medium text-gray-900">{orderId}</span>
					<button
						onClick={handleCopyOrderId}
						className="p-1 hover:bg-gray-100 rounded transition-colors"
						title={copied ? "Copied!" : "Copy Order ID"}
					>
						<Copy className="w-4 h-4 text-gray-500" />
					</button>
					{copied && (
						<span className="text-sm text-green-600 ml-2">Copied!</span>
					)}
				</div>

				{/* Save Product Button */}
				<button
					onClick={handleSaveProduct}
					className="bg-pink-600 hover:bg-pink-700 text-white font-medium px-8 py-3 rounded-lg transition-colors"
				>
					Save the product
				</button>
			</div>
		</div>
	);
}
