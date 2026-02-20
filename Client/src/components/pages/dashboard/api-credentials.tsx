"use client";

import { Check, Copy, Loader2 } from "lucide-react";
import { useState } from "react";
import { getApiBaseUrl } from "@/lib/utils";
import { handleAsyncWithToast } from "@/utils/handleAsyncWithToast";
import {
	useGenerateDeveloperApiMutation,
	useGetDeveloperApiQuery,
} from "@/redux/features/developersApi/developersApi";

export default function ApiCredentials() {
	const [copiedField, setCopiedField] = useState<string | null>(null);

	// Fetch existing credentials
	const {
		data,
		isLoading: isLoadingCredentials,
		isError,
	} = useGetDeveloperApiQuery(undefined);

	// Mutation for generating credentials
	const [generateApi, { isLoading: isGenerating }] =
		useGenerateDeveloperApiMutation();

	const apiData = data?.data;

	const credentials = {
		userUuid: apiData?.user_id?.toString() || "",
		apiUrl: apiData?.domain || getApiBaseUrl(),
		apiKey: apiData?.api_key || "",
		apiSecret: apiData?.api_secret || "",
		status: apiData?.status || "Inactive",
	};

	const handleGenerate = async () => {
		await handleAsyncWithToast(
			async () => generateApi(undefined),
			true,
			"Generating API credentials...",
			"API credentials generated successfully!",
			"Failed to generate credentials."
		);
	};

	const copyToClipboard = async (text: string, field: string) => {
		try {
			await navigator.clipboard.writeText(text);
			setCopiedField(field);
			setTimeout(() => setCopiedField(null), 2000);
		} catch (err) {
			console.error("Failed to copy text: ", err);
		}
	};

	const Field = ({
		label,
		value,
		fieldName,
	}: {
		label: string;
		value: string;
		fieldName: string;
	}) => (
		<div className="space-y-1.5">
			<label className="text-sm font-medium text-gray-700">{label}</label>
			<div className="relative">
				<input
					value={value}
					readOnly
					className="w-full pr-10 py-2.5 px-3 border border-gray-200 rounded-lg bg-gray-50 text-sm text-gray-900 focus:outline-none"
				/>
				<button
					type="button"
					className="absolute right-1 top-1/2 -translate-y-1/2 h-8 w-8 flex items-center justify-center hover:bg-gray-200 rounded-md cursor-pointer transition-colors"
					onClick={() => copyToClipboard(value, fieldName)}
				>
					{copiedField === fieldName ? (
						<Check className="h-4 w-4 text-green-600" />
					) : (
						<Copy className="h-4 w-4 text-gray-400" />
					)}
				</button>
			</div>
		</div>
	);

	if (isLoadingCredentials) {
		return (
			<div className="m-3 sm:m-4 lg:m-6 bg-white rounded-xl border border-gray-100 shadow-sm p-4 sm:p-5 lg:p-8 mb-24">
				<div className="animate-pulse space-y-4">
					<div className="h-6 bg-gray-200 rounded w-1/3" />
					<div className="h-10 bg-gray-200 rounded" />
					<div className="h-10 bg-gray-200 rounded" />
					<div className="h-10 bg-gray-200 rounded" />
				</div>
			</div>
		);
	}

	if (isError) {
		return (
			<div className="m-3 sm:m-4 lg:m-6 bg-white rounded-xl border border-gray-100 shadow-sm p-4 sm:p-5 lg:p-8 mb-24">
				<p className="text-sm text-red-500">Failed to load API credentials.</p>
			</div>
		);
	}

	return (
		<div className="m-3 sm:m-4 lg:m-6 bg-white rounded-xl border border-gray-100 shadow-sm p-4 sm:p-5 lg:p-8 mb-24">
			{/* Header */}
			<div className="flex items-center justify-between mb-6">
				<h1 className="text-base sm:text-lg font-semibold text-gray-900">
					Developer API
				</h1>
				{apiData && (
					<span
						className={`px-3 py-1 rounded-full text-xs font-medium border ${credentials.status === "Active"
								? "bg-green-50 text-green-700 border-green-200"
								: "bg-red-50 text-red-700 border-red-200"
							}`}
					>
						{credentials.status}
					</span>
				)}
			</div>

			{apiData ? (
				<div className="space-y-5">
					<Field
						label="User UUID"
						value={credentials.userUuid}
						fieldName="userUuid"
					/>
					<Field
						label="API URL"
						value={credentials.apiUrl}
						fieldName="apiUrl"
					/>
					<Field
						label="API Key"
						value={credentials.apiKey}
						fieldName="apiKey"
					/>
					<Field
						label="API Secret"
						value={credentials.apiSecret}
						fieldName="apiSecret"
					/>
				</div>
			) : (
				<div className="text-center py-10">
					<p className="text-sm text-gray-500 mb-4">
						No API credentials found. Generate your credentials to get started.
					</p>
					<button
						disabled={isGenerating}
						onClick={handleGenerate}
						className="bg-[#E5005F] hover:bg-pink-600 !text-white px-6 py-3 text-sm font-medium rounded-md cursor-pointer transition-colors disabled:opacity-50 inline-flex items-center gap-2"
					>
						{isGenerating ? (
							<>
								<Loader2 className="w-4 h-4 animate-spin" />
								Generating...
							</>
						) : (
							"Generate API Credentials"
						)}
					</button>
				</div>
			)}
		</div>
	);
}
