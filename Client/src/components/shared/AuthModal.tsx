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
import { useState } from "react";
import { FaApple } from "react-icons/fa";
import { FcGoogle } from "react-icons/fc";
import logo from "@/assets/icons/NavLogo.png";
import {
	useGetMeQuery,
	useLoginMutation,
	useRegisterMutation,
} from "@/redux/features/auth/authApi";
import { setUser } from "@/redux/features/auth/authSlice";
import { useAppDispatch, useAppSelector } from "@/redux/hooks";
import { handleAsyncWithToast } from "@/utils/handleAsyncWithToast";

interface AuthModalProps {
	open: boolean;
	onClose: () => void;
	setIsPricingModalOpen: (isOpen: boolean) => void;
}

export default function AuthModal({
	open,
	onClose,
	setIsPricingModalOpen,
}: AuthModalProps) {
	const dispatch = useAppDispatch();
	const token = useAppSelector((state) => state.auth.access_token);
	const { data: user } = useGetMeQuery(token, { skip: !token });
	const [isLogin, setIsLogin] = useState(true);
	const [activeTab, setActiveTab] = useState("reseller");

	// const [isLoginModalOpen, setIsLoginModalOpen] = useState(false);
	const [form] = Form.useForm();
	const tabItems = [{ key: "reseller", label: "Log in as Reseller" }];

	const [login] = useLoginMutation();
	const [register] = useRegisterMutation();
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
			onClose();
			if (user?.data?.profile?.membership_status == "Unpaid") {
				setIsPricingModalOpen(true);
			}
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
			form.resetFields();
			onClose();
		}
	};

	const handleSocialLogin = (provider: string) => {
		console.log("Social login:", provider);
		// TODO: social login logic
	};

	return (
		<ConfigProvider
			theme={{
				token: {
					colorPrimary: "#e91e63",
					colorLink: "#e91e63",
				},
			}}
		>
			<Modal open={open} onCancel={onClose} footer={null} width={700} centered>
				<div className="md:p-4">
					{/* Logo + Title */}
					<div className="text-center mb-6">
						<div className="flex items-center justify-center mb-4">
							<img src={logo.src} alt="Logo" className="w-60" />
						</div>

						<p className="text-gray-600 mb-6">
							{isLogin
								? "Use your correct email and password to log in to the admin panel."
								: "Create your account to get started with the admin panel."}
						</p>
					</div>

					{/* Tabs */}
					<Tabs
						activeKey={activeTab}
						onChange={setActiveTab}
						items={tabItems}
						centered
						tabBarGutter={40}
						tabBarStyle={{ marginBottom: 24 }}
					/>

					{/* Login/Register Form */}
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
											{
												required: true,
												message: "Please input your password!",
											},
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
											{
												required: true,
												message: "Please input your password!",
											},
										]}
									>
										<Input.Password
											size="large"
											placeholder="Enter your password..."
										/>
									</Form.Item>

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

									<Form.Item
										name="refer_code"
										label="Refer code (optional) *"
										rules={[{ required: false }]}
									>
										<Input
											size="large"
											placeholder="Enter your refer code..."
										/>
									</Form.Item>

									<Button type="primary" size="large" htmlType="submit" block>
										Registration
									</Button>
								</Form>
							)}
						</>
					) : null}

					{/* Switch Login/Register */}
					<p className="text-center mt-5">
						{isLogin ? (
							<>
								If you don’t have any account?{" "}
								<span
									className="text-[#E5005F] cursor-pointer"
									onClick={() => setIsLogin(false)}
								>
									register now
								</span>
							</>
						) : (
							<>
								Already have an account?{" "}
								<span
									className="text-[#E5005F] cursor-pointer"
									onClick={() => setIsLogin(true)}
								>
									log in
								</span>
							</>
						)}
					</p>

					<Divider className="my-6">
						<span className="text-gray-400">Or</span>
					</Divider>

					{/* Social Buttons */}
					<div className="space-y-3">
						<Button
							size="large"
							block
							icon={<FcGoogle />}
							onClick={() => handleSocialLogin("google")}
							className="h-12 rounded-md border-gray-300 text-gray-600 hover:border-gray-400"
						>
							Continue with Google
						</Button>

						<Button
							size="large"
							block
							icon={<FaApple />}
							onClick={() => handleSocialLogin("apple")}
							className="h-12 rounded-md border-gray-300 text-gray-600 hover:border-gray-400"
						>
							Continue with Apple
						</Button>
					</div>
				</div>
			</Modal>
		</ConfigProvider>
	);
}
