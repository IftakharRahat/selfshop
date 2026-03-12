"use client";

import Link from "next/link";
import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import {
	useRegisterVendorMutation,
	useGetCarryBeeCitiesQuery,
	useGetCarryBeeZonesQuery,
	useGetCarryBeeAreasQuery,
} from "@/redux/api/vendorApi";
import { toast } from "sonner";

const VendorRegisterPage = () => {
	const router = useRouter();
	const [registerVendor, { isLoading }] = useRegisterVendorMutation();

	const [name, setName] = useState("");
	const [phone, setPhone] = useState("");
	const [password, setPassword] = useState("");
	const [companyName, setCompanyName] = useState("");
	const [businessType, setBusinessType] = useState("");
	const [pickupAddress, setPickupAddress] = useState("");

	// Carry Bee cascading selection
	const [selectedCityId, setSelectedCityId] = useState<number | null>(null);
	const [selectedZoneId, setSelectedZoneId] = useState<number | null>(null);
	const [selectedAreaId, setSelectedAreaId] = useState<number | null>(null);

	const { data: citiesData, isLoading: citiesLoading } = useGetCarryBeeCitiesQuery();
	const { data: zonesData, isLoading: zonesLoading } = useGetCarryBeeZonesQuery(
		selectedCityId!,
		{ skip: !selectedCityId }
	);
	const { data: areasData, isLoading: areasLoading } = useGetCarryBeeAreasQuery(
		{ cityId: selectedCityId!, zoneId: selectedZoneId! },
		{ skip: !selectedCityId || !selectedZoneId }
	);

	const cities = citiesData?.data?.cities ?? [];
	const zones = zonesData?.data?.zones ?? [];
	const areas = areasData?.data?.areas ?? [];

	// Reset downstream when upstream changes
	useEffect(() => {
		setSelectedZoneId(null);
		setSelectedAreaId(null);
	}, [selectedCityId]);

	useEffect(() => {
		setSelectedAreaId(null);
	}, [selectedZoneId]);

	const handleSubmit = async (e: React.FormEvent) => {
		e.preventDefault();
		try {
			const res = await registerVendor({
				name,
				phone,
				password,
				company_name: companyName,
				business_type: businessType,
				pickup_city_id: selectedCityId ?? undefined,
				pickup_zone_id: selectedZoneId ?? undefined,
				pickup_area_id: selectedAreaId ?? undefined,
				pickup_address: pickupAddress || undefined,
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

	const selectClass =
		"mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white";
	const inputClass =
		"mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500";

	return (
		<div className="min-h-screen flex items-center justify-center bg-gray-50 px-4">
			<div className="w-full max-w-xl rounded-2xl bg-white shadow-sm border border-gray-100 p-8 space-y-6">
				<div className="space-y-1 text-center">
					<h1 className="text-2xl font-bold text-gray-900">
						Become a SelfShop supplier
					</h1>
					<p className="text-sm text-gray-600">
						Create your supplier account. We will review your details before
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
								className={inputClass}
							/>
						</label>
						<label className="flex flex-col text-sm font-medium text-gray-700">
							Phone number
							<input
								required
								type="tel"
								placeholder="01XXXXXXXXX"
								value={phone}
								onChange={(e) => setPhone(e.target.value)}
								className={inputClass}
							/>
						</label>
						<label className="flex flex-col text-sm font-medium text-gray-700">
							Password
							<input
								required
								type="password"
								value={password}
								onChange={(e) => setPassword(e.target.value)}
								className={inputClass}
							/>
						</label>
						<label className="flex flex-col text-sm font-medium text-gray-700">
							Company name
							<input
								required
								value={companyName}
								onChange={(e) => setCompanyName(e.target.value)}
								className={inputClass}
							/>
						</label>
					</div>

					<label className="flex flex-col text-sm font-medium text-gray-700">
						Business type
						<select
							value={businessType}
							onChange={(e) => setBusinessType(e.target.value)}
							className={selectClass}
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

					{/* ── Pickup Point (Carry Bee) ── */}
					<div className="border border-gray-200 rounded-lg p-4 space-y-3 bg-gray-50">
						<p className="text-sm font-semibold text-gray-800">
							📍 Pickup Point
						</p>
						<p className="text-xs text-gray-500">
							Select the nearest pickup location for courier pickups.
						</p>

						<div className="grid grid-cols-1 md:grid-cols-3 gap-3">
							{/* City */}
							<label className="flex flex-col text-sm font-medium text-gray-700">
								City
								<select
									value={selectedCityId ?? ""}
									onChange={(e) =>
										setSelectedCityId(
											e.target.value ? Number(e.target.value) : null,
										)
									}
									className={selectClass}
									disabled={citiesLoading}
								>
									<option value="">
										{citiesLoading ? "Loading..." : "Select city"}
									</option>
									{cities.map((c) => (
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
									value={selectedZoneId ?? ""}
									onChange={(e) =>
										setSelectedZoneId(
											e.target.value ? Number(e.target.value) : null,
										)
									}
									className={selectClass}
									disabled={!selectedCityId || zonesLoading}
								>
									<option value="">
										{zonesLoading
											? "Loading..."
											: !selectedCityId
												? "Select city first"
												: "Select zone"}
									</option>
									{zones.map((z) => (
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
									value={selectedAreaId ?? ""}
									onChange={(e) =>
										setSelectedAreaId(
											e.target.value ? Number(e.target.value) : null,
										)
									}
									className={selectClass}
									disabled={!selectedZoneId || areasLoading}
								>
									<option value="">
										{areasLoading
											? "Loading..."
											: !selectedZoneId
												? "Select zone first"
												: "Select area"}
									</option>
									{areas.map((a) => (
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

					<button
						type="submit"
						disabled={isLoading}
						className="w-full inline-flex items-center justify-center px-4 py-2 rounded-lg bg-[#2d2a5d] text-white text-sm font-medium hover:bg-[#252947] disabled:opacity-60"
					>
						{isLoading ? "Submitting..." : "Submit registration"}
					</button>
				</form>

				<p className="text-xs text-gray-500 text-center">
					After approval, you can sign in from the supplier login page using the
					same phone number and password.
				</p>
				<div className="text-center">
					<Link
						href="/vendor/login"
						className="inline-flex items-center justify-center text-sm font-semibold text-indigo-700 hover:text-indigo-900"
					>
						Back to supplier login
					</Link>
				</div>
			</div>
		</div>
	);
};

export default VendorRegisterPage;
