interface StarRatingProps {
	rating: number;
	maxRating?: number;
	size?: "sm" | "md" | "lg";
}

export default function StarRating({
	rating,
	maxRating = 5,
	size = "lg",
}: StarRatingProps) {
	const sizeClasses = {
		sm: "text-sm",
		md: "text-base",
		lg: "text-lg",
	};

	return (
		<div className="flex items-center ">
			{Array.from({ length: maxRating }, (_, index) => (
				<span
					key={index}
					className={`${sizeClasses[size]} ${index < rating ? "text-yellow-400" : "text-gray-300"}`}
				>
					★
				</span>
			))}
		</div>
	);
}
