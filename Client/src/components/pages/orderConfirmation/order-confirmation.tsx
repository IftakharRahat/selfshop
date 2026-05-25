/* eslint-disable @typescript-eslint/no-unused-vars */
/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { Bookmark, BookmarkCheck, Minus, Plus, Trash2, X } from "lucide-react";
import Image from "next/image";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { FaCheckCircle } from "react-icons/fa";
import { IoMdInformationCircleOutline } from "react-icons/io";
import { TbCurrencyTaka } from "react-icons/tb";
import Swal from "sweetalert2";
import { z } from "zod";
import { getImageUrl } from "@/lib/utils";
import { formatBDT } from "@/lib/format-currency";
import {
	useCreateOrderMutation,
	useDeleteCartItemMutation,
	useGetAllCartItemsQuery,
	useUpdateCartItemMutation,
	useGetDeliveryChargesQuery,
} from "@/redux/features/cartApi";
import { useGetBasicInfoQuery } from "@/redux/features/home/homeApi";
import {
	useGetShippingAddressesQuery,
	useCreateShippingAddressMutation,
	useDeleteShippingAddressMutation,
} from "@/redux/features/shippingAddressApi";
import {
	useGetCarryBeeCitiesQuery,
	useGetCarryBeeZonesQuery,
	useGetCarryBeeAreasQuery,
} from "@/redux/api/vendorApi";
import { useAppSelector } from "@/redux/hooks";
import { handleAsyncWithToast } from "@/utils/handleAsyncWithToast";

// ✅ Zod Schema for Validation
const customerSchema = z.object({
	name: z.string().min(3, "Customer name must be at least 3 characters"),
	address: z.string().min(5, "Address must be at least 5 characters"),
	phone: z
		.string()
		.regex(
			/^01[0-9]{9}$/,
			"Invalid Bangladeshi phone number (must be 11 digits & start with 01)",
		),
	note: z.string().optional(),
});


export default function OrderConfirmation() {
	const router = useRouter();
	const {
		data: cartItems,
		isLoading,
		isError: isCartError,
		isSuccess: isCartSuccess,
	} = useGetAllCartItemsQuery(undefined);

	// Avoid redirecting on initial render — give RTK Query time to refetch after addToCart invalidation
	const [ready, setReady] = useState(false);
	useEffect(() => {
		const timer = setTimeout(() => setReady(true), 1500);
		return () => clearTimeout(timer);
	}, []);

	// Redirect only when cart fetch succeeds and is truly empty.
	// Avoid redirecting on transient API/auth/network errors.
	useEffect(() => {
		if (
			ready &&
			!isLoading &&
			isCartSuccess &&
			!isCartError &&
			(!cartItems?.data || cartItems.data.length === 0)
		) {
			router.replace("/");
		}
	}, [cartItems, isLoading, isCartSuccess, isCartError, router, ready]);
	const [updateCartItem] = useUpdateCartItemMutation();
	const [deleteCartItem] = useDeleteCartItemMutation();
	const [createOrder] = useCreateOrderMutation();
	const [selected, setSelected] = useState("account");
	const [agreedToTerms, setAgreedToTerms] = useState(false);
	const [advanceDelivery, setAdvanceDelivery] = useState<"yes" | "no">("no");
	const [customerData, setCustomerData] = useState({
		name: "",
		address: "",
		phone: "",
		note: "",
	});

	const [errors, setErrors] = useState<Record<string, string>>({});

	// Saved addresses from API
	const token = useAppSelector((state) => state.auth.access_token);
	const { data: savedAddressesData } = useGetShippingAddressesQuery(undefined, { skip: !token });
	const savedAddresses = savedAddressesData?.data || [];
	const [createShippingAddress] = useCreateShippingAddressMutation();
	const [deleteShippingAddress] = useDeleteShippingAddressMutation();
	const [showSaveInput, setShowSaveInput] = useState(false);
	const [saveLabel, setSaveLabel] = useState("");

	// CarryBee city/zone/area for courier routing
	const [selectedCityId, setSelectedCityId] = useState<number | null>(null);
	const [selectedZoneId, setSelectedZoneId] = useState<number | null>(null);
	const [selectedAreaId, setSelectedAreaId] = useState<number | null>(null);

	const { data: citiesData, isLoading: citiesLoading } = useGetCarryBeeCitiesQuery();
	const { data: zonesData, isLoading: zonesLoading } = useGetCarryBeeZonesQuery(selectedCityId!, { skip: !selectedCityId });
	const { data: areasData, isLoading: areasLoading } = useGetCarryBeeAreasQuery(
		{ cityId: selectedCityId!, zoneId: selectedZoneId! },
		{ skip: !selectedCityId || !selectedZoneId }
	);

	const cities = citiesData?.data?.cities ?? [];
	const zones = zonesData?.data?.zones ?? [];
	const areas = areasData?.data?.areas ?? [];

	// Auto-computed delivery charges from backend (CarryBee city matching)
	const { data: deliveryChargeData, isFetching: chargesFetching, isError: chargesError } = useGetDeliveryChargesQuery(
		{ cityId: selectedCityId! },
		{ skip: !selectedCityId }
	);

	// Fallback: basic info charges for client-side computation when API unavailable
	const { data: basicInfoData } = useGetBasicInfoQuery(undefined);
	const insideDhakaCharge: number = Number(basicInfoData?.data?.default_same_city_charge || basicInfoData?.data?.inside_dhaka_charge) || 60;
	const outsideDhakaCharge: number = Number(basicInfoData?.data?.default_inter_city_charge || basicInfoData?.data?.outside_dhaka_charge) || 130;

	// Client-side fallback: compute delivery per vendor when API endpoint is unavailable
	const clientFallbackCharges = (() => {
		if (!selectedCityId || (!chargesError && deliveryChargeData)) return null;
		if (!cartItems?.data || cartItems.data.length === 0) return null;

		// Group by vendor and check city match
		const vendorMap = new Map<number, { vendor_id: number; vendor_name: string; pickup_city_id?: number }>();
		for (const item of cartItems.data) {
			const vid = item.vendor_id || item.shop_id || 0;
			if (!vendorMap.has(vid)) {
				vendorMap.set(vid, {
					vendor_id: vid,
					vendor_name: item.shop_name || 'Supplier',
					pickup_city_id: item.pickup_city_id,
				});
			}
		}

		let total = 0;
		const vendors: Array<{ vendor_id: number; charge: number; zone: string; zone_label: string; vendor_name: string }> = [];
		for (const [, v] of vendorMap) {
			const isSameCity = v.pickup_city_id != null && v.pickup_city_id === selectedCityId;
			const charge = isSameCity ? insideDhakaCharge : outsideDhakaCharge;
			total += charge;
			vendors.push({
				vendor_id: v.vendor_id,
				charge,
				zone: isSameCity ? 'same_city' : 'inter_city',
				zone_label: isSameCity ? 'Same City' : 'Inter-City',
				vendor_name: v.vendor_name,
			});
		}

		return { vendors, total_charge: total, same_city_charge: insideDhakaCharge, inter_city_charge: outsideDhakaCharge };
	})();

	// Use API data if available, otherwise use client-side fallback
	const effectiveChargeData = deliveryChargeData ?? clientFallbackCharges;

	// Extra delivery charge per product (from backend cart API)
	const extraDeliveryCharge: number = Number(cartItems?.extra_delivery_charge ?? 0);

	// Delivery charge resolved — per vendor city matching + extra per-product charges
	const deliveryCharge: number = selectedCityId && effectiveChargeData
		? (effectiveChargeData.total_charge ?? effectiveChargeData.same_city_charge ?? 0)
		: 0;
	const deliveryZoneLabel: string | null = selectedCityId && effectiveChargeData
		? (effectiveChargeData.vendors?.[0]?.zone_label
			?? (effectiveChargeData.total_charge != null ? "Delivery Charge" : null))
		: null;
	const totalDeliveryCharge: number = deliveryCharge + extraDeliveryCharge;
	const hasDeliveryCharge = selectedCityId != null && deliveryCharge > 0;
	const isChargeLoading = chargesFetching && !chargesError;

	const handleSaveAddress = async () => {
		if (!customerData.name || !customerData.address || !customerData.phone) return;
		const label = saveLabel.trim() || `${customerData.name} - ${customerData.phone}`;
		await handleAsyncWithToast(
			async () => createShippingAddress({
				label,
				name: customerData.name,
				address: customerData.address,
				phone: customerData.phone,
				city_id: selectedCityId,
				zone_id: selectedZoneId,
				area_id: selectedAreaId,
			}),
			true,
			"Saving address...",
			"Address saved!",
		);
		setShowSaveInput(false);
		setSaveLabel("");
	};

	const handleDeleteSavedAddress = async (id: number) => {
		await handleAsyncWithToast(
			async () => deleteShippingAddress(id),
			true,
			"Removing...",
			"Address removed",
		);
	};

	const handleSelectSavedAddress = (addr: { name: string; address: string; phone: string; city_id?: number | null; zone_id?: number | null; area_id?: number | null }) => {
		setCustomerData((prev) => ({
			...prev,
			name: addr.name,
			address: addr.address,
			phone: addr.phone,
		}));
		// Auto-fill CarryBee dropdowns from saved address
		if (addr.city_id) {
			setSelectedCityId(addr.city_id);
			setSelectedZoneId(addr.zone_id ?? null);
			setSelectedAreaId(addr.area_id ?? null);
		}
		setErrors({});
	};

	const handleInputChange = (field: string, value: string) => {
		setCustomerData((prev) => ({ ...prev, [field]: value }));
	};

	const subtotal =
		cartItems?.data?.reduce(
			(total: number, item: any) => total + parseFloat(item.price) * item.qty,
			0,
		) || 0;
	const discount = 0;
	const totalProfit =
		cartItems?.data?.reduce((total: number, item: any) => {
			const sellingPrice = parseFloat(item.options?.selling_price || item.price);
			const costPrice = parseFloat(item.price);
			return total + (sellingPrice - costPrice) * item.qty;
		}, 0) || 0;
	const grandTotal =
		advanceDelivery === "yes" || !hasDeliveryCharge
			? subtotal + totalProfit - discount
			: subtotal + totalProfit - discount + totalDeliveryCharge;

	// ✅ Form Validation Before Submission
	const validateForm = () => {
		const validation = customerSchema.safeParse(customerData);
		if (!validation.success) {
			const newErrors: Record<string, string> = {};
			validation.error.errors.forEach((err) => {
				const field = err.path[0] as string;
				newErrors[field] = err.message;
			});
			setErrors(newErrors);
			return false;
		}
		setErrors({});
		return true;
	};

	const handleOrderConfirm = async () => {
		if (!validateForm()) return;
		if (!selectedCityId) {
			Swal.fire({ icon: "warning", title: "Please select a delivery city", text: "Select a city to calculate delivery charges.", confirmButtonText: "OK" });
			return;
		}

		const formData = new FormData();
		formData.append("customerName", customerData.name);
		formData.append("customerPhone", customerData.phone);
		formData.append("customerAddress", customerData.address);
		formData.append("subTotal", subtotal.toString());
		formData.append("deliveryCharge", totalDeliveryCharge.toString());
		formData.append("delivery_zone", deliveryZoneLabel ?? "Delivery Charge");
		formData.append("advance_delivery", advanceDelivery);
		if (selectedCityId) formData.append("city_id", selectedCityId.toString());
		if (selectedZoneId) formData.append("zone_id", selectedZoneId.toString());
		formData.append(
			"balance_from",
			selected === "account" ? "from_account" : "online_pay",
		);
		if (customerData.note) {
			formData.append("customerNote", customerData.note);
		}

		const result = await handleAsyncWithToast(
			async () => createOrder(formData),
			true,
			"Creating order...",
			"Order created successfully",
		);

		// Handle SSLCommerz redirect
		if (result?.data?.ssl_redirect && result?.data?.gateway_url) {
			Swal.fire({
				icon: "info",
				title: "Redirecting to Payment",
				text: "You will be redirected to the payment gateway...",
				timer: 2000,
				showConfirmButton: false,
			}).then(() => {
				window.location.href = result.data.gateway_url;
			});
			return;
		}

		if (result?.data?.status) {
			setCustomerData({ name: "", address: "", phone: "", note: "" });
			Swal.fire({
				icon: "success",
				title: "Order Confirmed",
				text: "Your order has been placed successfully!",
				confirmButtonText: "OK",
			}).then(() => {
				router.push("/");
			});
		}
	};

	const handleUpdateCartItem = async (cartId: number, newQty: number) => {
		await handleAsyncWithToast(async () => updateCartItem({ cartId, qty: newQty }));
	};

	const handleDeleteCartItem = async (cartId: number) => {
		await handleAsyncWithToast(
			async () => deleteCartItem(cartId),
			true,
			"Removing item...",
			"Item removed from cart",
		);
	};



	return (
		<div className="min-h-screen py-8">
			<div className="container mx-auto px-4 max-w-7xl">
				<div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
					{/* Left Side - Order Form */}
					<div className="bg-white rounded-lg p-6 shadow-sm order-2 lg:order-1">
						<h1 className="text-2xl font-bold text-gray-900 mb-2">
							Let&apos;s get to the confirm order
						</h1>
						<p className="text-gray-600 mb-6">
							Enter customer details to confirm the order.
						</p>

						{/* Saved Addresses */}
						{savedAddresses.length > 0 && (
							<div className="mb-6">
								<label className="block text-sm font-medium text-gray-700 mb-2">
									Saved Addresses
								</label>
								<div className="space-y-2">
									{savedAddresses.map((addr) => (
										<div
											key={addr.id}
											className="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:border-pink-300 hover:bg-pink-50/30 transition-colors cursor-pointer group"
											onClick={() => handleSelectSavedAddress(addr)}
										>
											<BookmarkCheck className="w-4 h-4 text-pink-500 flex-shrink-0" />
											<div className="flex-1 min-w-0">
												<p className="text-sm font-medium text-gray-900 truncate">{addr.label || `${addr.name} - ${addr.phone}`}</p>
												<p className="text-xs text-gray-500 truncate">{addr.address} • {addr.phone}</p>
											</div>
											<button
												onClick={(e) => {
													e.stopPropagation();
													handleDeleteSavedAddress(addr.id);
												}}
												className="opacity-0 group-hover:opacity-100 w-6 h-6 rounded-full flex items-center justify-center text-gray-400 hover:text-red-500 hover:bg-red-50 transition-all"
											>
												<X className="w-3.5 h-3.5" />
											</button>
										</div>
									))}
								</div>
							</div>
						)}

						<div className="space-y-6">
							{["name", "address", "phone"].map((field) => (
								<div key={field}>
									<label className="block text-sm font-medium text-gray-700 mb-2 capitalize">
										Customer {field}
									</label>
									<input
										type={field === "phone" ? "tel" : "text"}
										placeholder={`Enter customer ${field}`}
										value={customerData[field as keyof typeof customerData]}
										onChange={(e) => handleInputChange(field, e.target.value)}
										className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 ${errors[field]
											? "border-red-500 focus:ring-red-500"
											: "border-gray-300 focus:ring-pink-500"
											}`}
									/>
									{errors[field] && (
										<p className="text-red-500 text-sm mt-1">{errors[field]}</p>
									)}
								</div>
							))}

							{/* Custom Note */}
							<div>
								<label className="block text-sm font-medium text-gray-700 mb-2">
									Custom note
								</label>
								<textarea
									placeholder="Enter custom note"
									value={customerData.note}
									onChange={(e) => handleInputChange("note", e.target.value)}
									rows={4}
									className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 resize-none"
								/>
							</div>

				{/* Delivery City / Zone / Area (CarryBee) */}
						<div className="p-4 border border-gray-200 rounded-lg bg-gray-50">
							<p className="text-sm font-semibold text-gray-800 mb-3">Delivery City &amp; Zone</p>
							<div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
								<div>
									<label className="block text-xs font-medium text-gray-600 mb-1">City / District</label>
									<select id="delivery-city" value={selectedCityId ?? ""} onChange={(e) => { const v = e.target.value ? Number(e.target.value) : null; setSelectedCityId(v); setSelectedZoneId(null); setSelectedAreaId(null); }} className="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 bg-white">
										<option value="">{citiesLoading ? "Loading..." : "Select City"}</option>
										{cities.map((c) => (<option key={c.id} value={c.id}>{c.name}</option>))}
									</select>
								</div>
								<div>
									<label className="block text-xs font-medium text-gray-600 mb-1">Zone</label>
									<select id="delivery-zone-cb" value={selectedZoneId ?? ""} onChange={(e) => { const v = e.target.value ? Number(e.target.value) : null; setSelectedZoneId(v); setSelectedAreaId(null); }} disabled={!selectedCityId || zonesLoading} className="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 bg-white disabled:bg-gray-100 disabled:cursor-not-allowed">
										<option value="">{zonesLoading ? "Loading..." : !selectedCityId ? "Select city first" : "Select Zone"}</option>
										{zones.map((z) => (<option key={z.id} value={z.id}>{z.name}</option>))}
									</select>
								</div>
								<div>
									<label className="block text-xs font-medium text-gray-600 mb-1">Area</label>
									<select id="delivery-area" value={selectedAreaId ?? ""} onChange={(e) => setSelectedAreaId(e.target.value ? Number(e.target.value) : null)} disabled={!selectedZoneId || areasLoading} className="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 bg-white disabled:bg-gray-100 disabled:cursor-not-allowed">
										<option value="">{areasLoading ? "Loading..." : !selectedZoneId ? "Select zone first" : "Select Area (optional)"}</option>
										{areas.map((a) => (<option key={a.id} value={a.id}>{a.name}</option>))}
									</select>
								</div>
							</div>
							<p className="text-xs text-gray-500 mt-2">Selecting city &amp; zone helps ensure accurate courier delivery</p>
						</div>

							{/* Save Address Button */}
						{customerData.name && customerData.address && customerData.phone && (() => {
								const isAlreadySaved = savedAddresses.some(
									(a) => a.name === customerData.name && a.address === customerData.address && a.phone === customerData.phone
								);
								if (isAlreadySaved) {
									return (
										<div className="flex items-center gap-2 px-4 py-2.5 bg-green-50 border border-green-200 rounded-lg">
											<BookmarkCheck className="w-4 h-4 text-green-600" />
											<span className="text-sm font-medium text-green-700">Address saved</span>
										</div>
									);
								}
								return (
									<div>
										{showSaveInput ? (
											<div className="flex gap-2">
												<input
													type="text"
													placeholder="Address label (e.g. Home, Office)"
													value={saveLabel}
													onChange={(e) => setSaveLabel(e.target.value)}
													className="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-pink-500"
													onKeyDown={(e) => e.key === "Enter" && handleSaveAddress()}
												/>
												<button
													onClick={handleSaveAddress}
													className="px-4 py-2 bg-pink-600 text-white text-sm font-medium rounded-lg hover:bg-pink-700 transition-colors"
												>
													Save
												</button>
												<button
													onClick={() => { setShowSaveInput(false); setSaveLabel(""); }}
													className="px-3 py-2 text-gray-500 text-sm rounded-lg hover:bg-gray-100 transition-colors"
												>
													Cancel
												</button>
											</div>
										) : (
											<button
												onClick={() => setShowSaveInput(true)}
												className="flex items-center gap-2 text-sm font-medium text-pink-600 border border-pink-200 bg-pink-50 hover:bg-pink-100 px-4 py-2.5 rounded-lg transition-colors w-full cursor-pointer"
											>
												<Bookmark className="w-4 h-4" />
												Save this address for future orders
											</button>
										)}
									</div>
								);
							})()}
						</div>

						{/* Auto-computed Delivery Charge (from CarryBee city matching) */}
						<div className="mt-6 p-4 border border-gray-200 rounded-lg bg-gray-50">
							<p className="text-sm font-semibold text-gray-800 mb-3">
								Delivery Charge
							</p>
							{!selectedCityId ? (
								<div className="flex items-center gap-2 p-3 rounded-lg bg-amber-50 border border-amber-200">
									<IoMdInformationCircleOutline size={18} className="text-amber-600 flex-shrink-0" />
									<p className="text-sm text-amber-800">Please select a city above to calculate delivery charges</p>
								</div>
							) : isChargeLoading ? (
								<div className="flex items-center justify-center gap-2 py-4">
									<div className="w-4 h-4 border-2 border-pink-500 border-t-transparent rounded-full animate-spin"></div>
									<span className="text-sm text-gray-500">Calculating delivery charge...</span>
								</div>
							) : effectiveChargeData?.vendors && effectiveChargeData.vendors.length > 0 ? (
								<div className="space-y-2">
									{effectiveChargeData.vendors.map((v, i) => (
										<div
											key={i}
											className={`flex items-center justify-between p-3 rounded-lg ${v.zone === "same_city" ? "bg-green-50 border border-green-200" : "bg-orange-50 border border-orange-200"}`}
										>
											<div>
												<p className="text-sm font-semibold text-gray-900">{v.vendor_name || "Supplier"}</p>
												<p className={`text-xs font-medium ${v.zone === "same_city" ? "text-green-600" : "text-orange-600"}`}>{v.zone_label}</p>
											</div>
											<span className={`digit-font text-base font-bold ${v.zone === "same_city" ? "text-green-600" : "text-pink-600"}`}>৳{formatBDT(v.charge, 0)}</span>
										</div>
									))}
									{extraDeliveryCharge > 0 && (
										<div className="flex items-center justify-between p-3 rounded-lg bg-blue-50 border border-blue-200">
											<div>
												<p className="text-sm font-semibold text-gray-900">Extra Delivery (per qty)</p>
												<p className="text-xs font-medium text-blue-600">Additional charge for extra items</p>
											</div>
											<span className="digit-font text-base font-bold text-blue-600">৳{formatBDT(extraDeliveryCharge, 0)}</span>
										</div>
									)}
									{(effectiveChargeData.vendors.length > 1 || extraDeliveryCharge > 0) && (
										<div className="flex items-center justify-between pt-2 border-t border-gray-200 mt-1">
											<span className="text-sm font-bold text-gray-900">Total Delivery</span>
											<span className="digit-font text-base font-bold text-pink-600">৳{formatBDT(totalDeliveryCharge, 0)}</span>
										</div>
									)}
								</div>
							) : hasDeliveryCharge ? (
								<div className="flex items-center justify-between p-3 rounded-lg bg-green-50 border border-green-200">
									<span className="text-sm font-semibold text-gray-900">{deliveryZoneLabel ?? "Delivery Charge"}</span>
									<span className="digit-font text-base font-bold text-green-600">৳{formatBDT(totalDeliveryCharge, 0)}</span>
								</div>
							) : null}
						</div>

						{/* Advance Delivery Toggle */}
						<div className="mt-6 p-4 border border-gray-200 rounded-lg bg-gray-50">
							<p className="text-sm font-semibold text-gray-800 mb-3">
								Did customer pay advance delivery charge?
							</p>
							<div className="flex items-center gap-3">
								<label
									className={`flex items-center gap-2 border rounded-lg px-4 py-2.5 cursor-pointer transition-all flex-1 ${advanceDelivery === "yes"
										? "border-green-500 bg-green-50 text-green-700"
										: "border-gray-300 text-gray-600 hover:border-gray-400"
										}`}
								>
									<input
										type="radio"
										name="advanceDelivery"
										value="yes"
										checked={advanceDelivery === "yes"}
										onChange={() => setAdvanceDelivery("yes")}
										className="accent-green-500"
									/>
									Yes
								</label>
								<label
									className={`flex items-center gap-2 border rounded-lg px-4 py-2.5 cursor-pointer transition-all flex-1 ${advanceDelivery === "no"
										? "border-pink-500 bg-pink-50 text-pink-700"
										: "border-gray-300 text-gray-600 hover:border-gray-400"
										}`}
								>
									<input
										type="radio"
										name="advanceDelivery"
										value="no"
										checked={advanceDelivery === "no"}
										onChange={() => setAdvanceDelivery("no")}
										className="accent-pink-500"
									/>
									No
								</label>
							</div>
							{!hasDeliveryCharge ? (
								<p className="flex items-center gap-2 text-gray-500 text-sm font-medium p-3 rounded-lg mt-3 bg-gray-100">
									<IoMdInformationCircleOutline size={18} />
									Please select a delivery city first
								</p>
							) : advanceDelivery === "yes" ? (
								<p className="flex flex-wrap items-center gap-1.5 text-green-700 text-sm font-medium p-3 rounded-lg mt-3 bg-green-100">
									<FaCheckCircle size={16} className="flex-shrink-0" />
									<span>Customer paid</span>
									<span className="digit-font">৳{formatBDT(totalDeliveryCharge, 0)}</span>
									<span>advance delivery</span>
								</p>
							) : (
								<p className="flex flex-wrap items-center gap-1.5 text-amber-700 text-sm font-medium p-3 rounded-lg mt-3 bg-amber-50">
									<IoMdInformationCircleOutline size={18} className="flex-shrink-0" />
									<span className="digit-font">৳{formatBDT(totalDeliveryCharge, 0)}</span>
									<span>delivery charge will be added to total</span>
								</p>
							)}
						</div>

						{/* Delivery Fee Payment Info */}
						{hasDeliveryCharge ? (
							<p className="flex flex-wrap items-center gap-1.5 bg-[#FFE5E5] text-red-700 text-sm font-medium p-4 rounded-lg mt-4">
								<IoMdInformationCircleOutline size={20} className="flex-shrink-0" />
								<span>Please pay</span>
								<span className="digit-font font-bold">৳{formatBDT(totalDeliveryCharge, 0)}</span>
								<span>delivery fee to confirm the order.</span>
							</p>
						) : (
							<p className="flex items-center gap-2 bg-gray-100 text-gray-500 text-sm font-medium p-4 rounded-lg mt-4">
								<IoMdInformationCircleOutline size={20} />
								Select a delivery city to see the delivery fee.
							</p>
						)}

						{/* Delivery Fee Payment Method */}
						<div className="mt-5">
							<p className="text-sm font-semibold text-gray-800 mb-3">Pay delivery fee via</p>
							<div className="grid grid-cols-2 gap-3">
								{/* Account Wallet */}
								<label
									className={`flex items-center gap-2 border rounded-md px-3 py-2.5 cursor-pointer transition-all text-sm ${selected === "account"
										? "border-pink-500 text-pink-500 bg-pink-50"
										: "border-gray-300 text-gray-700 hover:border-gray-400"
										}`}
								>
									<input
										type="radio"
										name="paymentMethod"
										value="account"
										checked={selected === "account"}
										onChange={() => setSelected("account")}
										className="accent-pink-500 flex-shrink-0"
									/>
									<span className="truncate">Account Wallet</span>
								</label>

								{/* SSL Commerz */}
								<label
									className={`flex items-center gap-2 border rounded-md px-3 py-2.5 cursor-pointer transition-all text-sm ${selected === "ssl"
										? "border-pink-500 text-pink-500 bg-pink-50"
										: "border-gray-300 text-gray-700 hover:border-gray-400"
										}`}
								>
									<input
										type="radio"
										name="paymentMethod"
										value="ssl"
										checked={selected === "ssl"}
										onChange={() => setSelected("ssl")}
										className="accent-pink-500 flex-shrink-0"
									/>
									<span className="truncate">SSL Commerz</span>
								</label>
							</div>
						</div>

						{/* Terms & Conditions Checkbox */}
						<label className="flex items-start gap-3 mt-6 cursor-pointer select-none">
							<input
								type="checkbox"
								checked={agreedToTerms}
								onChange={(e) => setAgreedToTerms(e.target.checked)}
								className="mt-1 w-4 h-4 accent-pink-500 flex-shrink-0"
							/>
							<span className="text-sm text-gray-600">
								I have read and agree to the{" "}
								<Link href="/terms-and-conditions" className="text-pink-600 underline hover:text-pink-700">Terms &amp; Conditions</Link>,{" "}
								<Link href="/privacy-policy" className="text-pink-600 underline hover:text-pink-700">Privacy Policy</Link>, and{" "}
								<Link href="/return-policy" className="text-pink-600 underline hover:text-pink-700">Return &amp; Refund Policy</Link>.
							</span>
						</label>

						<button
							onClick={handleOrderConfirm}
							disabled={!agreedToTerms}
							className={`w-full mt-4 font-semibold py-4 px-6 rounded-lg transition-colors ${agreedToTerms
								? "bg-pink-600 hover:bg-pink-700 text-white"
								: "bg-gray-300 text-gray-500 cursor-not-allowed"
								}`}
						>
							{hasDeliveryCharge ? <>Pay <span className="digit-font">৳{formatBDT(totalDeliveryCharge, 0)}</span> {"&"} Confirm Order</> : "Confirm Order"}
						</button>
					</div>

					{/* Right Side - Order Summary */}
					<div className="bg-white rounded-lg p-6 shadow-sm order-1 lg:order-2">
						{/* Customer Order Section */}
						<div className="mb-8">
							<h2 className="text-lg sm:text-xl font-semibold text-gray-900 mb-6">
								Customer order
							</h2>
							<div className="space-y-4">
								{cartItems?.data?.length ? (
									cartItems?.data.map((item: any) => (
										<div
											key={item.id}
											className="flex flex-col sm:flex-row sm:items-center gap-4 p-4 border border-gray-200 rounded-lg"
										>
											<div className="w-20 h-20 sm:w-16 sm:h-16 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0 mx-auto sm:mx-0">
												<Image
													src={getImageUrl(item.image)}
													alt={item?.name || "Order item"}
													width={64}
													height={64}
													className="w-full h-full object-cover"
												/>
											</div>

											<div className="flex-1 text-center sm:text-left">
												<h3 className="font-medium text-gray-900">
													{item.name}
												</h3>
												<p className="text-sm text-gray-500">{item.code}</p>
												<p className="font-semibold text-gray-900 flex items-center digit-font">
													<TbCurrencyTaka size={20} />
													{formatBDT(item.price)}
												</p>
											</div>

											<div className="flex justify-center sm:justify-end items-center gap-3">
												<button
													onClick={() =>
														handleUpdateCartItem(item.id, item.qty - 1)
													}
													disabled={item.qty <= 1}
													className="w-8 h-8 rounded-full border border-gray-300 flex items-center justify-center hover:bg-gray-50 disabled:opacity-50"
												>
													<Minus className="w-4 h-4" />
												</button>
												<span className="w-8 text-center font-medium">
													{item.qty}
												</span>
												<button
													onClick={() =>
														handleUpdateCartItem(item.id, item.qty + 1)
													}
													className="w-8 h-8 rounded-full border border-pink-500 text-pink-500 flex items-center justify-center hover:bg-pink-50"
												>
													<Plus className="w-4 h-4" />
												</button>
												<button
													onClick={() => handleDeleteCartItem(item.id)}
													className="w-8 h-8 rounded-full border border-gray-300 flex items-center justify-center hover:bg-red-50 hover:border-red-300 hover:text-red-500 ml-2"
												>
													<Trash2 className="w-4 h-4" />
												</button>
											</div>
										</div>
									))
								) : (
									<p className="text-gray-500">No items in cart.</p>
								)}
							</div>
						</div>

						{/* Product Summary Section */}
						<div>
							<h2 className="text-lg sm:text-xl font-semibold text-gray-900 mb-6">
								Product Summary
							</h2>
							<div className="space-y-4">
								<div className="flex justify-between items-center text-gray-600">
									<span>Subtotal</span>
									<span className="flex items-center digit-font">
										<TbCurrencyTaka size={20} />
										{formatBDT(subtotal)}
									</span>
								</div>
								{totalProfit > 0 && (
									<div className="flex justify-between items-center text-pink-600 font-medium">
										<span>Profit amount</span>
										<span className="flex items-center digit-font">
											<TbCurrencyTaka size={20} />
											{formatBDT(totalProfit)}
										</span>
									</div>
								)}
								{discount > 0 && (
									<div className="flex justify-between items-center text-gray-600">
										<span>Discount</span>
										<span className="flex items-center text-green-600">
											-<TbCurrencyTaka size={20} />
											{discount}
										</span>
									</div>
								)}
								<div className="flex justify-between items-start text-gray-600">
									<span>Delivery Charge</span>
									<span className="flex flex-col items-end">
										{!hasDeliveryCharge ? (
											<span className="text-gray-400 text-sm italic">Select a city</span>
										) : advanceDelivery === "yes" ? (
											<span className="text-green-600 text-sm font-medium">Paid by customer</span>
										) : (
											<span className="digit-font flex items-center">
												<TbCurrencyTaka size={20} />
												{formatBDT(deliveryCharge, 0)}
											</span>
										)}
									</span>
								</div>
								{extraDeliveryCharge > 0 && (
									<div className="flex justify-between items-center text-gray-600">
										<span className="flex items-center gap-1">
											Extra Delivery
											<span className="text-xs text-blue-500 font-medium">(per qty)</span>
										</span>
										<span className="digit-font flex items-center">
											<TbCurrencyTaka size={20} />
											{formatBDT(extraDeliveryCharge, 0)}
										</span>
									</div>
								)}
								<div className="border-t pt-4">
									<div className="flex justify-between items-center text-lg font-semibold text-gray-900">
										<span>Total</span>
										<span className="flex items-center digit-font">
											<TbCurrencyTaka size={20} />
											{formatBDT(grandTotal)}
										</span>
									</div>
								</div>
								<div className="border-t pt-4">
									<div className={`flex justify-between items-center text-sm font-semibold p-3 rounded-lg ${hasDeliveryCharge ? "text-pink-700 bg-pink-50" : "text-gray-500 bg-gray-50"}`}>
										<span>Delivery fee (pay now)</span>
										{hasDeliveryCharge ? (
											<span className="flex items-center digit-font">
												<TbCurrencyTaka size={18} />
												{formatBDT(totalDeliveryCharge, 0)}
											</span>
										) : (
											<span className="text-gray-400 text-xs italic">Select a city</span>
										)}
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div >
	);
}
