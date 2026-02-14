"use client";

import { useEffect, useState } from "react";
import {
	useGetVendorProfileQuery,
	useUpsertVendorProfileMutation,
	useGetVendorKycDocumentsQuery,
	useCreateVendorKycDocumentMutation,
} from "@/redux/api/vendorApi";
import { toast } from "sonner";
import WithVendorAuth from "../WithVendorAuth";

export default function VendorProfilePage() {
	const { data, isLoading } = useGetVendorProfileQuery();
	const [saveProfile, { isLoading: isSaving }] =
		useUpsertVendorProfileMutation();
	const {
		data: kycData,
		isLoading: isKycLoading,
	} = useGetVendorKycDocumentsQuery();
	const [createKyc, { isLoading: isCreatingKyc }] =
		useCreateVendorKycDocumentMutation();

	const [companyName, setCompanyName] = useState("");
	const [businessType, setBusinessType] = useState("");
	const [contactName, setContactName] = useState("");
	const [contactEmail, setContactEmail] = useState("");
	const [contactPhone, setContactPhone] = useState("");
	const [country, setCountry] = useState("");
	const [city, setCity] = useState("");
	const [addressLine1, setAddressLine1] = useState("");
	const [vendorStatus, setVendorStatus] = useState<null | string>(null);

	const [kycType, setKycType] = useState("");
	const [kycNumber, setKycNumber] = useState("");
	const [kycFile, setKycFile] = useState<File | null>(null);

	useEffect(() => {
		const vendor = data?.data?.vendor ?? null;
		const user = data?.data?.user as
			| { name?: string; email?: string; phone?: string }
			| undefined;

		if (vendor) {
			setCompanyName(vendor.company_name ?? "");
			setBusinessType(vendor.business_type ?? "");
			setContactName(vendor.contact_name ?? "");
			setContactEmail(vendor.contact_email ?? (user?.email ?? ""));
			setContactPhone(vendor.contact_phone ?? (user?.phone ?? ""));
			setCountry(vendor.country ?? "");
			setCity(vendor.city ?? "");
			setAddressLine1(vendor.address_line_1 ?? "");
			setVendorStatus(vendor.status ?? null);
		} else if (user) {
			// Fallback right after registration – prefill from user info
			setContactName(user.name ?? "");
			setContactEmail(user.email ?? "");
			setContactPhone(user.phone ?? "");
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

	const handleCreateKyc = async (e: React.FormEvent) => {
		e.preventDefault();
		try {
			await createKyc({
				document_type: kycType,
				document_number: kycNumber || undefined,
				file: kycFile ?? undefined,
			}).unwrap();
			setKycType("");
			setKycNumber("");
			setKycFile(null);
			toast.success("KYC document submitted");
		} catch (err: unknown) {
			console.error(err);
			toast.error("Failed to submit KYC document");
		}
	};

	return (
		<WithVendorAuth>
			<div className="space-y-6">
				<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
					<div>
						<h1 className="text-2xl font-bold text-gray-900 mb-1">
							Vendor account &amp; profile
						</h1>
						<p className="text-gray-600 text-sm">
							Update your business identity, contact details, and base address.
							This information is used for KYC and order fulfillment.
						</p>
					</div>
					{vendorStatus && (
						<div className="flex flex-col items-start md:items-end gap-1">
							<span className="text-xs uppercase tracking-wide text-gray-500">
								Account status
							</span>
							<span
								className={`inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ${
									vendorStatus === "approved"
										? "bg-emerald-100 text-emerald-700"
										: vendorStatus === "rejected"
											? "bg-red-100 text-red-700"
											: "bg-amber-100 text-amber-700"
								}`}
							>
								{vendorStatus.charAt(0).toUpperCase() +
									vendorStatus.slice(1)}
							</span>
						</div>
					)}
				</div>

				<div className="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
					<form
						onSubmit={handleSubmit}
						className="rounded-xl bg-white p-6 shadow-sm border border-gray-100 space-y-4 lg:col-span-2"
					>
						<div className="grid grid-cols-1 md:grid-cols-2 gap-4">
							<label className="flex flex-col text-sm font-medium text-gray-700">
								Company name
								<input
									required
									value={companyName}
									onChange={(e) => setCompanyName(e.target.value)}
									className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
								/>
							</label>

							<label className="flex flex-col text-sm font-medium text-gray-700">
								Business type
								<input
									value={businessType}
									onChange={(e) => setBusinessType(e.target.value)}
									placeholder="Manufacturer, wholesaler, importer..."
									className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
								/>
							</label>

							<label className="flex flex-col text-sm font-medium text-gray-700">
								Contact person
								<input
									value={contactName}
									onChange={(e) => setContactName(e.target.value)}
									className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
								/>
							</label>

							<label className="flex flex-col text-sm font-medium text-gray-700">
								Contact email
								<input
									type="email"
									value={contactEmail}
									onChange={(e) => setContactEmail(e.target.value)}
									className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
								/>
							</label>

							<label className="flex flex-col text-sm font-medium text-gray-700">
								Contact phone
								<input
									value={contactPhone}
									onChange={(e) => setContactPhone(e.target.value)}
									className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
								/>
							</label>

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

						<label className="flex flex-col text-sm font-medium text-gray-700">
							Address line
							<input
								value={addressLine1}
								onChange={(e) => setAddressLine1(e.target.value)}
								className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
							/>
						</label>

						<button
							type="submit"
							disabled={isSaving || isLoading}
							className="inline-flex items-center px-4 py-2 rounded-lg bg-[#2d2a5d] text-white text-sm font-medium hover:bg-[#252947] disabled:opacity-60"
						>
							{isSaving ? "Saving..." : "Save profile"}
						</button>
					</form>

					<div className="rounded-xl bg-white p-6 shadow-sm border border-gray-100 space-y-4">
						<div className="flex items-center justify-between gap-2">
							<div>
								<h2 className="text-sm font-semibold text-gray-900">
									KYC documents
								</h2>
								<p className="text-xs text-gray-500">
									Add NID, trade license or other documents for verification.
								</p>
							</div>
						</div>

						<form onSubmit={handleCreateKyc} className="space-y-2">
							<label className="flex flex-col text-xs font-medium text-gray-700">
								Document type
								<input
									required
									value={kycType}
									onChange={(e) => setKycType(e.target.value)}
									placeholder="nid, trade_license..."
									className="mt-1 rounded-md border border-gray-300 px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
								/>
							</label>
							<label className="flex flex-col text-xs font-medium text-gray-700">
								Document number (optional)
								<input
									value={kycNumber}
									onChange={(e) => setKycNumber(e.target.value)}
									className="mt-1 rounded-md border border-gray-300 px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
								/>
							</label>
							<label className="flex flex-col text-xs font-medium text-gray-700">
								Document file (image/PDF)
								<input
									type="file"
									accept="image/*,application/pdf"
									onChange={(e) =>
										setKycFile(e.target.files?.[0] ?? null)
									}
									className="mt-1 block w-full text-xs text-gray-700 file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-gray-900 file:text-white hover:file:bg-black"
								/>
							</label>
							<button
								type="submit"
								disabled={isCreatingKyc}
								className="w-full inline-flex items-center justify-center px-3 py-1.5 rounded-lg bg-gray-900 text-white text-xs font-medium hover:bg-black disabled:opacity-60"
							>
								{isCreatingKyc ? "Submitting..." : "Submit KYC"}
							</button>
						</form>

						<div className="pt-2 border-t border-gray-100">
							<p className="text-xs font-medium text-gray-700 mb-2">
								Submitted documents
							</p>
							{isKycLoading ? (
								<p className="text-xs text-gray-500">Loading...</p>
							) : !kycData ||
							  !kycData.data ||
							  !Array.isArray(kycData.data.documents) ||
							  kycData.data.documents.length === 0 ? (
								<p className="text-xs text-gray-400">
									No documents submitted yet.
								</p>
							) : (
								<ul className="space-y-1 max-h-40 overflow-y-auto text-xs">
									{(kycData.data?.documents ?? []).map((doc) => (
										<li
											key={doc.id}
											className="flex items-center justify-between gap-2 rounded-md bg-gray-50 px-2 py-1"
										>
											<div className="flex flex-col">
												<span className="font-medium text-gray-800">
													{doc.document_type}
													{doc.document_number
														? ` • ${doc.document_number}`
														: ""}
												</span>
												<span className="text-[10px] text-gray-500">
													{new Date(
														doc.created_at,
													).toLocaleString()}
												</span>
											</div>
											<span
												className={`inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold ${
													doc.status === "approved"
														? "bg-emerald-100 text-emerald-700"
														: doc.status === "rejected"
															? "bg-red-100 text-red-700"
															: "bg-amber-100 text-amber-700"
												}`}
											>
												{doc.status}
											</span>
										</li>
									))}
								</ul>
							)}
						</div>
					</div>
				</div>
			</div>
		</WithVendorAuth>
	);
}

