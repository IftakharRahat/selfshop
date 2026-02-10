"use client";
import Image from "next/image";

import feature1 from "@/assets/images/features/Frame.png";
import feature2 from "@/assets/images/features/Frame (2).png";
import feature3 from "@/assets/images/features/Frame (3).png";

const defaultFeatureImages = [feature1, feature2, feature3];

export default function FeaturesSection() {
	return (
		<div className="container">
			<div className="px-3 sm:px-6 lg:px-8 py-3 sm:py-6 lg:py-8 relative rounded-xl bg-[#FDF0F6]">
				<div className="grid grid-cols-3 gap-3 sm:gap-6 lg:gap-12">
					{defaultFeatureImages.map((img, index) => (
						<div
							key={index}
							className="relative text-center rounded-md overflow-hidden"
						>
							<div className="flex justify-center py-1 sm:py-3 px-1 sm:px-4">
								<div className="h-full w-full flex items-center justify-center">
									<Image
										src={img}
										alt={`Feature ${index + 1}`}
										width={400}
										height={400}
										className="w-full h-full object-contain"
										placeholder="blur"
									/>
								</div>
							</div>
						</div>
					))}
				</div>
			</div>
		</div>
	);
}
