/* eslint-disable @typescript-eslint/no-unused-vars */
/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { useState } from "react";
import { Minus, Plus, Trash2 } from "lucide-react";
import Image from "next/image";
import { handleAsyncWithToast } from "@/utils/handleAsyncWithToast";
import { useCreateOrderMutation, useDeleteCartItemMutation, useGetAllCartItemsQuery, useUpdateCartItemMutation } from "@/redux/features/cartApi";
import { TbCurrencyTaka } from "react-icons/tb";
import { z } from "zod";
import Swal from "sweetalert2";
import { useRouter } from "next/navigation";
import { IoMdInformationCircleOutline } from "react-icons/io";
import { FaCheckCircle } from "react-icons/fa";
import { getImageUrl } from "@/lib/utils";
import { useGetPricingQuery } from "@/redux/features/pricingApi";

// ✅ Zod Schema for Validation
const customerSchema = z.object({
  name: z.string().min(3, "Customer name must be at least 3 characters"),
  address: z.string().min(5, "Address must be at least 5 characters"),
  phone: z.string().regex(/^01[0-9]{9}$/, "Invalid Bangladeshi phone number (must be 11 digits & start with 01)"),
  note: z.string().optional(),
});

export default function OrderConfirmation() {
  const router = useRouter();
  const { data: cartItems } = useGetAllCartItemsQuery(undefined);
  const [updateCartItem] = useUpdateCartItemMutation();
  const [deleteCartItem] = useDeleteCartItemMutation();
  const [createOrder] = useCreateOrderMutation();
  const [selected, setSelected] = useState("ssl");
  const [selectedLocation, setSelectedLocation] = useState("inside");
  const { data: pricingData } = useGetPricingQuery(undefined);
  console.log("invoice id:", pricingData?.data?.invoice?.invoiceID);
  const [customerData, setCustomerData] = useState({
    name: "",
    address: "",
    phone: "",
    note: "",
  });

  const [errors, setErrors] = useState<Record<string, string>>({});

  const handleInputChange = (field: string, value: string) => {
    setCustomerData((prev) => ({ ...prev, [field]: value }));
  };

  const subtotal = cartItems?.data?.reduce((total: number, item: any) => total + parseFloat(item.price) * item.qty, 0) || 0;
  const discount = 0;
  const taxAndFee = 0;

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
    formData.append("deliveryCharge", selectedLocation === "inside" ? "60" : "120");
    formData.append("balance_from", selected === "account" ? "from_account" : "online_pay");

    const result = await handleAsyncWithToast(async () => createOrder(formData), true, "Creating order...", "Order created successfully");
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

  const handleUpdateCartItem = async (productId: string, newQty: number) => {
    const formData = new FormData();
    formData.append("product_id", productId);
    formData.append("qty", newQty.toString());

    await handleAsyncWithToast(async () => updateCartItem({ formData }));
  };

  const handleDeleteCartItem = async (productId: string) => {
    await handleAsyncWithToast(async () => deleteCartItem(productId), true, "Removing item...", "Item removed from cart");
  };

  return (
    <div className="min-h-screen py-8">
      <div className="container mx-auto px-4 max-w-7xl">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
          {/* Left Side - Order Form */}
          <div className="bg-white rounded-lg p-6 shadow-sm order-2 lg:order-1">
            <h1 className="text-2xl font-bold text-gray-900 mb-2">Let&apos;s get to the confirm order</h1>
            <p className="text-gray-600 mb-8">Enter customer details to confirm the order.</p>

            <div className="space-y-6">
              {["name", "address", "phone"].map((field) => (
                <div key={field}>
                  <label className="block text-sm font-medium text-gray-700 mb-2 capitalize">Customer {field}</label>
                  <input
                    type={field === "phone" ? "tel" : "text"}
                    placeholder={`Enter customer ${field}`}
                    value={customerData[field as keyof typeof customerData]}
                    onChange={(e) => handleInputChange(field, e.target.value)}
                    className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 ${
                      errors[field] ? "border-red-500 focus:ring-red-500" : "border-gray-300 focus:ring-pink-500"
                    }`}
                  />
                  {errors[field] && <p className="text-red-500 text-sm mt-1">{errors[field]}</p>}
                </div>
              ))}

              {/* Custom Note */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Custom note</label>
                <textarea
                  placeholder="Enter custom note"
                  value={customerData.note}
                  onChange={(e) => handleInputChange("note", e.target.value)}
                  rows={4}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 resize-none"
                />
              </div>
            </div>

            {/* <div className="flex items-center justify-between w-full gap-3 mt-5">
              <label
                className={`flex items-center gap-2 border rounded-md px-4 py-2 cursor-pointer transition-all w-full ${
                  selectedLocation === "inside" ? "border-pink-500 text-pink-500" : "border-gray-300 text-gray-700"
                }`}
              >
                <input
                  type="radio"
                  name="deliveryLocation"
                  value="inside"
                  checked={selectedLocation === "inside"}
                  onChange={() => setSelectedLocation("inside")}
                  className="accent-pink-500"
                />
                Inside Dhaka
              </label>

              <span className="text-gray-500">or</span>

      
              <label
                className={`flex items-center gap-2 border rounded-md px-4 py-2 cursor-pointer transition-all  w-full ${
                  selectedLocation === "outside" ? "border-pink-500 text-pink-500" : "border-gray-300 text-gray-700"
                }`}
              >
                <input
                  type="radio"
                  name="deliveryLocation"
                  value="outside"
                  checked={selectedLocation === "outside"}
                  onChange={() => setSelectedLocation("outside")}
                  className="accent-pink-500"
                />
                Outside Dhaka
              </label>
            </div>

            <div className="pb-3 border-b border-gray-300">
              <p className="flex items-center justify-center gap-2 text-green-700 text-sm font-medium p-4 rounded-lg mt-4 bg-green-100">
                <FaCheckCircle size={20} />
                You have
                <span className="mx-[2px] font-bold text-lg ">

                {selectedLocation === "inside" ? "60" : "120"}
                </span>
                TK for payment.
              </p>
            </div> */}

            <p className="flex items-center gap-2 bg-[#FFE5E5] text-red-700 text-sm font-medium p-4 rounded-lg mt-2 ">
              <IoMdInformationCircleOutline size={20} />
              Please pay the delivery charge before confirm the order.
            </p>

            <div className="flex items-center justify-between w-full gap-3 mt-5">
              {/* Account Payment */}
              <label
                className={`flex items-center gap-2 border rounded-md px-4 py-2 cursor-pointer transition-all w-full ${
                  selected === "account" ? "border-pink-500 text-pink-500" : "border-gray-300 text-gray-700"
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
                Account wallet
              </label>

              <span className="text-gray-500">or</span>

              {/* SSL Commerce */}
              <label
                className={`flex items-center gap-2 border rounded-md px-4 py-2 cursor-pointer transition-all w-full ${
                  selected === "ssl" ? "border-pink-500 text-pink-500" : "border-gray-300 text-gray-700"
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
                SSL commerce
              </label>
            </div>

            <button
              onClick={handleOrderConfirm}
              className="w-full mt-8 bg-pink-600 hover:bg-pink-700 text-white font-semibold py-4 px-6 rounded-lg transition-colors"
            >
              Confirm order
            </button>
          </div>

          {/* Right Side - Order Summary */}
          <div className="bg-white rounded-lg p-6 shadow-sm order-1 lg:order-2">
            {/* Customer Order Section */}
            <div className="mb-8">
              <h2 className="text-lg sm:text-xl font-semibold text-gray-900 mb-6">Customer order</h2>
              <div className="space-y-4">
                {cartItems?.data?.length ? (
                  cartItems?.data.map((item: any) => (
                    <div key={item.id} className="flex flex-col sm:flex-row sm:items-center gap-4 p-4 border border-gray-200 rounded-lg">
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
                        <h3 className="font-medium text-gray-900">{item.name}</h3>
                        <p className="text-sm text-gray-500">{item.code}</p>
                        <p className="font-semibold text-gray-900 flex items-center">
                          <TbCurrencyTaka size={20} />
                          {item.price}
                        </p>
                      </div>

                      <div className="flex justify-center sm:justify-end items-center gap-3">
                        <button
                          onClick={() => handleUpdateCartItem(item.product_id, item.qty - 1)}
                          disabled={item.qty <= 1}
                          className="w-8 h-8 rounded-full border border-gray-300 flex items-center justify-center hover:bg-gray-50 disabled:opacity-50"
                        >
                          <Minus className="w-4 h-4" />
                        </button>
                        <span className="w-8 text-center font-medium">{item.qty}</span>
                        <button
                          onClick={() => handleUpdateCartItem(item.product_id, item.qty + 1)}
                          className="w-8 h-8 rounded-full border border-pink-500 text-pink-500 flex items-center justify-center hover:bg-pink-50"
                        >
                          <Plus className="w-4 h-4" />
                        </button>
                        <button
                          onClick={() => handleDeleteCartItem(item.product_id)}
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
              <h2 className="text-lg sm:text-xl font-semibold text-gray-900 mb-6">Product Summary</h2>
              <div className="space-y-4">
                <div className="flex flex-col sm:flex-row justify-between text-gray-600">
                  <span>Total Price</span>
                  <span className="flex items-center">
                    {" "}
                    <TbCurrencyTaka size={20} />
                    {cartItems?.data.reduce((total: number, item: any) => total + parseFloat(item.price) * item.qty, 0).toFixed(2)}
                  </span>
                </div>
                <div className="flex flex-col sm:flex-row justify-between text-gray-600">
                  <span>Total Price (Discount)</span>
                  <span className="flex items-center">
                    {" "}
                    <TbCurrencyTaka size={20} />
                    {discount}
                  </span>
                </div>
                <div className="flex flex-col sm:flex-row justify-between text-gray-600">
                  <span>Tax & Fee</span>
                  <span className="flex items-center">
                    {" "}
                    <TbCurrencyTaka size={20} />
                    {taxAndFee}
                  </span>
                </div>
                <div className="border-t pt-4">
                  <div className="flex flex-col sm:flex-row justify-between text-lg font-semibold text-gray-900">
                    <span>Total Price</span>
                    <span className="flex items-center">
                      {" "}
                      <TbCurrencyTaka size={20} />
                      {cartItems?.data.reduce((total: number, item: any) => total + parseFloat(item.price) * item.qty, 0).toFixed(2)}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
