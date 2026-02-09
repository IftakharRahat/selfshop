/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";
import { CreateTicketModal } from "@/components/pages/dashboard/create-ticket-modal";
import { useGetAllSupportTicketsQuery } from "@/redux/features/supportTicket/supportTicketApi";

const Page = () => {
	const { data: tickets } = useGetAllSupportTicketsQuery(undefined);

	// Convert the object (0,1,2 keys) to an array
	const ticketList = tickets?.data ? Object.values(tickets.data) : [];

	return (
		<div className="m-4 lg:m-6 md:bg-white rounded-md md:p-6">
			{/* table */}
			<div className="bg-white ">
				<div className="p-0">
					<div className="flex items-center justify-between p-4 border- border-gray-200">
						<h2 className="text-md font-semibold text-gray-900">Tickets</h2>

						<div className="flex items-center gap-4">
							<CreateTicketModal />
						</div>
					</div>
					<div className="flex items-center justify-between p-4 border- border-gray-200">
						<h2 className="text-md font-medium text-gray-900">All Tickets</h2>

						{/* <div className="flex items-center gap-4">
              <p className="text-sm font-medium text-gray-600">Sort: </p>
              <Select defaultValue="2024">
                <Select.Option value="2024">2024</Select.Option>
                <Select.Option value="2023">2023</Select.Option>
                <Select.Option value="2022">2022</Select.Option>
              </Select>
            </div> */}
					</div>

					{/* Desktop Table */}
					<div className=" overflow-x-auto">
						<table className="w-full">
							<thead>
								<tr>
									<th className="text-left p-4 text-sm font-medium text-gray-600">
										Department
									</th>
									<th className="text-left p-4 text-sm font-medium text-gray-600">
										Subject
									</th>
									<th className="text-left p-4 text-sm font-medium text-gray-600">
										Last Update
									</th>
									<th className="text-left p-4 text-sm font-medium text-gray-600">
										Status
									</th>
								</tr>
							</thead>
							<tbody>
								{ticketList.map((ticket: any, index: number) => (
									<tr
										key={index}
										className="border-b border-gray-100 hover:bg-gray-50"
									>
										<td className="p-4 text-sm text-gray-900">
											{ticket.department}
										</td>
										<td className="p-4 text-sm text-gray-900">
											{ticket.subject}
										</td>
										<td className="p-4 text-sm text-gray-600">
											{new Date(ticket.updated_at).toLocaleDateString()}
										</td>
										<td className="p-4">
											<div
												className={`bg-green-200 border-0 py-1 w-min rounded-md px-5 text-xs`}
											>
												{ticket.status}
											</div>
										</td>
									</tr>
								))}

								{/* If no data */}
								{ticketList.length === 0 && (
									<tr>
										<td
											colSpan={4}
											className="p-4 text-center text-sm text-gray-600"
										>
											No tickets found
										</td>
									</tr>
								)}
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	);
};

export default Page;
