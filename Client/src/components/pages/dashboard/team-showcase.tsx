"use client";

import { Facebook, Instagram, Linkedin, Twitter, Users } from "lucide-react";
import { useGetTeamMembersQuery } from "@/redux/features/dashboardApi";

type TeamMember = {
	id: number;
	name: string;
	email?: string;
	phone?: string;
	my_referral_code?: string;
	profile?: string;
	status?: string;
	created_at?: string;
};

export default function TeamShowcase() {
	const { data, isLoading, isError } = useGetTeamMembersQuery(undefined);
	const members: TeamMember[] = data?.data ?? [];

	if (isLoading) {
		return (
			<div className="m-4 lg:m-6 md:bg-white rounded-md">
				<div className="md:p-6">
					<div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
						{[1, 2, 3].map((i) => (
							<div
								key={i}
								className="bg-white p-8 text-center border-0 shadow-sm animate-pulse"
							>
								<div className="w-32 h-32 mx-auto rounded-full bg-gray-200 mb-6" />
								<div className="h-5 bg-gray-200 rounded w-3/4 mx-auto mb-3" />
								<div className="h-4 bg-gray-200 rounded w-1/2 mx-auto mb-2" />
								<div className="h-3 bg-gray-200 rounded w-2/3 mx-auto" />
							</div>
						))}
					</div>
				</div>
			</div>
		);
	}

	if (isError) {
		return (
			<div className="m-4 lg:m-6 md:bg-white rounded-md">
				<div className="md:p-6 text-center py-16">
					<p className="text-red-500 text-lg">Failed to load team members. Please try again later.</p>
				</div>
			</div>
		);
	}

	if (members.length === 0) {
		return (
			<div className="m-4 lg:m-6 md:bg-white rounded-md">
				<div className="md:p-6 text-center py-16">
					<div className="w-20 h-20 mx-auto rounded-full bg-pink-50 flex items-center justify-center mb-4">
						<Users className="w-10 h-10 text-pink-400" />
					</div>
					<h3 className="text-xl font-semibold text-gray-900 mb-2">No Team Members Yet</h3>
					<p className="text-gray-500 max-w-md mx-auto">
						Share your referral code with others to grow your team. When someone joins using your code, they&apos;ll appear here.
					</p>
				</div>
			</div>
		);
	}

	return (
		<div className="m-4 lg:m-6 md:bg-white rounded-md">
			<div className="md:p-6">
				{/* Summary Header */}
				<div className="flex items-center justify-between mb-6">
					<h2 className="text-lg font-bold text-gray-900">
						My Team Members ({members.length})
					</h2>
				</div>

				<div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
					{members.map((member) => {
						const avatarUrl = member.profile
							? member.profile.startsWith("http")
								? member.profile
								: `${process.env.NEXT_PUBLIC_IMAGE_URL}/${member.profile}`
							: `https://ui-avatars.com/api/?name=${encodeURIComponent(member.name)}&background=fce4ec&color=e91e63&bold=true&size=150`;

						return (
							<div
								key={member.id}
								className="bg-white p-8 text-center border-0 shadow-sm hover:shadow-md transition-shadow duration-300 rounded-lg"
							>
								{/* Profile Image */}
								<div className="mb-6">
									<div className="w-32 h-32 mx-auto rounded-full overflow-hidden bg-gradient-to-br from-pink-100 to-rose-200">
										<img
											src={avatarUrl}
											alt={member.name}
											className="w-full h-full object-cover"
										/>
									</div>
								</div>

								{/* Name and Details */}
								<div className="mb-4">
									<h3 className="text-xl font-semibold text-gray-900 mb-1">
										{member.name}
									</h3>
									{member.my_referral_code && (
										<p className="text-sm text-pink-600 font-medium mb-1">
											{member.my_referral_code}
										</p>
									)}
									{member.created_at && (
										<p className="text-xs text-gray-400">
											Joined: {new Date(member.created_at).toLocaleDateString("en-US", {
												year: "numeric",
												month: "short",
												day: "numeric",
											})}
										</p>
									)}
								</div>

								{/* Status Badge */}
								<div className="mb-4">
									<span
										className={`inline-block px-3 py-1 rounded-full text-xs font-semibold ${member.status === "Active"
												? "bg-green-100 text-green-700"
												: "bg-gray-100 text-gray-600"
											}`}
									>
										{member.status || "Member"}
									</span>
								</div>

								{/* Contact Info */}
								<div className="text-sm text-gray-500 space-y-1">
									{member.phone && <p>📱 {member.phone}</p>}
									{member.email && (
										<p className="truncate" title={member.email}>
											✉️ {member.email}
										</p>
									)}
								</div>
							</div>
						);
					})}
				</div>
			</div>
		</div>
	);
}
