/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";
import { CreateTicketModal } from "@/components/pages/dashboard/create-ticket-modal";
import { useGetAllSupportTicketsQuery } from "@/redux/features/supportTicket/supportTicketApi";

const Page = () => {
	const { data: tickets } = useGetAllSupportTicketsQuery(undefined);

	// Convert the object (0,1,2 keys) to an array
	const ticketList = tickets?.data ? Object.values(tickets.data) : [];

	return (
		<div className="m-3 sm:m-4 lg:m-6 bg-white rounded-xl border border-gray-100 shadow-sm p-3 sm:p-5 lg:p-6 mb-24">
			{/* Header */}
			<div className="flex items-center justify-between mb-4">
				<h2 className="text-base sm:text-lg font-semibold text-gray-900">Tickets</h2>
				<CreateTicketModal />
			</div>

			{/* Mobile Card Layout */}
			<div className="md:hidden space-y-3">
				{ticketList.length > 0 ? (
					ticketList.map((ticket: any, index: number) => (
						<div
							key={index}
							className="bg-gray-50/60 border border-gray-100 rounded-xl p-3"
						>
							<div className="flex items-center justify-between mb-1">
								<p className="text-sm font-semibold text-gray-900 truncate mr-2">{ticket.subject}</p>
								<span className="px-2 py-0.5 rounded-full text-[10px] font-medium bg-green-50 text-green-700 border border-green-200 flex-shrink-0">
									{ticket.status}
								</span>
							</div>

							<div className="flex items-center justify-between text-xs text-gray-400">
								<span>{ticket.department}</span>
								<span>{new Date(ticket.updated_at).toLocaleDateString()}</span>
							</div>
						</div>
					))
				) : (
					<div className="py-10 text-center text-gray-400 text-sm">
						No tickets found.
					</div>
				)}
			</div>

			{/* Desktop Table Layout */}
			<div className="hidden md:block overflow-x-auto">
				<table className="w-full">
					<thead>
						<tr className="bg-gray-50/80">
							<th className="p-4 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">
								Department
							</th>
							<th className="p-4 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">
								Subject
							</th>
							<th className="p-4 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">
								Last Update
							</th>
							<th className="p-4 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">
								Status
							</th>
						</tr>
					</thead>

					<tbody>
						{ticketList.length > 0 ? (
							ticketList.map((ticket: any, index: number) => (
								<tr
									key={index}
									className="border-b border-gray-50 hover:bg-gray-50/50 transition-colors"
								>
									<td className="p-4 text-sm font-medium text-gray-900">
										{ticket.department}
									</td>
									<td className="p-4 text-sm text-gray-900">
										{ticket.subject}
									</td>
									<td className="p-4 text-sm text-gray-500">
										{new Date(ticket.updated_at).toLocaleDateString()}
									</td>
									<td className="p-4">
										<span className="px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200">
											{ticket.status}
										</span>
									</td>
								</tr>
							))
						) : (
							<tr>
								<td colSpan={4} className="py-12 text-center text-gray-400 text-sm">
									No tickets found.
								</td>
							</tr>
						)}
					</tbody>
				</table>
			</div>
		</div>
	);
};

export default Page;
