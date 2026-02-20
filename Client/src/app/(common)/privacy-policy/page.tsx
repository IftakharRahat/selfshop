import React from "react";

export const metadata = {
    title: "Privacy Policy | SelfShop",
    description:
        "Read SelfShop's privacy policy to understand how we collect, use, and protect your personal information.",
};

export default function PrivacyPolicyPage() {
    return (
        <section className="px-4 md:px-8 lg:px-20 py-10 text-gray-800">
            <div className="container mx-auto max-w-4xl bg-white p-6 md:p-10">
                <h1 className="text-3xl font-bold text-center mb-2 text-gray-900">
                    Privacy Policy
                </h1>
                <p className="text-center text-gray-500 text-sm mb-2">
                    selfshop.com.bd
                </p>
                <p className="text-center text-gray-500 text-sm mb-10">
                    Last updated: February 2026
                </p>

                <p className="text-gray-700 leading-relaxed mb-6">
                    selfshop.com.bd (&ldquo;we&rdquo;, &ldquo;us&rdquo;,
                    &ldquo;our&rdquo;) operates a business-to-business (B2B) online
                    platform providing services primarily to companies, merchants,
                    retailers, distributors and other business entities in Bangladesh and
                    internationally.
                </p>
                <p className="text-gray-700 leading-relaxed mb-6">
                    This Privacy Policy explains how we collect, use, disclose, store and
                    protect personal information in the course of our B2B activities. It
                    applies to personal data of individuals who act on behalf of business
                    customers, prospects, suppliers, partners, website visitors and other
                    business contacts (&ldquo;you&rdquo;).
                </p>
                <p className="text-gray-700 leading-relaxed mb-10">
                    We are committed to handling personal information responsibly and in
                    compliance with applicable laws, including the Digital Security Act
                    2018, the Information and Communication Technology Act 2006 (as
                    amended), and relevant Bangladesh regulations. If you are located
                    outside Bangladesh, additional data protection laws (such as GDPR for
                    EU residents) may apply in specific cases.
                </p>

                {/* Section 1 */}
                <div className="mb-8">
                    <h2 className="text-xl font-semibold mb-4 text-gray-900 border-b pb-2">
                        1. Information We Collect
                    </h2>

                    <div className="space-y-5">
                        <div className="bg-gray-50 rounded-lg p-5">
                            <h3 className="text-base font-semibold mb-2 text-gray-900">
                                A. Information you provide directly
                            </h3>
                            <ul className="list-disc pl-6 text-gray-700 space-y-1 text-sm">
                                <li>
                                    Business contact details: name, job title/position, company
                                    name, department, business email address, business phone
                                    number, business address
                                </li>
                                <li>
                                    Account and registration information: username, password,
                                    company registration details, TIN/VAT number (where required)
                                </li>
                                <li>
                                    Payment-related information (handled by secure third-party
                                    payment processors)
                                </li>
                                <li>
                                    Communications: messages, support tickets, emails, chat
                                    records, meeting notes
                                </li>
                                <li>
                                    Uploaded business documents: trade license, partnership deed,
                                    authorization letters (if required for verification)
                                </li>
                                <li>
                                    Any other information you voluntarily provide when using our
                                    services, requesting demos, quotes, support or contacting us
                                </li>
                            </ul>
                        </div>

                        <div className="bg-gray-50 rounded-lg p-5">
                            <h3 className="text-base font-semibold mb-2 text-gray-900">
                                B. Information collected automatically
                            </h3>
                            <ul className="list-disc pl-6 text-gray-700 space-y-1 text-sm">
                                <li>
                                    Device &amp; network information: IP address, browser
                                    type/version, operating system, device identifiers
                                </li>
                                <li>
                                    Usage data: pages visited, time/date of access, features used,
                                    referral source, session duration
                                </li>
                                <li>
                                    Cookies and similar technologies (see our Cookie Policy below)
                                </li>
                            </ul>
                        </div>

                        <div className="bg-gray-50 rounded-lg p-5">
                            <h3 className="text-base font-semibold mb-2 text-gray-900">
                                C. Information from third parties
                            </h3>
                            <ul className="list-disc pl-6 text-gray-700 space-y-1 text-sm">
                                <li>Public business directories and company registries</li>
                                <li>
                                    Payment gateway providers (transaction confirmation only)
                                </li>
                                <li>
                                    Business partners or referrers (with your consent or legitimate
                                    interest)
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                {/* Section 2 */}
                <div className="mb-8">
                    <h2 className="text-xl font-semibold mb-4 text-gray-900 border-b pb-2">
                        2. How We Use Your Information
                    </h2>
                    <ul className="list-disc pl-6 text-gray-700 space-y-2 text-sm">
                        <li>
                            To create, administer and manage business accounts and
                            subscriptions
                        </li>
                        <li>
                            To provide, maintain, improve and personalize our B2B services
                        </li>
                        <li>To process orders, invoices, payments and deliveries</li>
                        <li>
                            To communicate with you about your account, orders, support,
                            updates, service changes
                        </li>
                        <li>To verify business identity and prevent fraud</li>
                        <li>
                            To send important service notices, legal notices and transactional
                            emails
                        </li>
                        <li>
                            To offer product updates, feature announcements, training/webinar
                            invitations (you can opt-out of marketing)
                        </li>
                        <li>
                            To respond to inquiries, support requests and customer service
                        </li>
                        <li>To analyze platform usage and improve user experience</li>
                        <li>
                            To comply with legal obligations, resolve disputes and enforce
                            agreements
                        </li>
                        <li>
                            For internal business purposes (accounting, auditing, reporting,
                            security)
                        </li>
                    </ul>
                </div>

                {/* Section 3 */}
                <div className="mb-8">
                    <h2 className="text-xl font-semibold mb-4 text-gray-900 border-b pb-2">
                        3. Legal Basis for Processing
                    </h2>
                    <ul className="list-disc pl-6 text-gray-700 space-y-2 text-sm">
                        <li>
                            Performance of a contract (your business agreement/subscription
                            with us)
                        </li>
                        <li>
                            Legitimate business interests (B2B contact management, service
                            delivery, fraud prevention, direct marketing of similar services)
                        </li>
                        <li>Compliance with legal obligations</li>
                        <li>
                            Consent (where we specifically ask for it – e.g. marketing
                            newsletters)
                        </li>
                    </ul>
                </div>

                {/* Section 4 */}
                <div className="mb-8">
                    <h2 className="text-xl font-semibold mb-4 text-gray-900 border-b pb-2">
                        4. Sharing of Information
                    </h2>
                    <p className="text-gray-700 mb-3 text-sm">
                        We share personal information only in these limited circumstances:
                    </p>
                    <ul className="list-disc pl-6 text-gray-700 space-y-2 text-sm">
                        <li>
                            With service providers who assist us (hosting, cloud storage,
                            email service, payment processors, analytics, customer support
                            tools) – all bound by strict confidentiality
                        </li>
                        <li>
                            With logistics/delivery partners to fulfill orders
                        </li>
                        <li>
                            With banks/payment gateways for transaction processing
                        </li>
                        <li>
                            To comply with law enforcement, court orders, regulatory
                            authorities or legal processes
                        </li>
                        <li>
                            In connection with a merger, acquisition, asset sale or
                            reorganization
                        </li>
                        <li>With your explicit consent or direction</li>
                    </ul>
                    <div className="bg-green-50 border-l-4 border-green-400 p-4 rounded mt-4">
                        <p className="text-sm text-green-800 font-medium">
                            ✅ We do not sell personal information to third parties for their
                            own marketing purposes.
                        </p>
                    </div>
                </div>

                {/* Section 5 */}
                <div className="mb-8">
                    <h2 className="text-xl font-semibold mb-4 text-gray-900 border-b pb-2">
                        5. Data Storage &amp; International Transfers
                    </h2>
                    <p className="text-gray-700 text-sm leading-relaxed">
                        Your data is primarily stored in secure servers. If data is
                        transferred outside Bangladesh, we ensure appropriate safeguards are
                        in place.
                    </p>
                </div>

                {/* Section 6 */}
                <div className="mb-8">
                    <h2 className="text-xl font-semibold mb-4 text-gray-900 border-b pb-2">
                        6. Data Retention
                    </h2>
                    <p className="text-gray-700 mb-3 text-sm">
                        We retain personal information only as long as necessary for the
                        purposes outlined in this policy or as required by law:
                    </p>
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div className="bg-gray-50 p-4 rounded-lg text-center">
                            <p className="font-semibold text-gray-900 text-sm">
                                Active Account Data
                            </p>
                            <p className="text-gray-600 text-xs mt-1">
                                During relationship + 2–5 years
                            </p>
                        </div>
                        <div className="bg-gray-50 p-4 rounded-lg text-center">
                            <p className="font-semibold text-gray-900 text-sm">
                                Marketing Data
                            </p>
                            <p className="text-gray-600 text-xs mt-1">
                                Until you unsubscribe
                            </p>
                        </div>
                        <div className="bg-gray-50 p-4 rounded-lg text-center">
                            <p className="font-semibold text-gray-900 text-sm">
                                Logs &amp; Security
                            </p>
                            <p className="text-gray-600 text-xs mt-1">6–24 months</p>
                        </div>
                    </div>
                    <p className="text-gray-600 text-xs mt-3">
                        After the retention period, data is securely deleted or anonymized.
                    </p>
                </div>

                {/* Section 7 */}
                <div className="mb-8">
                    <h2 className="text-xl font-semibold mb-4 text-gray-900 border-b pb-2">
                        7. Security
                    </h2>
                    <p className="text-gray-700 text-sm leading-relaxed">
                        We implement commercially reasonable technical, administrative and
                        physical security measures to protect personal information from
                        unauthorized access, loss, misuse or alteration. However, no method
                        of transmission over the Internet or electronic storage is 100%
                        secure.
                    </p>
                </div>

                {/* Section 8 */}
                <div className="mb-8">
                    <h2 className="text-xl font-semibold mb-4 text-gray-900 border-b pb-2">
                        8. Your Rights
                    </h2>
                    <p className="text-gray-700 mb-3 text-sm">
                        Depending on applicable law you may have the right to:
                    </p>
                    <ul className="list-disc pl-6 text-gray-700 space-y-1 text-sm">
                        <li>Access your personal data</li>
                        <li>Correct inaccurate data</li>
                        <li>
                            Request deletion (subject to legal retention obligations)
                        </li>
                        <li>Object to or restrict certain processing</li>
                        <li>
                            Withdraw consent (where processing is based on consent)
                        </li>
                        <li>Data portability (where technically feasible)</li>
                    </ul>
                    <p className="text-gray-700 text-sm mt-3">
                        To exercise these rights, contact us at{" "}
                        <a
                            href="mailto:privacy@selfshop.com.bd"
                            className="text-pink-600 hover:underline"
                        >
                            privacy@selfshop.com.bd
                        </a>
                        . We will respond within reasonable time (usually 30 days).
                    </p>
                </div>

                {/* Section 9 */}
                <div className="mb-8">
                    <h2 className="text-xl font-semibold mb-4 text-gray-900 border-b pb-2">
                        9. Cookies &amp; Tracking Technologies
                    </h2>
                    <p className="text-gray-700 text-sm leading-relaxed">
                        We use cookies and similar technologies for essential functionality,
                        performance, analytics and (if enabled) marketing. You can manage
                        preferences via our cookie banner or browser settings.
                    </p>
                </div>

                {/* Section 10 */}
                <div className="mb-8">
                    <h2 className="text-xl font-semibold mb-4 text-gray-900 border-b pb-2">
                        10. Children&apos;s Privacy
                    </h2>
                    <p className="text-gray-700 text-sm leading-relaxed">
                        Our services are not directed to individuals under 18 years of age.
                        We do not knowingly collect personal information from children.
                    </p>
                </div>

                {/* Section 11 */}
                <div className="mb-8">
                    <h2 className="text-xl font-semibold mb-4 text-gray-900 border-b pb-2">
                        11. Changes to This Privacy Policy
                    </h2>
                    <p className="text-gray-700 text-sm leading-relaxed">
                        We may update this policy from time to time. The updated version
                        will be posted on this page with a revised &ldquo;Last
                        updated&rdquo; date. We encourage you to review it periodically.
                    </p>
                </div>

                {/* Section 12 */}
                <div className="bg-gray-50 rounded-lg p-6 text-center">
                    <h2 className="text-xl font-semibold mb-3 text-gray-900">
                        12. Contact Us
                    </h2>
                    <p className="text-gray-700 text-sm mb-2">
                        If you have questions about this Privacy Policy or our data
                        practices, please contact:
                    </p>
                    <p className="font-semibold text-gray-900 text-sm">Privacy Officer</p>
                    <p className="text-gray-700 text-sm">selfshop.com.bd</p>
                    <p className="text-gray-700 text-sm">
                        📧{" "}
                        <a
                            href="mailto:privacy@selfshop.com.bd"
                            className="text-pink-600 hover:underline"
                        >
                            privacy@selfshop.com.bd
                        </a>
                    </p>
                </div>
            </div>
        </section>
    );
}
