/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { ConfigProvider, Input, Modal, Select } from "antd";
import TextArea from "antd/es/input/TextArea";
import R2ImageUploader from "@/components/shared/r2-image-uploader";
import { useState } from "react";
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
	const [attachment, setAttachment] = useState<File | null>(null);
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

	const onSubmit = async (data: TicketFormData) => {
		const formData = new FormData();
		formData.append("subject", data.subject);
		formData.append("department", data.department);
		formData.append("priority", data.priority);
		formData.append("message", data.message);
		if (attachment) {
			formData.append("attachment", attachment);
		}

		const response = await handleAsyncWithToast(async () => {
			return createSupportTicket(formData);
		});
		if (response?.data?.status) {
			reset();
			setAttachment(null);
			setOpen(false);
		}
	};

	const handleCancel = () => {
		reset();
		setAttachment(null);
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
						<R2ImageUploader
							label="Attachment"
							value={attachment}
							onChange={setAttachment}
							maxSizeMB={5}
							accept="image/*,.pdf"
							compact
						/>
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
