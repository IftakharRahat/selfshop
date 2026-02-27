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
	Sparkles,
	Star,
	Target,
	Trophy,
	Zap,
} from "lucide-react";
import { toast } from "sonner";
import {
	useClaimSalesTargetRewardMutation,
	useGetAllDashboardDataQuery,
	useParticipateSalesTargetMutation,
} from "@/redux/features/dashboardApi";

/* ── Types ── */
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

/* ── Helpers ── */
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
		return `Tk ${parsed.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
	}
	return `${parsed.toLocaleString(undefined, { maximumFractionDigits: 0 })} Qty`;
};

const getApiErrorMessage = (error: unknown, fallback: string): string => {
	const maybe = error as { data?: { message?: string }; message?: string };
	return maybe?.data?.message || maybe?.message || fallback;
};

/* ── Inline keyframes (injected once) ── */
const AnimationStyles = () => (
	<style jsx global>{`
		@keyframes shimmer {
			0% { background-position: -200% 0; }
			100% { background-position: 200% 0; }
		}
		@keyframes pulse-glow {
			0%, 100% { box-shadow: 0 0 8px rgba(229, 0, 95, 0.3); }
			50% { box-shadow: 0 0 20px rgba(229, 0, 95, 0.6); }
		}
		@keyframes pulse-glow-green {
			0%, 100% { box-shadow: 0 0 8px rgba(16, 185, 129, 0.3); }
			50% { box-shadow: 0 0 20px rgba(16, 185, 129, 0.6); }
		}
		@keyframes float {
			0%, 100% { transform: translateY(0); }
			50% { transform: translateY(-4px); }
		}
		@keyframes gradient-shift {
			0% { background-position: 0% 50%; }
			50% { background-position: 100% 50%; }
			100% { background-position: 0% 50%; }
		}
		@keyframes progress-glow {
			0%, 100% { filter: brightness(1); }
			50% { filter: brightness(1.3); }
		}
		@keyframes celebrate {
			0% { transform: scale(1); }
			50% { transform: scale(1.05); }
			100% { transform: scale(1); }
		}
		.animate-shimmer {
			background: linear-gradient(90deg, transparent 25%, rgba(255,255,255,0.4) 50%, transparent 75%);
			background-size: 200% 100%;
			animation: shimmer 2s infinite;
		}
		.animate-pulse-glow { animation: pulse-glow 2s ease-in-out infinite; }
		.animate-pulse-glow-green { animation: pulse-glow-green 2s ease-in-out infinite; }
		.animate-float { animation: float 3s ease-in-out infinite; }
		.animate-gradient { 
			background-size: 200% 200%;
			animation: gradient-shift 4s ease infinite; 
		}
		.animate-progress-glow { animation: progress-glow 2s ease-in-out infinite; }
		.animate-celebrate { animation: celebrate 0.6s ease-in-out; }
	`}</style>
);

/* ── Participating card ── */
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
	const progressPercent = Math.max(0, Math.min(100, Number(progress?.progress_percent ?? 0)));
	const canClaim = completed && !rewardClaimed;
	const isThisClaiming = isClaiming && claimingId === t.id;

	return (
		<div
			className={`rounded-2xl overflow-hidden transition-all duration-300 hover:shadow-lg ${rewardClaimed
				? "bg-gradient-to-r from-emerald-50 to-teal-50 border-2 border-emerald-200"
				: completed
					? "bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-300"
					: "bg-gradient-to-r from-pink-50/50 to-orange-50/50 border-2 border-pink-200"
				}`}
		>
			<div className="p-4 sm:p-5 space-y-3">
				{/* Top row */}
				<div className="flex items-start justify-between gap-3">
					<div className="flex items-center gap-3 flex-1 min-w-0">
						<div
							className={`w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 ${rewardClaimed
								? "bg-emerald-500 text-white"
								: completed
									? "bg-blue-500 text-white animate-celebrate"
									: "bg-gradient-to-br from-[#E5005F] to-pink-400 text-white"
								}`}
						>
							{rewardClaimed ? (
								<CheckCircle2 className="w-5 h-5" />
							) : completed ? (
								<Trophy className="w-5 h-5" />
							) : (
								<Zap className="w-5 h-5" />
							)}
						</div>
						<div className="min-w-0">
							<h3 className="text-sm font-bold text-gray-900 truncate">
								{t.title || "Sales Target"}
							</h3>
							<p className="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
								<CalendarDays className="w-3 h-3" />
								{formatDate(t.start_date)} – {formatDate(t.end_date)}
							</p>
						</div>
					</div>
					<div className="flex items-center gap-2 flex-shrink-0">
						{rewardClaimed ? (
							<span className="text-xs font-bold px-3 py-1.5 rounded-full bg-emerald-500 text-white flex items-center gap-1">
								<Star className="w-3 h-3" /> Claimed
							</span>
						) : completed ? (
							<span className="text-xs font-bold px-3 py-1.5 rounded-full bg-blue-500 text-white flex items-center gap-1">
								<Trophy className="w-3 h-3" /> Completed!
							</span>
						) : (
							<span className="text-xs font-bold px-3 py-1.5 rounded-full bg-gradient-to-r from-amber-400 to-orange-400 text-white">
								In Progress
							</span>
						)}
					</div>
				</div>

				{/* Progress bar */}
				<div>
					<div className="flex items-center justify-between mb-1.5">
						<span className="text-xs font-medium text-gray-600">
							{formatTargetValue(progress?.achieved, String(t.target_type ?? ""))} / {formatTargetValue(progress?.target, String(t.target_type ?? ""))}
						</span>
						<span className="text-xs font-bold text-gray-800">
							{progressPercent.toFixed(1)}%
						</span>
					</div>
					<div className="w-full h-3 rounded-full bg-white/80 overflow-hidden shadow-inner">
						<div
							className={`h-full rounded-full transition-all duration-1000 ease-out ${completed
								? "bg-gradient-to-r from-emerald-400 to-emerald-500"
								: "bg-gradient-to-r from-[#E5005F] via-pink-400 to-orange-400 animate-progress-glow"
								}`}
							style={{ width: `${progressPercent}%` }}
						/>
					</div>
				</div>

				{/* Action row */}
				<div className="flex items-center gap-2 flex-wrap">
					{canClaim && (
						<button
							type="button"
							onClick={() => onClaim(t.id)}
							disabled={isThisClaiming}
							className="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 px-4 py-2 text-xs font-bold text-white disabled:opacity-50 disabled:cursor-not-allowed animate-pulse-glow-green hover:scale-105 transition-transform"
						>
							<HandCoins className="w-4 h-4" />
							{isThisClaiming ? "Claiming..." : "🎉 Grab Reward"}
						</button>
					)}
					<button
						type="button"
						onClick={() => setExpanded((v) => !v)}
						className="inline-flex items-center gap-1 rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 transition-all"
					>
						{expanded ? "Hide Details" : "View Details"}
						{expanded ? <ChevronUp className="w-3.5 h-3.5" /> : <ChevronDown className="w-3.5 h-3.5" />}
					</button>
				</div>
			</div>

			{/* Expanded details */}
			{expanded && (
				<div className="border-t border-gray-200/50 bg-white/60 backdrop-blur-sm p-4 sm:p-5 space-y-3">
					{t.description && <p className="text-sm text-gray-600">{t.description}</p>}
					<div className="grid grid-cols-2 md:grid-cols-4 gap-3">
						<InfoBlock icon={Target} label="Type" value={String(t.target_type ?? "-")} capitalize />
						<InfoBlock icon={Trophy} label="Target" value={formatTargetValue(t.target_value, String(t.target_type ?? ""))} />
						<InfoBlock icon={Gift} label="Reward" value={`${String(t.reward_type ?? "-")}${t.reward_value ? ` (${Number(t.reward_value).toLocaleString()})` : ""}`} capitalize />
						<InfoBlock icon={CalendarDays} label="Window" value={`${formatDate(t.start_date)} – ${formatDate(t.end_date)}`} />
					</div>
					<div className="grid grid-cols-3 gap-2 text-sm text-gray-700">
						<p><span className="font-medium">Achieved:</span> {formatTargetValue(progress?.achieved, String(t.target_type ?? ""))}</p>
						<p><span className="font-medium">Remaining:</span> {formatTargetValue(progress?.remaining, String(t.target_type ?? ""))}</p>
						<p><span className="font-medium">Completion:</span> {progressPercent.toFixed(2)}%</p>
					</div>
					{completed && !rewardClaimed && (
						<p className="text-sm text-emerald-700 font-bold flex items-center gap-1">
							<CheckCircle2 className="w-4 h-4" /> Target completed! Grab your reward now.
						</p>
					)}
					{rewardClaimed && (
						<p className="text-sm text-emerald-700 font-medium">✅ Reward successfully claimed.</p>
					)}
				</div>
			)}
		</div>
	);
}

/* ── Available card — eye-catching to drive participation ── */
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
		<div className="relative rounded-2xl overflow-hidden group">
			{/* Gradient border effect */}
			<div className="absolute inset-0 bg-gradient-to-r from-[#E5005F] via-purple-500 to-pink-400 rounded-2xl animate-gradient" />
			<div className="relative m-[2px] bg-white rounded-[14px] overflow-hidden">
				{/* Top banner */}
				<div className="bg-gradient-to-r from-[#E5005F] via-pink-500 to-purple-600 px-4 sm:px-5 py-2.5 flex items-center justify-between animate-gradient">
					<div className="flex items-center gap-2">
						<Sparkles className="w-4 h-4 text-yellow-300 animate-float" />
						<span className="text-xs font-bold text-white uppercase tracking-wider">
							New Challenge
						</span>
					</div>
					{t.reward_type && (
						<span className="text-xs font-bold text-yellow-200 flex items-center gap-1">
							<Gift className="w-3.5 h-3.5" />
							{String(t.reward_type).charAt(0).toUpperCase() + String(t.reward_type).slice(1)}
							{t.reward_value ? ` worth ${Number(t.reward_value).toLocaleString()}` : ""}
						</span>
					)}
				</div>

				{/* Content */}
				<div className="p-4 sm:p-5">
					<div className="flex flex-col sm:flex-row sm:items-center gap-3">
						<div className="flex items-center gap-3 flex-1 min-w-0">
							<div className="w-10 h-10 rounded-xl bg-gradient-to-br from-[#E5005F] to-purple-600 text-white flex items-center justify-center flex-shrink-0 animate-float">
								<Rocket className="w-5 h-5" />
							</div>
							<div className="min-w-0">
								<h3 className="text-sm font-bold text-gray-900 truncate">
									{t.title || "Sales Target"}
								</h3>
								<p className="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
									<CalendarDays className="w-3 h-3" />
									{formatDate(t.start_date)} – {formatDate(t.end_date)}
								</p>
							</div>
						</div>

						<div className="flex items-center gap-2 flex-shrink-0">
							<button
								type="button"
								onClick={() => onParticipate(t.id)}
								disabled={isThisParticipating}
								className="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-[#E5005F] to-purple-600 px-5 py-2.5 text-xs font-bold text-white disabled:opacity-50 disabled:cursor-not-allowed animate-pulse-glow hover:scale-105 transition-transform shadow-lg"
							>
								<Rocket className="w-4 h-4" />
								{isThisParticipating ? "Joining..." : "🚀 Join Now"}
							</button>
							<button
								type="button"
								onClick={() => setExpanded((v) => !v)}
								className="inline-flex items-center gap-1 rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-xs font-medium text-gray-700 hover:bg-gray-50 transition-all"
							>
								{expanded ? "Hide" : "Details"}
								{expanded ? <ChevronUp className="w-3.5 h-3.5" /> : <ChevronDown className="w-3.5 h-3.5" />}
							</button>
						</div>
					</div>
				</div>

				{/* Expanded details */}
				{expanded && (
					<div className="border-t border-gray-100 bg-gray-50/50 p-4 sm:p-5 space-y-3">
						{t.description && <p className="text-sm text-gray-600">{t.description}</p>}
						<div className="grid grid-cols-2 md:grid-cols-4 gap-3">
							<InfoBlock icon={Target} label="Type" value={String(t.target_type ?? "-")} capitalize />
							<InfoBlock icon={Trophy} label="Target" value={formatTargetValue(t.target_value, String(t.target_type ?? ""))} />
							<InfoBlock icon={Gift} label="Reward" value={`${String(t.reward_type ?? "-")}${t.reward_value ? ` (${Number(t.reward_value).toLocaleString()})` : ""}`} capitalize />
							<InfoBlock icon={CalendarDays} label="Window" value={`${formatDate(t.start_date)} – ${formatDate(t.end_date)}`} />
						</div>
					</div>
				)}
			</div>
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
		<div className="rounded-xl border border-gray-200 bg-white p-3">
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
	gradient,
}: {
	icon: typeof Rocket;
	title: string;
	count: number;
	gradient: string;
}) {
	return (
		<div className="flex items-center gap-3 mb-4">
			<div className={`w-8 h-8 rounded-xl flex items-center justify-center text-white ${gradient}`}>
				<Icon className="w-4 h-4" />
			</div>
			<h2 className="text-base font-bold text-gray-900">{title}</h2>
			<span className="text-xs font-bold text-white bg-gradient-to-r from-[#E5005F] to-pink-400 px-2.5 py-0.5 rounded-full">
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

	console.log(payload)

	const participating = allTargets.filter((i) => i.participation?.joined);
	const available = allTargets.filter((i) => !i.participation?.joined);

	const [participatingId, setParticipatingId] = useState<number | null>(null);
	const [claimingId, setClaimingId] = useState<number | null>(null);

	const handleParticipate = async (targetId: number) => {
		try {
			setParticipatingId(targetId);
			const res = (await participateSalesTarget(targetId).unwrap()) as { message?: string };
			console.log(res)
			toast.success(res?.message || "Challenge participation successful.");
			refetch();
		} catch (error: unknown) {
			console.log(error)
			toast.error(getApiErrorMessage(error, "Could not join challenge."));
		} finally {
			setParticipatingId(null);
		}
	};

	const handleClaimReward = async (targetId: number) => {
		try {
			setClaimingId(targetId);
			const res = (await claimSalesTargetReward(targetId).unwrap()) as { message?: string };
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
			<AnimationStyles />

			{/* Header */}
			<div className="bg-white rounded-xl border border-gray-100 shadow-sm p-4 sm:p-5 mb-6">
				<div className="flex items-center gap-3">
					<div className="w-10 h-10 rounded-full bg-pink-100 text-[#E5005F] flex items-center justify-center">
						<Trophy className="w-5 h-5" />
					</div>
					<div>
						<h1 className="text-lg font-semibold text-gray-900">
							Event Challenges
						</h1>
						<p className="text-sm text-gray-500">
							Participate in challenges and earn rewards
						</p>
					</div>
				</div>
			</div>

			{/* Content */}
			<div className="space-y-8">
				{isLoading || isFetching ? (
					<div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center">
						<div className="w-10 h-10 border-4 border-pink-200 border-t-[#E5005F] rounded-full animate-spin mx-auto mb-3" />
						<p className="text-sm text-gray-600">Loading challenges...</p>
					</div>
				) : allTargets.length === 0 ? (
					<div className="bg-white rounded-2xl border border-dashed border-gray-300 p-8 text-center">
						<div className="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
							<CalendarDays className="w-6 h-6 text-gray-400" />
						</div>
						<p className="text-sm text-gray-600 font-medium">
							No active challenges right now
						</p>
						<p className="text-xs text-gray-400 mt-1">
							Check back soon for new exciting challenges!
						</p>
					</div>
				) : (
					<>
						{/* ── My Challenges ── */}
						{participating.length > 0 && (
							<section>
								<SectionHeader
									icon={Trophy}
									title="My Challenges"
									count={participating.length}
									gradient="bg-gradient-to-br from-amber-400 to-orange-500"
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
									gradient="bg-gradient-to-br from-[#E5005F] to-purple-600"
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
					</>
				)}
			</div>
		</main>
	);
}
