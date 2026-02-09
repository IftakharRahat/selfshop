/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { useState } from "react";
import { Mousewheel, Scrollbar } from "swiper/modules";
import { Swiper, SwiperSlide } from "swiper/react";

import "swiper/css";
import "swiper/css/scrollbar";

import Image from "next/image";
import { useRouter } from "next/navigation";
import { getImageUrl } from "@/lib/utils";
import { useGetAllNavbarCategoryDropdownOptionsQuery } from "@/redux/features/home/homeApi";

export default function CategoriesPageComponent() {
	const router = useRouter();

	const { data: menuOptions } =
		useGetAllNavbarCategoryDropdownOptionsQuery(undefined);

	const categories =
		menuOptions?.data?.map((menu: any) => ({
			id: menu?.id,
			name: menu?.category_name,
			icon: menu?.category_icon,
			slug: menu?.slug,
			subcategories: menu?.subcategories,
		})) || [];

	const [selectedCategory, setSelectedCategory] = useState<any>(
		categories[0] || null,
	);

	return (
		<div className="flex gap-1  py-6">
			{/* LEFT SIDE — ALL CATEGORIES LIST */}
			<div className="w-20 h-screen">
				<Swiper
					direction="vertical"
					slidesPerView="auto"
					modules={[Scrollbar, Mousewheel]}
					scrollbar={{ draggable: true }}
					mousewheel={{ forceToAxis: true }}
					className="h-full"
				>
					{categories.map((cat: any) => (
						<SwiperSlide key={cat.id} className="!w-full !h-auto">
							<div
								onClick={() => setSelectedCategory(cat)}
								className={` gap-2 p-3 cursor-pointer rounded-md transition-all
                  ${
										selectedCategory?.id === cat.id
											? "bg-green-100 text-green-700"
											: "hover:bg-gray-100"
									}
                `}
							>
								<Image
									src={getImageUrl(cat?.icon)}
									alt={cat?.name || "Category"}
									width={40}
									height={40}
									className="w-8 h-8 mx-auto"
								/>
								<span className="text-sm font-medium truncate">
									{cat.name.length > 5
										? cat.name.slice(0, 5) + "..."
										: cat.name}
								</span>
							</div>
						</SwiperSlide>
					))}
				</Swiper>
			</div>

			{/* RIGHT SIDE — SELECTED CATEGORY DETAILS */}
			<div className="flex-1">
				<h2 className="text-xl font-semibold mb-4 text-wrap">
					{selectedCategory?.name}
				</h2>

				{/* {selectedCategory?.subcategories?.length > 0 && (
          <select
            className="border border-gray-300 rounded-lg px-3 py-2 mb-6"
            onChange={(e) =>
              router.push(`/product-filter?subcategory=${e.target.value}`)
            }
          >
            <option value="">Select Subcategory</option>
            {selectedCategory.subcategories.map((sub: any) => (
              <option key={sub.id} value={sub.slug}>
                {sub.sub_category_name}
              </option>
            ))}
          </select>
        )} */}

				<div className="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-3">
					{selectedCategory?.subcategories?.map((sub: any) => (
						<div
							key={sub.id}
							onClick={() =>
								router.push(
									`/product-filter?category=${sub.slug}&subcategory=${sub.slug}`,
								)
							}
							className="flex flex-col items-center text-center cursor-pointer group"
						>
							<div className="w-16 h-16 mb-2 rounded-full bg-gray-100 flex items-center justify-center group-hover:bg-gray-200 transition">
								<Image
									src={getImageUrl(sub.subcategory_icon)}
									width={40}
									height={40}
									alt={sub?.sub_category_name || "Subcategory"}
									className="w-10 h-10"
								/>
							</div>

							<span className="text-xs font-medium group-hover:text-green-600">
								{sub.sub_category_name}
							</span>
						</div>
					))}
				</div>
			</div>
		</div>
	);
}
