"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { File, Upload, X } from "lucide-react";
import type React from "react";
import { useRef, useState } from "react";
import { Controller, useForm } from "react-hook-form";
import * as z from "zod";
import { Button } from "@/components/ui/button";
import {
	Dialog,
	DialogContent,
	DialogHeader,
	DialogTitle,
	DialogTrigger,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
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
		<Dialog open={open} onOpenChange={setOpen}>
			<DialogTrigger asChild>
				<Button className="bg-[#E91E63] hover:bg-[#C2185B] text-white">
					+ Add now
				</Button>
			</DialogTrigger>
			<DialogContent className="sm:max-w-[600px] p-0 gap-0 bg-white">
				<DialogHeader className="px-6 pt-6 pb-4">
					<DialogTitle className="text-xl font-semibold">
						Create New Ticket
					</DialogTitle>
				</DialogHeader>

				<form onSubmit={handleSubmit(onSubmit)} className="px-6 pb-6 space-y-4">
					{/* Subject */}
					<div className="space-y-2">
						<Label htmlFor="subject" className="text-sm font-medium">
							Subject
						</Label>
						<Controller
							name="subject"
							control={control}
							render={({ field }) => (
								<Input
									{...field}
									id="subject"
									placeholder="Enter the Subject"
									className={`w-full ${errors.subject ? "border-red-500" : ""}`}
								/>
							)}
						/>
						{errors.subject && (
							<p className="text-sm text-red-500">{errors.subject.message}</p>
						)}
					</div>

					{/* Department and Priority */}
					<div className="grid grid-cols-2 gap-4">
						<div className="space-y-2 bg-white">
							<Label htmlFor="department" className="text-sm font-medium">
								Department
							</Label>
							<Controller
								name="department"
								control={control}
								render={({ field }) => (
									<Select value={field.value} onValueChange={field.onChange}>
										<SelectTrigger
											id="department"
											className={errors.department ? "border-red-500" : ""}
										>
											<SelectValue placeholder="Select department" />
										</SelectTrigger>
										<SelectContent className="bg-white">
											<SelectItem value="Billing">Billing</SelectItem>
											<SelectItem value="Parcel Support">
												Parcel Support
											</SelectItem>
											<SelectItem value="Technical Support">
												Technical Support
											</SelectItem>
										</SelectContent>
									</Select>
								)}
							/>
							{errors.department && (
								<p className="text-sm text-red-500">
									{errors.department.message}
								</p>
							)}
						</div>

						<div className="space-y-2">
							<Label htmlFor="priority" className="text-sm font-medium">
								Priority
							</Label>
							<Controller
								name="priority"
								control={control}
								render={({ field }) => (
									<Select value={field.value} onValueChange={field.onChange}>
										<SelectTrigger
											id="priority"
											className={errors.priority ? "border-red-500" : ""}
										>
											<SelectValue placeholder="Select priority" />
										</SelectTrigger>
										<SelectContent className="bg-white">
											<SelectItem value="Low">Low</SelectItem>
											<SelectItem value="Medium">Medium</SelectItem>
											<SelectItem value="High">High</SelectItem>
										</SelectContent>
									</Select>
								)}
							/>
							{errors.priority && (
								<p className="text-sm text-red-500">
									{errors.priority.message}
								</p>
							)}
						</div>
					</div>

					{/* Message */}
					<div className="space-y-2">
						<Label htmlFor="message" className="text-sm font-medium">
							Message
						</Label>
						<Controller
							name="message"
							control={control}
							render={({ field }) => (
								<Textarea
									{...field}
									id="message"
									placeholder="Enter the message..."
									className={`min-h-[120px] resize-none ${errors.message ? "border-red-500" : ""}`}
								/>
							)}
						/>
						{errors.message && (
							<p className="text-sm text-red-500">{errors.message.message}</p>
						)}
					</div>

					{/* Attachment */}
					<div className="space-y-2">
						<Label className="text-sm font-medium">Attachment</Label>
						<input
							ref={fileInputRef}
							type="file"
							multiple
							onChange={handleFileInput}
							className="hidden"
							accept="*/*"
						/>
						<div
							className={`border-2 border-dashed rounded-lg p-8 text-center transition-colors cursor-pointer ${
								dragActive
									? "border-primary bg-primary/5"
									: "border-gray-300 hover:border-gray-400"
							}`}
							onDragEnter={handleDrag}
							onDragLeave={handleDrag}
							onDragOver={handleDrag}
							onDrop={handleDrop}
							onClick={handleUploadClick}
						>
							<Upload className="mx-auto h-8 w-8 text-gray-400 mb-2" />
							<p className="text-sm text-gray-500">drag & drop or upload now</p>
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
										<Button
											variant="ghost"
											size="sm"
											onClick={(e) => {
												e.stopPropagation();
												removeFile(index);
											}}
											className="h-8 w-8 p-0 hover:bg-gray-200 flex-shrink-0"
										>
											<X className="h-4 w-4 text-gray-500" />
										</Button>
									</div>
								))}
							</div>
						)}
					</div>

					{/* Action Buttons */}
					<div className="flex gap-3 pt-2">
						<Button
							type="button"
							variant="outline"
							className="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 border-0"
							onClick={handleCancel}
						>
							Cancel
						</Button>
						<Button
							type="submit"
							className="flex-1 bg-[#4CAF50] hover:bg-[#45a049] text-white"
						>
							Save
						</Button>
					</div>
				</form>
			</DialogContent>
		</Dialog>
	);
}
