import axios, { type AxiosInstance } from "axios";
import { getItem, deleteItem } from "./storage";

const TOKEN_KEY = "supplier_auth_token";

const apiClient: AxiosInstance = axios.create({
  baseURL: process.env.EXPO_PUBLIC_API_URL,
  headers: {
    Accept: "application/json",
    "Content-Type": "application/json",
  },
  timeout: 15000,
});

// Attach auth token to every request
apiClient.interceptors.request.use(async (config) => {
  const token = await getItem(TOKEN_KEY);
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Handle 401 responses globally — clear token so AuthGate redirects to login
apiClient.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401) {
      // Don't clear token for login/register requests (those are expected 401s)
      const url = error.config?.url ?? "";
      if (!url.includes("/login") && !url.includes("/register")) {
        await deleteItem(TOKEN_KEY);
      }
    }
    return Promise.reject(error);
  },
);

export { apiClient, TOKEN_KEY };
export default apiClient;
