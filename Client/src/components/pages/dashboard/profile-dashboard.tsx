/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { Button, DatePicker, Input, Modal, message, Upload } from "antd";
import dayjs from "dayjs";
import { Copy, Edit, Package, ShoppingBag, TrendingUp, User, Wallet } from "lucide-react";
import Image from "next/image";
import { useState } from "react";
import { Controller, useForm } from "react-hook-form";
import { getImageUrl } from "@/lib/utils";
import {
	useGetMeQuery,
	useUpdateBankInfoMutation,
	useUpdateUserMutation,
} from "@/redux/features/auth/authApi";
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
			formData.append("dob", values.dob ? values.dob.format("YYYY-MM-DD") : "");
			formData.append("address", values.address);
			formData.append("shop_name", values.shop_name);

			if (values.profile_file?.file)
				formData.append("profile", values.profile_file.file);
			if (values.nid_file?.file) formData.append("nid", values.nid_file.file);

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
		<div className="px-3 sm:px-4 lg:px-6 py-4 lg:py-6 pb-20">
			<div className="p-0 md:p-6 space-y-6">
				{/* PROFILE + BANK GRID */}
				<div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
					{/* PROFILE CARD */}
					<div className="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
						{/* Gradient accent bar */}
						<div className="h-20 bg-gradient-to-r from-[#E5005F] to-[#ff3d8a]" />
						<div className="px-4 sm:px-6 pb-5 -mt-10">
							{/* Avatar */}
							<div className="w-20 h-20 rounded-full overflow-hidden bg-white border-4 border-white shadow-md flex items-center justify-center mb-4">
								{profile?.profile ? (
									<Image
										src={getImageUrl(profile.profile)}
										alt="Profile"
										width={96}
										height={96}
										className="w-full h-full object-cover"
									/>
								) : (
									<div className="w-full h-full bg-gray-100 flex items-center justify-center">
										<User className="w-8 h-8 text-gray-400" />
									</div>
								)}
							</div>

							{/* Name & Email */}
							<h2 className="text-lg sm:text-xl font-bold text-gray-900">
								{profile?.name || "N/A"}
							</h2>
							<p className="text-sm text-gray-500 mt-0.5">
								{profile?.email || "N/A"}
							</p>

							{/* Info rows */}
							<div className="mt-4 space-y-2 text-sm">
								<div className="flex items-center justify-between py-1.5 border-b border-gray-50">
									<span className="text-gray-500">Shop</span>
									<span className="font-medium text-gray-800">{profile?.shop_name || "Not set"}</span>
								</div>
								<div className="flex items-center justify-between py-1.5 border-b border-gray-50">
									<span className="text-gray-500">Referral Code</span>
									<span className="font-medium text-gray-800">{profile?.my_referral_code || "N/A"}</span>
								</div>
							</div>

							{/* Buttons */}
							<div className="flex flex-wrap gap-2 mt-5">
								<button
									onClick={openModal}
									className="flex items-center gap-2 px-4 py-2 bg-gray-50 border border-gray-200 rounded-full text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors cursor-pointer"
								>
									<Edit className="w-4 h-4" />
									Edit Profile
								</button>

								<button
									onClick={handleCopyReferralCode}
									className={`flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium text-white transition-colors cursor-pointer ${copied ? "bg-green-500" : "bg-[#E5005F] hover:bg-[#c0004d]"
										}`}
								>
									<Copy className="w-4 h-4" />
									{copied ? "Copied!" : "Copy Code"}
								</button>
							</div>
						</div>
					</div>

					{/* BANK INFO CARD */}
					<div className="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
						<div className="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-gray-100">
							<h3 className="text-lg font-semibold text-gray-900">
								Payout Info
							</h3>
							<button
								onClick={openBankModal}
								className="flex items-center gap-2 px-3 py-1.5 bg-gray-50 border border-gray-200 rounded-full text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors cursor-pointer"
							>
								<Edit className="w-3.5 h-3.5" />
								Edit
							</button>
						</div>

						<div className="px-4 sm:px-6 py-4">
							<div className="space-y-0 text-sm">
								<div className="flex items-center justify-between py-3 border-b border-gray-50">
									<span className="text-gray-500">Bank Name</span>
									<span className="font-medium text-gray-800">{bankinfo?.bank_name || "Not set"}</span>
								</div>
								<div className="flex items-center justify-between py-3 border-b border-gray-50">
									<span className="text-gray-500">Account Title</span>
									<span className="font-medium text-gray-800">{bankinfo?.account_name || "Not set"}</span>
								</div>
								<div className="flex items-center justify-between py-3 border-b border-gray-50">
									<span className="text-gray-500">Account Number</span>
									<span className="font-medium text-gray-800">{bankinfo?.account_number || "Not set"}</span>
								</div>
								<div className="flex items-center justify-between py-3">
									<span className="text-gray-500">Routing Number</span>
									<span className="font-medium text-gray-800">{bankinfo?.routing_number || "Not set"}</span>
								</div>
							</div>
						</div>
					</div>
				</div>

				{/* STATS */}
				<div className="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
					<div className="grid grid-cols-2 md:grid-cols-4 divide-x divide-y md:divide-y-0 divide-gray-100">
						<div className="p-4 sm:p-6">
							<p className="text-xs text-gray-400 uppercase tracking-wide mb-1">Products</p>
							<p className="text-2xl sm:text-3xl font-bold text-gray-900">{shopproducts}</p>
						</div>
						<div className="p-4 sm:p-6">
							<p className="text-xs text-gray-400 uppercase tracking-wide mb-1">Orders</p>
							<p className="text-2xl sm:text-3xl font-bold text-gray-900">{totalorders}</p>
						</div>
						<div className="p-4 sm:p-6">
							<p className="text-xs text-gray-400 uppercase tracking-wide mb-1">Sold</p>
							<p className="text-2xl sm:text-3xl font-bold text-gray-900">৳{soldamount.toLocaleString()}</p>
						</div>
						<div className="p-4 sm:p-6">
							<p className="text-xs text-gray-400 uppercase tracking-wide mb-1">Wallet</p>
							<p className="text-2xl sm:text-3xl font-bold text-gray-900">৳{walletbalance.toLocaleString()}</p>
						</div>
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
