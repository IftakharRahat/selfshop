"use client";
import Image from "next/image";

import feature1 from "@/assets/images/features/Frame.png";
import feature2 from "@/assets/images/features/Frame (2).png";
import feature3 from "@/assets/images/features/Frame (3).png";

const defaultFeatureImages = [feature1, feature2, feature3];

export default function FeaturesSection() {
	return (
		<div className="container">
			<div className="px-3 sm:px-6 lg:px-8 py-3 sm:py-4 lg:py-5 relative rounded-xl bg-[#FDF0F6]">
				<div className="grid grid-cols-3 gap-3 sm:gap-6 lg:gap-8">
					{defaultFeatureImages.map((img, index) => (
						<div
							key={index}
							className="flex justify-center"
						>
							<div className="w-16 h-16 sm:w-20 sm:h-20 lg:w-24 lg:h-24 flex items-center justify-center">
								<Image
									src={img}
									alt={`Feature ${index + 1}`}
									width={96}
									height={96}
									className="w-full h-full object-contain"
									placeholder="blur"
								/>
							</div>
						</div>
					))}
				</div>
			</div>
		</div>
	);
}
