/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import {
	Button,
	ConfigProvider,
	Divider,
	Form,
	Input,
	Modal,
	Tabs,
	message,
} from "antd";
import { useState } from "react";
import { FaApple } from "react-icons/fa";
import { FcGoogle } from "react-icons/fc";
import logo from "@/assets/images/loginLogo.png";
import {
	useForgotPasswordMutation,
	useLoginMutation,
	useRegisterMutation,
	useResetPasswordMutation,
	useVerifyOtpMutation,
} from "@/redux/features/auth/authApi";
import { setUser } from "@/redux/features/auth/authSlice";
import { useAppDispatch } from "@/redux/hooks";
import { handleAsyncWithToast } from "@/utils/handleAsyncWithToast";
import { trackLead } from "@/lib/trackingEvents";

interface LoginModalProps {
	open: boolean;
	onCancel: () => void;
}

type ViewMode = "login" | "register" | "forgot-phone" | "forgot-otp" | "forgot-reset";

export default function LoginModal({ open, onCancel }: LoginModalProps) {
	const [form] = Form.useForm();
	const [activeTab, setActiveTab] = useState("reseller");
	const [viewMode, setViewMode] = useState<ViewMode>("login");
	const [showCoupon, setShowCoupon] = useState(false);
	const [forgotPhone, setForgotPhone] = useState("");

	const [login] = useLoginMutation();
	const [register] = useRegisterMutation();
	const [forgotPassword, { isLoading: isSendingOtp }] = useForgotPasswordMutation();
	const [verifyOtp, { isLoading: isVerifyingOtp }] = useVerifyOtpMutation();
	const [resetPassword, { isLoading: isResetting }] = useResetPasswordMutation();
	const dispatch = useAppDispatch();

	const handleLogin = async (values: any) => {
		const response = await handleAsyncWithToast(async () => {
			return login(values);
		});
		if (response?.data?.status) {
			await dispatch(
				setUser({
					access_token: response?.data?.token,
				}),
			);
			form.resetFields();
			onCancel();
		}
	};

	const handleRegistration = async (values: any) => {
		console.log("Registration data:", values);
		const response = await handleAsyncWithToast(async () => {
			return register(values);
		});
		if (response?.data?.status) {
			await dispatch(
				setUser({
					access_token: response?.data?.token,
				}),
			);
			// Fire Lead tracking event on successful registration
			trackLead({ method: "phone" });
			form.resetFields();
			onCancel();
		}
	};

	const handleSocialLogin = (provider: string) => {
		console.log("Social login:", provider);
		// TODO: social login logic
	};

	const handleSendOtp = async (values: { phone: string }) => {
		try {
			const res = await forgotPassword({ phone: values.phone }).unwrap();
			if (res?.status) {
				message.success(res.message || "OTP sent successfully!");
				setForgotPhone(values.phone);
				form.resetFields();
				setViewMode("forgot-otp");
			}
		} catch (err: any) {
			message.error(
				err?.data?.message || "Failed to send OTP. Please try again.",
			);
		}
	};

	const handleVerifyOtp = async (values: { otp: string }) => {
		try {
			const res = await verifyOtp({ phone: forgotPhone, otp: values.otp }).unwrap();
			if (res?.status) {
				message.success(res.message || "OTP verified!");
				form.setFieldsValue({ otp: values.otp });
				setViewMode("forgot-reset");
			}
		} catch (err: any) {
			message.error(
				err?.data?.message || "Invalid OTP. Please try again.",
			);
		}
	};

	const handleResetPassword = async (values: {
		otp: string;
		password: string;
		password_confirmation: string;
	}) => {
		try {
			const res = await resetPassword({
				phone: forgotPhone,
				otp: values.otp,
				password: values.password,
				password_confirmation: values.password_confirmation,
			}).unwrap();
			if (res?.status) {
				message.success(res.message || "Password reset successfully!");
				form.resetFields();
				setForgotPhone("");
				setViewMode("login");
			}
		} catch (err: any) {
			message.error(
				err?.data?.message || "Failed to reset password. Please try again.",
			);
		}
	};

	const handleResendOtp = async () => {
		try {
			const res = await forgotPassword({ phone: forgotPhone }).unwrap();
			if (res?.status) {
				message.success("OTP resent successfully!");
			}
		} catch (err: any) {
			message.error(
				err?.data?.message || "Failed to resend OTP.",
			);
		}
	};

	const resetToLogin = () => {
		form.resetFields();
		setForgotPhone("");
		setViewMode("login");
	};

	const getTitle = () => {
		switch (viewMode) {
			case "forgot-phone":
				return "Forgot Password";
			case "forgot-otp":
				return "Verify OTP";
			case "forgot-reset":
				return "Set New Password";
			default:
				return null;
		}
	};

	const getDescription = () => {
		switch (viewMode) {
			case "forgot-phone":
				return "Enter your registered phone number to receive a password reset OTP.";
			case "forgot-otp":
				return `We've sent a 6-digit OTP to ${forgotPhone}. Please enter it below.`;
			case "forgot-reset":
				return "Enter your new password below.";
			case "register":
				return "Join our dropshipping and wholesale marketplace to start selling and sourcing products easily.";
			default:
				return "Sign in to your account to access dropshipping products and wholesale deals.";
		}
	};

	const tabItems = [
		{
			key: "reseller",
			label: "Login as Reseller",
		},
	];

	const isForgotFlow = viewMode.startsWith("forgot");

	return (
		<ConfigProvider
			theme={{
				token: {
					colorPrimary: "#e91e63",
					colorLink: "#e91e63",
				},
				components: {
					Form: {
						itemMarginBottom: 12,
					},
				},
			}}
		>
			<Modal
				open={open}
				onCancel={onCancel}
				footer={null}
				width={460}
				centered
				styles={{
					body: { padding: "24px 28px 16px 28px" },
				}}
				closeIcon={null}
			>
				{/* Logo + Title */}
				<div className="text-center mb-3">
					<div className="flex items-center justify-center mb-2">
						<img src={logo.src} alt="SelfShop Logo" className="w-44" />
					</div>

					{isForgotFlow && (
						<h3 className="text-lg font-semibold mb-1">{getTitle()}</h3>
					)}

					<p className="text-gray-600 text-sm mb-3">
						{getDescription()}
					</p>
				</div>

				{/* Tabs - only show for login/register */}
				{!isForgotFlow && (
					<Tabs
						activeKey={activeTab}
						onChange={setActiveTab}
						items={tabItems}
						centered
						tabBarGutter={24}
						tabBarStyle={{ marginBottom: 16 }}
					/>
				)}

				{activeTab === "reseller" ? (
					<>
						{viewMode === "login" && (
							<Form form={form} layout="vertical" onFinish={handleLogin}>
								<Form.Item
									name="email"
									label="Phone Number"
									rules={[
										{
											required: true,
											message: "Please input your phone number!",
										},
									]}
								>
									<Input
										size="large"
										placeholder="Enter your phone number..."
									/>
								</Form.Item>

								<Form.Item
									name="password"
									label="Password"
									rules={[
										{ required: true, message: "Please input your password!" },
									]}
								>
									<Input.Password
										size="large"
										placeholder="Enter your password..."
									/>
								</Form.Item>

								<div className="flex justify-end mb-3">
									<span
										className="text-[#e91e63] cursor-pointer text-sm hover:underline"
										onClick={() => {
											form.resetFields();
											setViewMode("forgot-phone");
										}}
									>
										Forgot Password?
									</span>
								</div>

								<Button type="primary" htmlType="submit" block size="large">
									Log in
								</Button>
							</Form>
						)}

						{viewMode === "register" && (
							<Form
								form={form}
								layout="vertical"
								onFinish={handleRegistration}
								initialValues={{ remember: true }}
							>
								<Form.Item
									name="name"
									label="Name"
									rules={[
										{ required: true, message: "Please input your name!" },
									]}
								>
									<Input size="large" placeholder="Enter your name..." />
								</Form.Item>
								<Form.Item
									name="email"
									label="Phone"
									rules={[
										{
											required: true,
											message: "Please input your phone number!",
										},
									]}
								>
									<Input
										size="large"
										placeholder="Enter your phone number..."
									/>
								</Form.Item>
								<Form.Item
									name="password"
									label="Password"
									rules={[
										{ required: true, message: "Please input your password!" },
									]}
								>
									<Input.Password
										size="large"
										placeholder="Enter your password..."
									/>
								</Form.Item>

								{!showCoupon ? (
									<p
										className="text-[#e91e63] cursor-pointer text-sm mb-4 hover:underline"
										onClick={() => setShowCoupon(true)}
									>
										Have a refer code?
									</p>
								) : (
									<Form.Item
										name="refer_by"
										label="Refer Code"
										rules={[{ required: false }]}
									>
										<Input size="large" placeholder="Enter refer code..." />
									</Form.Item>
								)}

								<Form.Item
									name="c_password"
									label="Confirm Password"
									rules={[
										{
											required: true,
											message: "Please confirm your password!",
										},
									]}
								>
									<Input.Password
										size="large"
										placeholder="Confirm your password..."
									/>
								</Form.Item>

								<Button type="primary" size="large" htmlType="submit" block>
									Registration
								</Button>
							</Form>
						)}

						{/* Forgot Password Step 1: Enter Phone */}
						{viewMode === "forgot-phone" && (
							<Form form={form} layout="vertical" onFinish={handleSendOtp}>
								<Form.Item
									name="phone"
									label="Phone Number"
									rules={[
										{
											required: true,
											message: "Please enter your phone number!",
										},
									]}
								>
									<Input
										size="large"
										placeholder="Enter your registered phone number..."
									/>
								</Form.Item>

								<Button
									type="primary"
									size="large"
									htmlType="submit"
									block
									loading={isSendingOtp}
								>
									Send OTP
								</Button>
							</Form>
						)}

						{/* Forgot Password Step 2: Enter OTP */}
						{viewMode === "forgot-otp" && (
							<Form form={form} layout="vertical" onFinish={handleVerifyOtp}>
								<Form.Item
									name="otp"
									label="OTP Code"
									rules={[
										{
											required: true,
											message: "Please enter the OTP!",
										},
										{
											len: 6,
											message: "OTP must be 6 digits!",
										},
									]}
								>
									<Input
										size="large"
										placeholder="Enter 6-digit OTP..."
										maxLength={6}
									/>
								</Form.Item>

								<Button type="primary" size="large" htmlType="submit" block loading={isVerifyingOtp}>
									Verify OTP
								</Button>

								<p className="text-center text-sm mt-3">
									Didn&apos;t receive the OTP?{" "}
									<span
										className="text-[#e91e63] cursor-pointer hover:underline"
										onClick={handleResendOtp}
									>
										Resend OTP
									</span>
								</p>
							</Form>
						)}

						{/* Forgot Password Step 3: New Password */}
						{viewMode === "forgot-reset" && (
							<Form
								form={form}
								layout="vertical"
								onFinish={handleResetPassword}
							>
								<Form.Item name="otp" hidden>
									<Input />
								</Form.Item>

								<Form.Item
									name="password"
									label="New Password"
									rules={[
										{
											required: true,
											message: "Please enter your new password!",
										},
										{
											min: 6,
											message: "Password must be at least 6 characters!",
										},
									]}
								>
									<Input.Password
										size="large"
										placeholder="Enter new password..."
									/>
								</Form.Item>

								<Form.Item
									name="password_confirmation"
									label="Confirm New Password"
									dependencies={["password"]}
									rules={[
										{
											required: true,
											message: "Please confirm your new password!",
										},
										({ getFieldValue }) => ({
											validator(_, value) {
												if (
													!value ||
													getFieldValue("password") === value
												) {
													return Promise.resolve();
												}
												return Promise.reject(
													new Error("Passwords do not match!"),
												);
											},
										}),
									]}
								>
									<Input.Password
										size="large"
										placeholder="Confirm new password..."
									/>
								</Form.Item>

								<Button
									type="primary"
									size="large"
									htmlType="submit"
									block
									loading={isResetting}
								>
									Reset Password
								</Button>
							</Form>
						)}
					</>
				) : (
					<Form form={form} layout="vertical" onFinish={handleLogin}>
						<Form.Item
							name="email"
							label="Phone Number"
							rules={[
								{ required: true, message: "Please input your phone number!" },
							]}
						>
							<Input placeholder="Enter your phone number..." size="large" />
						</Form.Item>

						<Form.Item
							name="password"
							label="Password"
							rules={[
								{ required: true, message: "Please input your password!" },
							]}
						>
							<Input.Password
								placeholder="Enter your password..."
								size="large"
							/>
						</Form.Item>

						<Button type="primary" htmlType="submit" block size="large">
							Log in
						</Button>
					</Form>
				)}

				{/* Switch Login/Register or Back to Login */}
				{isForgotFlow ? (
					<p className="text-center text-sm mt-3">
						Remember your password?{" "}
						<span
							className="text-[#E5005F] cursor-pointer"
							onClick={resetToLogin}
						>
							Back to Login
						</span>
					</p>
				) : viewMode === "login" ? (
					<p className="text-center text-sm mt-3">
						If you don&apos;t have any account?{" "}
						<span
							onClick={() => {
								form.resetFields();
								setViewMode("register");
							}}
							className="text-[#E5005F] cursor-pointer"
						>
							register now
						</span>
					</p>
				) : viewMode === "register" ? (
					<p className="text-center text-sm mt-3">
						Already have an account?{" "}
						<span
							onClick={resetToLogin}
							className="text-[#E5005F] cursor-pointer"
						>
							log in
						</span>
					</p>
				) : null}

				{/* Social Logins */}
				{!isForgotFlow && (
					<>
						<Divider className="my-3">
							<span className="text-gray-400 text-xs">Or</span>
						</Divider>

						<div className="space-y-2">
							<Button
								size="middle"
								block
								icon={<FcGoogle />}
								onClick={() => handleSocialLogin("google")}
								className="h-10 rounded-md border-gray-300 text-gray-600 hover:border-gray-400"
							>
								Continue with Google
							</Button>

							<Button
								size="middle"
								block
								icon={<FaApple />}
								onClick={() => handleSocialLogin("apple")}
								className="h-10 rounded-md border-gray-300 text-gray-600 hover:border-gray-400"
							>
								Continue with Apple
							</Button>
						</div>
					</>
				)}
			</Modal>
		</ConfigProvider>
	);
}
