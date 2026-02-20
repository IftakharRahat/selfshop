/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { BookOpen, Play } from "lucide-react";
import Link from "next/link";
import { useGetAllCourseQuery } from "@/redux/features/courseApi";

export default function ThumbnailGallery() {
	const { data, isLoading, isError } = useGetAllCourseQuery(undefined);

	if (isLoading) {
		return (
			<div className="m-4 lg:m-6 md:bg-white rounded-md">
				<div className="md:p-6">
					<div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
						{[1, 2, 3].map((i) => (
							<div key={i} className="animate-pulse space-y-3">
								<div className="rounded-xl bg-gray-200 h-48" />
								<div className="h-4 bg-gray-200 rounded w-3/4" />
								<div className="h-3 bg-gray-200 rounded w-1/2" />
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
					<p className="text-red-500 text-lg">Failed to load courses. Please try again later.</p>
				</div>
			</div>
		);
	}

	const thumbnails =
		data?.data?.map((item: any) => ({
			id: item.id,
			title: item.coursecategory_name,
			slug: item.slug,
			image: item.coursecategory_image
				? item.coursecategory_image.startsWith("http")
					? item.coursecategory_image
					: `${process.env.NEXT_PUBLIC_IMAGE_URL}/${item.coursecategory_image}`
				: null,
			youtubeId: item.youtube_embade,
			totalCourses: item.totalcourse || 0,
		})) || [];

	if (thumbnails.length === 0) {
		return (
			<div className="m-4 lg:m-6 md:bg-white rounded-md">
				<div className="md:p-6 text-center py-16">
					<div className="w-20 h-20 mx-auto rounded-full bg-pink-50 flex items-center justify-center mb-4">
						<BookOpen className="w-10 h-10 text-pink-400" />
					</div>
					<h3 className="text-xl font-semibold text-gray-900 mb-2">No Courses Available</h3>
					<p className="text-gray-500 max-w-md mx-auto">
						Free courses will appear here once they are published. Check back soon!
					</p>
				</div>
			</div>
		);
	}

	return (
		<div className="m-4 lg:m-6 md:bg-white rounded-md">
			<div className="md:p-6">
				{/* Header */}
				<div className="flex items-center justify-between mb-6">
					<h2 className="text-lg font-bold text-gray-900">
						Free Courses ({thumbnails.length})
					</h2>
				</div>

				<div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
					{thumbnails.map((thumbnail: any) => (
						<div key={thumbnail.id}>
							<Link
								href={`/dashboard/free-course/${thumbnail.slug}`}
								className="block group"
							>
								<div className="space-y-3">
									{/* Thumbnail Card */}
									<div className="relative overflow-hidden rounded-xl shadow-lg group-hover:shadow-xl transition-shadow duration-300">
										<div className="relative h-48 bg-gray-800 flex items-center justify-center">
											{thumbnail.image ? (
												<img
													src={thumbnail.image}
													alt={thumbnail.title}
													className="w-full h-full object-cover"
													onError={(e) => {
														(e.target as HTMLImageElement).style.display = "none";
													}}
												/>
											) : (
												<div className="w-full h-full bg-gradient-to-br from-pink-500 to-rose-600 flex items-center justify-center">
													<BookOpen className="w-12 h-12 text-white/70" />
												</div>
											)}

											{/* Play Button Overlay */}
											{thumbnail.youtubeId && (
												<div className="absolute inset-0 flex items-center justify-center">
													<div className="bg-red-600 rounded-full p-3 shadow-lg group-hover:bg-red-700 group-hover:scale-110 transition-all cursor-pointer">
														<Play className="w-6 h-6 text-white fill-white ml-0.5" />
													</div>
												</div>
											)}

											{/* Title Overlay */}
											<div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-4">
												<h3 className="text-white font-bold text-lg leading-tight">
													{thumbnail.title}
												</h3>
												<p className="text-white/80 text-xs mt-1">
													{thumbnail.totalCourses}{" "}
													{thumbnail.totalCourses === 1 ? "course" : "courses"}
												</p>
											</div>
										</div>
									</div>

									{/* Info */}
									<div className="space-y-1 px-1">
										<p className="text-gray-600 text-sm leading-relaxed">
											{thumbnail.youtubeId
												? "📺 Video available — click to watch"
												: "📖 Click to view course content"}
										</p>
									</div>
								</div>
							</Link>
						</div>
					))}
				</div>
			</div>
		</div>
	);
}
