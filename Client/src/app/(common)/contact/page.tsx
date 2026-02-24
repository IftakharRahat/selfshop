import React from "react";
import Script from "next/script";
import ContactPage from "@/components/pages/Contact/contact-page";

const page = () => {
	return (
		<>
			<ContactPage />
			<Script id="tawk-to-script" strategy="afterInteractive">
				{`
					var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
					(function(){
						var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
						s1.async=true;
						s1.src='https://embed.tawk.to/691215275805cd195914d534/1j9na58v8';
						s1.charset='UTF-8';
						s1.setAttribute('crossorigin','*');
						s0.parentNode.insertBefore(s1,s0);
					})();
				`}
			</Script>
		</>
	);
};

export default page;
