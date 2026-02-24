"use client";

import {
	CalendarDays,
	CheckCircle2,
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

interface DashboardPayload {
	active_sales_target?: ActiveSalesTarget | null;
	sales_target_progress?: SalesTargetProgress | null;
	sales_target_participation?: SalesTargetParticipation | null;
}

const formatDate = (raw?: string | null): string => {
	if (!raw) return "Not set";
	const dt = new Date(raw);
	if (Number.isNaN(dt.getTime())) return raw;
	return dt.toLocaleDateString();
};

const formatTargetValue = (value: number | string | undefined, type: string): string => {
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

export default function DashboardEventPage() {
	const {
		data,
		isLoading,
		isFetching,
		refetch,
	} = useGetAllDashboardDataQuery(undefined, {
		refetchOnMountOrArgChange: true,
	});
	const [participateSalesTarget, { isLoading: isParticipating }] =
		useParticipateSalesTargetMutation();
	const [claimSalesTargetReward, { isLoading: isClaiming }] =
		useClaimSalesTargetRewardMutation();

	const payload = (data?.data ?? null) as DashboardPayload | null;
	const activeTarget = payload?.active_sales_target ?? null;
	const progress = payload?.sales_target_progress ?? null;
	const participation = payload?.sales_target_participation ?? null;

	const joined = Boolean(participation?.joined);
	const rewardClaimed = Boolean(participation?.reward_claimed);
	const completed = Boolean(progress?.completed);
	const progressPercent = Math.max(
		0,
		Math.min(100, Number(progress?.progress_percent ?? 0)),
	);

	const canParticipate = Boolean(activeTarget) && !joined;
	const canClaim = Boolean(activeTarget) && joined && completed && !rewardClaimed;

	const handleParticipate = async () => {
		try {
			const res = (await participateSalesTarget(undefined).unwrap()) as {
				message?: string;
			};
			toast.success(res?.message || "Challenge participation successful.");
			refetch();
		} catch (error: unknown) {
			toast.error(getApiErrorMessage(error, "Could not join challenge."));
		}
	};

	const handleClaimReward = async () => {
		try {
			const res = (await claimSalesTargetReward(undefined).unwrap()) as {
				message?: string;
			};
			toast.success(res?.message || "Reward claimed successfully.");
			refetch();
		} catch (error: unknown) {
			toast.error(getApiErrorMessage(error, "Could not claim reward."));
		}
	};

	return (
		<main className="flex-1 p-3 sm:p-5 lg:p-6 pb-24">
			<div className="bg-white rounded-xl border border-gray-100 shadow-sm p-4 sm:p-6">
				<div className="flex flex-wrap items-start justify-between gap-3 mb-4">
					<div className="flex items-center gap-3">
						<div className="w-10 h-10 rounded-full bg-pink-100 text-pink-600 flex items-center justify-center">
							<CalendarDays className="w-5 h-5" />
						</div>
						<div>
							<h1 className="text-lg sm:text-xl font-semibold text-gray-900">
								Event Challenge
							</h1>
							<p className="text-sm text-gray-500">
								Participate, complete the challenge, and grab your reward.
							</p>
						</div>
					</div>

					<div className="flex flex-wrap items-center gap-2">
						<span
							className={`text-xs font-semibold px-2.5 py-1 rounded-full ${
								rewardClaimed
									? "bg-emerald-100 text-emerald-700"
									: completed
										? "bg-blue-100 text-blue-700"
										: joined
											? "bg-amber-100 text-amber-700"
											: "bg-gray-100 text-gray-600"
							}`}
						>
							{rewardClaimed
								? "Reward Grabbed"
								: completed
									? "Challenge Completed"
									: joined
										? "Participating"
										: "Not Participating"}
						</span>
					</div>
				</div>

				{isLoading || isFetching ? (
					<p className="text-sm text-gray-600">Loading event data...</p>
				) : activeTarget ? (
					<div className="space-y-4">
						<div className="rounded-lg border border-gray-200 p-4">
							<h2 className="text-base font-semibold text-gray-900">
								{activeTarget.title || "Sales Target Challenge"}
							</h2>
							{activeTarget.description ? (
								<p className="text-sm text-gray-600 mt-1">
									{activeTarget.description}
								</p>
							) : null}
						</div>

						<div className="grid grid-cols-1 md:grid-cols-4 gap-3">
							<div className="rounded-lg border border-gray-200 p-4">
								<div className="flex items-center gap-2 text-gray-500 text-sm mb-1">
									<Target className="w-4 h-4" />
									Target Type
								</div>
								<p className="text-gray-900 font-semibold capitalize">
									{String(activeTarget?.target_type ?? "-")}
								</p>
							</div>
							<div className="rounded-lg border border-gray-200 p-4">
								<div className="flex items-center gap-2 text-gray-500 text-sm mb-1">
									<Trophy className="w-4 h-4" />
									Target Value
								</div>
								<p className="text-gray-900 font-semibold">
									{formatTargetValue(
										activeTarget?.target_value,
										String(activeTarget?.target_type ?? ""),
									)}
								</p>
							</div>
							<div className="rounded-lg border border-gray-200 p-4">
								<div className="flex items-center gap-2 text-gray-500 text-sm mb-1">
									<Gift className="w-4 h-4" />
									Reward
								</div>
								<p className="text-gray-900 font-semibold capitalize">
									{String(activeTarget?.reward_type ?? "-")}
									{activeTarget?.reward_value
										? ` (${Number(activeTarget.reward_value).toLocaleString()})`
										: ""}
								</p>
							</div>
							<div className="rounded-lg border border-gray-200 p-4">
								<div className="flex items-center gap-2 text-gray-500 text-sm mb-1">
									<CalendarDays className="w-4 h-4" />
									Challenge Window
								</div>
								<p className="text-gray-900 font-semibold text-sm">
									{formatDate(activeTarget?.start_date)} -{" "}
									{formatDate(activeTarget?.end_date)}
								</p>
							</div>
						</div>

						<div className="rounded-lg border border-gray-200 p-4">
							<div className="flex items-center justify-between gap-3 mb-2">
								<h2 className="text-sm font-semibold text-gray-900">Progress</h2>
								<span className="text-sm font-medium text-gray-700">
									{progressPercent.toFixed(2)}%
								</span>
							</div>
							<div className="w-full h-2.5 rounded-full bg-gray-100 overflow-hidden">
								<div
									className="h-full bg-gradient-to-r from-[#E5005F] to-pink-400"
									style={{ width: `${progressPercent}%` }}
								/>
							</div>
							<div className="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-2 text-sm text-gray-700">
								<p>
									<span className="font-medium">Achieved:</span>{" "}
									{progress?.achieved ?? 0}
								</p>
								<p>
									<span className="font-medium">Remaining:</span>{" "}
									{progress?.remaining ?? 0}
								</p>
								<p>
									<span className="font-medium">Completion:</span>{" "}
									{progressPercent.toFixed(2)}%
								</p>
							</div>
						</div>

						<div className="rounded-lg border border-gray-200 p-4">
							<h2 className="text-sm font-semibold text-gray-900 mb-3">
								Challenge Actions
							</h2>
							<div className="flex flex-wrap gap-2">
								<button
									type="button"
									onClick={handleParticipate}
									disabled={!canParticipate || isParticipating}
									className="inline-flex items-center gap-2 rounded-lg bg-[#E5005F] px-4 py-2 text-sm font-semibold text-white disabled:opacity-50 disabled:cursor-not-allowed"
								>
									<Rocket className="w-4 h-4" />
									{joined
										? "Joined Challenge"
										: isParticipating
											? "Joining..."
											: "Participate Now"}
								</button>

								<button
									type="button"
									onClick={handleClaimReward}
									disabled={!canClaim || isClaiming}
									className="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50 disabled:cursor-not-allowed"
								>
									<HandCoins className="w-4 h-4" />
									{rewardClaimed
										? "Reward Claimed"
										: isClaiming
											? "Claiming..."
											: "Grab Reward"}
								</button>
							</div>

							<div className="mt-3 text-sm text-gray-600 space-y-1">
								{!joined ? (
									<p>Join the challenge first to start participation.</p>
								) : null}
								{joined && !completed ? (
									<p>Complete the target to unlock reward claim.</p>
								) : null}
								{joined && completed && !rewardClaimed ? (
									<p className="text-emerald-700 font-medium">
										<CheckCircle2 className="w-4 h-4 inline-block mr-1" />
										Target completed. You can now grab the reward.
									</p>
								) : null}
								{rewardClaimed ? (
									<p className="text-emerald-700 font-medium">
										Reward has been successfully claimed.
									</p>
								) : null}
							</div>
						</div>
					</div>
				) : (
					<div className="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-5">
						<p className="text-sm text-gray-600">
							No active event is available right now.
						</p>
					</div>
				)}
			</div>
		</main>
	);
}
