import Image from "next/image";
import Link from "next/link";
import contactImage from "@/assets/images/contact/image (16).png";
import contactImage1 from "@/assets/images/image.png";
import contactImageFb from "@/assets/images/image (1).png";

export default function ContactMethodsSection() {
	const contactMethods = [
		{
			id: 1,
			title: "Call Center",
			description:
				"Lorem ipsum dolor sit amet consectetur. Faucibus tempus lacus ultrices eu. Tristique nunc morbi viverra nec malesuada amet a consectetur.",
			image: contactImage1,
			imageAlt: "Call center representative with headset",
			buttonText: "Contact now",
			layout: "image-left",
		},
		{
			id: 2,
			title: "Mail Us",
			description:
				"Lorem ipsum dolor sit amet consectetur. Faucibus tempus lacus ultrices eu. Tristique nunc morbi viverra nec malesuada amet a consectetur.",
			image: contactImage,
			imageAlt: "Hands holding envelope icon",
			buttonText: "Contact now",
			layout: "image-right",
		},
		{
			id: 3,
			title: "Our Page",
			description:
				"Lorem ipsum dolor sit amet consectetur. Faucibus tempus lacus ultrices eu. Tristique nunc morbi viverra nec malesuada amet a consectetur.",
			image: contactImage,
			imageAlt: "Hands holding envelope icon",
			buttonText: "Contact now",
			layout: "image-left",
		},
		{
			id: 4,
			title: "Facebook Group",
			description:
				"Lorem ipsum dolor sit amet consectetur. Faucibus tempus lacus ultrices eu. Tristique nunc morbi viverra nec malesuada amet a consectetur.",
			image: contactImageFb,
			imageAlt: "Team holding Facebook icons",
			buttonText: "Contact now",
			layout: "image-right",
		},
	];

	return (
		<section className="py-12 lg:py-16 bg-white">
			<div className="container mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
				{contactMethods.map((method) => (
					<div
						key={method.id}
						className={`grid grid-cols-1 lg:grid-cols-2 gap-8 items-center ${
							method.layout === "image-right"
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
							<Link
								href={"/contact"}
								className="bg-[#E7005E] hover:bg-pink-600 text-white font-medium px-6 py-3 rounded-lg transition-colors duration-200"
							>
								<button className="bg-[#E7005E] hover:bg-pink-600 text-white font-medium px-6 py-3 rounded-lg transition-colors duration-200">
									{method.buttonText}
								</button>
							</Link>
						</div>
					</div>
				))}
			</div>
		</section>
	);
}
