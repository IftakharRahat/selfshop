
import { TrendingUp, TrendingDown } from "lucide-react"
import Image, { StaticImageData } from "next/image"
import React from "react"

interface MetricCardProps {
  title: string
  value: string
  change?: string
  changeType?: "positive" | "negative"
  subtitle?: string
  icon: string | React.ReactNode | StaticImageData
  iconColor: string
}

export default function MetricCard({ title, value, change, changeType, subtitle, icon, }: MetricCardProps) {
  return (
    <div className="relative overflow-hidden bg-white rounded-lg shadow-sm  hover:shadow-md transition-shadow duration-200">
      <div className="p-4 sm:p-6">
        <div className="flex items-start justify-between mb-3 sm:mb-4">
          <h3 className="text-xs sm:text-sm font-medium text-gray-600">{title}</h3>
          <div className={` rounded-full flex items-center justify-center bg-[#E5005F14] p-2`}>
            <span className="text-white font-bold text-xs sm:text-sm   bg-white w-6 h-6 sm:w-8 sm:h-8  rounded-full">
              {typeof icon === "string" ? (
                icon
              ) : typeof icon === "object" && icon !== null && "src" in icon ? (
                // StaticImageData
                <Image
                  src={icon.src}
                  alt={title}
                  width={32}
                  height={32}
                  className="w-full h-full object-cover"
                />
              ) : React.isValidElement(icon) ? (
                icon
              ) : null}
              ৳
            </span>
          </div>
        </div>

        <div className="mb-2">
          <p className="text-xl sm:text-2xl font-bold text-gray-900">{value}</p>
        </div>

        {change && (
          <div className="flex items-center gap-1">
            {changeType === "positive" ? (
              <div className="flex items-center gap-1 px-2 py-1 bg-green-100 rounded-full">
                <TrendingUp className="w-3 h-3 text-green-600" />
                <span className="text-xs font-medium text-green-600">{change}</span>
              </div>
            ) : (
              <div className="flex items-center gap-1 px-2 py-1 bg-red-100 rounded-full">
                <TrendingDown className="w-3 h-3 text-red-600" />
                <span className="text-xs font-medium text-red-600">{change}</span>
              </div>
            )}
          </div>
        )}

        {subtitle && <p className="text-xs text-gray-500 mt-2">{subtitle}</p>}
      </div>
    </div>
  )
}
