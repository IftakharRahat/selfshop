import React from "react";

export const metadata = {
  title: "Return Policy | SelfShop",
  description:
    "Read SelfShop’s return and refund policy to understand eligibility, refund process, and timelines.",
};

export default function ReturnPolicyPage() {
  return (
    <section className="px-4 md:px-8 lg:px-20 py-10 text-gray-800">
      <div className="container mx-auto bg-white  p-6 md:p-10">
        <h1 className="text-3xl font-bold text-center mb-6 text-gray-900">
          Return Policy
        </h1>
        <p className="text-center text-gray-600 mb-10">
          Thank you for shopping at SelfShop. We appreciate your business and
          want to ensure your satisfaction with our products. Please read the
          following refund policy carefully.
        </p>

        {/* Section 1 */}
        <div className="mb-8">
          <h2 className="text-xl font-semibold mb-4 text-gray-900">
            1. Eligibility for Refund:
          </h2>
          <ul className="space-y-2 list-disc pl-6 text-gray-700">
            <li>
              To be eligible for a refund, the item must be unused and in the
              same condition that you received it. It must also be in the
              original packaging.
            </li>
            <li>
              Items that are damaged, used, or not in their original condition
              may not be eligible for a refund.
            </li>
            <li>
              Refund requests must be made within <strong>7 days</strong> of
              receiving the product.
            </li>
            <li>Account subscriptions are not refundable.</li>
          </ul>
        </div>

        {/* Section 2 */}
        <div className="mb-8">
          <h2 className="text-xl font-semibold mb-4 text-gray-900">
            2. Refund Process:
          </h2>
          <ul className="space-y-2 list-disc pl-6 text-gray-700">
            <li>
              To initiate a refund, please contact our customer service team at{" "}
              <a
                href="mailto:support@selfshop.com.bd"
                className="text-blue-600 hover:underline"
              >
                support@selfshop.com.bd
              </a>{" "}
              or call{" "}
              <a
                href="tel:01976367981"
                className="text-blue-600 hover:underline"
              >
                01976367981
              </a>{" "}
              with your order number and details of the issue.
            </li>
            <li>
              Our team will review your request and notify you of the approval
              or rejection of your refund.
            </li>
            <li>
              If your refund is approved, it will be processed, and a credit
              will automatically be applied to your original method of payment
              within <strong>14 days</strong>.
            </li>
          </ul>
        </div>

        {/* Section 3 */}
        <div className="mb-8">
          <h2 className="text-xl font-semibold mb-4 text-gray-900">
            3. Late or Missing Refunds:
          </h2>
          <ul className="space-y-2 list-disc pl-6 text-gray-700">
            <li>
              If you haven’t received a refund within the specified time frame (
              <strong>5 to 7 working days</strong> after initiating refund),
              please check your bank or Bkash account again and contact your
              card issuer bank or MFS. It may take some time before your refund
              is officially posted.
            </li>
            <li>
              If you’ve done all of this and still haven’t received your refund,
              please contact us at{" "}
              <a
                href="mailto:refund@selfshop.com.bd"
                className="text-blue-600 hover:underline"
              >
                refund@selfshop.com.bd
              </a>
              .
            </li>
          </ul>
        </div>

        {/* Section 4 */}
        <div>
          <h2 className="text-xl font-semibold mb-4 text-gray-900">
            4. Changes to this Refund Policy:
          </h2>
          <ul className="space-y-2 list-disc pl-6 text-gray-700">
            <li>
              We reserve the right to modify this refund policy at any time.
              Changes and clarifications will take effect immediately upon
              posting on our website.
            </li>
            <li>
              By making a purchase on our website, you agree to and accept the
              terms of this refund policy. If you have any questions or
              concerns, please contact our customer service team.
            </li>
          </ul>
        </div>
      </div>
    </section>
  );
}
