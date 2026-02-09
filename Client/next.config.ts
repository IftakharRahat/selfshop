import type { NextConfig } from "next";

const nextConfig: NextConfig = {
	// Ensure Turbopack uses Client as root (fixes "Cannot find the middleware module" when multiple lockfiles exist)
	turbopack: {
		root: process.cwd(),
	},
	images: {
		unoptimized: true,
		remotePatterns: [
			{
				protocol: "https",
				hostname: "**",
			},
			{
				protocol: "http",
				hostname: "**",
			},
			{
				protocol: "http",
				hostname: "localhost",
				port: "5000",
			},
			{
				protocol: "http",
				hostname: "localhost",
				port: "8000",
			},
			{
				protocol: "http",
				hostname: "127.0.0.1",
				port: "8000",
			},
		],
	},
};

export default nextConfig;
