import React, { Suspense } from "react";
import SupplierDetailsComponent from "@/components/pages/supplier/SupplierDetailsComponent";

interface PageProps {
    params: Promise<{
        slug: string;
    }>;
}

const SupplierPage = async ({ params }: PageProps) => {
    const resolvedParams = await params;
    const { slug } = resolvedParams;
    return (
        <Suspense fallback={
            <div className="min-h-[60vh] flex flex-col items-center justify-center">
                <div className="w-10 h-10 border-4 border-gray-200 border-t-[#E5005F] rounded-full animate-spin" />
                <p className="mt-4 text-gray-400 text-sm">Loading supplier...</p>
            </div>
        }>
            <SupplierDetailsComponent slug={slug} />
        </Suspense>
    );
};

export default SupplierPage;
