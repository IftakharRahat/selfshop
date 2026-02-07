/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { useGetAllCourseQuery } from "@/redux/features/courseApi";
import {  Play } from "lucide-react";
import Link from "next/link";

export default function ThumbnailGallery() {
  const { data, isLoading, isError } = useGetAllCourseQuery(undefined);

  if (isLoading) {
    return <div className="m-6">Loading courses...</div>;
  }

  if (isError) {
    return <div className="m-6 text-red-500">Failed to load course categories.</div>;
  }

  // ✅ Map API data to gallery items
  const thumbnails =
    data?.data?.map((item: any) => ({
      id: item.id,
      title: item.coursecategory_name,
      subtitle: item.slug,
      image: item.coursecategory_image || "/placeholder.svg",
      youtubeId: item.youtube_embade,
      bgColor: "bg-gray-800", // fallback color
    })) || [];

  return (
    <div className="m-4 lg:m-6 md:bg-white rounded-md">
      <div className="md:p-6">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          {thumbnails.map((thumbnail: any) => (
            <div key={thumbnail.id}>
              <Link href={`/dashboard/free-course/${thumbnail.subtitle}`} className="block">
              <div className="space-y-4">
                {/* Thumbnail Card */}
                <div className="relative overflow-hidden rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-300">
                  <div className={`relative h-48 ${thumbnail.bgColor} flex items-center justify-center`}>
                    <img src={thumbnail.image} alt={thumbnail.title} className="w-full h-full object-cover" />

                    {/* Play Button Overlay */}
                    <div className="absolute inset-0 flex items-center justify-center">
                      <div className="bg-red-600 rounded-full p-3 shadow-lg hover:bg-red-700 transition-colors cursor-pointer">
                        <Play className="w-6 h-6 text-white fill-white ml-1" />
                      </div>
                    </div>

                    {/* Title Overlay */}
                    <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-4">
                      <h3 className="text-white font-bold text-lg leading-tight">{thumbnail.title}</h3>
                      {thumbnail.subtitle && <p className="text-white/90 text-sm font-medium">{thumbnail.subtitle}</p>}
                    </div>
                  </div>
                </div>

                {/* Description */}
                <div className="space-y-2">
                  {/* <h4 className="text-lg font-semibold text-gray-900">
                  Total Courses: {item.totalcourse || 0}
                </h4> */}
                  <p className="text-gray-600 text-sm leading-relaxed">
                    {thumbnail.youtubeId ? `Watch on YouTube: ${thumbnail.youtubeId}` : "No video available"}
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
