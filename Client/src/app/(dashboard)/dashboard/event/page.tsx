"use client";

import { useState } from "react";
import {
	CalendarDays,
	CheckCircle2,
	ChevronDown,
	ChevronUp,
	Gift,
	HandCoins,
	Rocket,
	Target,
	Trophy,
} from "lucide-react";
import { toast } from "sonner";
import {
	useClaimSalesTargetRewardMutation,
	useGetAllDashboardDataQuery,
	useParticipateSalesTargetMutation,
} from "@/redux/features/dashboardApi";

interface ActiveSalesTarget {
	id: number;
	title: string;
	description?: string | null;
	target_type: string;
	target_value: number | string;
	order_scope?: string | null;
	reward_type?: string | null;
	reward_value?: number | string | null;
	reward_note?: string | null;
	start_date?: string | null;
	end_date?: string | null;
}

interface SalesTargetProgress {
	target: number;
	achieved: number;
	remaining: number;
	completed: boolean;
	progress_percent: number;
}

interface SalesTargetParticipation {
	joined: boolean;
	joined_at?: string | null;
	reward_claimed: boolean;
	reward_claimed_at?: string | null;
	can_claim: boolean;
}

interface SalesTargetWithMeta {
	target: ActiveSalesTarget;
	progress: SalesTargetProgress;
	participation: SalesTargetParticipation;
}

interface DashboardPayload {
	active_sales_targets?: SalesTargetWithMeta[] | null;
}

const formatDate = (raw?: string | null): string => {
	if (!raw) return "Not set";
	const dt = new Date(raw);
	if (Number.isNaN(dt.getTime())) return raw;
	return dt.toLocaleDateString();
};

const formatTargetValue = (
	value: number | string | undefined,
	type: string,
): string => {
	const parsed = Number(value ?? 0);
	if (type === "amount") {
		return `Tk ${parsed.toLocaleString(undefined, {
			minimumFractionDigits: 2,
			maximumFractionDigits: 2,
		})}`;
	}
	return `${parsed.toLocaleString(undefined, {
		maximumFractionDigits: 0,
	})} Qty`;
};

const getApiErrorMessage = (error: unknown, fallback: string): string => {
	const maybe = error as { data?: { message?: string }; message?: string };
	return maybe?.data?.message || maybe?.message || fallback;
};

/* ── Participating card — shows progress, details toggle, claim button ── */
function ParticipatingCard({
	item,
	onClaim,
	isClaiming,
	claimingId,
}: {
	item: SalesTargetWithMeta;
	onClaim: (id: number) => void;
	isClaiming: boolean;
	claimingId: number | null;
}) {
	const [expanded, setExpanded] = useState(false);
	const { target: t, progress, participation } = item;

	const rewardClaimed = Boolean(participation?.reward_claimed);
	const completed = Boolean(progress?.completed);
	const progressPercent = Math.max(
		0,
		Math.min(100, Number(progress?.progress_percent ?? 0)),
	);
	const canClaim = completed && !rewardClaimed;
	const isThisClaiming = isClaiming && claimingId === t.id;

	return (
		<div className="rounded-xl border border-gray-200 overflow-hidden">
			<div className="p-4 space-y-3">
				{/* Top row: title + status */}
				<div className="flex items-start justify-between gap-3">
					<div className="flex-1 min-w-0">
						<h3 className="text-sm font-semibold text-gray-900 truncate">
							{t.title || "Sales Target"}
						</h3>
						<p className="text-xs text-gray-500 mt-0.5">
							{formatDate(t.start_date)} – {formatDate(t.end_date)}
						</p>
					</div>
					<div className="flex items-center gap-2 flex-shrink-0">
						{rewardClaimed ? (
							<span className="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700">
								Reward Grabbed
							</span>
						) : completed ? (
							<span className="text-xs font-semibold px-2.5 py-1 rounded-full bg-blue-100 text-blue-700">
								Completed
							</span>
						) : (
							<span className="text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-100 text-amber-700">
								In Progress
							</span>
						)}
					</div>
				</div>

				{/* Progress bar */}
				<div>
					<div className="flex items-center justify-between mb-1">
						<span className="text-xs text-gray-500">
							{progress?.achieved ?? 0} / {progress?.target ?? 0}
						</span>
						<span className="text-xs font-medium text-gray-700">
							{progressPercent.toFixed(1)}%
						</span>
					</div>
					<div className="w-full h-2 rounded-full bg-gray-100 overflow-hidden">
						<div
							className="h-full bg-gradient-to-r from-[#E5005F] to-pink-400 rounded-full transition-all duration-500"
							style={{ width: `${progressPercent}%` }}
						/>
					</div>
				</div>

				{/* Action row */}
				<div className="flex items-center gap-2">
					{canClaim && (
						<button
							type="button"
							onClick={() => onClaim(t.id)}
							disabled={isThisClaiming}
							className="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white disabled:opacity-50 disabled:cursor-not-allowed"
						>
							<HandCoins className="w-3.5 h-3.5" />
							{isThisClaiming ? "Claiming..." : "Grab Reward"}
						</button>
					)}
					<button
						type="button"
						onClick={() => setExpanded((v) => !v)}
						className="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 transition-colors"
					>
						{expanded ? "Hide Details" : "View Details"}
						{expanded ? (
							<ChevronUp className="w-3.5 h-3.5" />
						) : (
							<ChevronDown className="w-3.5 h-3.5" />
						)}
					</button>
				</div>
			</div>

			{/* Expanded details */}
			{expanded && (
				<div className="border-t border-gray-100 bg-gray-50/50 p-4 space-y-3">
					{t.description && (
						<p className="text-sm text-gray-600">{t.description}</p>
					)}
					<div className="grid grid-cols-2 md:grid-cols-4 gap-3">
						<InfoBlock icon={Target} label="Type" value={String(t.target_type ?? "-")} capitalize />
						<InfoBlock icon={Trophy} label="Target" value={formatTargetValue(t.target_value, String(t.target_type ?? ""))} />
						<InfoBlock icon={Gift} label="Reward" value={`${String(t.reward_type ?? "-")}${t.reward_value ? ` (${Number(t.reward_value).toLocaleString()})` : ""}`} capitalize />
						<InfoBlock icon={CalendarDays} label="Window" value={`${formatDate(t.start_date)} – ${formatDate(t.end_date)}`} />
					</div>
					<div className="grid grid-cols-3 gap-2 text-sm text-gray-700">
						<p><span className="font-medium">Achieved:</span> {progress?.achieved ?? 0}</p>
						<p><span className="font-medium">Remaining:</span> {progress?.remaining ?? 0}</p>
						<p><span className="font-medium">Completion:</span> {progressPercent.toFixed(2)}%</p>
					</div>
					{completed && !rewardClaimed && (
						<p className="text-sm text-emerald-700 font-medium">
							<CheckCircle2 className="w-4 h-4 inline-block mr-1" />
							Target completed. You can now grab the reward.
						</p>
					)}
					{rewardClaimed && (
						<p className="text-sm text-emerald-700 font-medium">
							Reward has been successfully claimed.
						</p>
					)}
				</div>
			)}
		</div>
	);
}

/* ── Available (not joined) card — simpler, emphasizes "Participate" ── */
function AvailableCard({
	item,
	onParticipate,
	isParticipating,
	participatingId,
}: {
	item: SalesTargetWithMeta;
	onParticipate: (id: number) => void;
	isParticipating: boolean;
	participatingId: number | null;
}) {
	const [expanded, setExpanded] = useState(false);
	const { target: t } = item;
	const isThisParticipating = isParticipating && participatingId === t.id;

	return (
		<div className="rounded-xl border border-dashed border-gray-300 overflow-hidden">
			<div className="flex flex-col sm:flex-row sm:items-center gap-3 p-4">
				{/* Left: title + date + reward teaser */}
				<div className="flex-1 min-w-0">
					<h3 className="text-sm font-semibold text-gray-900 truncate">
						{t.title || "Sales Target"}
					</h3>
					<p className="text-xs text-gray-500 mt-0.5">
						{formatDate(t.start_date)} – {formatDate(t.end_date)}
						{t.reward_type && (
							<>
								{" · "}
								<span className="text-pink-600 font-medium capitalize">
									{t.reward_type}
									{t.reward_value ? ` (${Number(t.reward_value).toLocaleString()})` : ""}
								</span>
							</>
						)}
					</p>
				</div>

				{/* Right: participate + details */}
				<div className="flex items-center gap-2 flex-shrink-0">
					<button
						type="button"
						onClick={() => onParticipate(t.id)}
						disabled={isThisParticipating}
						className="inline-flex items-center gap-1.5 rounded-lg bg-[#E5005F] px-4 py-2 text-xs font-semibold text-white disabled:opacity-50 disabled:cursor-not-allowed"
					>
						<Rocket className="w-3.5 h-3.5" />
						{isThisParticipating ? "Joining..." : "Participate"}
					</button>
					<button
						type="button"
						onClick={() => setExpanded((v) => !v)}
						className="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 transition-colors"
					>
						{expanded ? "Hide" : "Details"}
						{expanded ? (
							<ChevronUp className="w-3.5 h-3.5" />
						) : (
							<ChevronDown className="w-3.5 h-3.5" />
						)}
					</button>
				</div>
			</div>

			{/* Expanded details */}
			{expanded && (
				<div className="border-t border-gray-200 bg-gray-50/50 p-4 space-y-3">
					{t.description && (
						<p className="text-sm text-gray-600">{t.description}</p>
					)}
					<div className="grid grid-cols-2 md:grid-cols-4 gap-3">
						<InfoBlock icon={Target} label="Type" value={String(t.target_type ?? "-")} capitalize />
						<InfoBlock icon={Trophy} label="Target" value={formatTargetValue(t.target_value, String(t.target_type ?? ""))} />
						<InfoBlock icon={Gift} label="Reward" value={`${String(t.reward_type ?? "-")}${t.reward_value ? ` (${Number(t.reward_value).toLocaleString()})` : ""}`} capitalize />
						<InfoBlock icon={CalendarDays} label="Window" value={`${formatDate(t.start_date)} – ${formatDate(t.end_date)}`} />
					</div>
				</div>
			)}
		</div>
	);
}

/* ── Shared info block ── */
function InfoBlock({
	icon: Icon,
	label,
	value,
	capitalize,
}: {
	icon: typeof Target;
	label: string;
	value: string;
	capitalize?: boolean;
}) {
	return (
		<div className="rounded-lg border border-gray-200 bg-white p-3">
			<div className="flex items-center gap-1.5 text-gray-500 text-xs mb-1">
				<Icon className="w-3.5 h-3.5" />
				{label}
			</div>
			<p className={`text-sm text-gray-900 font-semibold ${capitalize ? "capitalize" : ""}`}>
				{value}
			</p>
		</div>
	);
}

/* ── Section header ── */
function SectionHeader({
	icon: Icon,
	title,
	count,
	color,
}: {
	icon: typeof Rocket;
	title: string;
	count: number;
	color: string;
}) {
	return (
		<div className="flex items-center gap-2 mb-3">
			<div
				className={`w-7 h-7 rounded-full flex items-center justify-center ${color}`}
			>
				<Icon className="w-3.5 h-3.5" />
			</div>
			<h2 className="text-sm font-semibold text-gray-900">{title}</h2>
			<span className="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">
				{count}
			</span>
		</div>
	);
}

/* ── Page ── */
export default function DashboardEventPage() {
	const { data, isLoading, isFetching, refetch } =
		useGetAllDashboardDataQuery(undefined, {
			refetchOnMountOrArgChange: true,
		});
	const [participateSalesTarget, { isLoading: isParticipating }] =
		useParticipateSalesTargetMutation();
	const [claimSalesTargetReward, { isLoading: isClaiming }] =
		useClaimSalesTargetRewardMutation();

	const payload = (data?.data ?? null) as DashboardPayload | null;
	const allTargets = payload?.active_sales_targets ?? [];

	const participating = allTargets.filter((i) => i.participation?.joined);
	const available = allTargets.filter((i) => !i.participation?.joined);

	const [participatingId, setParticipatingId] = useState<number | null>(null);
	const [claimingId, setClaimingId] = useState<number | null>(null);

	const handleParticipate = async (targetId: number) => {
		try {
			setParticipatingId(targetId);
			const res = (await participateSalesTarget(targetId).unwrap()) as {
				message?: string;
			};
			toast.success(res?.message || "Challenge participation successful.");
			refetch();
		} catch (error: unknown) {
			toast.error(getApiErrorMessage(error, "Could not join challenge."));
		} finally {
			setParticipatingId(null);
		}
	};

	const handleClaimReward = async (targetId: number) => {
		try {
			setClaimingId(targetId);
			const res = (await claimSalesTargetReward(targetId).unwrap()) as {
				message?: string;
			};
			toast.success(res?.message || "Reward claimed successfully.");
			refetch();
		} catch (error: unknown) {
			toast.error(getApiErrorMessage(error, "Could not claim reward."));
		} finally {
			setClaimingId(null);
		}
	};

	return (
		<main className="flex-1 p-3 sm:p-5 lg:p-6 pb-24">
			<div className="bg-white rounded-xl border border-gray-100 shadow-sm p-4 sm:p-6">
				{/* Header */}
				<div className="flex items-center gap-3 mb-6">
					<div className="w-10 h-10 rounded-full bg-pink-100 text-pink-600 flex items-center justify-center">
						<CalendarDays className="w-5 h-5" />
					</div>
					<div>
						<h1 className="text-lg sm:text-xl font-semibold text-gray-900">
							Event Challenges
						</h1>
						<p className="text-sm text-gray-500">
							Participate, complete the challenges, and grab your rewards.
						</p>
					</div>
				</div>

				{isLoading || isFetching ? (
					<p className="text-sm text-gray-600">Loading event data...</p>
				) : allTargets.length === 0 ? (
					<div className="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-5">
						<p className="text-sm text-gray-600">
							No active event is available right now.
						</p>
					</div>
				) : (
					<div className="space-y-8">
						{/* ── My Challenges ── */}
						{participating.length > 0 && (
							<section>
								<SectionHeader
									icon={Trophy}
									title="My Challenges"
									count={participating.length}
									color="bg-amber-100 text-amber-600"
								/>
								<div className="space-y-3">
									{participating.map((item) => (
										<ParticipatingCard
											key={item.target.id}
											item={item}
											onClaim={handleClaimReward}
											isClaiming={isClaiming}
											claimingId={claimingId}
										/>
									))}
								</div>
							</section>
						)}

						{/* ── Available Challenges ── */}
						{available.length > 0 && (
							<section>
								<SectionHeader
									icon={Rocket}
									title="Available Challenges"
									count={available.length}
									color="bg-pink-100 text-pink-600"
								/>
								<div className="space-y-3">
									{available.map((item) => (
										<AvailableCard
											key={item.target.id}
											item={item}
											onParticipate={handleParticipate}
											isParticipating={isParticipating}
											participatingId={participatingId}
										/>
									))}
								</div>
							</section>
						)}
					</div>
				)}
			</div>
		</main>
	);
}
