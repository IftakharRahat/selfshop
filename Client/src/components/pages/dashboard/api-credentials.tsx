"use client";

import { useState } from "react";
import { Copy, Check } from "lucide-react";
import {
  useGetDeveloperApiQuery,
  useGenerateDeveloperApiQuery,
} from "@/redux/features/developersApi/developersApi";

export default function ApiCredentials() {
  const [copiedField, setCopiedField] = useState<string | null>(null);

  // Fetch existing credentials
  const {
    data,
    isLoading: isLoadingCredentials,
    isError,
    refetch: refetchCredentials,
  } = useGetDeveloperApiQuery(undefined);

  // Manual trigger for generation
  const {
    data: generatedData,
    isLoading: isGenerating,
    refetch: triggerGenerate,
  } = useGenerateDeveloperApiQuery(undefined, {
    skip: true,
  });

  // Decide which data to show (generated or fetched)
  const apiData = generatedData?.data || data?.data;

  const credentials = {
    userUuid: apiData?.user_id?.toString() || "",
    apiUrl: apiData?.domain || "selfshop.com.bd/api/...",
    apiKey: apiData?.api_key || "",
    apiSecret: apiData?.api_secret || "",
    status: apiData?.status || "Inactive",
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
    <div className="space-y-2">
      <label className="text-sm font-medium text-gray-700">{label}</label>
      <div className="relative">
        <input
          value={value}
          readOnly
          className="w-full pr-10 py-2 px-3 border border-gray-300 rounded-md bg-gray-50 text-sm"
        />
        <button
          type="button"
          className="absolute right-1 top-1/2 -translate-y-1/2 h-8 w-8 flex items-center justify-center hover:bg-gray-200 rounded-md"
          onClick={() => copyToClipboard(value, fieldName)}
        >
          {copiedField === fieldName ? (
            <Check className="h-4 w-4 text-green-600" />
          ) : (
            <Copy className="h-4 w-4 text-gray-500" />
          )}
        </button>
      </div>
    </div>
  );

  if (isLoadingCredentials) {
    return <div className="m-4">Loading credentials...</div>;
  }

  if (isError) {
    return <div className="m-4 text-red-500">Failed to load API credentials.</div>;
  }

  return (
    <div className="m-4 lg:m-6 md:bg-white rounded-md md:p-6">
      <h1 className="text-2xl font-semibold text-gray-900 mb-8">
        Developer API
      </h1>

      {apiData ? (
        <div className="space-y-6">
          <Field label="User UUID" value={credentials.userUuid} fieldName="userUuid" />
          <Field label="API URL" value={credentials.apiUrl} fieldName="apiUrl" />
          <Field label="API Key" value={credentials.apiKey} fieldName="apiKey" />
          <Field label="API Secret" value={credentials.apiSecret} fieldName="apiSecret" />

          {/* Status */}
          <div className="space-y-2 flex items-center justify-between">
            <div className="text-sm font-medium text-gray-700">Status</div>
            <div
              className={`inline-flex items-center px-5 py-2 rounded-md text-sm font-medium ${
                credentials.status === "Active"
                  ? "bg-green-600 text-white"
                  : "bg-red-500 text-white"
              }`}
            >
              {credentials.status}
            </div>
          </div>
        </div>
      ) : (
        <button
          disabled={isGenerating}
          onClick={() =>
            triggerGenerate().then(() => refetchCredentials())
          }
          className="px-6 py-3 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700"
        >
          {isGenerating ? "Generating..." : "Generate API Credentials"}
        </button>
      )}
    </div>
  );
}
