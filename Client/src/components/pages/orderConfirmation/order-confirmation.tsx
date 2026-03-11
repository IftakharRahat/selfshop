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
} from "@/redux/features/cartApi";
import { useGetBasicInfoQuery } from "@/redux/features/home/homeApi";
import {
	useGetShippingAddressesQuery,
	useCreateShippingAddressMutation,
	useDeleteShippingAddressMutation,
} from "@/redux/features/shippingAddressApi";
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
	const { data: cartItems, isLoading } = useGetAllCartItemsQuery(undefined);

	// Redirect to home if cart is empty
	useEffect(() => {
		if (!isLoading && (!cartItems?.data || cartItems.data.length === 0)) {
			router.replace("/");
		}
	}, [cartItems, isLoading, router]);
	const [updateCartItem] = useUpdateCartItemMutation();
	const [deleteCartItem] = useDeleteCartItemMutation();
	const [createOrder] = useCreateOrderMutation();
	const [selected, setSelected] = useState("account");
	const [agreedToTerms, setAgreedToTerms] = useState(false);
	const [advanceDelivery, setAdvanceDelivery] = useState<"yes" | "no">("no");
	const [deliveryZone, setDeliveryZone] = useState<"inside" | "outside">("inside");
	const { data: basicInfoData } = useGetBasicInfoQuery(undefined);
	const insideDhakaCharge: number = Number(basicInfoData?.data?.inside_dhaka_charge) || 60;
	const outsideDhakaCharge: number = Number(basicInfoData?.data?.outside_dhaka_charge) || 130;
	const deliveryCharge: number = deliveryZone === "inside" ? insideDhakaCharge : outsideDhakaCharge;
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

	const handleSaveAddress = async () => {
		if (!customerData.name || !customerData.address || !customerData.phone) return;
		const label = saveLabel.trim() || `${customerData.name} - ${customerData.phone}`;
		await handleAsyncWithToast(
			async () => createShippingAddress({
				label,
				name: customerData.name,
				address: customerData.address,
				phone: customerData.phone,
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

	const handleSelectSavedAddress = (addr: { name: string; address: string; phone: string }) => {
		setCustomerData((prev) => ({
			...prev,
			name: addr.name,
			address: addr.address,
			phone: addr.phone,
		}));
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
	const grandTotal =
		advanceDelivery === "yes"
			? subtotal - discount // customer already paid delivery
			: subtotal - discount + deliveryCharge; // delivery added

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

		const formData = new FormData();
		formData.append("customerName", customerData.name);
		formData.append("customerPhone", customerData.phone);
		formData.append("customerAddress", customerData.address);
		formData.append("subTotal", subtotal.toString());
		formData.append("deliveryCharge", deliveryCharge.toString());
		formData.append("delivery_zone", deliveryZone === "inside" ? "Inside Dhaka" : "Outside Dhaka");
		formData.append("advance_delivery", advanceDelivery);
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

	const totalProfit =
		cartItems?.data?.reduce((total: number, item: any) => {
			const sellingPrice = parseFloat(item.options?.selling_price || item.price);
			const costPrice = parseFloat(item.price);
			return total + (sellingPrice - costPrice) * item.qty;
		}, 0) || 0;

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

						{/* Delivery Zone Selector */}
						<div className="mt-6 p-4 border border-gray-200 rounded-lg bg-gray-50">
							<p className="text-sm font-semibold text-gray-800 mb-3">
								Delivery Zone
							</p>
							<div className="flex items-center gap-3">
								<label
									className={`flex items-center justify-between border rounded-lg px-4 py-2.5 cursor-pointer transition-all flex-1 ${deliveryZone === "inside"
										? "border-pink-500 bg-pink-50 text-pink-700"
										: "border-gray-300 text-gray-600 hover:border-gray-400"
										}`}
								>
									<div className="flex items-center gap-2">
										<input
											type="radio"
											name="deliveryZone"
											value="inside"
											checked={deliveryZone === "inside"}
											onChange={() => setDeliveryZone("inside")}
											className="accent-pink-500"
										/>
										Inside Dhaka
									</div>
								<span className="text-xs font-semibold digit-font">৳{formatBDT(insideDhakaCharge, 0)}</span>
								</label>
								<label
									className={`flex items-center justify-between border rounded-lg px-4 py-2.5 cursor-pointer transition-all flex-1 ${deliveryZone === "outside"
										? "border-pink-500 bg-pink-50 text-pink-700"
										: "border-gray-300 text-gray-600 hover:border-gray-400"
										}`}
								>
									<div className="flex items-center gap-2">
										<input
											type="radio"
											name="deliveryZone"
											value="outside"
											checked={deliveryZone === "outside"}
											onChange={() => setDeliveryZone("outside")}
											className="accent-pink-500"
										/>
										Outside Dhaka
									</div>
								<span className="text-xs font-semibold digit-font">৳{formatBDT(outsideDhakaCharge, 0)}</span>
								</label>
							</div>
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
							{advanceDelivery === "yes" ? (
								<p className="flex items-center gap-2 text-green-700 text-sm font-medium p-3 rounded-lg mt-3 bg-green-100">
									<FaCheckCircle size={16} />
									Customer paid <span className="digit-font">৳{formatBDT(deliveryCharge, 0)}</span> advance delivery
								</p>
							) : (
								<p className="flex items-center gap-2 text-amber-700 text-sm font-medium p-3 rounded-lg mt-3 bg-amber-50">
									<IoMdInformationCircleOutline size={18} />
									<span className="digit-font">৳{formatBDT(deliveryCharge, 0)}</span> delivery charge will be added to total
								</p>
							)}
						</div>

						{/* Delivery Fee Payment Info */}
						<p className="flex items-center gap-2 bg-[#FFE5E5] text-red-700 text-sm font-medium p-4 rounded-lg mt-4">
							<IoMdInformationCircleOutline size={20} />
							Please pay <span className="digit-font">৳{formatBDT(deliveryCharge, 0)}</span> delivery fee to confirm the order.
						</p>

						{/* Delivery Fee Payment Method */}
						<div className="mt-5">
							<p className="text-sm font-semibold text-gray-800 mb-3">Pay delivery fee via</p>
							<div className="flex flex-wrap items-center w-full gap-3">
								{/* Account Wallet */}
								<label
									className={`flex items-center gap-2 border rounded-md px-4 py-2.5 cursor-pointer transition-all flex-1 ${selected === "account"
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
										className="accent-pink-500"
									/>
									Account Wallet
								</label>

								{/* SSL Commerz */}
								<label
									className={`flex items-center gap-2 border rounded-md px-4 py-2.5 cursor-pointer transition-all flex-1 ${selected === "ssl"
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
										className="accent-pink-500"
									/>
									SSL Commerz
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
							Pay <span className="digit-font">৳{formatBDT(deliveryCharge, 0)}</span> & Confirm Order
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
								<div className="flex flex-col sm:flex-row justify-between text-gray-600">
									<span>Subtotal</span>
									<span className="flex items-center digit-font">
										<TbCurrencyTaka size={20} />
										{formatBDT(subtotal)}
									</span>
								</div>
								{totalProfit > 0 && (
									<div className="flex flex-col sm:flex-row justify-between text-pink-600 font-medium">
										<span>Profit amount</span>
										<span className="flex items-center digit-font">
											<TbCurrencyTaka size={20} />
											{formatBDT(totalProfit)}
										</span>
									</div>
								)}
								{discount > 0 && (
									<div className="flex flex-col sm:flex-row justify-between text-gray-600">
										<span>Discount</span>
										<span className="flex items-center text-green-600">
											-<TbCurrencyTaka size={20} />
											{discount}
										</span>
									</div>
								)}
								<div className="flex flex-col sm:flex-row justify-between text-gray-600">
									<span>Delivery Charge</span>
									<span className="flex items-center">
										{advanceDelivery === "yes" ? (
											<span className="text-green-600 text-sm font-medium">Paid by customer</span>
										) : (
											<span className="digit-font flex items-center">
												<TbCurrencyTaka size={20} />
												{formatBDT(deliveryCharge, 0)}
											</span>
										)}
									</span>
								</div>
								<div className="border-t pt-4">
									<div className="flex flex-col sm:flex-row justify-between text-lg font-semibold text-gray-900">
										<span>Total</span>
										<span className="flex items-center digit-font">
											<TbCurrencyTaka size={20} />
											{formatBDT(grandTotal)}
										</span>
									</div>
								</div>
								<div className="border-t pt-4">
									<div className="flex flex-col sm:flex-row justify-between text-sm font-semibold text-pink-700 bg-pink-50 p-3 rounded-lg">
										<span>Delivery fee (pay now)</span>
										<span className="flex items-center digit-font">
											<TbCurrencyTaka size={18} />
											{formatBDT(deliveryCharge, 0)}
										</span>
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
