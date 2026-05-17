import axios, { type AxiosInstance } from "axios";
import * as SecureStore from "expo-secure-store";

const TOKEN_KEY = "selfshop_auth_token";
const DEFAULT_API_URL = "https://api-v1.selfshop.com.bd/api";

function isFormDataPayload(value: unknown): value is FormData {
  if (typeof FormData !== "undefined" && value instanceof FormData) return true;
  return Boolean(
    value &&
      typeof value === "object" &&
      typeof (value as { append?: unknown }).append === "function" &&
      typeof (value as { getParts?: unknown }).getParts === "function",
  );
}

function removeContentTypeHeader(headers: unknown) {
  if (!headers || typeof headers !== "object") return;

  const maybeAxiosHeaders = headers as { delete?: (name: string) => void };
  if (typeof maybeAxiosHeaders.delete === "function") {
    maybeAxiosHeaders.delete("Content-Type");
    return;
  }

  const headerMap = headers as Record<string, unknown>;
  delete headerMap["Content-Type"];
  delete headerMap["content-type"];
}

const apiClient: AxiosInstance = axios.create({
  baseURL: process.env.EXPO_PUBLIC_API_URL || DEFAULT_API_URL,
  headers: {
    Accept: "application/json",
  },
  timeout: 15000,
});

// Attach auth token to every request
apiClient.interceptors.request.use(async (config) => {
  if (isFormDataPayload(config.data)) {
    // On React Native, we must explicitly set Content-Type to multipart/form-data.
    // RN's XMLHttpRequest will automatically append the correct boundary.
    // Simply removing the header (as done for web) causes Axios on RN to
    // fall back to JSON serialization in production builds.
    config.headers["Content-Type"] = "multipart/form-data";
  }

  const token = await SecureStore.getItemAsync(TOKEN_KEY);
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Handle 401 responses globally
apiClient.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401) {
      await SecureStore.deleteItemAsync(TOKEN_KEY);
    }
    return Promise.reject(error);
  },
);

export { apiClient, TOKEN_KEY };
export default apiClient;
