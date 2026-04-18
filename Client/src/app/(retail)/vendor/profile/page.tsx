"use client";

import { useEffect, useState } from "react";
import {
	useGetVendorProfileQuery,
	useUpsertVendorProfileMutation,
	useGetVendorKycDocumentsQuery,
	useCreateVendorKycDocumentMutation,
	useChangeVendorPasswordMutation,
	useGetCarryBeeCitiesQuery,
	useGetCarryBeeZonesQuery,
	useGetCarryBeeAreasQuery,
} from "@/redux/api/vendorApi";
import { toast } from "sonner";
import WithVendorAuth from "../WithVendorAuth";
import R2ImageUploader from "@/components/shared/r2-image-uploader";
import Image from "next/image";
import { getImageUrl } from "@/lib/utils";

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
	const [isVerifiedBadge, setIsVerifiedBadge] = useState(false);

	// Pickup point
	const [pickupCityId, setPickupCityId] = useState<number | null>(null);
	const [pickupZoneId, setPickupZoneId] = useState<number | null>(null);
	const [pickupAreaId, setPickupAreaId] = useState<number | null>(null);
	const [pickupAddress, setPickupAddress] = useState("");

	// Logo & banner
	const [logoFile, setLogoFile] = useState<File | null>(null);
	const [bannerFile, setBannerFile] = useState<File | null>(null);
	const [existingLogo, setExistingLogo] = useState<string>("");
	const [existingBanner, setExistingBanner] = useState<string>("");
	const [pendingLogoUrl, setPendingLogoUrl] = useState<string>("");
	const [pendingBannerUrl, setPendingBannerUrl] = useState<string>("");

	const [kycType, setKycType] = useState("");
	const [kycNumber, setKycNumber] = useState("");
	const [kycFile, setKycFile] = useState<File | null>(null);

	// Password change
	const [changePassword, { isLoading: isChangingPassword }] = useChangeVendorPasswordMutation();
	const [oldPassword, setOldPassword] = useState("");
	const [newPassword, setNewPassword] = useState("");
	const [confirmPassword, setConfirmPassword] = useState("");

	const handleChangePassword = async (e: React.FormEvent) => {
		e.preventDefault();
		if (newPassword !== confirmPassword) {
			toast.error("Passwords do not match.");
			return;
		}
		try {
			const res = await changePassword({
				old_password: oldPassword,
				password: newPassword,
				password_confirmation: confirmPassword,
			}).unwrap();
			toast.success(res.message || "Password changed successfully.");
			setOldPassword("");
			setNewPassword("");
			setConfirmPassword("");
		} catch (err: any) {
			const msg = err?.data?.message || "Failed to change password.";
			toast.error(msg);
		}
	};

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
			setIsVerifiedBadge(Boolean(vendor.is_verified_badge));
			setExistingLogo(vendor.logo_path ?? "");
			setExistingBanner(vendor.banner_path ?? "");
			setPendingLogoUrl(vendor.pending_logo_path ?? "");
			setPendingBannerUrl(vendor.pending_banner_path ?? "");
			setPickupCityId(vendor.pickup_city_id ?? null);
			setPickupZoneId(vendor.pickup_zone_id ?? null);
			setPickupAreaId(vendor.pickup_area_id ?? null);
			setPickupAddress(vendor.pickup_address ?? "");
		} else if (user) {
			setContactName(user.name ?? "");
			setContactEmail(user.email ?? "");
			setContactPhone(user.phone ?? "");
			setIsVerifiedBadge(false);
		}
	}, [data]);

	const handleSubmit = async (e: React.FormEvent) => {
		e.preventDefault();
		try {
			const formData = new FormData();
			formData.append("company_name", companyName);
			if (businessType) formData.append("business_type", businessType);
			if (contactName) formData.append("contact_name", contactName);
			if (contactEmail) formData.append("contact_email", contactEmail);
			if (contactPhone) formData.append("contact_phone", contactPhone);
			if (country) formData.append("country", country);
			if (city) formData.append("city", city);
			if (addressLine1) formData.append("address_line_1", addressLine1);
			if (logoFile && isVerifiedBadge) formData.append("logo_path", logoFile);
			if (bannerFile && isVerifiedBadge) formData.append("banner_path", bannerFile);
			if (pickupCityId) formData.append("pickup_city_id", String(pickupCityId));
			if (pickupZoneId) formData.append("pickup_zone_id", String(pickupZoneId));
			if (pickupAreaId) formData.append("pickup_area_id", String(pickupAreaId));
			if (pickupAddress) formData.append("pickup_address", pickupAddress);

			const result = await saveProfile(formData).unwrap();
			const hadBranding = Boolean(logoFile) || Boolean(bannerFile);
			setLogoFile(null);
			setBannerFile(null);
			if (hadBranding) {
				if (logoFile) setPendingLogoUrl("pending");
				if (bannerFile) setPendingBannerUrl("pending");
				toast.success(result?.message || "Profile saved. Your logo/banner changes are pending admin approval.");
			} else {
				toast.success("Profile saved successfully.");
			}
		} catch (err: unknown) {
			console.error(err);
			toast.error("Unable to save profile. Please try again.");
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
			toast.success("KYC document submitted successfully.");
		} catch (err: unknown) {
			console.error(err);
			toast.error("Unable to submit KYC document. Please try again.");
		}
	};

	const vendor = data?.data?.vendor ?? null;

	return (
		<WithVendorAuth>
			<div className="space-y-6">
				{/* ── Supplier Info Card ── */}
				{vendor && (
					<div className="rounded-xl overflow-hidden shadow-sm border border-gray-100">
						{/* Gradient Header */}
						<div className="relative bg-gradient-to-r from-[#2d2a5d] via-[#3b3878] to-[#4a45a0] px-5 py-6 sm:px-8 sm:py-8">
							{/* Decorative circles */}
							<div className="absolute top-0 right-0 w-40 h-40 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/4" />
							<div className="absolute bottom-0 left-20 w-24 h-24 bg-white/5 rounded-full translate-y-1/2" />

							<div className="relative z-10 flex flex-col sm:flex-row sm:items-center gap-5">
								{/* Logo */}
								<div className="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl bg-white/10 border-2 border-white/20 backdrop-blur-sm overflow-hidden flex-shrink-0 flex items-center justify-center">
									{existingLogo ? (
										<Image
											src={getImageUrl(existingLogo)}
											alt={companyName || "Supplier logo"}
											width={96}
											height={96}
											className="w-full h-full object-cover"
										/>
									) : (
										<span className="text-3xl font-bold text-white/60">
											{(companyName || "S").charAt(0).toUpperCase()}
										</span>
									)}
								</div>

								{/* Identity Info */}
								<div className="flex-1 min-w-0">
									<div className="flex flex-wrap items-center gap-2 mb-1">
										<span className="text-white/60 text-xs font-mono tracking-wider bg-white/10 px-2.5 py-0.5 rounded-full">
											SID-{String(vendor.id).padStart(5, "0")}
										</span>
										{vendorStatus && (
											<span
												className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider ${
													vendorStatus === "approved"
														? "bg-emerald-400/20 text-emerald-300"
														: vendorStatus === "rejected"
															? "bg-red-400/20 text-red-300"
															: "bg-amber-400/20 text-amber-300"
												}`}
											>
												{vendorStatus}
											</span>
										)}
										{isVerifiedBadge && (
											<span className="inline-flex items-center gap-1 rounded-full bg-sky-400/20 text-sky-300 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider">
												✓ Verified
											</span>
										)}
									</div>
									<h2 className="text-xl sm:text-2xl font-bold text-white truncate">
										{companyName || "Your Business"}
									</h2>
									{businessType && (
										<p className="text-white/50 text-sm mt-0.5">{businessType}</p>
									)}
									{vendor.slug && (
										<p className="text-white/40 text-xs font-mono mt-1">
											selfshop.com/supplier/{vendor.slug}
										</p>
									)}
								</div>

								{/* View Profile Button */}
								{vendor.slug && (
									<a
										href={`/supplier/${vendor.slug}`}
										target="_blank"
										rel="noopener noreferrer"
										className="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-white/10 hover:bg-white/20 border border-white/20 text-white text-sm font-medium backdrop-blur-sm transition-all self-start sm:self-center"
									>
										<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
										View Public Profile
									</a>
								)}
							</div>
						</div>

						{/* Stats Bar */}
						<div className="bg-white px-5 sm:px-8 py-4 flex flex-wrap gap-6 sm:gap-10">
							<div className="flex items-center gap-3">
								<div className="w-10 h-10 rounded-lg bg-pink-50 flex items-center justify-center">
									<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#E5005F" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
								</div>
								<div>
									<p className="text-lg font-bold text-gray-900">{vendor.followers_count ?? 0}</p>
									<p className="text-xs text-gray-500">Followers</p>
								</div>
							</div>

							<div className="flex items-center gap-3">
								<div className="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center">
									<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#4a45a0" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
								</div>
								<div>
									<p className="text-lg font-bold text-gray-900">{vendor.total_products ?? 0}</p>
									<p className="text-xs text-gray-500">Products</p>
								</div>
							</div>

							<div className="flex items-center gap-3">
								<div className="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center">
									<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="#F59E0B" stroke="#F59E0B" strokeWidth="1" strokeLinecap="round" strokeLinejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
								</div>
								<div>
									<p className="text-lg font-bold text-gray-900">{vendor.avg_rating ?? 0}</p>
									<p className="text-xs text-gray-500">Avg Rating</p>
								</div>
							</div>
						</div>
					</div>
				)}

				{/* Shop Branding — Logo & Banner */}
				{isVerifiedBadge ? (
					<div className="rounded-xl bg-white p-4 sm:p-6 shadow-sm border border-gray-100 space-y-5">
						<div>
							<h2 className="text-lg font-semibold text-gray-900 mb-1">
								Shop branding
							</h2>
							<p className="text-sm text-gray-500">
								Upload your shop logo and cover banner. These are displayed on your public storefront.
							</p>
							{(pendingLogoUrl || pendingBannerUrl) && (
								<div className="mt-2 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3">
									<p className="text-sm text-amber-800 font-medium">
										Your {pendingLogoUrl && pendingBannerUrl ? 'logo & banner' : pendingLogoUrl ? 'logo' : 'banner'} update is pending admin approval.
									</p>
									<p className="text-xs text-amber-600 mt-1">
										Your current images will remain visible until the changes are approved.
									</p>
								</div>
							)}
						</div>

						<div className="grid grid-cols-1 md:grid-cols-2 gap-6">
							{/* Logo */}
							<div>
								<label className="block text-sm font-medium text-gray-700 mb-2">
									Shop logo
									<span className="text-gray-400 font-normal ml-1">(max 5MB)</span>
								</label>
								{existingLogo && !logoFile && (
									<div className="mb-3 w-24 h-24 rounded-full overflow-hidden border-2 border-gray-200 bg-gray-50">
										<Image
											src={getImageUrl(existingLogo)}
											alt="Current logo"
											width={96}
											height={96}
											className="w-full h-full object-cover"
										/>
									</div>
								)}
								<R2ImageUploader
									value={logoFile}
									existingImageUrl={existingLogo ? getImageUrl(existingLogo) : undefined}
									onChange={(file) => setLogoFile(file)}
									accept="image/*"
									maxSizeMB={5}
									compact
								/>
								{pendingLogoUrl && pendingLogoUrl !== "pending" && (
									<div className="mt-2 flex items-center gap-2 bg-amber-50 border border-amber-200 rounded-lg p-2">
										<Image
											src={getImageUrl(pendingLogoUrl)}
											alt="Pending logo"
											width={40}
											height={40}
											className="w-10 h-10 rounded-full object-cover border-2 border-amber-300"
										/>
										<span className="text-xs text-amber-700 font-medium">Pending approval</span>
									</div>
								)}
							</div>

							{/* Banner */}
							<div>
								<label className="block text-sm font-medium text-gray-700 mb-2">
									Cover banner
									<span className="text-gray-400 font-normal ml-1">(max 5MB, recommended 1200×300)</span>
								</label>
								{existingBanner && !bannerFile && (
									<div className="mb-3 w-full h-24 rounded-lg overflow-hidden border-2 border-gray-200 bg-gray-50">
										<Image
											src={getImageUrl(existingBanner)}
											alt="Current banner"
											width={600}
											height={150}
											className="w-full h-full object-cover"
										/>
									</div>
								)}
								<R2ImageUploader
									value={bannerFile}
									existingImageUrl={existingBanner ? getImageUrl(existingBanner) : undefined}
									onChange={(file) => setBannerFile(file)}
									accept="image/*"
									maxSizeMB={5}
									compact
								/>
								{pendingBannerUrl && pendingBannerUrl !== "pending" && (
									<div className="mt-2 flex items-center gap-2 bg-amber-50 border border-amber-200 rounded-lg p-2">
										<Image
											src={getImageUrl(pendingBannerUrl)}
											alt="Pending banner"
											width={120}
											height={40}
											className="w-[120px] h-10 rounded object-cover border-2 border-amber-300"
										/>
										<span className="text-xs text-amber-700 font-medium">Pending approval</span>
									</div>
								)}
							</div>
						</div>
					</div>
				) : (
					<div className="rounded-xl bg-white p-4 sm:p-6 shadow-sm border border-gray-100">
						<h2 className="text-lg font-semibold text-gray-900 mb-3">
							Shop branding
						</h2>
						<div className="flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-lg p-4">
							<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#d97706" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="flex-shrink-0 mt-0.5">
								<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
								<path d="M7 11V7a5 5 0 0 1 10 0v4"/>
							</svg>
							<div>
								<p className="font-semibold text-sm text-amber-800">Verification Required</p>
								<p className="text-xs text-amber-600 mt-1">
									Only verified suppliers can upload a shop logo and cover banner.
									Submit your KYC documents below and wait for admin approval to unlock this feature.
								</p>
							</div>
						</div>
					</div>
				)}

				<div className="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
					<form
						onSubmit={handleSubmit}
						className="rounded-xl bg-white p-4 sm:p-6 shadow-sm border border-gray-100 space-y-4 lg:col-span-2"
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
								<select
									value={businessType}
									onChange={(e) => setBusinessType(e.target.value)}
									className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white"
								>
									<option value="">Select business type...</option>
									<option value="Manufacturer">Manufacturer</option>
									<option value="Wholesaler">Wholesaler</option>
									<option value="Distributor">Distributor</option>
									<option value="Importer">Importer</option>
									<option value="Exporter">Exporter</option>
									<option value="Service">Service</option>
								</select>
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

					{/* ── Pickup Point (Carry Bee) ── */}
					<PickupPointSection
						pickupCityId={pickupCityId}
						setPickupCityId={setPickupCityId}
						pickupZoneId={pickupZoneId}
						setPickupZoneId={setPickupZoneId}
						pickupAreaId={pickupAreaId}
						setPickupAreaId={setPickupAreaId}
						pickupAddress={pickupAddress}
						setPickupAddress={setPickupAddress}
					/>

					<button
							type="submit"
							disabled={isSaving || isLoading}
							className="inline-flex items-center px-4 py-2 rounded-lg bg-[#2d2a5d] text-white text-sm font-medium hover:bg-[#252947] disabled:opacity-60"
						>
							{isSaving ? "Saving..." : "Save profile"}
						</button>
					</form>

					<div className="rounded-xl bg-white p-4 sm:p-6 shadow-sm border border-gray-100 space-y-4">
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
								<select
									required
									value={kycType}
									onChange={(e) => setKycType(e.target.value)}
									className="mt-1 rounded-md border border-gray-300 px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white"
								>
									<option value="">Select document type</option>
									<option value="nid">NID</option>
									<option value="trade_license">Trade License</option>
									<option value="passport">Passport</option>
									<option value="driving_license">Driving License</option>
									<option value="tin_certificate">TIN Certificate</option>
									<option value="other">Other</option>
								</select>
							</label>
							<label className="flex flex-col text-xs font-medium text-gray-700">
								Document number (optional)
								<input
									value={kycNumber}
									onChange={(e) => setKycNumber(e.target.value)}
									className="mt-1 rounded-md border border-gray-300 px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
								/>
							</label>
							<div className="flex flex-col text-xs font-medium text-gray-700">
								<span>Document file (image/PDF)</span>
								<R2ImageUploader
									value={kycFile}
									onChange={(file) => setKycFile(file)}
									accept="image/*,application/pdf"
									maxSizeMB={2}
									compact
								/>
							</div>
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
								<ul className="space-y-2 max-h-64 overflow-y-auto text-xs">
									{(kycData.data?.documents ?? []).map((doc) => {
										const docUrl = doc.document_path ? getImageUrl(doc.document_path) : null;
										const isImage = docUrl && /\.(jpg|jpeg|png|webp|gif)$/i.test(docUrl);
										return (
										<li
											key={doc.id}
											className="rounded-lg bg-gray-50 border border-gray-100 p-2.5"
										>
											<div className="flex items-start gap-3">
												{/* Document Preview */}
												{docUrl && (
													<a href={docUrl} target="_blank" rel="noopener noreferrer" className="flex-shrink-0">
														{isImage ? (
															<Image
																src={docUrl}
																alt={doc.document_type}
																width={56}
																height={56}
																className="w-14 h-14 rounded-md object-cover border border-gray-200 hover:border-indigo-400 transition-colors"
															/>
														) : (
															<div className="w-14 h-14 rounded-md bg-gray-100 border border-gray-200 flex items-center justify-center hover:border-indigo-400 transition-colors">
																<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6b7280" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
																	<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
																	<polyline points="14 2 14 8 20 8"/>
																	<line x1="16" y1="13" x2="8" y2="13"/>
																	<line x1="16" y1="17" x2="8" y2="17"/>
																</svg>
															</div>
														)}
													</a>
												)}
												{/* Info */}
												<div className="flex-1 min-w-0">
													<div className="flex items-center justify-between gap-2">
														<span className="font-medium text-gray-800 capitalize">
															{doc.document_type.replace(/_/g, " ")}
															{doc.document_number
																? ` • ${doc.document_number}`
																: ""}
														</span>
														<span
															className={`inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold flex-shrink-0 ${doc.status === "approved"
																? "bg-emerald-100 text-emerald-700"
																: doc.status === "rejected"
																	? "bg-red-100 text-red-700"
																	: "bg-amber-100 text-amber-700"
																}`}
														>
															{doc.status}
														</span>
													</div>
													<span className="text-[10px] text-gray-500">
														{new Date(doc.created_at).toLocaleString()}
													</span>
													{docUrl && (
														<a
															href={docUrl}
															target="_blank"
															rel="noopener noreferrer"
															className="block text-[10px] text-indigo-500 hover:text-indigo-700 mt-0.5"
														>
															View full document ↗
														</a>
													)}
												</div>
											</div>
										</li>
									);
									})}
								</ul>
							)}
						</div>
					</div>
				</div>

				{/* ── Change Password ── */}
				<div className="rounded-xl bg-white shadow-sm border border-gray-100 overflow-hidden">
					<div className="bg-gradient-to-r from-amber-50 to-orange-50 px-5 py-4 border-b border-amber-100">
						<h2 className="text-lg font-semibold text-gray-900 flex items-center gap-2">
							<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#d97706" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
							Change Password
						</h2>
						<p className="text-sm text-gray-500 mt-1">Update your account password. Use a strong password with at least 8 characters.</p>
					</div>
					<form onSubmit={handleChangePassword} className="p-5 space-y-4">
						<label className="flex flex-col text-sm font-medium text-gray-700">
							Current Password <span className="text-red-500">*</span>
							<input
								required
								type="password"
								value={oldPassword}
								onChange={(e) => setOldPassword(e.target.value)}
								placeholder="Enter current password"
								autoComplete="current-password"
								className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
							/>
						</label>
						<div className="grid grid-cols-1 md:grid-cols-2 gap-4">
							<label className="flex flex-col text-sm font-medium text-gray-700">
								New Password <span className="text-red-500">*</span>
								<span className="text-gray-400 font-normal text-xs">(min 8 characters)</span>
								<input
									required
									type="password"
									value={newPassword}
									onChange={(e) => setNewPassword(e.target.value)}
									placeholder="Enter new password"
									autoComplete="new-password"
									minLength={8}
									className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
								/>
							</label>
							<label className="flex flex-col text-sm font-medium text-gray-700">
								Confirm New Password <span className="text-red-500">*</span>
								<span className="text-gray-400 font-normal text-xs">&nbsp;</span>
								<input
									required
									type="password"
									value={confirmPassword}
									onChange={(e) => setConfirmPassword(e.target.value)}
									placeholder="Confirm new password"
									autoComplete="new-password"
									className={`mt-1 rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 ${
										confirmPassword.length > 0 && newPassword !== confirmPassword
											? "border-red-400"
											: "border-gray-300"
									}`}
								/>
								{confirmPassword.length > 0 && newPassword !== confirmPassword && (
									<span className="text-xs text-red-500 mt-1 flex items-center gap-1">
										<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
										Passwords do not match
									</span>
								)}
							</label>
						</div>
						<div className="flex justify-end pt-2">
							<button
								type="submit"
								disabled={isChangingPassword || (confirmPassword.length > 0 && newPassword !== confirmPassword)}
								className="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-amber-600 text-white text-sm font-medium hover:bg-amber-700 disabled:opacity-60 transition-colors"
							>
								<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
								{isChangingPassword ? "Changing..." : "Change Password"}
							</button>
						</div>
					</form>
				</div>
			</div>
		</WithVendorAuth>
	);
}

/* ────────────────────────────────────────────────────────────── */
/*  Pickup Point sub-component – reuses CarryBee hooks            */
/* ────────────────────────────────────────────────────────────── */

function PickupPointSection({
	pickupCityId,
	setPickupCityId,
	pickupZoneId,
	setPickupZoneId,
	pickupAreaId,
	setPickupAreaId,
	pickupAddress,
	setPickupAddress,
}: {
	pickupCityId: number | null;
	setPickupCityId: (v: number | null) => void;
	pickupZoneId: number | null;
	setPickupZoneId: (v: number | null) => void;
	pickupAreaId: number | null;
	setPickupAreaId: (v: number | null) => void;
	pickupAddress: string;
	setPickupAddress: (v: string) => void;
}) {
	const { data: citiesData, isLoading: citiesLoading } =
		useGetCarryBeeCitiesQuery();
	const { data: zonesData, isLoading: zonesLoading } =
		useGetCarryBeeZonesQuery(pickupCityId!, { skip: !pickupCityId });
	const { data: areasData, isLoading: areasLoading } =
		useGetCarryBeeAreasQuery(
			{ cityId: pickupCityId!, zoneId: pickupZoneId! },
			{ skip: !pickupCityId || !pickupZoneId },
		);

	const cities = citiesData?.data?.cities ?? [];
	const zones = zonesData?.data?.zones ?? [];
	const areas = areasData?.data?.areas ?? [];

	const selectClass =
		"mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white";
	const inputClass =
		"mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500";

	return (
		<div className="border border-gray-200 rounded-lg p-4 space-y-3 bg-gray-50 col-span-full">
			<p className="text-sm font-semibold text-gray-800">📍 Pickup Point</p>
			<p className="text-xs text-gray-500">
				Select the nearest pickup location for courier pickups.
			</p>

			<div className="grid grid-cols-1 md:grid-cols-3 gap-3">
				{/* City */}
				<label className="flex flex-col text-sm font-medium text-gray-700">
					City
					<select
						value={pickupCityId ?? ""}
						onChange={(e) => {
							setPickupCityId(
								e.target.value ? Number(e.target.value) : null,
							);
							setPickupZoneId(null);
							setPickupAreaId(null);
						}}
						className={selectClass}
						disabled={citiesLoading}
					>
						<option value="">
							{citiesLoading ? "Loading..." : "Select city"}
						</option>
						{cities.map((c: any) => (
							<option key={c.id} value={c.id}>
								{c.name}
							</option>
						))}
					</select>
				</label>

				{/* Zone */}
				<label className="flex flex-col text-sm font-medium text-gray-700">
					Zone
					<select
						value={pickupZoneId ?? ""}
						onChange={(e) => {
							setPickupZoneId(
								e.target.value ? Number(e.target.value) : null,
							);
							setPickupAreaId(null);
						}}
						className={selectClass}
						disabled={!pickupCityId || zonesLoading}
					>
						<option value="">
							{zonesLoading
								? "Loading..."
								: !pickupCityId
									? "Select city first"
									: "Select zone"}
						</option>
						{zones.map((z: any) => (
							<option key={z.id} value={z.id}>
								{z.name}
							</option>
						))}
					</select>
				</label>

				{/* Area */}
				<label className="flex flex-col text-sm font-medium text-gray-700">
					Area
					<select
						value={pickupAreaId ?? ""}
						onChange={(e) =>
							setPickupAreaId(
								e.target.value ? Number(e.target.value) : null,
							)
						}
						className={selectClass}
						disabled={!pickupZoneId || areasLoading}
					>
						<option value="">
							{areasLoading
								? "Loading..."
								: !pickupZoneId
									? "Select zone first"
									: "Select area"}
						</option>
						{areas.map((a: any) => (
							<option key={a.id} value={a.id}>
								{a.name}
							</option>
						))}
					</select>
				</label>
			</div>

			<label className="flex flex-col text-sm font-medium text-gray-700">
				Pickup address
				<input
					value={pickupAddress}
					onChange={(e) => setPickupAddress(e.target.value)}
					placeholder="Full address for courier pickup"
					className={inputClass}
				/>
			</label>
		</div>
	);
}
