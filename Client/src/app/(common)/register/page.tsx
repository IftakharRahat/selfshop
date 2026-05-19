import ResellerRegisterPage from "@/components/pages/auth/reseller-register-page";
import { getReferralCodeFromSearchParams } from "@/lib/registration-redirect";

type SearchParams = Promise<Record<string, string | string[] | undefined>>;

export default async function RegisterPage({
	searchParams,
}: {
	searchParams: SearchParams;
}) {
	const params = await searchParams;
	return <ResellerRegisterPage referralCode={getReferralCodeFromSearchParams(params)} />;
}
