"use client";

import { useEffect, useState } from "react";
import {
	useGetVendorProfileQuery,
	useUpsertVendorProfileMutation,
} from "@/redux/api/vendorApi";
import { toast } from "sonner";
import WithVendorAuth from "../WithVendorAuth";

export default function VendorProfilePage() {
	const { data, isLoading } = useGetVendorProfileQuery();
	const [saveProfile, { isLoading: isSaving }] =
		useUpsertVendorProfileMutation();

	const [companyName, setCompanyName] = useState("");
	const [businessType, setBusinessType] = useState("");
	const [contactName, setContactName] = useState("");
	const [contactEmail, setContactEmail] = useState("");
	const [contactPhone, setContactPhone] = useState("");
	const [country, setCountry] = useState("");
	const [city, setCity] = useState("");
	const [addressLine1, setAddressLine1] = useState("");

	useEffect(() => {
		if (data?.vendor) {
			const v = data.vendor;
			setCompanyName(v.company_name ?? "");
			setBusinessType(v.business_type ?? "");
			setContactName(v.contact_name ?? "");
			setContactEmail(v.contact_email ?? "");
			setContactPhone(v.contact_phone ?? "");
			setCountry(v.country ?? "");
			setCity(v.city ?? "");
			setAddressLine1(v.address_line_1 ?? "");
		}
	}, [data]);

	const handleSubmit = async (e: React.FormEvent) => {
		e.preventDefault();
		try {
			await saveProfile({
				company_name: companyName,
				business_type: businessType,
				contact_name: contactName,
				contact_email: contactEmail,
				contact_phone: contactPhone,
				country,
				city,
				address_line_1: addressLine1,
			}).unwrap();
			toast.success("Vendor profile saved");
		} catch (err: unknown) {
			console.error(err);
			toast.error("Failed to save profile");
		}
	};

	return (
		<WithVendorAuth>
			<div className="space-y-6">
			<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
				<h1 className="text-2xl font-bold text-gray-900 mb-2">
					Vendor account &amp; profile
				</h1>
				<p className="text-gray-600 text-sm">
					Update your business identity, contact details, and base address. This
					information is used for KYC and order fulfillment.
				</p>
			</div>

			<form
				onSubmit={handleSubmit}
				className="rounded-xl bg-white p-6 shadow-sm border border-gray-100 space-y-4 max-w-2xl"
			>
				<div className="grid grid-cols-1 md:grid-cols-2 gap-4">
					<label className="flex flex-col text-sm font-medium text-gray-700">
						Company name
						<input
							required
							value={companyName}
							onChange={(e) => setCompanyName(e.target.value)}
							className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-pink-500"
						/>
					</label>

					<label className="flex flex-col text-sm font-medium text-gray-700">
						Business type
						<input
							value={businessType}
							onChange={(e) => setBusinessType(e.target.value)}
							placeholder="Manufacturer, wholesaler, importer..."
							className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-pink-500"
						/>
					</label>

					<label className="flex flex-col text-sm font-medium text-gray-700">
						Contact person
						<input
							value={contactName}
							onChange={(e) => setContactName(e.target.value)}
							className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-pink-500"
						/>
					</label>

					<label className="flex flex-col text-sm font-medium text-gray-700">
						Contact email
						<input
							type="email"
							value={contactEmail}
							onChange={(e) => setContactEmail(e.target.value)}
							className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-pink-500"
						/>
					</label>

					<label className="flex flex-col text-sm font-medium text-gray-700">
						Contact phone
						<input
							value={contactPhone}
							onChange={(e) => setContactPhone(e.target.value)}
							className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-pink-500"
						/>
					</label>

					<label className="flex flex-col text-sm font-medium text-gray-700">
						Country
						<input
							value={country}
							onChange={(e) => setCountry(e.target.value)}
							className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-pink-500"
						/>
					</label>

					<label className="flex flex-col text-sm font-medium text-gray-700">
						City
						<input
							value={city}
							onChange={(e) => setCity(e.target.value)}
							className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-pink-500"
						/>
					</label>
				</div>

				<label className="flex flex-col text-sm font-medium text-gray-700">
					Address line
					<input
						value={addressLine1}
						onChange={(e) => setAddressLine1(e.target.value)}
						className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-pink-500"
					/>
				</label>

				<button
					type="submit"
					disabled={isSaving || isLoading}
					className="inline-flex items-center px-4 py-2 rounded-lg bg-[#E5005F] text-white text-sm font-medium hover:bg-pink-700 disabled:opacity-60"
				>
					{isSaving ? "Saving..." : "Save profile"}
				</button>
			</form>
			</div>
		</WithVendorAuth>
	);
}

