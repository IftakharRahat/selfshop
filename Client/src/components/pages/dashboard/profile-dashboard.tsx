/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { useState } from "react";
import { Copy, Edit, User } from "lucide-react";
import {
  useGetMeQuery,
  useUpdateBankInfoMutation,
  useUpdateUserMutation,
} from "@/redux/features/auth/authApi";
import { Modal, Button, Upload, DatePicker, Input, message } from "antd";
import { Controller, useForm } from "react-hook-form";
import dayjs from "dayjs";
import { getImageUrl } from "@/lib/utils";
import Image from "next/image";
import { handleAsyncWithToast } from "@/utils/handleAsyncWithToast";

type ProfileFormValues = {
  name: string;
  dob: dayjs.Dayjs | null;
  address: string;
  shop_name: string;
  profile_file: { file: File } | null;
  nid_file: { file: File } | null;
};

type BankFormValues = {
  bank_name: string;
  account_name: string;
  account_number: string;
  routing_number: string;
};

export default function ProfileDashboard() {
  const { data } = useGetMeQuery(undefined);
  const [updateUser] = useUpdateUserMutation();
  const [updateBankInfo] = useUpdateBankInfoMutation();
  const [copied, setCopied] = useState(false);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [profilePreview, setProfilePreview] = useState<string | null>(null);
  const [isBankModalOpen, setIsBankModalOpen] = useState(false);

  const profile = data?.data?.profile;
  const bankinfo = data?.data?.bankinfo;
  const shopproducts = data?.data?.shopproducts || 0;
  const totalorders = data?.data?.totalorders || 0;
  const soldamount = data?.data?.soldamount || 0;
  const walletbalance = data?.data?.walletbalance || 0;

  const { control, handleSubmit, reset, watch } = useForm<ProfileFormValues>({
    defaultValues: {
      name: profile?.name || "",
      dob: profile?.dob ? dayjs(profile.dob) : null,
      address: profile?.address || "",
      shop_name: profile?.shop_name || "",
      profile_file: null,
      nid_file: null,
    },
  });

  const {
    control: bankControl,
    handleSubmit: handleBankSubmit,
    reset: resetBank,
  } = useForm<BankFormValues>({
    defaultValues: {
      bank_name: bankinfo?.bank_name || "",
      account_name: bankinfo?.account_name || "",
      account_number: bankinfo?.account_number || "",
      routing_number: bankinfo?.routing_number || "",
    },
  });

  const profileFile = watch("profile_file");

  if (profileFile?.file && !profilePreview) {
    const reader = new FileReader();
    reader.onload = (e) => setProfilePreview(e.target?.result as string);
    reader.readAsDataURL(profileFile.file);
  }

  const handleCopyReferralCode = () => {
    if (profile?.my_referral_code) {
      navigator.clipboard.writeText(profile.my_referral_code);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    }
  };

  const openModal = () => {
    reset({
      name: profile?.name || "",
      dob: profile?.dob ? dayjs(profile.dob) : null,
      address: profile?.address || "",
      shop_name: profile?.shop_name || "",
      profile_file: null,
      nid_file: null,
    });
    setProfilePreview(profile?.profile || null);
    setIsModalOpen(true);
  };

  const onSubmit = async (values: any) => {
    try {
      const formData = new FormData();

      formData.append("name", values.name);
      formData.append(
        "dob",
        values.dob ? values.dob.format("YYYY-MM-DD") : ""
      );
      formData.append("address", values.address);
      formData.append("shop_name", values.shop_name);

      if (values.profile_file?.file)
        formData.append("profile", values.profile_file.file);
      if (values.nid_file?.file)
        formData.append("nid", values.nid_file.file);

      await handleAsyncWithToast(async () => updateUser(formData));

      setIsModalOpen(false);
    } catch (err: any) {
      message.error(err?.data?.message || "Failed to update profile");
    }
  };

  const openBankModal = () => {
    resetBank({
      bank_name: bankinfo?.bank_name || "",
      account_name: bankinfo?.account_name || "",
      account_number: bankinfo?.account_number || "",
      routing_number: bankinfo?.routing_number || "",
    });
    setIsBankModalOpen(true);
  };

  const onBankSubmit = async (values: BankFormValues) => {
    try {
      const formData = new FormData();
      formData.append("bank_name", values.bank_name);
      formData.append("account_name", values.account_name);
      formData.append("account_number", values.account_number);
      formData.append("routing_number", values.routing_number);

      await handleAsyncWithToast(async () => updateBankInfo(formData));

      setIsBankModalOpen(false);
    } catch (err: any) {
      message.error(err?.data?.message || "Failed to update bank info");
    }
  };

  return (
    <div className="px-3 sm:px-4 lg:px-6 py-4 lg:py-6 mb-10">
      <div className="p-0 md:p-6 space-y-6">

        {/* PROFILE + BANK GRID */}
        <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">

          {/* PROFILE CARD */}
          <div className="bg-white p-4 sm:p-6 rounded-xl shadow border border-gray-100">
            <div className="flex flex-col sm:flex-row items-center sm:items-start gap-4 sm:gap-6 text-center sm:text-left">

              {/* AVATAR */}
              <div className="w-20 h-20 sm:w-24 sm:h-24 rounded-full overflow-hidden bg-gray-300 flex items-center justify-center">
                {profile?.profile ? (
                  <Image
                    src={getImageUrl(profile.profile)}
                    alt="Profile"
                    width={96}
                    height={96}
                    className="w-full h-full object-fill"
                  />
                ) : (
                  <User className="w-10 h-10 sm:w-12 sm:h-12 text-gray-600" />
                )}
              </div>

              {/* PROFILE INFO */}
              <div className="flex-1 space-y-3">
                <div>
                  <h2 className="text-lg sm:text-xl font-semibold text-gray-900">
                    {profile?.name || "N/A"}
                  </h2>
                  <p className="text-gray-600">{profile?.email || "N/A"}</p>
                  <p className="text-gray-600">
                    Shop: {profile?.shop_name || "Not set"}
                  </p>
                  <p className="text-gray-600">
                    ID: {profile?.my_referral_code || "N/A"}
                  </p>
                </div>

                {/* BUTTON WRAP */}
                <div className="flex flex-wrap gap-2 justify-center sm:justify-start">
                  <button
                    onClick={openModal}
                    className="flex items-center gap-2 px-3 py-1 border border-gray-300 rounded text-gray-600 hover:bg-gray-100"
                  >
                    <Edit className="w-4 h-4" />
                    Edit profile
                  </button>

                  <button
                    onClick={handleCopyReferralCode}
                    className={`flex items-center gap-2 px-3 py-1 rounded text-white ${
                      copied
                        ? "bg-green-500"
                        : "bg-pink-600 hover:bg-pink-700"
                    }`}
                  >
                    <Copy className="w-4 h-4" />
                    {copied ? "Copied!" : "Copy code"}
                  </button>
                </div>
              </div>

            </div>
          </div>

          {/* BANK INFO CARD */}
          <div className="bg-white p-4 sm:p-6 rounded-xl shadow border border-gray-100">
            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-3 sm:mb-4 gap-3">

              <h3 className="text-lg font-semibold text-gray-900">Payout info:</h3>

              <button
                onClick={openBankModal}
                className="flex items-center gap-2 px-3 py-1 border border-gray-300 rounded text-gray-600 hover:bg-gray-100"
              >
                <Edit className="w-4 h-4" />
                Edit
              </button>

            </div>

            <div className="space-y-2 text-sm sm:text-base leading-relaxed">
              <p><span className="font-medium">Bank name:</span> {bankinfo?.bank_name || "Not set"}</p>
              <p><span className="font-medium">Account title:</span> {bankinfo?.account_name || "Not set"}</p>
              <p><span className="font-medium">Account number:</span> {bankinfo?.account_number || "Not set"}</p>
              <p><span className="font-medium">Routing number:</span> {bankinfo?.routing_number || "Not set"}</p>
            </div>
          </div>

        </div>

        {/* STATS GRID */}
        <div className="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 lg:gap-6 mt-6">

          <div className="bg-white p-3 sm:p-5 rounded-xl shadow border border-gray-100 text-center">
            <div className="text-2xl sm:text-4xl font-bold text-gray-900">{shopproducts}</div>
            <div className="text-gray-600 text-xs sm:text-sm">Total products</div>
          </div>

          <div className="bg-white p-3 sm:p-5 rounded-xl shadow border border-gray-100 text-center">
            <div className="text-2xl sm:text-4xl font-bold text-gray-900">{totalorders}</div>
            <div className="text-gray-600 text-xs sm:text-sm">Total orders</div>
          </div>

          <div className="bg-white p-3 sm:p-5 rounded-xl shadow border border-gray-100 text-center">
            <div className="flex justify-center text-2xl sm:text-4xl font-bold text-gray-900">
              <span className="text-base sm:text-xl mr-1">৳</span>
              {soldamount}
            </div>
            <div className="text-gray-600 text-xs sm:text-sm">Sold amount</div>
          </div>

          <div className="bg-white p-3 sm:p-5 rounded-xl shadow border border-gray-100 text-center">
            <div className="flex justify-center text-2xl sm:text-4xl font-bold text-gray-900">
              <span className="text-base sm:text-xl mr-1">৳</span>
              {walletbalance}
            </div>
            <div className="text-gray-600 text-xs sm:text-sm">Wallet balance</div>
          </div>

        </div>
      </div>

      {/* PROFILE MODAL */}
      <Modal
        title="Edit Profile"
        open={isModalOpen}
        onCancel={() => setIsModalOpen(false)}
        footer={null}
        centered
        width={500}
      >
        <form
          onSubmit={handleSubmit(onSubmit)}
          className="space-y-4 sm:space-y-5 py-2"
        >
          <div className="flex flex-col gap-1">
            <label className="font-medium text-gray-700">Full Name</label>
            <Controller
              name="name"
              control={control}
              render={({ field }) => <Input {...field} />}
            />
          </div>

          <div className="flex flex-col gap-1">
            <label className="font-medium text-gray-700">Date of Birth</label>
            <Controller
              name="dob"
              control={control}
              render={({ field }) => (
                <DatePicker {...field} style={{ width: "100%" }} />
              )}
            />
          </div>

          <div className="flex flex-col gap-1">
            <label className="font-medium text-gray-700">Address</label>
            <Controller
              name="address"
              control={control}
              render={({ field }) => <Input {...field} />}
            />
          </div>

          <div className="flex flex-col gap-1">
            <label className="font-medium text-gray-700">Shop Name</label>
            <Controller
              name="shop_name"
              control={control}
              render={({ field }) => <Input {...field} />}
            />
          </div>

          <div className="flex flex-col gap-1">
            <label className="font-medium text-gray-700">Profile Image</label>
            <Controller
              name="profile_file"
              control={control}
              render={({ field }) => (
                <Upload beforeUpload={() => false} maxCount={1} {...field}>
                  <Button>Upload Image</Button>
                </Upload>
              )}
            />
          </div>

          <div className="flex flex-col gap-1">
            <label className="font-medium text-gray-700">NID Document</label>
            <Controller
              name="nid_file"
              control={control}
              render={({ field }) => (
                <Upload beforeUpload={() => false} maxCount={1} {...field}>
                  <Button>Upload NID</Button>
                </Upload>
              )}
            />
          </div>

          <Button type="primary" htmlType="submit" block>
            Update Profile
          </Button>
        </form>
      </Modal>

      {/* BANK MODAL */}
      <Modal
        title="Edit Bank Info"
        open={isBankModalOpen}
        onCancel={() => setIsBankModalOpen(false)}
        footer={null}
        centered
        width={500}
      >
        <form
          onSubmit={handleBankSubmit(onBankSubmit)}
          className="space-y-4 sm:space-y-5 py-2"
        >
          <div className="flex flex-col gap-1">
            <label className="font-medium text-gray-700">Bank Name</label>
            <Controller
              name="bank_name"
              control={bankControl}
              render={({ field }) => <Input {...field} />}
            />
          </div>

          <div className="flex flex-col gap-1">
            <label className="font-medium text-gray-700">Account Title</label>
            <Controller
              name="account_name"
              control={bankControl}
              render={({ field }) => <Input {...field} />}
            />
          </div>

          <div className="flex flex-col gap-1">
            <label className="font-medium text-gray-700">Account Number</label>
            <Controller
              name="account_number"
              control={bankControl}
              render={({ field }) => <Input {...field} />}
            />
          </div>

          <div className="flex flex-col gap-1">
            <label className="font-medium text-gray-700">Routing Number</label>
            <Controller
              name="routing_number"
              control={bankControl}
              render={({ field }) => <Input {...field} />}
            />
          </div>

          <Button type="primary" htmlType="submit" block>
            Update Bank Info
          </Button>
        </form>
      </Modal>
    </div>
  );
}
