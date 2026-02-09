"use client";

import { Check, Copy } from "lucide-react";
import { useState } from "react";

export function InvoicePage() {
	const [copied, setCopied] = useState(false);
	const invoiceId = "SSINV4252";

	const handleCopy = async () => {
		try {
			await navigator.clipboard.writeText(invoiceId);
			setCopied(true);
			setTimeout(() => setCopied(false), 2000);
		} catch (err) {
			console.error("Failed to copy text: ", err);
		}
	};

	const handlePayment = () => {
		// Handle payment logic here
		console.log("Proceeding to payment...");
	};

	return (
		<div className="w-full max-w-md mx-auto bg-white shadow-lg">
			<div className="p-8 text-center space-y-6">
				{/* Logo */}
				<div className="flex items-center justify-center space-x-2">
					<div className="w-8 h-8 bg-pink-600 rounded-md flex items-center justify-center">
						<div className="w-5 h-5 bg-white rounded-sm flex items-center justify-center">
							<div className="w-3 h-3 bg-pink-600 rounded-full"></div>
						</div>
					</div>
					<div>
						<h1 className="text-2xl font-bold text-pink-600">SELFSHOP</h1>
						<p className="text-xs text-gray-500">
							No #1 Reseller Platform in Bangladesh
						</p>
					</div>
				</div>

				{/* Thank you message */}
				<div className="space-y-4">
					<p className="text-gray-700 text-sm leading-relaxed">
						Thank you for selecting your preferred package. We have created an
						Invoice ID for you. Copy the invoice ID by clicking the copy button
						below and go to the PAY NOW option and put your Invoice ID in the
						reference and make the payment now.
					</p>
				</div>

				{/* Invoice ID section */}
				<div className="space-y-4">
					<div className="flex items-center justify-center space-x-3 p-4 bg-gray-50 rounded-lg">
						<span className="text-pink-600 font-bold text-lg">
							INVOICE ID: {invoiceId}
						</span>
						<button
							onClick={handleCopy}
							className="h-8 px-3 border-gray-300 hover:bg-gray-100 bg-transparent"
						>
							{copied ? (
								<Check className="h-4 w-4 text-green-600" />
							) : (
								<Copy className="h-4 w-4" />
							)}
						</button>
					</div>

					<p className="text-gray-600 text-sm">
						Thanks for your order. Please copy the Invoice ID and paste it to
						payment reference box.
					</p>
				</div>

				{/* Payment button */}
				<button
					onClick={handlePayment}
					className="w-full bg-pink-600 hover:bg-pink-700 text-white font-medium py-3 rounded-lg"
				>
					Payment now
				</button>
			</div>
		</div>
	);
}
