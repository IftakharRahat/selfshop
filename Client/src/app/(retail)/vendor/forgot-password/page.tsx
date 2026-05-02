"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { toast } from "sonner";
import Link from "next/link";

const BASE_URL = process.env.NEXT_PUBLIC_BASE_URL ?? "";

export default function VendorForgotPasswordPage() {
	const router = useRouter();

	// Step 1: Send OTP
	const [phone, setPhone] = useState("");
	const [isSendingOtp, setIsSendingOtp] = useState(false);
	const [otpSent, setOtpSent] = useState(false);

	// Step 2: Verify OTP + Reset
	const [otp, setOtp] = useState("");
	const [newPassword, setNewPassword] = useState("");
	const [confirmPassword, setConfirmPassword] = useState("");
	const [isResetting, setIsResetting] = useState(false);

	const handleSendOtp = async (e: React.FormEvent) => {
		e.preventDefault();
		setIsSendingOtp(true);
		try {
			const res = await fetch(`${BASE_URL}/forgot-password`, {
				method: "POST",
				headers: { "Content-Type": "application/json", Accept: "application/json" },
				body: JSON.stringify({ phone }),
			});
			const data = await res.json().catch(() => ({}));

			if (res.ok && data.status) {
				toast.success(data.message || "OTP sent to your phone number.");
				setOtpSent(true);
			} else {
				toast.error(data.message || "Failed to send OTP. Please check your phone number.");
			}
		} catch {
			toast.error("Network error. Please try again.");
		} finally {
			setIsSendingOtp(false);
		}
	};

	const handleResetPassword = async (e: React.FormEvent) => {
		e.preventDefault();
		if (newPassword !== confirmPassword) {
			toast.error("Passwords do not match.");
			return;
		}
		setIsResetting(true);
		try {
			const res = await fetch(`${BASE_URL}/reset-password`, {
				method: "POST",
				headers: { "Content-Type": "application/json", Accept: "application/json" },
				body: JSON.stringify({
					phone,
					otp,
					password: newPassword,
					password_confirmation: confirmPassword,
				}),
			});
			const data = await res.json().catch(() => ({}));

			if (res.ok && data.status) {
				toast.success(data.message || "Password reset successfully! Please log in.");
				router.replace("/vendor/login");
			} else {
				toast.error(data.message || "Invalid OTP or failed to reset password.");
			}
		} catch {
			toast.error("Network error. Please try again.");
		} finally {
			setIsResetting(false);
		}
	};

	return (
		<div className="min-h-screen flex items-center justify-center bg-gray-50 px-4">
			<div className="w-full max-w-md rounded-2xl bg-white shadow-sm border border-gray-100 overflow-hidden">
				{/* Header */}
				<div className="bg-gradient-to-r from-[#2d2a5d] via-[#3b3878] to-[#4a45a0] px-8 py-8 text-center relative overflow-hidden">
					<div className="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/4" />
					<div className="absolute bottom-0 left-8 w-20 h-20 bg-white/5 rounded-full translate-y-1/2" />
					<div className="relative z-10">
						<div className="w-16 h-16 mx-auto mb-4 rounded-2xl bg-white/10 border-2 border-white/20 flex items-center justify-center">
							<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
								<rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
								<path d="M7 11V7a5 5 0 0 1 10 0v4" />
							</svg>
						</div>
						<h1 className="text-xl font-bold text-white">
							{otpSent ? "Reset Your Password" : "Forgot Password?"}
						</h1>
						<p className="text-white/60 text-sm mt-2">
							{otpSent
								? "Enter the OTP sent to your phone and set a new password."
								: "Don't worry! Enter your registered phone number and we'll send you an OTP to reset your password."}
						</p>
					</div>
				</div>

				<div className="p-8 space-y-6">
					{!otpSent ? (
						/* ── Step 1: Enter Phone ── */
						<form onSubmit={handleSendOtp} className="space-y-4">
							<label className="flex flex-col text-sm font-medium text-gray-700">
								Phone number
								<input
									required
									type="tel"
									placeholder="01XXXXXXXXX"
									value={phone}
									onChange={(e) => setPhone(e.target.value)}
									className="mt-1 rounded-md border border-gray-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
								/>
							</label>

							<button
								type="submit"
								disabled={isSendingOtp || !phone.trim()}
								className="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-[#2d2a5d] text-white text-sm font-medium hover:bg-[#252947] disabled:opacity-60 transition-colors"
							>
								{isSendingOtp ? (
									<>
										<svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" /><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>
										Sending OTP...
									</>
								) : (
									"Send OTP"
								)}
							</button>
						</form>
					) : (
						/* ── Step 2: OTP + New Password ── */
						<form onSubmit={handleResetPassword} className="space-y-4">
							<div className="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
								<p className="font-medium">✓ OTP sent to {phone}</p>
								<p className="text-green-600 text-xs mt-1">Check your SMS inbox for the 6-digit code.</p>
							</div>

							<label className="flex flex-col text-sm font-medium text-gray-700">
								OTP Code
								<input
									required
									type="text"
									inputMode="numeric"
									maxLength={6}
									placeholder="Enter 6-digit OTP"
									value={otp}
									onChange={(e) => setOtp(e.target.value.replace(/\D/g, ""))}
									className="mt-1 rounded-md border border-gray-300 px-3 py-2.5 text-sm text-center tracking-[0.5em] font-mono text-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
								/>
							</label>

							<label className="flex flex-col text-sm font-medium text-gray-700">
								New Password <span className="text-gray-400 font-normal">(min 8 characters)</span>
								<input
									required
									type="password"
									minLength={8}
									value={newPassword}
									onChange={(e) => setNewPassword(e.target.value)}
									placeholder="Enter new password"
									autoComplete="new-password"
									className="mt-1 rounded-md border border-gray-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
								/>
							</label>

							<label className="flex flex-col text-sm font-medium text-gray-700">
								Confirm Password
								<input
									required
									type="password"
									value={confirmPassword}
									onChange={(e) => setConfirmPassword(e.target.value)}
									placeholder="Confirm new password"
									autoComplete="new-password"
									className={`mt-1 rounded-md border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 ${
										confirmPassword.length > 0 && newPassword !== confirmPassword
											? "border-red-400"
											: "border-gray-300"
									}`}
								/>
								{confirmPassword.length > 0 && newPassword !== confirmPassword && (
									<span className="text-xs text-red-500 mt-1">Passwords do not match</span>
								)}
							</label>

							<button
								type="submit"
								disabled={isResetting || (confirmPassword.length > 0 && newPassword !== confirmPassword)}
								className="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-[#2d2a5d] text-white text-sm font-medium hover:bg-[#252947] disabled:opacity-60 transition-colors"
							>
								{isResetting ? (
									<>
										<svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" /><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>
										Resetting...
									</>
								) : (
									"Reset Password"
								)}
							</button>

							<button
								type="button"
								onClick={() => { setOtpSent(false); setOtp(""); setNewPassword(""); setConfirmPassword(""); }}
								className="w-full text-sm text-gray-500 hover:text-gray-700 py-1"
							>
								← Change phone number
							</button>
						</form>
					)}

					<div className="border-t border-gray-100 pt-4">
						<p className="text-xs text-gray-600 text-center">
							Remember your password?{" "}
							<Link href="/vendor/login" className="font-medium text-[#2d2a5d] hover:underline">
								Back to login
							</Link>
						</p>
					</div>
				</div>
			</div>
		</div>
	);
}
