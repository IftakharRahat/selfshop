import React from "react";

export const metadata = {
	title: "Return & Refund Policy | SelfShop",
	description:
		"Read SelfShop's return and refund policy to understand eligibility, refund process, and timelines.",
};

export default function ReturnPolicyPage() {
	return (
		<section className="px-4 md:px-8 lg:px-20 py-10 text-gray-800">
			<div className="container mx-auto max-w-4xl bg-white p-6 md:p-10">
				<h1 className="text-3xl font-bold text-center mb-2 text-gray-900">
					Return &amp; Refund Policy
				</h1>
				<p className="text-center text-gray-600 mb-10">
					Thank you for shopping with SelfShop! We always strive to ensure the
					best possible experience for our customers. However, if you are not
					completely satisfied with your purchase, you may request a return or
					refund in accordance with the policy below.
				</p>

				{/* Section 1 - Return Policy */}
				<div className="mb-8">
					<h2 className="text-xl font-semibold mb-4 text-gray-900 border-b pb-2">
						1. Return Policy
					</h2>
					<p className="text-gray-700 mb-3">
						You may apply for a return within{" "}
						<strong>7 days</strong> of receiving your product.
					</p>
					<p className="text-gray-700 mb-3">
						Returns are only accepted under the following conditions:
					</p>
					<ul className="list-disc pl-6 text-gray-700 space-y-2 mb-4">
						<li>Wrong product delivered</li>
						<li>Damaged or defective product</li>
						<li>Product not as described</li>
					</ul>
					<p className="text-gray-700 mb-4">
						The return process usually takes{" "}
						<strong>7–10 working days</strong> to complete after the claim is
						approved.
					</p>
					<div className="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
						<p className="text-sm text-yellow-800 font-medium">
							⚠️ Returned items must be unused, undamaged, and in their
							original packaging.
						</p>
					</div>
				</div>

				{/* Section 2 - Refund Policy */}
				<div className="mb-8">
					<h2 className="text-xl font-semibold mb-4 text-gray-900 border-b pb-2">
						2. Refund Policy
					</h2>
					<ul className="list-disc pl-6 text-gray-700 space-y-2 mb-4">
						<li>
							Refunds are issued only after the returned product has been
							inspected and approved by our quality control team.
						</li>
						<li>
							The refund process will typically be completed within{" "}
							<strong>7–10 working days</strong> after approval.
						</li>
						<li>
							Refunds will be made using the same payment method used during the
							purchase.
						</li>
					</ul>
					<div className="bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
						<p className="text-sm text-blue-800">
							<strong>Note:</strong> For Cash on Delivery (COD) orders, refunds
							will be processed via bank transfer or mobile financial services.
						</p>
					</div>
				</div>

				{/* Section 3 - Cancellation Policy */}
				<div className="mb-8">
					<h2 className="text-xl font-semibold mb-4 text-gray-900 border-b pb-2">
						3. Cancellation Policy
					</h2>
					<ul className="list-disc pl-6 text-gray-700 space-y-2">
						<li>
							You may cancel your order anytime before order confirmation or
							shipment.
						</li>
						<li>
							Once the order has been shipped, cancellation requests will not be
							accepted.
						</li>
					</ul>
				</div>

				{/* Section 4 - No Return / No Refund */}
				<div className="mb-8">
					<h2 className="text-xl font-semibold mb-4 text-gray-900 border-b pb-2">
						4. No Return / No Refund (If Applicable)
					</h2>
					<div className="bg-red-50 border-l-4 border-red-400 p-4 rounded">
						<p className="text-sm text-red-800">
							For certain products, a &ldquo;No Return / No Refund&rdquo;
							policy may apply. If this is the case, it will be clearly
							mentioned in the product description, and such products will not
							be eligible for return or refund.
						</p>
					</div>
				</div>

				{/* Contact */}
				<div className="bg-gray-50 rounded-lg p-6 text-center">
					<h3 className="text-lg font-semibold mb-3 text-gray-900">
						Contact Us
					</h3>
					<p className="text-gray-700 mb-3 text-sm">
						For any questions or concerns regarding returns or refunds, please
						contact us at:
					</p>
					<div className="space-y-1 text-sm">
						<p className="text-gray-700">
							📧{" "}
							<a
								href="mailto:support@selfshop.com"
								className="text-pink-600 hover:underline"
							>
								support@selfshop.com
							</a>
						</p>
						<p className="text-gray-700">
							📞{" "}
							<a
								href="tel:+8801976367981"
								className="text-pink-600 hover:underline"
							>
								+8801976367981
							</a>
						</p>
						<p className="text-gray-700">
							🌐{" "}
							<a
								href="https://www.selfshop.com"
								className="text-pink-600 hover:underline"
								target="_blank"
								rel="noopener noreferrer"
							>
								www.selfshop.com
							</a>
						</p>
					</div>
				</div>
			</div>
		</section>
	);
}
