import { ClassValue, clsx } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

/** Base URL for API requests (e.g. http://localhost:8000/api) */
export const getApiBaseUrl = () =>
  process.env.NEXT_PUBLIC_BASE_URL || "http://localhost:8000/api";

/** Full URL for images/assets from backend (e.g. http://localhost:8000) */
export function getImageUrl(path: string | undefined | null): string {
  const p = typeof path === "string" ? path.trim() : "";
  if (!p || p.length < 2 || p === "image") return "/placeholder.svg";
  if (p.startsWith("http")) return p;
  const base = process.env.NEXT_PUBLIC_IMAGE_BASE || "http://localhost:8000";
  // Laravel's web root is public/, so strip leading "public/" so URL is base/images/...
  const pathWithoutPublic = p.replace(/^\//, "").replace(/^public\/?/, "");
  return `${base}/${pathWithoutPublic}`;
}
