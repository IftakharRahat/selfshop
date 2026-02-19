import React from "react";
import SupplierDetailsComponent from "@/components/pages/supplier/SupplierDetailsComponent";

interface PageProps {
    params: Promise<{
        slug: string;
    }>;
}

const SupplierPage = async ({ params }: PageProps) => {
    const resolvedParams = await params;
    const { slug } = resolvedParams;
    return <SupplierDetailsComponent slug={slug} />;
};

export default SupplierPage;
