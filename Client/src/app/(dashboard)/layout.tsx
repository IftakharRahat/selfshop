import type { ReactNode } from "react";
import DashboardLayout from "@/components/pages/dashboard/DashboardLayout";
import WithAuthForAdmin from "./dashboard/WithAuthForAdmin/WithAuthForAdmin";

const layout = ({ children }: { children: ReactNode }) => {
	return (
		<DashboardLayout>
			<WithAuthForAdmin>{children}</WithAuthForAdmin>
		</DashboardLayout>
	);
};

export default layout;
