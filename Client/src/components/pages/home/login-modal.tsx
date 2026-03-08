/* eslint-disable @typescript-eslint/no-unused-vars */
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
} from "antd";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { FaApple } from "react-icons/fa";
import { FcGoogle } from "react-icons/fc";
import logo from "@/assets/images/loginLogo.png";
import {
	useLoginMutation,
	useRegisterMutation,
} from "@/redux/features/auth/authApi";
import { setUser } from "@/redux/features/auth/authSlice";
import { useAppDispatch } from "@/redux/hooks";
import { handleAsyncWithToast } from "@/utils/handleAsyncWithToast";
import { verifyToken } from "@/utils/verifyToken";

interface LoginModalProps {
	open: boolean;
	onCancel: () => void;
}

export default function LoginModal({ open, onCancel }: LoginModalProps) {
	const [form] = Form.useForm();
	const [activeTab, setActiveTab] = useState("reseller");
	const [isLogin, setIsLogin] = useState(true);
	const [isRegistration, setIsRegistration] = useState(false);
	const [showCoupon, setShowCoupon] = useState(false);

	const [login] = useLoginMutation();
	const [register] = useRegisterMutation();
	const dispatch = useAppDispatch();
	const router = useRouter();

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

	// const handleLogin = (values: any) => {
	//   console.log("Login data:", values);
	//   // TODO: login logic
	//   onCancel();
	// };

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
			form.resetFields();
			onCancel();
		}
	};

	const handleSocialLogin = (provider: string) => {
		console.log("Social login:", provider);
		// TODO: social login logic
	};

	const tabItems = [
		{
			key: "reseller",
			label: "Login as Reseller",
		},
		// {
		//   key: "supplier",
		//   label: "Login as Supplier",
		// },
	];

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

					<p className="text-gray-600 text-sm mb-3">
						{isRegistration
							? "Join our dropshipping and wholesale marketplace to start selling and sourcing products easily."
							: "Sign in to your account to access dropshipping products and wholesale deals."}
					</p>
				</div>

				{/* Tabs */}
				<Tabs
					activeKey={activeTab}
					onChange={setActiveTab}
					items={tabItems}
					centered
					tabBarGutter={24}
					tabBarStyle={{ marginBottom: 16 }}
				/>
				{activeTab === "reseller" ? (
					<>
						{isLogin ? (
							<Form form={form} layout="vertical" onFinish={handleLogin}>
								<Form.Item
									name="email"
									label="Phone Number"
									rules={[
										{
											required: true,
											message: "Please input your phone number!",
										},
										// { type: "email", message: "Please enter a valid email!" },
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

								<Button type="primary" size="large" htmlType="submit" block>
									Log in
								</Button>
							</Form>
						) : (
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
								{/* <Form.Item name="referCode" label="Refer code(optional) *" rules={[{ required: false, message: "Please input your refer code!" }]}>
                  <Input size="large" placeholder="Enter your refer code..." />
                </Form.Item> */}
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
										Have a coupon code?
									</p>
								) : (
									<Form.Item
										name="refer_by"
										label="Coupon Code"
										rules={[{ required: false }]}
									>
										<Input size="large" placeholder="Enter coupon code..." />
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
					</>
				) : (
					<Form form={form} layout="vertical" onFinish={handleLogin}>
						<Form.Item
							name="email"
							label="Phone Number"
							rules={[
								{ required: true, message: "Please input your phone number!" },
								// { type: "email", message: "Please enter a valid email!" },
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

				{isLogin ? (
					<p className="text-center text-sm mt-3">
						If you don’t have any account?{" "}
						<span
							onClick={() => setIsLogin(false)}
							className="text-[#E5005F] cursor-pointer"
						>
							register now
						</span>
					</p>
				) : (
					<p className="text-center text-sm mt-3">
						Already have an account?{" "}
						<span
							onClick={() => setIsLogin(true)}
							className="text-[#E5005F] cursor-pointer"
						>
							log in
						</span>
					</p>
				)}
				{/* Social Logins */}
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
			</Modal>
		</ConfigProvider>
	);
}
