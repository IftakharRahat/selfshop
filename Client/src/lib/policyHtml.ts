export function beautifyPolicyHtml(rawHtml: string): string {
	let html = rawHtml || "";

	// Convert note lines into blue cards
	html = html.replace(
		/<p>(\s*(?:<strong>)?\s*(?:note|n\.b\.?|important)\s*:?\s*(?:<\/strong>)?[\s\S]*?)<\/p>/gi,
		'<div class="policy-note-card">$1</div>'
	);

	// Convert warning/caution lines into yellow cards
	html = html.replace(
		/<p>(\s*(?:<strong>)?\s*(?:warning|caution|⚠️|⚠)\s*:?\s*(?:<\/strong>)?[\s\S]*?)<\/p>/gi,
		'<div class="policy-warn-card">$1</div>'
	);

	// Convert contact sections into a soft highlighted block
	html = html.replace(
		/<h[2-6][^>]*>\s*contact\s*us\s*<\/h[2-6]>\s*([\s\S]*?)(?=<h[2-6][^>]*>|$)/gi,
		'<div class="policy-contact-card"><h3>Contact Us</h3>$1</div>'
	);

	return html;
}

