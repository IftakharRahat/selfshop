"use client";

import { useEffect, useRef, useState } from "react";
import { useRouter } from "next/navigation";
import { Bell, CheckCheck } from "lucide-react";
import { useAppSelector } from "@/redux/hooks";
import {
	useGetUserNotificationsQuery,
	useMarkAllUserNotificationsReadMutation,
	useMarkUserNotificationReadMutation,
} from "@/redux/features/dashboardApi";

type Props = {
	disabled?: boolean;
};

function isAbsoluteUrl(url: string): boolean {
	return /^https?:\/\//i.test(url);
}

export default function UserNotificationCenter({ disabled = false }: Props) {
	const router = useRouter();
	const token = useAppSelector((state) => state.auth.access_token);
	const [open, setOpen] = useState(false);
	const panelRef = useRef<HTMLDivElement | null>(null);

	const isDisabled = disabled || !token;

	const { data, isFetching } = useGetUserNotificationsQuery(
		{ per_page: 12, page: 1 },
		{ skip: isDisabled },
	);

	const [markRead, { isLoading: markingRead }] =
		useMarkUserNotificationReadMutation();
	const [markAllRead, { isLoading: markingAll }] =
		useMarkAllUserNotificationsReadMutation();

	const notifications = data?.data ?? [];
	const unreadCount = data?.unread_count ?? 0;

	useEffect(() => {
		const onDocClick = (event: MouseEvent) => {
			if (
				panelRef.current &&
				!panelRef.current.contains(event.target as Node)
			) {
				setOpen(false);
			}
		};

		document.addEventListener("mousedown", onDocClick);
		return () => document.removeEventListener("mousedown", onDocClick);
	}, []);

	const handleNotificationClick = async (
		id: string,
		isRead: boolean,
		actionUrl?: string | null,
	) => {
		if (!isRead) {
			try {
				await markRead({ id }).unwrap();
			} catch {
				// best effort
			}
		}

		if (actionUrl) {
			if (isAbsoluteUrl(actionUrl)) {
				window.open(actionUrl, "_blank", "noopener,noreferrer");
			} else {
				router.push(actionUrl);
			}
			setOpen(false);
		}
	};

	return (
		<div className="relative" ref={panelRef}>
			<button
				type="button"
				onClick={() => !isDisabled && setOpen((v) => !v)}
				className={`relative inline-flex items-center justify-center w-10 h-10 rounded-full border border-gray-200 ${
					isDisabled
						? "bg-gray-50 text-gray-400 cursor-not-allowed"
						: "bg-white text-gray-700 hover:bg-gray-50"
				}`}
				aria-label="User notifications"
				disabled={isDisabled}
			>
				<Bell className="w-5 h-5" />
				{!isDisabled && unreadCount > 0 && (
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
							<p className="px-3 py-4 text-xs text-gray-500">
								Loading notifications...
							</p>
						) : notifications.length === 0 ? (
							<p className="px-3 py-4 text-xs text-gray-500">
								No notifications yet.
							</p>
						) : (
							<ul className="divide-y divide-gray-100">
								{notifications.map((item) => {
									const bodyText = item.message || item.description || "";
									const actionUrl = item.link || item.url || null;
									return (
										<li
											key={item.id}
											className={`px-3 py-2 transition ${
												item.is_read ? "bg-white" : "bg-indigo-50"
											}`}
										>
											<button
												type="button"
												onClick={() =>
													handleNotificationClick(
														item.id,
														item.is_read,
														actionUrl,
													)
												}
												disabled={markingRead}
												className="w-full text-left"
											>
												<p className="text-sm font-medium text-gray-900">
													{item.title}
												</p>
												{bodyText && (
													<p className="text-xs text-gray-600 mt-0.5 line-clamp-2">
														{bodyText}
													</p>
												)}
												<p className="text-[11px] text-gray-400 mt-1">
													{item.created_at
														? new Date(item.created_at).toLocaleString()
														: ""}
												</p>
											</button>
										</li>
									);
								})}
							</ul>
						)}
					</div>
				</div>
			)}
		</div>
	);
}
