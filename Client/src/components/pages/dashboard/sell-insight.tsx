"use client"


import { cn } from "@/lib/utils"
import { Select } from "antd"
import { Area, AreaChart, ResponsiveContainer, XAxis, YAxis } from "recharts"
import order from "@/assets/images/dashboard/Group 1321314503 (1).png";
import cancelled from "@/assets/images/dashboard/Group 1321314503 (2).png"; 
import returnIcon from "@/assets/images/dashboard/Group 1321314504.png"; 
import delivery from "@/assets/images/dashboard/Group 1321314505.png"; 
import delivered from "@/assets/images/dashboard/Group 1321314506 (3).png"; 


const orderStats = [
  { title: "New order", value: 20, icon: order },
  { title: "Cancelled", value: 20, icon: cancelled },
  { title: "Returned", value: 20, icon: returnIcon  },
  { title: "On delivery", value: 20, icon: delivery  },
  { title: "Delivered", value: 20, icon: delivered },
]

const chartData = [
  { month: "Jan", rate: 75 },
  { month: "Feb", rate: 72 },
  { month: "Mar", rate: 78 },
  { month: "Apr", rate: 85 },
  { month: "May", rate: 92 },
  { month: "Jun", rate: 88 },
  { month: "Jul", rate: 95 },
  { month: "Aug", rate: 98 },
  { month: "Sep", rate: 96 },
  { month: "Oct", rate: 89 },
  { month: "Nov", rate: 82 },
  { month: "Dec", rate: 76 },
]

// const chartConfig = {
//   rate: {
//     label: "Order Rate",
//     color: "hsl(var(--chart-1))",
//   },
// }

export default function SellInsight() {
  return (
    <div className=" bg-white p-4 rounded-md">
      <div className="">
        {/* Header */}
        <div className="flex items-center justify-between">
          <h1 className="text-2xl font-semibold text-gray-900">Sell insight</h1>
        </div>

        {/* Order Statistics Cards */}
        <div className="grid grid-cols-1 gap-4 xs:grid-cols-2 lg:grid-cols-5 xl:border-b xl:pb-3 xl:mb-3 border-gray-200">
          {orderStats.map((stat, index) => (
            <div key={index} className={cn("bg-white ",
              // index === orderStats.length - 1 ? "border-none" : "xl:border-r xl:border-gray-400"
            )}>
              <div className="p-6 flex items-center justify-between">
                <div className="flex items-start space-x-3">
                  <div className="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
                    <img src={stat.icon.src} alt={stat.title} className="w-8 h-8" />
                  </div>
                  <div>
                    <p className="text-sm font-medium text-gray-600">{stat.title}</p>
                    <p className="text-2xl font-bold text-gray-900">{stat.value}</p>
                  </div>
                </div>
                <div className={cn(
 index === orderStats.length - 1 ? "" : "hidden 2xl:block h-12 w-[0.2px] bg-gray-300"

                )}></div>
              </div>
            </div>
          ))}
        </div>

        {/* Order Rate Chart */}
        <div className="bg-white">
          <div className="flex flex-row items-center justify-between space-y-0 pb-2">
            <div className="text-base font-medium text-gray-900">Order rate</div>
              <div className="flex items-center gap-4">
            <p className="text-sm font-medium text-gray-600">Sort: </p>
            <Select defaultValue="2024">
              <Select.Option value="2024">2024</Select.Option>
              <Select.Option value="2023">2023</Select.Option>
              <Select.Option value="2022">2022</Select.Option>
            </Select>
          </div>
          </div>
          <div>
            {/* ChartContainer is not defined, so use a div as a wrapper */}
            <div className="h-[300px]">
              <ResponsiveContainer width="100%" height="100%">
                <AreaChart data={chartData}>
                  <XAxis dataKey="month" axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: "#6B7280" }} />
                  <YAxis
                    axisLine={false}
                    tickLine={false}
                    tick={{ fontSize: 12, fill: "#6B7280" }}
                    tickFormatter={(value) => `${value}%`}
                    domain={[0, 100]}
                  />
                  {/* ChartTooltip and ChartTooltipContent are not defined, so remove them */}
                  <Area type="monotone" dataKey="rate" stroke="#EC4899" fill="#FCE7F3" strokeWidth={2} />
                </AreaChart>
              </ResponsiveContainer>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
