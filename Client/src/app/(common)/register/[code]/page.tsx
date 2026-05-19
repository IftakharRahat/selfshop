import ResellerRegisterPage from "@/components/pages/auth/reseller-register-page";

type Params = Promise<{ code: string }>;

export default async function RegisterCodePage({
	params,
}: {
	params: Params;
}) {
	const { code } = await params;
	return <ResellerRegisterPage referralCode={code} />;
}
