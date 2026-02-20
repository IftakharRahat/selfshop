import React from "react";

export const metadata = {
    title: "Terms & Conditions | SelfShop",
    description:
        "Read SelfShop's terms and conditions including delivery policy, return policy, and subscription policy.",
};

export default function TermsAndConditionsPage() {
    return (
        <section className="px-4 md:px-8 lg:px-20 py-10 text-gray-800">
            <div className="container mx-auto max-w-4xl bg-white p-6 md:p-10">
                <h1 className="text-3xl font-bold text-center mb-2 text-gray-900">
                    Terms &amp; Conditions
                </h1>
                <p className="text-center text-gray-500 text-sm mb-10">
                    Last updated: February 2026
                </p>

                {/* Delivery Policy */}
                <div className="mb-10">
                    <h2 className="text-2xl font-bold mb-4 text-gray-900 border-b pb-2">
                        Delivery Policy
                    </h2>
                    <p className="text-gray-700 mb-6 leading-relaxed">
                        Thank you for choosing SelfShop as your trusted online shopping
                        platform. This Delivery Policy outlines the terms and conditions
                        regarding the delivery of products purchased through our platform.
                        By placing an order with us, you agree to comply with and be bound
                        by the following policies.
                    </p>

                    <div className="space-y-6">
                        <div>
                            <h3 className="text-lg font-semibold mb-2 text-gray-900">
                                Order Processing Time
                            </h3>
                            <ul className="list-disc pl-6 text-gray-700 space-y-1">
                                <li>
                                    Orders are typically processed within 1-2 business days from
                                    the date of purchase.
                                </li>
                                <li>
                                    Processing times may vary depending on product availability or
                                    peak seasons.
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h3 className="text-lg font-semibold mb-2 text-gray-900">
                                Delivery Timeframe
                            </h3>
                            <ul className="list-disc pl-6 text-gray-700 space-y-1">
                                <li>
                                    We aim to deliver orders within 3 to 7 business days after
                                    processing.
                                </li>
                                <li>
                                    Delivery times may vary due to location, courier service, or
                                    other factors.
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h3 className="text-lg font-semibold mb-2 text-gray-900">
                                Delivery Methods
                            </h3>
                            <p className="text-gray-700 mb-2">
                                We offer multiple delivery options based on the nature of your
                                order:
                            </p>
                            <ul className="list-disc pl-6 text-gray-700 space-y-1">
                                <li>
                                    <strong>Standard Shipping:</strong> Estimated delivery within
                                    3-7 business days.
                                </li>
                                <li>
                                    <strong>Express Shipping:</strong> Faster delivery at an
                                    additional charge (if available).
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h3 className="text-lg font-semibold mb-2 text-gray-900">
                                Shipping Address
                            </h3>
                            <ul className="list-disc pl-6 text-gray-700 space-y-1">
                                <li>
                                    Customers must provide an accurate and complete shipping
                                    address.
                                </li>
                                <li>
                                    SelfShop is not responsible for orders delivered to incorrect
                                    addresses provided by the customer.
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h3 className="text-lg font-semibold mb-2 text-gray-900">
                                Shipping Restrictions
                            </h3>
                            <ul className="list-disc pl-6 text-gray-700 space-y-1">
                                <li>
                                    Some products may have shipping restrictions based on
                                    geographic location or local regulations.
                                </li>
                                <li>
                                    Customers must verify and comply with these restrictions
                                    before placing an order.
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h3 className="text-lg font-semibold mb-2 text-gray-900">
                                Delivery Confirmation
                            </h3>
                            <ul className="list-disc pl-6 text-gray-700 space-y-1">
                                <li>
                                    Once your order is delivered, you will receive a confirmation
                                    email or SMS with relevant details, including tracking
                                    information (if applicable).
                                </li>
                                <li>
                                    Digital products will be delivered via email or through your
                                    SelfShop account.
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h3 className="text-lg font-semibold mb-2 text-gray-900">
                                Shipping Delays
                            </h3>
                            <p className="text-gray-700">
                                While we strive to meet delivery timelines, external factors
                                such as weather, customs delays, or logistical issues may cause
                                delays. We appreciate your patience in such cases.
                            </p>
                        </div>

                        <div>
                            <h3 className="text-lg font-semibold mb-2 text-gray-900">
                                Returns Due to Non-Delivery
                            </h3>
                            <p className="text-gray-700">
                                If an order is returned due to non-delivery (e.g., incorrect
                                address), the customer will be responsible for any additional
                                shipping costs to resend the order.
                            </p>
                        </div>
                    </div>
                </div>

                {/* Terms & Conditions (Bangla) */}
                <div className="mb-10">
                    <h2 className="text-2xl font-bold mb-4 text-gray-900 border-b pb-2">
                        Terms &amp; Conditions (শর্তাবলী)
                    </h2>
                    <p className="text-gray-700 mb-6 leading-relaxed">
                        একজন ড্রপ-শিপার হিসাবে আপনাকে নিজের মত করে আপনার ষ্টোরের জন্য
                        (নিজ নিজ ষ্টোরেরে জন্য) প্রোডাক্ট Delivery &amp; Return করার জন্য
                        একটি পলিসি সেট করে নিতে হবে এবং তা প্রতিটি কাস্টমারকে অর্ডারের
                        আগেই জানিয়ে দিতে হবে যাতে প্রতিটি কাস্টমার আগে থেকেই বিষয়গুলো
                        সম্পর্কে অবগত হন। মনে রাখবেন শুধু অর্ডার নেয়ার মধ্যেই সীমাবদ্ধ
                        না থেকে কাস্টমারের সাথে ট্রান্সপারেন্টভাবে তথ্য শেয়ার করার
                        ব্যাপারে অবশ্যই সচেতন থাকবেন।
                    </p>

                    <div className="space-y-6">
                        <div className="bg-gray-50 rounded-lg p-5">
                            <h3 className="text-lg font-semibold mb-2 text-gray-900">
                                প্রোডাক্ট ডেলিভারি
                            </h3>
                            <p className="text-gray-700 leading-relaxed">
                                ড্রপ-শপের সকল পার্সেল ক্লোজড বক্স ডেলিভারি হবে অর্থাৎ
                                ডেলিভারির সময় আগে পেমেন্ট করে পার্সেল রিসিভ করতে হবে এবং
                                ডেলিভারির সময় প্রোডাক্ট চেক করে দেখে পছন্দ হলে নেবে পছন্দ না
                                হলে ডেলিভারি চার্জ দিয়ে প্রোডাক্ট রিটার্ন করতে পারবে। আপনি
                                কাস্টোমারকে আগেই জানিয়ে দেবেন কোন সমস্যা হলে জেন আপনার সাথে
                                সরাসরি যোগাযোগ করেন।
                            </p>
                        </div>

                        <div className="bg-gray-50 rounded-lg p-5">
                            <h3 className="text-lg font-semibold mb-2 text-gray-900">
                                রিটার্ন পলিসি
                            </h3>
                            <p className="text-gray-700 leading-relaxed mb-4">
                                প্রোডাক্ট ডেলিভারি পাবার পর বাসায় নিয়ে অবশ্যই ফুল আনবক্সিং
                                ভিডিও করতে হবে। প্যাকেজে কোন কিছু মিসিং বা ভুল প্রোডাক্ট
                                ডেলিভারি হলে এই ভিডিও প্রুফ হিসাবে আমাদের পাঠাতে হবে এবং
                                ইনভেস্টিগেট করে যথাযথ ব্যাবস্থা নেয়া হবে। আমাদের দিক থেকে
                                ভুল প্রোডাক্ট ডেলিভারি হলে আমরা নিজ খরচে রিপ্লেস করে দেবো।
                            </p>
                            <p className="text-gray-700 leading-relaxed mb-4">
                                যে সকল প্রোডাক্টের ওয়ারেন্টি থাকে যেমন ৩ মাস, ৬ মাস, ১ বছর
                                ইত্যাদি সেগুলিসহ সকল প্রোডাক্ট ইনভয়েস ডেট থেকে ৭ দিনের
                                মধ্যে প্রোডাক্টের কোন ফল্ট প্রমাণিত হলে তা আমাদের কাছে
                                সুন্দরবন কুরিয়ার বা পাঠাও বা রেডেএক্স বা ই-কুরিয়ার বা
                                স্টিডফাস্ট বা অন্য যেকোনো কুরিয়ার যা অফিস ডেলিভারি করে এমন
                                কুরিয়ার দিয়ে পাঠাতে হবে।
                            </p>

                            <div className="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded mb-4">
                                <p className="text-sm text-yellow-800 font-medium">
                                    ⚠️ নোটঃ প্রোডাক্ট পাঠানোর আগে অবশ্যই ডেলিভারির সময় সাথে
                                    যা যা পেয়েছে সব কিছু এবং সুন্দর করে বক্স করে র্যাপিং করে
                                    পাঠাতে হবে। প্রোডাক্টের বক্সে টেপ লাগানো যাবে না। বক্স না
                                    থাকলে প্রয়োজনে পত্রিকার কাগজ দিয়ে তার উপড়ে টেপ লাগিয়ে
                                    কুরিয়ারে পাঠাতে হবে। ভালভাবে প্যাকিং না করলে প্রোডাক্ট বা
                                    বক্স নষ্ট হলে বা রিসেলেবল কন্ডিশনে না থাকলে রিটার্ন
                                    রিকোয়েস্ট একসেপ্ট করা হবেনা।
                                </p>
                            </div>
                        </div>

                        <div className="bg-red-50 rounded-lg p-5 border border-red-100">
                            <h3 className="text-lg font-semibold mb-3 text-gray-900">
                                যে সকল ক্ষেত্রে প্রোডাক্ট রিটার্ন, এক্সচেঞ্জ, ওয়ারেন্টি
                                এবং রিফান্ড প্রযোজ্য হবে না
                            </h3>
                            <ul className="list-disc pl-6 text-gray-700 space-y-2">
                                <li>
                                    প্রোডাক্ট এ কোন প্রকার বার্ন বা ফিজিক্যাল ড্যামেজ হয়ে
                                    থাকলে ওয়ারেন্টি পাবেন না।
                                </li>
                                <li>
                                    যদি প্রোডাক্ট এর ইন্ট্যাক্ট এর সিল বা স্টিকার তুলে ফেলা
                                    হয় সেক্ষেত্রে ওয়ারেন্টি পলিসি অনুযায়ী ওয়ারেন্টি। যেমন-
                                    প্রোডাক্ট এর ১ বছরের ওয়ারেন্টি থাকলে এর মধ্যে কোন সমস্যা
                                    থাকলে সেটি আফটার সেলস সার্ভিস ওয়ারেন্টি পাবেন কিন্তু
                                    রিটার্ন বা এক্সচেঞ্জ বা পছন্দ হয়নি এমন কোন কারনে রিটার্ন
                                    বা রিফান্ড বা এক্সচেঞ্জ প্রযোজ্য হবে না।
                                </li>
                                <li>
                                    প্রোডাক্টে এর গায়ে কোন স্ক্র্যাচ বা দাগ বা আঠা বা
                                    রিসেলেবল কন্ডিশনে না থাকলে ওয়ারেন্টি পাবে না।
                                </li>
                                <li>
                                    প্রোডাক্ট এর সাথে যেকোনো ধরনের এক্সেসরিস বা চার্জার বা
                                    এডাপ্টার এর কোন ওয়ারেন্টি পাবেন না।
                                </li>
                                <li>
                                    যেকোনো গিফট আইটেম বা পুরষ্কার যা বিনামূল্যে দেয়া হয়েছে
                                    তার কোন প্রকার ওয়ারেন্টি পাবেন না।
                                </li>
                                <li>
                                    থার্ড পার্টি যেকোনো হার্ডওয়্যার বা ডিভাইস বা অ্যাপ বা
                                    সফটওয়্যার এর সাথে কম্প্যাটিবিলিটি ইস্যু যা প্রোডাক্ট এর
                                    ডিফল্ট ফিচার নয় এমন ক্ষেত্রে প্রোডাক্ট যদি স্ট্যান্ডার্ড
                                    অন্য ডিভাইসে কাজ করে কিন্তু স্পেসিফিক কোন একটি ডিভাইসে
                                    কাজ না করলে এমন ক্ষেত্রে প্রোডাক্ট রিটার্ন বা এক্সচেঞ্জ
                                    সুবিধা পাবেন না।
                                </li>
                            </ul>
                        </div>

                        {/* Warranty Steps */}
                        <div>
                            <h3 className="text-lg font-semibold mb-3 text-gray-900">
                                ওয়ারেন্টি/রিটার্ন প্রসেস
                            </h3>
                            <div className="space-y-3">
                                {[
                                    {
                                        step: "স্টেপ ১",
                                        text: "প্রোডাক্ট আমাদের কাছে পাঠানোর পরে অবশ্যই বুকিং এর স্লিপ কাস্টমার থেকে নিয়ে আমাদের WhatsApp করবেন 01976367981.",
                                    },
                                    {
                                        step: "স্টেপ ২",
                                        text: "প্রোডাক্ট আমাদের কাছে আসার পরে চেক করা হবে এবং কাস্টমারের অভিযোগ সত্য প্রমাণিত হলে কাস্টমারকে প্রোডাক্ট রিপেয়ার, চেঞ্জ করে দেয়া বা প্রয়োজনীয় পদক্ষেপ নিয়ে ইস্যুটি শলভ করে দেয়া হবে।",
                                    },
                                    {
                                        step: "স্টেপ ৩",
                                        text: "আমাদের টেস্টে প্রোডাক্টের কোন ফল্ট না থাকলে কাস্টমারের থেকে বা ড্রপ-শিপারের থেকে কুরিয়ার ফী পাবার পর সেই সেম প্রোডাক্ট আবার কাস্টমারের ঠিকানায় কুরিয়ার করা হবে। প্রয়োজনে ইস্যু সল্ভিং ভিডিও শেয়ার করা হবে।",
                                    },
                                    {
                                        step: "স্টেপ ৪",
                                        text: "প্রোডাক্টে ফল্ট পাওয়া গেলে, ইস্যু রিপেয়ার বা প্রয়োজনীয় ফিক্স করে বা রিপ্লেস করে আমাদের নিজেদের খরচে কাস্টমারের ঠিকানায় কুরিয়ার করে দেয়া হবে।",
                                    },
                                ].map((item) => (
                                    <div
                                        key={item.step}
                                        className="flex gap-3 items-start bg-gray-50 p-4 rounded-lg"
                                    >
                                        <span className="bg-pink-600 text-white text-xs font-bold px-2 py-1 rounded whitespace-nowrap mt-0.5">
                                            {item.step}
                                        </span>
                                        <p className="text-gray-700 text-sm leading-relaxed">
                                            {item.text}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </div>

                        <div className="bg-blue-50 rounded-lg p-5 border border-blue-100">
                            <h3 className="text-lg font-semibold mb-2 text-gray-900">
                                কুরিয়ার ফী কে বিয়ার করবে?
                            </h3>
                            <p className="text-gray-700 leading-relaxed">
                                ইনভয়েস ডেট থেকে যদি প্রোডাক্টের ফল্ট ৭ দিনের মধ্যে হলে
                                কুরিয়ার ফী আমাদের কাছে পাঠানোর খরচ আপনি/কাস্টমার বিয়ার
                                করবে এবং রিপ্লেস করে সেটি আমাদের খরচ দিয়ে কাস্টমার বা
                                ড্রপশিপারের কাছে আমরা পাঠিয়ে দেবো কিন্তু যদি ওয়ারেন্টি
                                ইস্যু ৭ দিনের পরে হয় অর্থাৎ উদাহরণ হিসাবে যদি ৬ মাসের
                                ওয়ারেন্টির কোন প্রোডাক্ট ২ মাস পরে ওয়ারেন্টি সাপোর্ট
                                প্রয়োজন হয় সেক্ষেত্রে আপ+ ডাউন কুরিয়ার ফী কাস্টমারকে
                                দিতে হবে অথবা চাইলে আমাদের ব্রাঞ্চে গিয়ে প্রোডাক্ট দিয়ে
                                আসতে হবে এবং ইস্যু শলভ হলে আবার গিয়ে নিয়ে আসতে হবে। তাই
                                প্রোডাক্ট রিটার্ন করার আগে অবশ্যই কাস্টমারকে সম্ভাব্য সব দিক
                                থেকে প্রপারলি টেস্ট করে কনফার্ম হতে বলবেন।
                            </p>
                        </div>

                        <div>
                            <h3 className="text-lg font-semibold mb-2 text-gray-900">
                                ডেলিভারি এবং রিটার্নের জন্য কতদিন সময় লাগতে পারে?
                            </h3>
                            <p className="text-gray-700 leading-relaxed mb-3">
                                আমাদের দিক থেকে প্রতিটি অর্ডার প্রসেস করার জন্য আমরা ৭২
                                ঘণ্টা পর্যন্ত সময় নিয়ে থাকি তবে চেষ্টা করি যতদ্রুত সম্ভব
                                কুরিয়ারে হ্যান্ডওভার করতে। সাধারণত ঢাকার ভিতরের ডেলিভারি
                                কুরিয়ারে হ্যান্ডওভার করার ১-৩ দিনের মধ্যে ডেলিভারি হয়।
                                ঢাকার বাইরের ডেলিভারির ক্ষেত্রে ২-৫ দিনে ডেলিভারি হয়ে থাকে।
                                মাঝে মাঝে এর থেকে দুই তিন দিন দেরি ও হতে পারে।
                            </p>
                            <p className="text-gray-700 leading-relaxed">
                                রিটার্নের ক্ষেত্রে প্রোডাক্ট আমাদের কাছে আসার পরে সেটি চেক
                                করে ইস্যু ফাইন্ডআউট করা থেকে শুরু করে প্রপার সল্যুশন দেয়ার
                                জন্য ৫-১৫ দিন সময় লাগতে পারে। এর পরে কুরিয়ারে হ্যান্ডওভার
                                করা হবে। তাই অবশ্যই আপনার কাস্টমার থেকে পর্যাপ্ত সময় নিয়ে
                                নেবেন এবং ওভার-কমিটমেন্ট করবেন না।
                            </p>
                        </div>

                        <div className="bg-red-50 border-l-4 border-red-400 p-4 rounded">
                            <p className="text-sm text-red-800 leading-relaxed">
                                ⚠️ অর্ডার কনফার্ম করার পূর্বেই, আপনার প্রতিটি কাস্টোমারকে
                                আপনার শপে অর্ডার করার জন্য টার্মস এবং কন্ডিশনগুলি অবশ্যই
                                জানাবেন বা ইনবক্সে পাঠাবেন। শিওর হয়েই শুধুমাত্র অর্ডার
                                কনফার্ম করবেন। রিটার্ন রেট বেশী হলে আপনার একাউন্ট ১৫ দিনের
                                জন্য সাসপেন্ড করে দেয়া হবে।
                            </p>
                        </div>

                        <div>
                            <h3 className="text-lg font-semibold mb-2 text-gray-900">
                                প্রোডাক্ট স্টক
                            </h3>
                            <p className="text-gray-700 leading-relaxed">
                                যেহেতু আমাদের থেকে রিসেলাররা প্রোডাক্ট নিয়ে থাকে, সেহেতু
                                আমাদের নির্দিষ্ট কোন স্টক সম্পর্কে জানানো সম্ভব নয়। মাঝে
                                মাঝে আমাদের অনেক স্টক থাকতে পারে, আবার মাঝে মাঝে প্রোডাক্ট
                                স্টক আউট হতে পারে, সেক্ষেত্রে আপনাকে নতুন প্রোডাক্ট স্টক
                                হওয়া পর্যন্ত সময় দিয়ে সাথে থাকতে হবে। তবে আমরা চেষ্টা করি
                                সব সময় আমাদের প্রোডাক্ট স্টকে রাখার জন্য।
                            </p>
                        </div>

                        <div className="bg-gray-50 rounded-lg p-5">
                            <h3 className="text-lg font-semibold mb-2 text-gray-900">
                                ডেলিভারি চার্জ
                            </h3>
                            <p className="text-gray-700 leading-relaxed">
                                ডেলিভারি চার্জ অবশ্যই কাস্টমারের কাছ থেকে এডভান্স নেবেন। যদি
                                আপনি অর্থাৎ রিসেলারের নিজের কাছ থেকে দিয়ে থাকেন তাহলে
                                অবশ্যই আমাদের অর্ডার কনফার্ম হওয়ার পূর্বে সে বিষয়টি
                                জানাবেন। যদি কাস্টমার প্রোডাক্ট না নেয়, তাহলে ডেলিভারি চার্জ
                                রিটার্ন করা হবে না।
                            </p>
                        </div>

                        <div className="bg-gray-50 rounded-lg p-5">
                            <h3 className="text-lg font-semibold mb-2 text-gray-900">
                                Subscription পলিসি
                            </h3>
                            <p className="text-gray-700 leading-relaxed">
                                একবার সাবস্ক্রিপশন ফি দিয়ে সাবস্ক্রিপশন নেওয়ার পর তা
                                রিফান্ড যোগ্য নয়। আমাদের সাবস্ক্রিপশনের মেয়াদ ১ বছর পর্যন্ত
                                পাবেন।
                            </p>
                        </div>

                        <p className="text-center text-pink-600 font-semibold text-lg">
                            Happy Drop Shipping- ধন্যবাদ সবাইকে ❤
                        </p>
                    </div>
                </div>

                {/* Contact */}
                <div className="bg-gray-50 rounded-lg p-6 text-center">
                    <h3 className="text-lg font-semibold mb-3 text-gray-900">
                        Contact Us
                    </h3>
                    <p className="text-gray-700 mb-2">
                        If you have any questions or concerns, feel free to contact our
                        customer service team:
                    </p>
                    <p className="text-gray-700">
                        📞{" "}
                        <a
                            href="tel:01976367981"
                            className="text-pink-600 hover:underline"
                        >
                            01976367981
                        </a>
                    </p>
                    <p className="text-gray-700">
                        📧{" "}
                        <a
                            href="mailto:support@selfshop.com"
                            className="text-pink-600 hover:underline"
                        >
                            support@selfshop.com
                        </a>
                    </p>
                </div>
            </div>
        </section>
    );
}
