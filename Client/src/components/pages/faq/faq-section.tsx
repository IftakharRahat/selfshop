"use client";

import { Minus, Plus } from "lucide-react";
import { useState } from "react";

interface FAQItem {
	id: number;
	question: string;
	answer: string;
}

interface FAQSectionProps {
	title?: string;
	description?: string;
	faqs?: FAQItem[];
}

const defaultFAQs: FAQItem[] = [
	{
		id: 1,
		question: "Is the package mandatory?",
		answer:
			"Lorem ipsum dolor sit amet consectetur. Odio vestibulum a netus accumsan euismod venenatis sed pellentesque. Lectus vitae diam ante sem pharetra aliquam.",
	},
	{
		id: 2,
		question: "How to open an account?",
		answer:
			"Lorem ipsum dolor sit amet consectetur. Odio vestibulum a netus accumsan euismod venenatis sed pellentesque. Lectus vitae diam ante sem pharetra aliquam.",
	},
	{
		id: 3,
		question: "How to open an account?",
		answer:
			"Lorem ipsum dolor sit amet consectetur. Odio vestibulum a netus accumsan euismod venenatis sed pellentesque. Lectus vitae diam ante sem pharetra aliquam.",
	},
	{
		id: 4,
		question: "How to open an account?",
		answer:
			"Lorem ipsum dolor sit amet consectetur. Odio vestibulum a netus accumsan euismod venenatis sed pellentesque. Lectus vitae diam ante sem pharetra aliquam.",
	},
	{
		id: 5,
		question: "How to open an account?",
		answer:
			"Lorem ipsum dolor sit amet consectetur. Odio vestibulum a netus accumsan euismod venenatis sed pellentesque. Lectus vitae diam ante sem pharetra aliquam.",
	},
];

export default function FAQSection({
	title = "Frequently Asked Questions",
	description = "Lorem ipsum dolor sit amet consectetur. Dignissim erat odio dictum curabitur donec at consequat arcu cursus. Eget quis cum amet iaculis orci non.",
	faqs = defaultFAQs,
}: FAQSectionProps) {
	const [openItems, setOpenItems] = useState<Set<number>>(new Set([1])); // First item open by default

	const toggleItem = (id: number) => {
		const newOpenItems = new Set(openItems);
		if (newOpenItems.has(id)) {
			newOpenItems.delete(id);
		} else {
			newOpenItems.add(id);
		}
		setOpenItems(newOpenItems);
	};

	return (
		<div className="bg-white py-12 lg:py-16">
			<div className="container mx-auto px-4 sm:px-6 lg:px-8">
				{/* Header */}
				<div className="text-center mb-8 lg:mb-12">
					<h2 className="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 mb-4">
						{title}
					</h2>
					<p className="text-gray-600 text-sm sm:text-base lg:text-lg max-w-6xl mx-auto leading-relaxed">
						{description}
					</p>
				</div>

				{/* FAQ Items */}
				<div className=" mx-auto">
					<div className="space-y-4">
						{faqs.map((faq) => {
							const isOpen = openItems.has(faq.id);

							return (
								<div
									key={faq.id}
									className="border border-gray-200 rounded-lg overflow-hidden"
								>
									{/* Question */}
									<button
										onClick={() => toggleItem(faq.id)}
										className="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors duration-200"
									>
										<span className="text-gray-900 font-medium text-sm sm:text-base lg:text-lg pr-4">
											{faq.id}. {faq.question}
										</span>
										<div className="flex-shrink-0">
											{isOpen ? (
												<Minus className="h-5 w-5 text-gray-600" />
											) : (
												<Plus className="h-5 w-5 text-gray-600" />
											)}
										</div>
									</button>

									{/* Answer */}
									{isOpen && (
										<div className="px-6 pb-4">
											<div className="pt-2 border-t border-gray-100">
												<p className="text-gray-600 text-sm sm:text-base leading-relaxed">
													{faq.answer}
												</p>
											</div>
										</div>
									)}
								</div>
							);
						})}
					</div>
				</div>
			</div>
		</div>
	);
}
