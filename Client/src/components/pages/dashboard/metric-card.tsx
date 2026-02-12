import { TrendingDown, TrendingUp } from "lucide-react";
import Image, { type StaticImageData } from "next/image";
import React from "react";

interface MetricCardProps {
	title: string;
	value: string;
	change?: string;
	changeType?: "positive" | "negative";
	subtitle?: string;
	icon: string | React.ReactNode | StaticImageData;
	iconColor: string;
}

export default function MetricCard({
	title,
	value,
	change,
	changeType,
	subtitle,
	icon,
}: MetricCardProps) {
	return (
		<div className="relative overflow-hidden bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-200 group">
			<div className="p-3 sm:p-5">
				<div className="flex items-center gap-2 sm:gap-3 mb-2 sm:mb-3">
					<div className="w-8 h-8 sm:w-10 sm:h-10 rounded-lg bg-pink-50 flex items-center justify-center flex-shrink-0">
						{typeof icon === "string" ? (
							<span className="text-xs sm:text-sm">{icon}</span>
						) : typeof icon === "object" && icon !== null && "src" in icon ? (
							<Image
								src={icon.src}
								alt={title}
								width={22}
								height={22}
								className="w-4 h-4 sm:w-[22px] sm:h-[22px] object-contain"
							/>
						) : React.isValidElement(icon) ? (
							icon
						) : null}
					</div>
					<h3 className="text-xs sm:text-sm font-medium text-gray-500 leading-tight">{title}</h3>
				</div>

				<p className="text-lg sm:text-2xl font-bold text-gray-900">{value}</p>

				{change && (
					<div className="flex items-center gap-1 mt-2">
						{changeType === "positive" ? (
							<div className="flex items-center gap-1 px-2 py-0.5 bg-green-50 rounded-full">
								<TrendingUp className="w-3 h-3 text-green-600" />
								<span className="text-xs font-medium text-green-600">
									{change}
								</span>
							</div>
						) : (
							<div className="flex items-center gap-1 px-2 py-0.5 bg-red-50 rounded-full">
								<TrendingDown className="w-3 h-3 text-red-600" />
								<span className="text-xs font-medium text-red-600">
									{change}
								</span>
							</div>
						)}
					</div>
				)}

				{subtitle && <p className="text-xs text-gray-400 mt-2">{subtitle}</p>}
			</div>
		</div>
	);
}
