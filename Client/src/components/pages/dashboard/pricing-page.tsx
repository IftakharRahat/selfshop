"use client";

import { Check } from "lucide-react";
import { useState } from "react";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
	useCreatePurchaseMutation,
	useGetPricingQuery,
} from "@/redux/features/pricingApi";
import { handleAsyncWithToast } from "@/utils/handleAsyncWithToast";

export function PricingPage() {
	const { data: pricingData } = useGetPricingQuery(undefined);
	const [createPurchase] = useCreatePurchaseMutation();
	// console.log(pricingData?.data?.packages);

	const [selectedPlan, setSelectedPlan] = useState("basic");

	const basicFeatures = [
		"অসংখ্যা প্রোডাক্ট",
		"অর্ডার ম্যানেজমেন্ট সিস্টেম",
		"২৪/৭ কাস্টমার সাপোর্ট",
		"অটো অর্ডার আপডেট",
		"পেমেন্ট গেটওয়ে",
		"মার্কেট অ্যানালিটিক্স",
		"৭২ ঘন্টার ডেলিভারি",
		"ফ্রি ট্রেনিং",
		"ম্যানুয়াল লিড জেনারেট",
		"রিটার্ন পলিসি",
		"সিস্টেম এক্সেসিবিলিটি",
	];

	const standardFeatures = [
		"সব বেসিক ফিচার + আরও এক্সক্লুসিভ",
		"অগ্রাধিকার সাপোর্ট",
		"কাস্টম ব্র্যান্ডিং সিস্টেম",
		"এডভান্স রিপোর্ট",
		"৪৮ ঘন্টা ডেলিভারি",
		"কাস্টম প্রোডাক্ট সিস্টেম",
		"৭২ ঘন্টার ডেলিভারি",
		"ফ্রি ট্রেনিং",
		"অটোমেটিক লিড জেনারেট",
		"রিটার্ন পলিসি",
		"সিস্টেম এক্সেসিবিলিটি",
	];

	const handlePurchase = (packageId: number, amount: number) => async () => {
		try {
			await handleAsyncWithToast(
				async () => createPurchase({ package_id: packageId, amount }),
				true,
			);
		} catch (error) {
			console.error("Error creating purchase:", error);
		}
	};

	return (
		<div className="w-full max-w-4xl mx-auto bg-white ">
			{/* Logo and Header */}
			<div className="text-center mb-8">
				<h2 className="text-2xl font-semibold text-gray-800 mb-2">Pricing</h2>
				<p className="text-gray-600 mb-6">
					Thank you for completing your registration. Please select your
					<br />
					preferred reseller package and make payment
				</p>
			</div>

			{/* Plan Selection Tabs */}
			<Tabs
				value={selectedPlan}
				onValueChange={setSelectedPlan}
				className="w-full"
			>
				<TabsList className="grid w-full max-w-md mx-auto grid-cols-2 mb-8 border border-pink-600 pb-8">
					<TabsTrigger
						value="basic"
						className="data-[state=active]:bg-pink-200 data-[state=active]:text-pink-800"
					>
						Basic
					</TabsTrigger>
					<TabsTrigger
						value="standard"
						className="data-[state=active]:bg-pink-200 data-[state=active]:text-pink-800"
					>
						Standard
					</TabsTrigger>
				</TabsList>

				{/* Pricing Cards */}
				<div className="grid md:grid-cols-2 gap-6">
					{/* Basic Plan */}
					<div
						onClick={() => setSelectedPlan("basic")}
						className={`relative ${selectedPlan === "basic" ? "ring-2 ring-pink-500 bg-pink-50" : "bg-white"}`}
					>
						<div className="p-6">
							<div className="text-center mb-6">
								<h3 className="text-xl font-semibold mb-2">
									{pricingData?.data?.packages[0]?.package_name}
								</h3>
								<div className="flex  justify-center gap-4">
									<div className="text-3xl font-bold text-pink-600 mb-1">
										৳{pricingData?.data?.packages[0]?.discount_price}
									</div>
									<div className="text-xl font-bold text-pink-600 mb-1 line-through">
										৳{pricingData?.data?.packages[0]?.price}
									</div>
								</div>
								<p className="  text-gray-600">
									{pricingData?.data?.packages[0]?.validity} মাস
								</p>
							</div>

							<div className="space-y-3 mb-6">
								{basicFeatures.map((feature, index) => (
									<div key={index} className="flex items-center gap-3">
										<Check className="w-4 h-4 text-green-500 flex-shrink-0" />
										<span className="text-sm text-gray-700">{feature}</span>
									</div>
								))}
							</div>

							<button
								className="w-full bg-pink-600 hover:bg-pink-700 text-white py-2 rounded"
								onClick={handlePurchase(
									pricingData?.data?.packages[0]?.id,
									pricingData?.data?.packages[0]?.discount_price,
								)}
							>
								Purchase now
							</button>
						</div>
					</div>

					{/* Standard Plan */}
					<div
						onClick={() => setSelectedPlan("standard")}
						className={`relative ${selectedPlan === "standard" ? "ring-2 ring-pink-500 bg-pink-50" : "bg-white"}`}
					>
						<div className="p-6">
							<div className="text-center mb-6">
								<h3 className="text-xl font-semibold mb-2">
									{pricingData?.data?.packages[1]?.package_name}
								</h3>
								<div className="flex  justify-center gap-4">
									<div className="text-3xl font-bold text-pink-600 mb-1">
										৳{pricingData?.data?.packages[1]?.discount_price}
									</div>
									<div className="text-xl font-bold text-pink-600 mb-1 line-through">
										৳{pricingData?.data?.packages[1]?.price}
									</div>
								</div>
								<p className=" text-gray-600">
									{pricingData?.data?.packages[1]?.validity} মাস
								</p>
							</div>

							<div className="space-y-3 mb-6">
								{standardFeatures.map((feature, index) => (
									<div key={index} className="flex items-center gap-3">
										<Check className="w-4 h-4 text-green-500 flex-shrink-0" />
										<span className="text-sm text-gray-700">{feature}</span>
									</div>
								))}
							</div>

							<button
								className="w-full bg-pink-600 hover:bg-pink-700 text-white mb-2 py-2 rounded"
								onClick={handlePurchase(
									pricingData?.data?.packages[1]?.id,
									pricingData?.data?.packages[1]?.discount_price,
								)}
							>
								Purchase now
							</button>
						</div>
					</div>
				</div>
			</Tabs>
		</div>
	);
}
