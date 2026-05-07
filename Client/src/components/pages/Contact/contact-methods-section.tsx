"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import Image from "next/image";
import contactImage from "@/assets/images/contact/image (16).png";
import contactImage1 from "@/assets/images/image.png";
import contactImageFb from "@/assets/images/image (1).png";

interface ContactInfo {
	phone_one?: string;
	phone_two?: string;
	email?: string;
	address?: string;
	wp_number?: string;
	wp_link?: string;
	messanger_link?: string;
	facebook?: string;
	instagram?: string;
	youtube?: string;
	tiktok?: string;
}

export default function ContactMethodsSection() {
	const [contactInfo, setContactInfo] = useState<ContactInfo | null>(null);

	useEffect(() => {
		fetch(`${process.env.NEXT_PUBLIC_BASE_URL}/contact-info`)
			.then((res) => res.json())
			.then((data) => setContactInfo(data?.data ?? null))
			.catch(() => {});
	}, []);

	const contactMethods = [
		{
			id: 1,
			title: "Call Center",
			description: contactInfo?.phone_one
				? `Reach us at ${contactInfo.phone_one}. Our dedicated support team is available to help you with any inquiries regarding your orders, account, or services.`
				: "Need immediate assistance? Our dedicated support team is available to help you with any inquiries regarding your orders, account, or services.",
			image: contactImage1,
			imageAlt: "Call center representative with headset",
			buttonText: "Contact now",
			buttonLink: contactInfo?.phone_one ? `tel:${contactInfo.phone_one}` : "/contact",
			layout: "image-left",
		},
		{
			id: 2,
			title: "Mail Us",
			description: contactInfo?.email
				? `Send us a message at ${contactInfo.email} and our team will respond within 24 hours. We're happy to help with any questions or concerns.`
				: "Prefer to write? Send us a detailed message and our team will respond within 24 hours. We're happy to help with any questions or concerns.",
			image: contactImage,
			imageAlt: "Hands holding envelope icon",
			buttonText: "Contact now",
			buttonLink: contactInfo?.email ? `mailto:${contactInfo.email}` : "/contact",
			layout: "image-right",
		},
		{
			id: 3,
			title: "Our Page",
			description: contactInfo?.facebook
				? "Stay updated with the latest news, offers, and announcements. Follow our official page for real-time updates and community engagement."
				: "Stay updated with the latest news, offers, and announcements. Follow our official page for real-time updates and community engagement.",
			image: contactImage,
			imageAlt: "Social media page",
			buttonText: "Visit Page",
			buttonLink: contactInfo?.facebook || "/contact",
			layout: "image-left",
		},
		{
			id: 4,
			title: "Facebook Group",
			description:
				"Join our growing community on Facebook! Connect with fellow resellers, share tips, get exclusive updates, and participate in group discussions.",
			image: contactImageFb,
			imageAlt: "Team holding Facebook icons",
			buttonText: "Join Group",
			buttonLink: contactInfo?.facebook || "/contact",
			layout: "image-right",
		},
	];

	// Add extra methods if social links are available
	if (contactInfo?.youtube) {
		contactMethods.push({
			id: 5,
			title: "YouTube Channel",
			description:
				"Watch tutorials, product reviews, and business tips on our official YouTube channel. Subscribe to stay updated!",
			image: contactImage1,
			imageAlt: "YouTube channel",
			buttonText: "Subscribe",
			buttonLink: contactInfo.youtube,
			layout: "image-left",
		});
	}

	if (contactInfo?.instagram) {
		contactMethods.push({
			id: 6,
			title: "Instagram",
			description:
				"Follow us on Instagram for the latest product photos, stories, and behind-the-scenes content from our team.",
			image: contactImage,
			imageAlt: "Instagram profile",
			buttonText: "Follow Us",
			buttonLink: contactInfo.instagram,
			layout: "image-right",
		});
	}

	return (
		<section className="py-12 lg:py-16 bg-white">
			<div className="container mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
				{contactMethods.map((method) => {
					const isExternal =
						method.buttonLink.startsWith("http") ||
						method.buttonLink.startsWith("tel:") ||
						method.buttonLink.startsWith("mailto:");

					return (
						<div
							key={method.id}
							className={`grid grid-cols-1 lg:grid-cols-2 gap-8 items-center ${method.layout === "image-right"
								? "lg:[&>*:first-child]:order-2"
								: ""
								}`}
						>
							{/* Image */}
							<div className="relative w-full h-56 lg:h-80 rounded-xl overflow-hidden">
								<Image
									src={method.image || "/placeholder.svg"}
									alt={method.imageAlt}
									fill
									className="object-cover"
								/>
							</div>

							{/* Text */}
							<div>
								<h3 className="text-2xl font-bold text-gray-900 mb-4">
									{method.title}
								</h3>
								<p className="text-gray-600 text-base leading-relaxed mb-6">
									{method.description}
								</p>
								{isExternal ? (
									<a
										href={method.buttonLink}
										target="_blank"
										rel="noopener noreferrer"
										className="inline-block bg-[#E7005E] hover:bg-pink-600 text-white font-medium px-6 py-3 rounded-lg transition-colors duration-200"
									>
										{method.buttonText}
									</a>
								) : (
									<Link
										href={method.buttonLink}
										className="inline-block bg-[#E7005E] hover:bg-pink-600 text-white font-medium px-6 py-3 rounded-lg transition-colors duration-200"
									>
										{method.buttonText}
									</Link>
								)}
							</div>
						</div>
					);
				})}
			</div>
		</section>
	);
}
