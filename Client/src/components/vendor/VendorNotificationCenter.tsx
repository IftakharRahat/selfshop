"use client";

import { useEffect, useRef, useState } from "react";
import { createPortal } from "react-dom";
import Link from "next/link";
import { Bell, CheckCheck, X, ExternalLink } from "lucide-react";
import {
	useGetVendorNotificationsQuery,
	useMarkAllVendorNotificationsReadMutation,
	useMarkVendorNotificationReadMutation,
} from "@/redux/api/vendorApi";

type Props = {
	disabled?: boolean;
};

/* eslint-disable @typescript-eslint/no-explicit-any */

function isAbsoluteUrl(url: string): boolean {
	return /^https?:\/\//i.test(url);
}

export default function VendorNotificationCenter({ disabled = false }: Props) {
	const [open, setOpen] = useState(false);
	const [selectedNotification, setSelectedNotification] = useState<any>(null);
	const panelRef = useRef<HTMLDivElement | null>(null);
	const { data, isFetching } = useGetVendorNotificationsQuery(
		{ per_page: 12, page: 1 },
		{ skip: disabled },
	);
	const [markRead, { isLoading: markingRead }] =
		useMarkVendorNotificationReadMutation();
	const [markAllRead, { isLoading: markingAll }] =
		useMarkAllVendorNotificationsReadMutation();

	const notifications = data?.data?.notifications ?? [];
	const unreadCount = data?.data?.unread_count ?? 0;

	useEffect(() => {
		const onDocClick = (event: MouseEvent) => {
			if (selectedNotification) return;
			if (
				panelRef.current &&
				!panelRef.current.contains(event.target as Node)
			) {
				setOpen(false);
			}
		};
		document.addEventListener("mousedown", onDocClick);
		return () => document.removeEventListener("mousedown", onDocClick);
	}, [selectedNotification]);

	const handleNotificationClick = async (item: any) => {
		if (!item.is_read) {
			try {
				await markRead({ id: item.id }).unwrap();
			} catch {
				// best effort
			}
		}
		setOpen(false);
		setSelectedNotification(item);
	};

	const closeModal = () => {
		setSelectedNotification(null);
	};

	const getActionUrl = (item: any) => item.action_url || item.link || item.url || null;

	return (
		<>
			<div className="relative" ref={panelRef}>
				<button
					type="button"
					onClick={() => setOpen((v) => !v)}
					className="relative inline-flex items-center justify-center w-10 h-10 rounded-full border border-gray-200 bg-white text-gray-700 hover:bg-gray-50"
					aria-label="Vendor notifications"
				>
					<Bell className="w-5 h-5" />
					{unreadCount > 0 && (
						<span className="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-red-600 text-white text-[10px] font-semibold flex items-center justify-center">
							{unreadCount > 99 ? "99+" : unreadCount}
						</span>
					)}
				</button>

				{open && (
					<div className="fixed left-1/2 -translate-x-1/2 sm:absolute sm:left-auto sm:translate-x-0 sm:right-0 mt-2 w-[calc(100vw-2rem)] max-w-[340px] rounded-xl border border-gray-200 bg-white shadow-xl z-50">
						<div className="px-3 py-2 border-b border-gray-100 flex items-center justify-between">
							<p className="text-sm font-semibold text-gray-900">Notifications</p>
							<button
								type="button"
								onClick={() => markAllRead()}
								disabled={markingAll || unreadCount === 0}
								className="inline-flex items-center gap-1 text-xs text-indigo-700 disabled:text-gray-400"
							>
								<CheckCheck className="w-3.5 h-3.5" />
								Mark all read
							</button>
						</div>

						<div className="max-h-96 overflow-y-auto">
							{isFetching ? (
								<p className="px-3 py-4 text-xs text-gray-500">Loading notifications...</p>
							) : notifications.length === 0 ? (
								<p className="px-3 py-4 text-xs text-gray-500">No notifications yet.</p>
							) : (
								<ul className="divide-y divide-gray-100">
									{notifications.map((item: any) => (
										<li
											key={item.id}
											className={`px-3 py-2 transition ${item.is_read ? "bg-white" : "bg-indigo-50"
												}`}
										>
											<button
												type="button"
												onClick={() => handleNotificationClick(item)}
												disabled={markingRead}
												className="w-full text-left cursor-pointer"
											>
												<p className="text-sm font-medium text-gray-900">
													{item.title}
												</p>
												<p className="text-xs text-gray-600 mt-0.5 line-clamp-2">
													{item.message}
												</p>
												<p className="text-[11px] text-gray-400 mt-1">
													{item.created_at
														? new Date(item.created_at).toLocaleString()
														: ""}
												</p>
											</button>
										</li>
									))}
								</ul>
							)}
						</div>
					</div>
				)}
			</div>

			{/* ── Notification Detail Modal ── */}
			{selectedNotification && createPortal(
				<div
					className="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40 px-4"
					onClick={closeModal}
				>
					<div
						className="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden"
						onClick={(e) => e.stopPropagation()}
					>
						{/* Modal Header */}
						<div className="flex items-center justify-between px-5 py-4 border-b border-gray-100">
							<h3 className="text-base font-semibold text-gray-900">
								Notification
							</h3>
							<button
								type="button"
								onClick={closeModal}
								className="w-8 h-8 rounded-full flex items-center justify-center text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors cursor-pointer"
							>
								<X className="w-4 h-4" />
							</button>
						</div>

						{/* Modal Body */}
						<div className="px-5 py-5 space-y-3">
							<h4 className="text-lg font-semibold text-gray-900">
								{selectedNotification.title}
							</h4>

							{selectedNotification.message && (
								<p className="text-sm text-gray-600 leading-relaxed whitespace-pre-line">
									{selectedNotification.message}
								</p>
							)}

							{selectedNotification.created_at && (
								<p className="text-xs text-gray-400">
									{new Date(selectedNotification.created_at).toLocaleString()}
								</p>
							)}

							{/* Clickable action link */}
							{getActionUrl(selectedNotification) && (
								<div className="pt-2">
									{isAbsoluteUrl(getActionUrl(selectedNotification)) ? (
										<a
											href={getActionUrl(selectedNotification)}
											target="_blank"
											rel="noopener noreferrer"
											className="inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-700 transition-colors"
										>
											<ExternalLink className="w-3.5 h-3.5" />
											Open Link
										</a>
									) : (
										<Link
											href={getActionUrl(selectedNotification)}
											onClick={closeModal}
											className="inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-700 transition-colors"
										>
											<ExternalLink className="w-3.5 h-3.5" />
											View Details
										</Link>
									)}
								</div>
							)}
						</div>

						{/* Modal Footer */}
						<div className="px-5 py-3 border-t border-gray-100 flex justify-end">
							<button
								type="button"
								onClick={closeModal}
								className="px-5 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors cursor-pointer"
							>
								Done
							</button>
						</div>
					</div>
				</div>
				, document.body)}
		</>
	);
}
