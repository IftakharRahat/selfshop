import HomeComponent from "@/components/pages/home/HomeComponent";
import { headers } from "next/headers";
import { redirect } from "next/navigation";

const VENDOR_HOSTS = new Set([
	"vendor.selfshop.com",
	"vendor.selfshop.com.bd",
	"supplier.selfshop.com.bd",
	"vendor.localhost",
]);

function getHostForRouting(rawHost: string) {
	return rawHost.split(",")[0]?.trim().toLowerCase().split(":")[0] ?? "";
}

const HomePage = async () => {
	const requestHeaders = await headers();
	const host =
		getHostForRouting(requestHeaders.get("x-forwarded-host") ?? "") ||
		getHostForRouting(requestHeaders.get("host") ?? "");

	if (VENDOR_HOSTS.has(host)) {
		redirect("/vendor");
	}

	return (
		<>
			<HomeComponent />
		</>
	);
};

export default HomePage;
