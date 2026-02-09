"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { Loader2, Mail, MapPin, Phone } from "lucide-react";
import Image from "next/image";
import { useState } from "react";
import { useForm } from "react-hook-form";
import * as z from "zod";
import mapImage from "@/assets/images/contact/dhaka-map-marker.png";
import contactImage from "@/assets/images/contact/image (15).png";

const contactSchema = z.object({
	name: z.string().min(2, "Name must be at least 2 characters"),
	email: z.string().email("Please enter a valid email address"),
	title: z.string().min(3, "Title must be at least 3 characters"),
	message: z.string().min(10, "Message must be at least 10 characters"),
});

type ContactFormData = z.infer<typeof contactSchema>;

export default function ContactPage() {
	const [isSubmitting, setIsSubmitting] = useState(false);
	const [submitSuccess, setSubmitSuccess] = useState(false);

	const {
		register,
		handleSubmit,
		formState: { errors },
		reset,
	} = useForm<ContactFormData>({
		resolver: zodResolver(contactSchema),
	});

	const onSubmit = async (data: ContactFormData) => {
		setIsSubmitting(true);
		try {
			// Simulate API call
			await new Promise((resolve) => setTimeout(resolve, 2000));
			console.log("Form submitted:", data);
			setSubmitSuccess(true);
			reset();
			setTimeout(() => setSubmitSuccess(false), 3000);
		} catch (error) {
			console.error("Submission error:", error);
		} finally {
			setIsSubmitting(false);
		}
	};

	return (
		<div className="min-h-screen bg-gray-50">
			{/* Main Contact Section */}
			<div className="container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-16">
				<div className="bg-white rounded-2xl shadow-lg overflow-hidden">
					<div className="grid grid-cols-1 lg:grid-cols-2">
						{/* Left Side - Image */}
						<div className="relative h-64 lg:h-full min-h-[400px]">
							<Image
								src={contactImage}
								alt="Business meeting with professionals working on laptops"
								fill
								className="object-cover"
								priority
							/>
						</div>

						{/* Right Side - Contact Form */}
						<div className="p-6 lg:p-12">
							<h1 className="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">
								Contact Us
							</h1>
							<p className="text-gray-600 mb-8 leading-relaxed">
								Lorem ipsum dolor sit amet consectetur. Dignissim erat odio
								dictum curabitur donec at consequat arcu cursus. Eget quis cum
								amet iaculis orci non.
							</p>

							{submitSuccess && (
								<div className="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
									<p className="text-green-800 font-medium">
										Thank you! Your message has been sent successfully.
									</p>
								</div>
							)}

							<form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
								{/* Name and Email Row */}
								<div className="grid grid-cols-1 md:grid-cols-2 gap-4">
									<div>
										<label
											htmlFor="name"
											className="block text-sm font-medium text-gray-700 mb-2"
										>
											Your Name
										</label>
										<input
											{...register("name")}
											type="text"
											id="name"
											placeholder="Enter customer name"
											className={`w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors ${
												errors.name ? "border-red-300" : "border-gray-300"
											}`}
										/>
										{errors.name && (
											<p className="mt-1 text-sm text-red-600">
												{errors.name.message}
											</p>
										)}
									</div>

									<div>
										<label
											htmlFor="email"
											className="block text-sm font-medium text-gray-700 mb-2"
										>
											Email Address
										</label>
										<input
											{...register("email")}
											type="email"
											id="email"
											placeholder="Enter email address"
											className={`w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors ${
												errors.email ? "border-red-300" : "border-gray-300"
											}`}
										/>
										{errors.email && (
											<p className="mt-1 text-sm text-red-600">
												{errors.email.message}
											</p>
										)}
									</div>
								</div>

								{/* Title */}
								<div>
									<label
										htmlFor="title"
										className="block text-sm font-medium text-gray-700 mb-2"
									>
										Title
									</label>
									<input
										{...register("title")}
										type="text"
										id="title"
										placeholder="Enter title"
										className={`w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors ${
											errors.title ? "border-red-300" : "border-gray-300"
										}`}
									/>
									{errors.title && (
										<p className="mt-1 text-sm text-red-600">
											{errors.title.message}
										</p>
									)}
								</div>

								{/* Message */}
								<div>
									<label
										htmlFor="message"
										className="block text-sm font-medium text-gray-700 mb-2"
									>
										Message
									</label>
									<textarea
										{...register("message")}
										id="message"
										rows={6}
										placeholder="Enter your message"
										className={`w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors resize-none ${
											errors.message ? "border-red-300" : "border-gray-300"
										}`}
									/>
									{errors.message && (
										<p className="mt-1 text-sm text-red-600">
											{errors.message.message}
										</p>
									)}
								</div>

								{/* Submit Button */}
								<button
									type="submit"
									disabled={isSubmitting}
									className="w-full bg-pink-600 hover:bg-pink-700 disabled:bg-pink-400 text-white font-semibold py-3 px-6 rounded-lg transition-colors flex items-center justify-center"
								>
									{isSubmitting ? (
										<>
											<Loader2 className="animate-spin h-5 w-5 mr-2" />
											Sending...
										</>
									) : (
										"Send Message"
									)}
								</button>
							</form>
						</div>
					</div>
				</div>
			</div>

			{/* Contact Information */}
			<div className="bg-white py-12">
				<div className="container mx-auto px-4 sm:px-6 lg:px-8">
					<div className="grid grid-cols-1 md:grid-cols-3 gap-8">
						{/* Location */}
						<div className="flex items-center space-x-4">
							<div className="flex-shrink-0">
								<div className="w-12 h-12 bg-pink-100 rounded-full flex items-center justify-center">
									<MapPin className="h-6 w-6 text-pink-600" />
								</div>
							</div>
							<div>
								<p className="text-gray-900 font-medium">Dhaka, Bangladesh</p>
							</div>
						</div>

						{/* Email */}
						<div className="flex items-center space-x-4">
							<div className="flex-shrink-0">
								<div className="w-12 h-12 bg-pink-100 rounded-full flex items-center justify-center">
									<Mail className="h-6 w-6 text-pink-600" />
								</div>
							</div>
							<div>
								<p className="text-gray-900 font-medium">
									contact@selfshop.com.bd
								</p>
							</div>
						</div>

						{/* Phone */}
						<div className="flex items-center space-x-4">
							<div className="flex-shrink-0">
								<div className="w-12 h-12 bg-pink-100 rounded-full flex items-center justify-center">
									<Phone className="h-6 w-6 text-pink-600" />
								</div>
							</div>
							<div>
								<p className="text-gray-900 font-medium">+(88) 01976367981</p>
								<p className="text-gray-900 font-medium">+(88) 01976367981</p>
							</div>
						</div>
					</div>
				</div>
			</div>

			{/* Live Location Section */}
			<div className="bg-gray-50 py-16">
				<div className="container mx-auto px-4 sm:px-6 lg:px-8">
					<div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
						{/* Left Side - Text */}
						<div>
							<h2 className="text-3xl lg:text-4xl font-bold text-gray-900 mb-6">
								Live location
							</h2>
							<p className="text-gray-600 leading-relaxed mb-8">
								Have questions, suggestions, or need assistance? We&apos;d love
								to hear from you! Whether you&apos;re looking to collaborate,
								inquire about our GPS products, or learn more about our
								sustainability initiatives, our team is ready to assist.
							</p>
						</div>

						{/* Right Side - Map */}
						<div className="relative">
							<div className="bg-gray-200 rounded-2xl overflow-hidden h-80 relative">
								<Image
									src={mapImage}
									alt="Map showing Dhaka, Bangladesh location"
									fill
									className="object-cover"
									priority
								/>
								<div className="absolute inset-0 flex items-center justify-center">
									<div className="bg-white rounded-lg px-4 py-2 shadow-lg">
										<p className="text-gray-900 font-semibold">
											Dhaka, Bangladesh
										</p>
									</div>
								</div>
							</div>
							<div className="mt-6 text-center">
								<button className="bg-pink-600 hover:bg-pink-700 text-white font-semibold py-3 px-8 rounded-full transition-colors">
									See map details location
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	);
}
