import { type ClassValue, clsx } from "clsx";
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
	const clean = p.replace(/^\//, "");

	// Paths starting with "public/" are in Laravel's web root directly (e.g. public/images/product/...)
	if (clean.startsWith("public/")) {
		return `${base}/${clean.replace(/^public\/?/, "")}`;
	}

	// Paths starting with "storage/" are already prefixed for the storage symlink
	if (clean.startsWith("storage/")) {
		return `${base}/${clean}`;
	}

	// Paths starting with "images/" are directly in the public web root
	if (clean.startsWith("images/")) {
		return `${base}/${clean}`;
	}

	// Anything else (e.g. "products/vendor/xyz.png" from Laravel's public disk)
	// needs the "storage/" prefix because it's served via the storage:link symlink
	return `${base}/storage/${clean}`;
}
