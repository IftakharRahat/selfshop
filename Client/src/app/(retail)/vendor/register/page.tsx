"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { useRegisterVendorMutation } from "@/redux/api/vendorApi";
import { toast } from "sonner";

const VendorRegisterPage = () => {
	const router = useRouter();
	const [registerVendor, { isLoading }] = useRegisterVendorMutation();

	const [name, setName] = useState("");
	const [email, setEmail] = useState("");
	const [password, setPassword] = useState("");
	const [companyName, setCompanyName] = useState("");
	const [businessType, setBusinessType] = useState("");
	const [country, setCountry] = useState("");
	const [city, setCity] = useState("");

	const handleSubmit = async (e: React.FormEvent) => {
		e.preventDefault();
		try {
			const res = await registerVendor({
				name,
				email,
				password,
				company_name: companyName,
				business_type: businessType,
				country,
				city,
			}).unwrap();

			if (!res.status) {
				toast.error(res.message || "Registration failed");
				return;
			}

			toast.success(
				"Registration submitted. Admin will approve your vendor account.",
			);
			router.replace("/vendor/login");
		} catch (error) {
			console.error(error);
			toast.error("Registration failed");
		}
	};

	return (
		<div className="min-h-screen flex items-center justify-center bg-gray-50 px-4">
			<div className="w-full max-w-xl rounded-2xl bg-white shadow-sm border border-gray-100 p-8 space-y-6">
				<div className="space-y-1 text-center">
					<h1 className="text-2xl font-bold text-gray-900">
						Become a SelfShop vendor
					</h1>
					<p className="text-sm text-gray-600">
						Create your vendor account. We will review your details before
						activating access to the portal.
					</p>
				</div>

				<form onSubmit={handleSubmit} className="space-y-4">
					<div className="grid grid-cols-1 md:grid-cols-2 gap-4">
						<label className="flex flex-col text-sm font-medium text-gray-700">
							Your name
							<input
								required
								value={name}
								onChange={(e) => setName(e.target.value)}
								className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
							/>
						</label>
						<label className="flex flex-col text-sm font-medium text-gray-700">
							Email or phone
							<input
								required
								value={email}
								onChange={(e) => setEmail(e.target.value)}
								className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
							/>
						</label>
						<label className="flex flex-col text-sm font-medium text-gray-700">
							Password
							<input
								required
								type="password"
								value={password}
								onChange={(e) => setPassword(e.target.value)}
								className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
							/>
						</label>
						<label className="flex flex-col text-sm font-medium text-gray-700">
							Company name
							<input
								required
								value={companyName}
								onChange={(e) => setCompanyName(e.target.value)}
								className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
							/>
						</label>
					</div>

					<label className="flex flex-col text-sm font-medium text-gray-700">
						Business type
						<input
							value={businessType}
							onChange={(e) => setBusinessType(e.target.value)}
							placeholder="Manufacturer, wholesaler, importer..."
							className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
						/>
					</label>

					<div className="grid grid-cols-1 md:grid-cols-2 gap-4">
						<label className="flex flex-col text-sm font-medium text-gray-700">
							Country
							<input
								value={country}
								onChange={(e) => setCountry(e.target.value)}
								className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
							/>
						</label>
						<label className="flex flex-col text-sm font-medium text-gray-700">
							City
							<input
								value={city}
								onChange={(e) => setCity(e.target.value)}
								className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
							/>
						</label>
					</div>

					<button
						type="submit"
						disabled={isLoading}
						className="w-full inline-flex items-center justify-center px-4 py-2 rounded-lg bg-[#2d2a5d] text-white text-sm font-medium hover:bg-[#252947] disabled:opacity-60"
					>
						{isLoading ? "Submitting..." : "Submit registration"}
					</button>
				</form>

				<p className="text-xs text-gray-500 text-center">
					After approval, you can sign in from the vendor login page using the
					same email and password.
				</p>
			</div>
		</div>
	);
};

export default VendorRegisterPage;

