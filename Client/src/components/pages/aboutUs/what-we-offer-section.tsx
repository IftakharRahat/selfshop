"use client";

import { ChevronDown, ChevronUp } from "lucide-react";
import Image from "next/image";
import { useState } from "react";
import icon4 from "@/assets/images/about/Frame.svg";
import icon1 from "@/assets/images/about/Frame (1).svg";
import icon2 from "@/assets/images/about/Frame (2).svg";
import icon3 from "@/assets/images/about/Frame 2147226261.svg";
import whyChoseUs from "@/assets/images/about/Rectangle 34624223.png";

interface AccordionItem {
	title: string;
	content: string;
	isOpen?: boolean;
}

interface WhatWeOfferSectionProps {
	offerItems?: {
		icon: string;
		title: string;
		description: string;
	}[];
	accordionItems?: AccordionItem[];
}

export default function WhatWeOfferSection({
	offerItems = defaultOfferItems,
	accordionItems = defaultAccordionItems,
}: WhatWeOfferSectionProps) {
	const [openAccordion, setOpenAccordion] = useState<number>(0);

	const toggleAccordion = (index: number) => {
		setOpenAccordion(openAccordion === index ? -1 : index);
	};

	return (
		<div className="">
			{/* What We Offer Section */}
			<div className="bg-[#FDF0F6] py-12 lg:py-20">
				<div className="container mx-auto px-4 sm:px-6 lg:px-8 ">
					<div className="text-center mb-12">
						<h2 className="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">
							What We Offer
						</h2>
						<p className="text-gray-600 max-w-3xl mx-auto text-base lg:text-lg leading-relaxed">
							We specialize in curating a broad selection of trending and
							high-demand products tailored to entrepreneurs and online
							resellers. Our product range includes:
						</p>
					</div>

					<div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-8">
						{offerItems.map((item, index) => (
							<div
								key={index}
								className="bg-white rounded-2xl p-6 lg:p-8 shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-300 text-center"
							>
								<div className="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
									<Image
										src={item.icon || "/placeholder.svg"}
										alt={item.title}
										width={32}
										height={32}
										className="w-8 h-8"
									/>
								</div>
								<h3 className="font-semibold text-gray-900 mb-2 text-sm lg:text-base leading-tight">
									{item.title}
								</h3>
							</div>
						))}
					</div>
				</div>
			</div>

			{/* Why Choose Us Section */}
			<div className="container mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-20">
				<div className="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-16 items-center">
					{/* Left Side - Accordion */}
					<div>
						<h2 className="text-3xl lg:text-4xl font-bold text-gray-900 mb-8">
							Why Choose Us
						</h2>

						<div className="space-y-4">
							{accordionItems.map((item, index) => (
								<div
									key={index}
									className="bg-white rounded-lg border border-gray-200 overflow-hidden"
								>
									<button
										onClick={() => toggleAccordion(index)}
										className="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors duration-200"
									>
										<span className="font-semibold text-gray-900 text-base lg:text-lg">
											{item.title}
										</span>
										{openAccordion === index ? (
											<ChevronUp className="h-5 w-5 text-gray-500" />
										) : (
											<ChevronDown className="h-5 w-5 text-gray-500" />
										)}
									</button>

									{openAccordion === index && (
										<div className="px-6 pb-4">
											<p className="text-gray-600 leading-relaxed">
												{item.content}
											</p>
										</div>
									)}
								</div>
							))}
						</div>
					</div>

					{/* Right Side - Image */}
					<div className="order-first lg:order-last">
						<div className="relative rounded-2xl overflow-hidden">
							<Image
								src={whyChoseUs}
								alt="Happy woman shopping online with floating product icons"
								width={600}
								height={400}
								className="w-full h-auto object-cover"
								priority
							/>
						</div>
					</div>
				</div>
			</div>
		</div>
	);
}

const defaultOfferItems = [
	{
		icon: icon4,
		title: "Dropshipping-friendly merchandise",
		description: "",
	},
	{
		icon: icon3,
		title: "Wholesale items for resellers",
		description: "",
	},
	{
		icon: icon1,
		title: "Digital tools for eCommerce management",
		description: "",
	},
	{
		icon: icon2,
		title: "Ensure quality, reliability, and market relevance, enabling",
		description: "",
	},
];

const defaultAccordionItems = [
	{
		title: "Quality Assurance",
		content:
			"We partner with reputable suppliers to guarantee top-tier products.",
	},
	{
		title: "Entrepreneur-Centric Services",
		content:
			"Our services are specifically designed to meet the unique needs of entrepreneurs and online business owners.",
	},
	{
		title: "Secure Transactions",
		content:
			"All transactions are protected with industry-standard security measures to ensure your business and customer data remains safe.",
	},
	{
		title: "Fast and Reliable Shipping",
		content:
			"We provide efficient shipping solutions to help you deliver products to your customers quickly and reliably.",
	},
];
