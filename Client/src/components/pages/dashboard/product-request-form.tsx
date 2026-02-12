/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { ConfigProvider, Input, Select } from "antd";
import { Upload, X } from "lucide-react";
import type React from "react";
import { useState } from "react";
import { Controller, useForm } from "react-hook-form";
import { z } from "zod";
import "antd/dist/reset.css";
import TextArea from "antd/es/input/TextArea";
import { getImageUrl } from "@/lib/utils";
import {
	useCreateRequestProductMutation,
	useGetAllRequestProductsQuery,
} from "@/redux/features/requestProductListApi";
import { handleAsyncWithToast } from "@/utils/handleAsyncWithToast";

const productRequestSchema = z.object({
	productName: z.string().min(1, "Product name is required"),
	description: z.string().min(1, "Description is required"),
	quantity: z.string().min(1, "Product quantity is required"),
	image: z.custom<File | undefined>(
		(val) => val === undefined || val instanceof File,
		{
			message: "Image is required",
		},
	),
});

type ProductRequestFormValues = z.infer<typeof productRequestSchema>;

export default function ProductRequestForm() {
	const {
		control,
		handleSubmit,
		setValue,
		formState: { errors },
		reset,
	} = useForm<ProductRequestFormValues>({
		resolver: zodResolver(productRequestSchema),
		defaultValues: {
			productName: "",
			description: "",
			quantity: "",
			image: undefined,
		},
	});

	const [preview, setPreview] = useState<string | null>(null);
	const [createRequestProduct] = useCreateRequestProductMutation();
	const { data } = useGetAllRequestProductsQuery(undefined);

	const onSubmit = async (formDataValues: ProductRequestFormValues) => {
		if (!formDataValues.image) {
			alert("Please upload an image before submitting.");
			return;
		}

		const formData = new FormData();
		formData.append("p_name", formDataValues.productName);
		formData.append("attachment", formDataValues.image);
		formData.append("p_quantity", formDataValues.quantity);
		formData.append("p_description", formDataValues.description);

		try {
			await handleAsyncWithToast(async () => {
				return createRequestProduct(formData);
			});
			reset();
			setPreview(null);
		} catch (error) {
			console.error("Upload failed:", error);
		}
	};

	const handleImageUpload = (event: React.ChangeEvent<HTMLInputElement>) => {
		const file = event.target.files?.[0];
		if (file) {
			setValue("image", file, { shouldValidate: true });
			setPreview(URL.createObjectURL(file));
		}
	};

	const handleImageRemove = () => {
		setValue("image", undefined, { shouldValidate: true });
		setPreview(null);
	};

	return (
		<div className="m-3 sm:m-4 lg:m-6 mb-24">
			<div className="mx-auto bg-white shadow-sm rounded-xl border border-gray-100 p-4 sm:p-5 lg:p-8">
				<h1 className="text-2xl font-semibold text-gray-900 mb-8">
					Product request list
				</h1>

				<ConfigProvider
					theme={{
						token: {
							colorPrimary: "#E5005F",
						},
					}}
				>
					<form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
						{/* Product Name */}
						<div>
							<p className="text-sm font-medium text-gray-700 h-3">
								Product name
							</p>
							<Controller
								name="productName"
								control={control}
								render={({ field }) => (
									<Input
										size="large"
										placeholder="Enter the product name"
										{...field}
										className="w-full border rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-pink-500"
									/>
								)}
							/>
							{errors.productName && (
								<p className="text-sm text-red-500">
									{errors.productName.message}
								</p>
							)}
						</div>

						{/* Description */}
						<div className="space-y-2">
							<p className="text-sm font-medium text-gray-700 h-3">
								Description
							</p>
							<Controller
								name="description"
								control={control}
								render={({ field }) => (
									<TextArea
										rows={4}
										placeholder="Enter the message..."
										{...field}
										className="w-full min-h-[120px] border rounded-md p-2 resize-none focus:outline-none focus:ring-2 focus:ring-pink-500"
									/>
								)}
							/>
							{errors.description && (
								<p className="text-sm text-red-500">
									{errors.description.message}
								</p>
							)}
						</div>

						<div className="space-y-2">
							<p className="text-sm font-medium text-gray-700 h-3">
								Product quantity
							</p>
							<Controller
								control={control}
								name="quantity"
								render={({ field }) => (
									<Select
										size="large"
										{...field}
										placeholder="Enter the product quantity"
										className="w-full"
										onChange={(value) => field.onChange(value)}
										options={[
											{ value: "1", label: "1" },
											{ value: "2", label: "2" },
											{ value: "3", label: "3" },
											{ value: "4", label: "4" },
											{ value: "5", label: "5" },
											{ value: "10", label: "10" },
											{ value: "20", label: "20" },
											{ value: "50", label: "50" },
											{ value: "100", label: "100+" },
										]}
									/>
								)}
							/>
							{errors.quantity && (
								<p className="text-sm text-red-500">
									{errors.quantity.message}
								</p>
							)}
						</div>

						<div className="space-y-2">
							<p className="text-sm font-medium text-gray-700 h-3">
								Upload image
							</p>
							<div className="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-gray-400 transition-colors">
								<div className="flex flex-col items-center space-y-4">
									<Upload className="w-12 h-12 text-gray-400" />
									<div className="space-y-2">
										<p className="text-gray-500">Upload image</p>
										<input
											type="file"
											accept="image/*"
											onChange={handleImageUpload}
											className="hidden"
											id="image-upload"
										/>
										<button
											type="button"
											onClick={() =>
												document.getElementById("image-upload")?.click()
											}
											className="px-4 py-2 border rounded-md text-gray-600 border-gray-300 hover:bg-gray-100"
										>
											Choose image
										</button>
									</div>
									{preview && (
										<div className="relative mt-4">
											<img
												src={preview}
												alt="Preview"
												className="max-h-32 rounded-lg border"
											/>
											<button
												type="button"
												onClick={handleImageRemove}
												className="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600"
											>
												<X size={14} />
											</button>
										</div>
									)}
								</div>
							</div>
							{errors.image && (
								<p className="text-sm text-red-500">{errors.image.message}</p>
							)}
						</div>

						<button
							type="submit"
							className="w-full bg-[#E5005F] hover:bg-pink-600 !text-white py-3 text-base font-medium rounded-md cursor-pointer"
						>
							Upload now
						</button>
					</form>
				</ConfigProvider>
			</div>

			{/* Requested Products Table */}
			<div className="w-full mt-4 sm:mt-6 pt-4 sm:pt-6 border-t border-gray-100">
				<h2 className="text-base sm:text-lg font-semibold text-gray-900 mb-3 sm:mb-4">
					Requested Products
				</h2>

				{/* Loading */}
				{!data?.data && (
					<div className="py-10 text-center text-gray-500 text-sm">
						Loading requested products...
					</div>
				)}

				{/* Mobile Card Layout */}
				{data?.data && (
					<div className="md:hidden space-y-3">
						{data.data.length > 0 ? (
							data.data.map((item: any) => (
								<div
									key={item.id}
									className="bg-gray-50/60 border border-gray-100 rounded-xl p-3"
								>
									<div className="flex items-start gap-3">
										<img
											src={getImageUrl(item.attachment)}
											alt="product"
											className="w-12 h-12 rounded-lg object-cover flex-shrink-0"
										/>
										<div className="flex-1 min-w-0">
											<div className="flex items-center justify-between mb-1">
												<p className="text-sm font-semibold text-gray-900 truncate mr-2">{item.p_name}</p>
												<span
													className={`px-2 py-0.5 rounded-full text-[10px] font-medium flex-shrink-0 ${item.status === "Paid"
															? "bg-green-50 text-green-700 border border-green-200"
															: item.status === "Pending"
																? "bg-amber-50 text-amber-700 border border-amber-200"
																: "bg-red-50 text-red-700 border border-red-200"
														}`}
												>
													{item.status}
												</span>
											</div>
											<div className="flex items-center justify-between text-xs text-gray-400">
												<span>#{item.id}</span>
												<span>{new Date(item.created_at).toLocaleDateString()}</span>
											</div>
										</div>
									</div>
								</div>
							))
						) : (
							<div className="py-10 text-center text-gray-400 text-sm">
								No requested products found.
							</div>
						)}
					</div>
				)}

				{/* Desktop Table Layout */}
				{data?.data && (
					<div className="hidden md:block overflow-x-auto">
						<table className="w-full">
							<thead>
								<tr className="bg-gray-50/80">
									<th className="p-4 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">
										ID
									</th>
									<th className="p-4 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">
										Product Name
									</th>
									<th className="p-4 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">
										Image
									</th>
									<th className="p-4 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">
										Status
									</th>
									<th className="p-4 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">
										Date
									</th>
								</tr>
							</thead>

							<tbody>
								{data.data.length > 0 ? (
									data.data.map((item: any) => (
										<tr
											key={item.id}
											className="border-b border-gray-50 hover:bg-gray-50/50 transition-colors"
										>
											<td className="p-4 text-sm font-medium text-gray-900">#{item.id}</td>
											<td className="p-4 text-sm text-gray-900">
												{item.p_name}
											</td>
											<td className="p-4">
												<img
													src={getImageUrl(item.attachment)}
													alt="product"
													className="w-12 h-12 rounded-lg object-cover"
												/>
											</td>
											<td className="p-4">
												<span
													className={`px-2.5 py-1 rounded-full text-xs font-medium ${item.status === "Paid"
															? "bg-green-50 text-green-700 border border-green-200"
															: item.status === "Pending"
																? "bg-amber-50 text-amber-700 border border-amber-200"
																: "bg-red-50 text-red-700 border border-red-200"
														}`}
												>
													{item.status}
												</span>
											</td>
											<td className="p-4 text-sm text-gray-500">
												{new Date(item.created_at).toLocaleDateString()}
											</td>
										</tr>
									))
								) : (
									<tr>
										<td colSpan={5} className="py-12 text-center text-gray-400 text-sm">
											No requested products found.
										</td>
									</tr>
								)}
							</tbody>
						</table>
					</div>
				)}
			</div>
		</div>
	);
}
