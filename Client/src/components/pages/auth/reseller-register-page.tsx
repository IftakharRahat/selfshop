"use client";

import { Button, ConfigProvider, Form, Input } from "antd";
import { CheckCircle2, LockKeyhole, ShieldCheck, Store, Truck } from "lucide-react";
import Image from "next/image";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useEffect } from "react";
import logo from "@/assets/icons/NavLogo.png";
import { setUser } from "@/redux/features/auth/authSlice";
import { useRegisterMutation } from "@/redux/features/auth/authApi";
import { useAppDispatch } from "@/redux/hooks";
import { trackLead, trackViewRegistration } from "@/lib/trackingEvents";
import { handleAsyncWithToast } from "@/utils/handleAsyncWithToast";

type RegisterFormValues = {
	name: string;
	email: string;
	password: string;
	c_password: string;
	refer_by?: string;
};

type ResellerRegisterPageProps = {
	referralCode?: string;
};

const phonePattern = /^01[3-9]\d{8}$/;

export default function ResellerRegisterPage({
	referralCode,
}: ResellerRegisterPageProps) {
	const router = useRouter();
	const dispatch = useAppDispatch();
	const [form] = Form.useForm<RegisterFormValues>();
	const [register, { isLoading }] = useRegisterMutation();
	const lockedReferralCode = referralCode?.trim() ?? "";

	useEffect(() => {
		trackViewRegistration();
	}, []);

	useEffect(() => {
		if (lockedReferralCode) {
			form.setFieldsValue({ refer_by: lockedReferralCode });
		}
	}, [form, lockedReferralCode]);

	const handleSubmit = async (values: RegisterFormValues) => {
		const referBy = (values.refer_by ?? lockedReferralCode).trim();
		const response = await handleAsyncWithToast(
			async () =>
				register({
					name: values.name.trim(),
					email: values.email.trim(),
					password: values.password,
					c_password: values.c_password,
					refer_by: referBy || undefined,
					campaign_code: lockedReferralCode || undefined,
				}),
			true,
			"Creating your account...",
			"Registration successful",
		);

		if (response?.data?.status) {
			await dispatch(
				setUser({
					access_token: response?.data?.token,
				}),
			);
			await trackLead({
				method: "phone",
				phone: values.email,
				name: values.name,
				campaignCode: referBy || undefined,
			});
			form.resetFields();
			router.push("/pricing");
		}
	};

	return (
		<ConfigProvider
			theme={{
				token: {
					colorPrimary: "#E5005F",
					colorLink: "#E5005F",
					borderRadius: 10,
				},
				components: {
					Form: {
						itemMarginBottom: 14,
					},
				},
			}}
		>
			<main className="bg-[#FFF8FB]">
				<section className="mx-auto grid min-h-[calc(100vh-160px)] max-w-6xl items-center px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
					<div className="grid overflow-hidden rounded-2xl border border-pink-100 bg-white shadow-[0_20px_60px_rgba(229,0,95,0.14)] lg:grid-cols-[0.9fr_1.1fr]">
						<aside className="bg-[#E5005F] px-6 py-8 text-[#FFF7FB] sm:px-8 lg:px-10">
							<div className="mb-8 inline-flex rounded-xl bg-white px-4 py-3">
								<Image src={logo} alt="SelfShop" width={164} height={44} priority />
							</div>

							<h1 className="max-w-sm text-3xl font-black leading-tight sm:text-4xl">
								Create your reseller account
							</h1>
							<p className="mt-4 max-w-md text-sm leading-6 text-pink-50">
								Join SelfShop to source products, manage orders, and build your
								reselling business from one dashboard.
							</p>

							<div className="mt-8 space-y-4">
								<div className="flex gap-3">
									<Store className="mt-0.5 h-5 w-5 flex-none" />
									<div>
										<p className="text-sm font-bold">Dropshipping dashboard</p>
										<p className="text-xs leading-5 text-pink-50">
											Access product sourcing, shop tools, and order workflows.
										</p>
									</div>
								</div>
								<div className="flex gap-3">
									<Truck className="mt-0.5 h-5 w-5 flex-none" />
									<div>
										<p className="text-sm font-bold">Wholesale and delivery support</p>
										<p className="text-xs leading-5 text-pink-50">
											Work with supplier inventory and fulfillment in Bangladesh.
										</p>
									</div>
								</div>
								<div className="flex gap-3">
									<ShieldCheck className="mt-0.5 h-5 w-5 flex-none" />
									<div>
										<p className="text-sm font-bold">Referral-ready signup</p>
										<p className="text-xs leading-5 text-pink-50">
											Shared links keep the referral code attached to this form.
										</p>
									</div>
								</div>
							</div>
						</aside>

						<div className="px-5 py-7 sm:px-8 lg:px-10">
							<div className="mb-6">
								<p className="text-xs font-bold uppercase tracking-[0.18em] text-[#E5005F]">
									Registration
								</p>
								<h2 className="mt-2 text-2xl font-black text-gray-950">
									Start with your phone number
								</h2>
								<p className="mt-2 text-sm leading-6 text-gray-600">
									Use an active Bangladeshi mobile number. You can complete your
									subscription after account creation.
								</p>
							</div>

							{lockedReferralCode ? (
								<div className="mb-5 flex items-center gap-3 rounded-xl border border-pink-100 bg-pink-50 px-4 py-3">
									<CheckCircle2 className="h-5 w-5 flex-none text-[#E5005F]" />
									<div>
										<p className="text-sm font-bold text-gray-950">
											Referral code applied
										</p>
										<p className="text-xs text-gray-600">{lockedReferralCode}</p>
									</div>
								</div>
							) : null}

							<Form
								form={form}
								layout="vertical"
								onFinish={handleSubmit}
								initialValues={{
									refer_by: lockedReferralCode,
								}}
							>
								<Form.Item
									name="name"
									label="Full Name"
									rules={[{ required: true, message: "Please enter your full name." }]}
								>
									<Input size="large" placeholder="Enter your full name" />
								</Form.Item>

								<Form.Item
									name="email"
									label="Phone Number"
									rules={[
										{ required: true, message: "Please enter your phone number." },
										{
											pattern: phonePattern,
											message: "Use a valid 11 digit Bangladeshi number.",
										},
									]}
								>
									<Input size="large" placeholder="01XXXXXXXXX" inputMode="numeric" />
								</Form.Item>

								<Form.Item
									name="password"
									label="Password"
									rules={[
										{ required: true, message: "Please enter a password." },
										{ min: 6, message: "Password must be at least 6 characters." },
									]}
								>
									<Input.Password size="large" placeholder="Create a password" />
								</Form.Item>

								<Form.Item
									name="c_password"
									label="Confirm Password"
									dependencies={["password"]}
									rules={[
										{ required: true, message: "Please confirm your password." },
										({ getFieldValue }) => ({
											validator(_, value) {
												if (!value || getFieldValue("password") === value) {
													return Promise.resolve();
												}
												return Promise.reject(new Error("Passwords do not match."));
											},
										}),
									]}
								>
									<Input.Password size="large" placeholder="Confirm your password" />
								</Form.Item>

								<Form.Item name="refer_by" label="Referral Code">
									<Input
										size="large"
										placeholder="Optional referral code"
										readOnly={Boolean(lockedReferralCode)}
										prefix={
											lockedReferralCode ? (
												<LockKeyhole className="h-4 w-4 text-gray-400" />
											) : null
										}
									/>
								</Form.Item>

								<Button
									type="primary"
									size="large"
									htmlType="submit"
									block
									loading={isLoading}
									className="mt-1 font-bold"
								>
									Create Account
								</Button>
							</Form>

							<p className="mt-5 text-center text-sm text-gray-600">
								Already have an account?{" "}
								<button
									type="button"
									className="font-bold text-[#E5005F] hover:underline"
									onClick={() => router.push("/?showAuth=true")}
								>
									Log in
								</button>
							</p>
							<p className="mt-2 text-center text-xs text-gray-400">
								By registering, you agree to SelfShop&apos;s{" "}
								<Link href="/terms-and-conditions" className="text-gray-600 underline">
									terms
								</Link>
								.
							</p>
						</div>
					</div>
				</section>
			</main>
		</ConfigProvider>
	);
}
