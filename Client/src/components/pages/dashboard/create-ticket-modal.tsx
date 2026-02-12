/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { ConfigProvider, Input, Modal, Select } from "antd";
import TextArea from "antd/es/input/TextArea";
import { File, Upload, X } from "lucide-react";
import type React from "react";
import { useRef, useState } from "react";
import { Controller, useForm } from "react-hook-form";
import * as z from "zod";
import "antd/dist/reset.css";
import { useCreateSupportTicketMutation } from "@/redux/features/supportTicket/supportTicketApi";
import { handleAsyncWithToast } from "@/utils/handleAsyncWithToast";

const ticketSchema = z.object({
	subject: z
		.string()
		.min(3, "Subject must be at least 3 characters")
		.max(100, "Subject is too long"),
	department: z.string().min(1, "Please select a department"),
	priority: z.string().min(1, "Please select a priority"),
	message: z
		.string()
		.min(10, "Message must be at least 10 characters")
		.max(1000, "Message is too long"),
});

type TicketFormData = z.infer<typeof ticketSchema>;

export function CreateTicketModal() {
	const [open, setOpen] = useState(false);
	const [dragActive, setDragActive] = useState(false);
	const [files, setFiles] = useState<File[]>([]);
	const fileInputRef = useRef<HTMLInputElement>(null);
	const [createSupportTicket] = useCreateSupportTicketMutation();

	const {
		control,
		handleSubmit,
		reset,
		formState: { errors },
	} = useForm<TicketFormData>({
		resolver: zodResolver(ticketSchema),
		defaultValues: {
			subject: "",
			department: "billing",
			priority: "low",
			message: "",
		},
	});

	const handleDrag = (e: React.DragEvent) => {
		e.preventDefault();
		e.stopPropagation();
		if (e.type === "dragenter" || e.type === "dragover") {
			setDragActive(true);
		} else if (e.type === "dragleave") {
			setDragActive(false);
		}
	};

	const handleDrop = (e: React.DragEvent) => {
		e.preventDefault();
		e.stopPropagation();
		setDragActive(false);

		if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
			const newFiles = Array.from(e.dataTransfer.files);
			setFiles((prev) => [...prev, ...newFiles]);
		}
	};

	const handleFileInput = (e: React.ChangeEvent<HTMLInputElement>) => {
		if (e.target.files && e.target.files.length > 0) {
			const newFiles = Array.from(e.target.files);
			setFiles((prev) => [...prev, ...newFiles]);
		}
	};

	const removeFile = (index: number) => {
		setFiles((prev) => prev.filter((_, i) => i !== index));
	};

	const handleUploadClick = () => {
		fileInputRef.current?.click();
	};

	const formatFileSize = (bytes: number) => {
		if (bytes === 0) return "0 Bytes";
		const k = 1024;
		const sizes = ["Bytes", "KB", "MB", "GB"];
		const i = Math.floor(Math.log(bytes) / Math.log(k));
		return Math.round((bytes / k ** i) * 100) / 100 + " " + sizes[i];
	};

	const onSubmit = async (data: TicketFormData) => {
		console.log("[v0] Ticket submitted:", { ...data, files });

		const formData = new FormData();
		formData.append("subject", data.subject);
		formData.append("department", data.department);
		formData.append("priority", data.priority);
		formData.append("message", data.message);
		files.forEach((file) => {
			formData.append(`attachment`, file);
		});

		const response = await handleAsyncWithToast(async () => {
			return createSupportTicket(formData);
		});
		if (response?.data?.status) {
			console.log("Ticket created successfully");
			reset();
			setFiles([]);
			setOpen(false);
		}
	};

	const handleCancel = () => {
		reset();
		setFiles([]);
		setOpen(false);
	};

	return (
		<ConfigProvider
			theme={{
				token: {
					colorPrimary: "#E5005F",
				},
			}}
		>
			<button
				onClick={() => setOpen(true)}
				className="bg-[#E5005F] hover:bg-pink-600 !text-white text-sm font-medium rounded-md px-4 py-2 cursor-pointer transition-colors"
			>
				+ Add now
			</button>

			<Modal
				title="Create New Ticket"
				open={open}
				onCancel={handleCancel}
				footer={null}
				width={600}
				centered
			>
				<form onSubmit={handleSubmit(onSubmit)} className="space-y-6 pt-4">
					{/* Subject */}
					<div>
						<p className="text-sm font-medium text-gray-700 mb-1">
							Subject
						</p>
						<Controller
							name="subject"
							control={control}
							render={({ field }) => (
								<Input
									size="large"
									placeholder="Enter the subject"
									{...field}
									status={errors.subject ? "error" : undefined}
									className="w-full"
								/>
							)}
						/>
						{errors.subject && (
							<p className="text-sm text-red-500 mt-1">{errors.subject.message}</p>
						)}
					</div>

					{/* Department and Priority */}
					<div className="grid grid-cols-2 gap-4">
						<div>
							<p className="text-sm font-medium text-gray-700 mb-1">
								Department
							</p>
							<Controller
								name="department"
								control={control}
								render={({ field }) => (
									<Select
										size="large"
										{...field}
										placeholder="Select department"
										className="w-full"
										status={errors.department ? "error" : undefined}
										onChange={(value) => field.onChange(value)}
										options={[
											{ value: "Billing", label: "Billing" },
											{ value: "Parcel Support", label: "Parcel Support" },
											{ value: "Technical Support", label: "Technical Support" },
										]}
									/>
								)}
							/>
							{errors.department && (
								<p className="text-sm text-red-500 mt-1">{errors.department.message}</p>
							)}
						</div>

						<div>
							<p className="text-sm font-medium text-gray-700 mb-1">
								Priority
							</p>
							<Controller
								name="priority"
								control={control}
								render={({ field }) => (
									<Select
										size="large"
										{...field}
										placeholder="Select priority"
										className="w-full"
										status={errors.priority ? "error" : undefined}
										onChange={(value) => field.onChange(value)}
										options={[
											{ value: "Low", label: "Low" },
											{ value: "Medium", label: "Medium" },
											{ value: "High", label: "High" },
										]}
									/>
								)}
							/>
							{errors.priority && (
								<p className="text-sm text-red-500 mt-1">{errors.priority.message}</p>
							)}
						</div>
					</div>

					{/* Message */}
					<div>
						<p className="text-sm font-medium text-gray-700 mb-1">
							Message
						</p>
						<Controller
							name="message"
							control={control}
							render={({ field }) => (
								<TextArea
									rows={4}
									placeholder="Enter the message..."
									{...field}
									status={errors.message ? "error" : undefined}
									className="w-full min-h-[120px] resize-none"
								/>
							)}
						/>
						{errors.message && (
							<p className="text-sm text-red-500 mt-1">{errors.message.message}</p>
						)}
					</div>

					{/* Attachment */}
					<div>
						<p className="text-sm font-medium text-gray-700 mb-1">
							Attachment
						</p>
						<input
							ref={fileInputRef}
							type="file"
							multiple
							onChange={handleFileInput}
							className="hidden"
							accept="*/*"
						/>
						<div
							className={`border-2 border-dashed rounded-lg p-8 text-center transition-colors cursor-pointer ${dragActive
									? "border-[#E5005F] bg-pink-50"
									: "border-gray-300 hover:border-gray-400"
								}`}
							onDragEnter={handleDrag}
							onDragLeave={handleDrag}
							onDragOver={handleDrag}
							onDrop={handleDrop}
							onClick={handleUploadClick}
						>
							<Upload className="mx-auto h-12 w-12 text-gray-400 mb-2" />
							<p className="text-gray-500">Upload image</p>
						</div>

						{files.length > 0 && (
							<div className="space-y-2 mt-3">
								{files.map((file, index) => (
									<div
										key={index}
										className="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200"
									>
										<div className="flex items-center gap-3 flex-1 min-w-0">
											<File className="h-5 w-5 text-gray-500 flex-shrink-0" />
											<div className="flex-1 min-w-0">
												<p className="text-sm font-medium text-gray-700 truncate">
													{file.name}
												</p>
												<p className="text-xs text-gray-500">
													{formatFileSize(file.size)}
												</p>
											</div>
										</div>
										<button
											type="button"
											onClick={(e) => {
												e.stopPropagation();
												removeFile(index);
											}}
											className="h-8 w-8 flex items-center justify-center rounded-md hover:bg-gray-200 flex-shrink-0 cursor-pointer"
										>
											<X className="h-4 w-4 text-gray-500" />
										</button>
									</div>
								))}
							</div>
						)}
					</div>

					{/* Submit Button */}
					<button
						type="submit"
						className="w-full bg-[#E5005F] hover:bg-pink-600 !text-white py-3 text-base font-medium rounded-md cursor-pointer transition-colors"
					>
						Submit Ticket
					</button>
				</form>
			</Modal>
		</ConfigProvider>
	);
}
