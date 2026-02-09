import Image from "next/image";
import React from "react";
import bgImage from "@/assets/images/about/image (14).png";
import mission from "@/assets/images/about/Union.png";
import MostPopularBrands from "../home/most-popular-brands";
import WhatWeOfferSection from "./what-we-offer-section";

const AboutUsComponent = () => {
	return (
		<>
			<div className="relative py-16 lg:py-28 px-4 text-white">
				{/* Background Image */}
				<div className="absolute inset-0 -z-10">
					<Image
						src={bgImage}
						alt="Background"
						fill
						className="object-cover"
						quality={100}
					/>
					<div className="absolute inset-0 bg-black/50" />
				</div>

				{/* Content */}
				<div className="container p-6 rounded-lg relative">
					<h1 className="text-3xl sm:text-4xl font-bold mb-4">
						About SelfShop
					</h1>
					<p className="mb-4">
						SelfShop is an innovative online platform dedicated to empowering
						entrepreneurs, dropshippers, and resellers with high-quality
						products and an exceptional shopping experience. Established with
						the vision of transforming the eCommerce landscape, SelfShop has
						rapidly grown to a community of over 32,000 users who leverage our
						platform to build and expand their businesses.
					</p>
				</div>
			</div>
			<div className="container flex flex-col md:flex-row items-center justify-between gap-20 py-16">
				<div className="max-h-[800px]">
					<Image
						src={mission}
						alt="Mission"
						height={800}
						width={800}
						className="h-full w-full"
						//   quality={100}
					/>
				</div>
				<div className="w-full">
					<h2 className="text-5xl font-semibold text-[#2C2C2C] mb-5">
						Our Mission At SelfShop
					</h2>
					<p>
						We envision a world where anyone can start and grow their own online
						business, regardless of their background or experience.
					</p>
				</div>
			</div>
			<WhatWeOfferSection />
			<MostPopularBrands />
		</>
	);
};

export default AboutUsComponent;
