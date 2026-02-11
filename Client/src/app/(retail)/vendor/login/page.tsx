"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { useLoginMutation } from "@/redux/features/auth/authApi";
import { useAppDispatch } from "@/redux/hooks";
import { setUser } from "@/redux/features/auth/authSlice";
import { toast } from "sonner";
import Link from "next/link";

const VendorLoginPage = () => {
	const router = useRouter();
	const dispatch = useAppDispatch();
	const [login, { isLoading }] = useLoginMutation();

	const [email, setEmail] = useState("");
	const [password, setPassword] = useState("");

	const handleSubmit = async (e: React.FormEvent) => {
		e.preventDefault();
		try {
			const res = (await login({ email, password }).unwrap()) as {
				status: boolean;
				token?: string;
				token_type?: string;
				message?: string;
			};

			if (!res.status || !res.token) {
				toast.error(res.message || "Login failed");
				return;
			}

			dispatch(
				setUser({
					user: null,
					access_token: res.token,
					refresh_token: null,
				}),
			);

			toast.success("Logged in successfully");
			router.replace("/vendor");
		} catch (error) {
			console.error(error);
			toast.error("Login failed");
		}
	};

	return (
		<div className="min-h-screen flex items-center justify-center bg-gray-50 px-4">
			<div className="w-full max-w-md rounded-2xl bg-white shadow-sm border border-gray-100 p-8 space-y-6">
				<div className="space-y-1 text-center">
					<h1 className="text-2xl font-bold text-gray-900">
						Vendor portal login
					</h1>
					<p className="text-sm text-gray-600">
						Sign in with your SelfShop vendor account to manage products and
						orders.
					</p>
				</div>

				<form onSubmit={handleSubmit} className="space-y-4">
					<label className="flex flex-col text-sm font-medium text-gray-700">
						Email or phone
						<input
							required
							value={email}
							onChange={(e) => setEmail(e.target.value)}
							className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-pink-500"
						/>
					</label>
					<label className="flex flex-col text-sm font-medium text-gray-700">
						Password
						<input
							required
							type="password"
							value={password}
							onChange={(e) => setPassword(e.target.value)}
							className="mt-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-pink-500"
						/>
					</label>

					<button
						type="submit"
						disabled={isLoading}
						className="w-full inline-flex items-center justify-center px-4 py-2 rounded-lg bg-[#E5005F] text-white text-sm font-medium hover:bg-pink-700 disabled:opacity-60"
					>
						{isLoading ? "Signing in..." : "Sign in"}
					</button>
				</form>

				<p className="text-xs text-gray-600 text-center">
					New to SelfShop vendor?{" "}
					<Link
						href="/vendor/register"
						className="font-medium text-[#E5005F] hover:underline"
					>
						Create vendor account
					</Link>
				</p>
			</div>
		</div>
	);
};

export default VendorLoginPage;

