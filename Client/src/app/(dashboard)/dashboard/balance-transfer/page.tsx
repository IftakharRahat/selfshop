import React from "react";
import { TransferForm } from "@/components/pages/dashboard/transfer-form";

const page = () => {
	return (
		<div className="m-3 sm:m-4 lg:m-6 bg-white rounded-xl border border-gray-100 shadow-sm p-3 sm:p-5 lg:p-6 mb-24">
			<TransferForm />
		</div>
	);
};

export default page;
