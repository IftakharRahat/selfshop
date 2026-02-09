/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { Play } from "lucide-react";
import { useParams } from "next/navigation";
import { useGetSingleCourseQuery } from "@/redux/features/courseApi";

export default function CourseDetailsPage() {
	const params = useParams(); // expects /course/[slug]
	const { slug } = params;

	const { data, isLoading, isError } = useGetSingleCourseQuery(slug);

	if (isLoading) {
		return <div className="m-6">Loading course details...</div>;
	}

	if (isError || !data?.data) {
		return (
			<div className="m-6 text-red-500">Failed to load course details.</div>
		);
	}

	const courseCategory = data.data.coursecategory;
	const courses = data.data.courses || [];

	return (
		<div className="m-4 lg:m-6 md:bg-white rounded-md md:p-6">
			{/* Category Header */}
			<div className="relative overflow-hidden rounded-xl shadow-lg mb-6">
				<div className="relative h-64 bg-gray-800 flex items-center justify-center">
					{courseCategory.coursecategory_image ? (
						<img
							src={courseCategory.coursecategory_image}
							alt={courseCategory.coursecategory_name}
							className="w-full h-full object-cover"
						/>
					) : (
						<div className="w-full h-full bg-gray-600 flex items-center justify-center text-white font-bold text-2xl">
							{courseCategory.coursecategory_name}
						</div>
					)}

					{/* Play Button Overlay */}
					{courseCategory.youtube_embade && (
						<div className="absolute inset-0 flex items-center justify-center">
							<a
								href={`https://www.youtube.com/watch?v=${courseCategory.youtube_embade}`}
								target="_blank"
								rel="noopener noreferrer"
							>
								<div className="bg-red-600 rounded-full p-3 shadow-lg hover:bg-red-700 transition-colors cursor-pointer">
									<Play className="w-6 h-6 text-white fill-white ml-1" />
								</div>
							</a>
						</div>
					)}
				</div>
			</div>

			{/* Course Info */}
			<h2 className="text-2xl font-bold mb-4">
				{courseCategory.coursecategory_name}
			</h2>
			{courses.length === 0 ? (
				<p className="text-gray-600">
					No courses available in this category yet.
				</p>
			) : (
				<div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
					{courses.map((course: any) => (
						<div
							key={course.id}
							className="rounded-md shadow-md overflow-hidden"
						>
							{course.image ? (
								<img
									src={course.image}
									alt={course.title}
									className="w-full h-40 object-cover"
								/>
							) : (
								<div className="w-full h-40 bg-gray-200 flex items-center justify-center text-gray-700 font-semibold">
									{course.title || "No Image"}
								</div>
							)}
							<div className="p-4">
								<h3 className="text-lg font-semibold">{course.title}</h3>
								<p className="text-sm text-gray-600">{course.description}</p>
							</div>
						</div>
					))}
				</div>
			)}
		</div>
	);
}
