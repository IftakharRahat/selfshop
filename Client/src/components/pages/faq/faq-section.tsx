"use client";

import { Minus, Plus } from "lucide-react";
import { useEffect, useState } from "react";
import { getApiBaseUrl } from "@/lib/utils";

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

const defaultFAQs: FAQItem[] = [];

export default function FAQSection({
	title = "Frequently Asked Questions",
	description = "",
	faqs = defaultFAQs,
}: FAQSectionProps) {
	const [openItems, setOpenItems] = useState<Set<number>>(new Set([1])); // First item open by default
	const [items, setItems] = useState<FAQItem[]>(faqs);

	useEffect(() => {
		// If parent explicitly passes FAQ data, prefer it and skip remote fetch.
		if (faqs !== defaultFAQs) {
			setItems(faqs);
			return;
		}

		const fetchFaqs = async () => {
			try {
				const res = await fetch(`${getApiBaseUrl()}/faqs`);
				if (!res.ok) return;

				const json = await res.json();
				const remoteFaqs = Array.isArray(json?.data)
					? json.data.map((faq: { id: number; question: string; answer: string }) => ({
							id: faq.id,
							question: faq.question,
							answer: faq.answer,
					  }))
					: [];

				if (remoteFaqs.length > 0) {
					setItems(remoteFaqs);
					setOpenItems(new Set([remoteFaqs[0].id]));
				}
			} catch {
				// Keep local fallback FAQs when API is unavailable.
			}
		};

		fetchFaqs();
	}, [faqs]);

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
					{description ? (
						<p className="text-gray-600 text-sm sm:text-base lg:text-lg max-w-6xl mx-auto leading-relaxed">
							{description}
						</p>
					) : null}
				</div>

				{/* FAQ Items */}
				<div className=" mx-auto">
					{items.length === 0 ? (
						<div className="border border-gray-200 rounded-lg p-6 text-center text-gray-500">
							No FAQs available right now.
						</div>
					) : (
						<div className="space-y-4">
							{items.map((faq) => {
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
					)}
				</div>
			</div>
		</div>
	);
}
