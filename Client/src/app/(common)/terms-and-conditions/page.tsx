import { getApiBaseUrl } from "@/lib/utils";
import { beautifyPolicyHtml } from "@/lib/policyHtml";

export const metadata = {
	title: "Terms & Conditions | SelfShop",
	description: "Read SelfShop's terms and conditions.",
};

type InformationResponse = {
	status?: boolean;
	data?: {
		key?: string;
		value?: string;
		updated_at?: string;
	};
};

async function getTermsContent() {
	try {
		const res = await fetch(`${getApiBaseUrl()}/information/terms_codition`, {
			cache: "no-store",
		});
		if (!res.ok) return null;

		const json: InformationResponse = await res.json();
		const html = beautifyPolicyHtml((json?.data?.value ?? "").trim());
		if (!html) return null;

		return {
			html,
			updatedAt: json?.data?.updated_at ?? "",
		};
	} catch {
		return null;
	}
}

export default async function TermsAndConditionsPage() {
	const content = await getTermsContent();
	const updatedLabel = content?.updatedAt
		? new Date(content.updatedAt).toLocaleDateString("en-GB", {
				year: "numeric",
				month: "long",
				day: "numeric",
		  })
		: null;

	return (
		<section className="px-4 md:px-8 lg:px-20 py-10 text-gray-800">
			<div className="container mx-auto max-w-4xl bg-white p-6 md:p-10">
				<h1 className="text-3xl font-bold text-center mb-2 text-gray-900">
					Terms &amp; Conditions
				</h1>
				{updatedLabel ? (
					<p className="text-center text-gray-500 text-sm mb-8">
						Last updated: {updatedLabel}
					</p>
				) : null}

				{content?.html ? (
					<div
						className="leading-relaxed policy-html"
						dangerouslySetInnerHTML={{ __html: content.html }}
					/>
				) : (
					<p className="text-center text-gray-500">
						Terms &amp; Conditions content is not available right now.
					</p>
				)}
			</div>
		</section>
	);
}
