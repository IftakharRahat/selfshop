/* eslint-disable @typescript-eslint/no-explicit-any */

import { Drawer } from "antd";
import { Minus, Plus } from "lucide-react";
import Link from "next/link";
import type React from "react";
import { MdDeleteOutline } from "react-icons/md";
import { getImageUrl } from "@/lib/utils";
import {
	useDeleteCartItemMutation,
	useGetAllCartItemsQuery,
	useUpdateCartItemMutation,
} from "@/redux/features/cartApi";
import { useAppSelector } from "@/redux/hooks";
import { handleAsyncWithToast } from "@/utils/handleAsyncWithToast";

interface CartDrawerProps {
	isOpen: boolean;
	onClose: () => void;
}

const CartDrawer: React.FC<CartDrawerProps> = ({ isOpen, onClose }) => {
	const token = useAppSelector((state) => state.auth.access_token);
	const { data: cartItems } = useGetAllCartItemsQuery(undefined, {
		skip: !token,
	});

	const [updateCartItem] = useUpdateCartItemMutation();
	const [deleteCartItem] = useDeleteCartItemMutation();

	const handleUpdateCartItem = async (productId: string, newQty: number) => {
		const formData = new FormData();
		formData.append("product_id", productId);
		formData.append("qty", newQty.toString());

		await handleAsyncWithToast(async () => {
			return updateCartItem({ formData });
		});
	};

	const handleDeleteCartItem = async (productId: string) => {
		await handleAsyncWithToast(
			async () => deleteCartItem(productId),
			true,
			"Removing item...",
			"Item removed from cart",
		);
	};

	const totalPrice = cartItems?.data?.reduce(
		(total: number, item: any) => total + parseFloat(item.price) * item.qty,
		0,
	);

	return (
		<Drawer
			title="Your Cart"
			placement="right"
			closable={true}
			onClose={onClose}
			open={isOpen}
			width={350}
		>
			<div className="flex flex-col h-full justify-between">
				<div>
					{cartItems?.data?.length ? (
						<div className="space-y-4">
							{cartItems.data.map((item: any) => (
								<div
									key={item.id}
									className="flex items-center gap-3 border-b pb-3"
								>
									<img
										src={getImageUrl(item.image)}
										alt={item?.name || "Cart item"}
										className="w-14 h-14 object-cover rounded"
									/>
									<div className="flex-1">
										<p className="text-sm font-medium">{item.name}</p>
										<p className="text-gray-500 text-xs">Code: {item.code}</p>

										{/* Quantity Controls */}
										<div className="flex items-center gap-2 mt-2">
											<button
												disabled={item.qty <= 1}
												onClick={() =>
													handleUpdateCartItem(item.product_id, item.qty - 1)
												}
												className="cursor-pointer w-10 h-10 border border-gray-300 rounded-lg flex items-center justify-center hover:bg-gray-50 transition-colors bg-white"
											>
												<Minus className="w-4 h-4" />
											</button>
											<span>{item.qty}</span>
											<button
												onClick={() =>
													handleUpdateCartItem(item.product_id, item.qty + 1)
												}
												className="cursor-pointer w-10 h-10 border border-gray-300 rounded-lg flex items-center justify-center hover:bg-pink-50 transition-colors bg-white"
											>
												<Plus className="w-4 h-4" />
											</button>

											{/* Delete Button */}
											<button
												className="ml-auto text-red-500 cursor-pointer"
												onClick={() => handleDeleteCartItem(item.product_id)}
											>
												<MdDeleteOutline size={20} />
											</button>
										</div>

										<p className="text-gray-700 font-semibold mt-1">
											৳{item.price} × {item.qty}
										</p>
									</div>
								</div>
							))}

							{/* Total Price */}
							<div className="pt-4 font-semibold text-lg">
								Total: ৳{totalPrice?.toFixed(2)}
							</div>
						</div>
					) : (
						<p className="text-gray-500">Your cart is empty.</p>
					)}
				</div>

				<div className="py-6">
					<Link href="/order-confirmation">
						<button
							onClick={onClose}
							className="cursor-pointer w-full bg-[#E7005E] hover:bg-pink-600 text-white py-2 px-4 rounded-full transition-colors"
						>
							Go to Checkout
						</button>
					</Link>
				</div>
			</div>
		</Drawer>
	);
};

export default CartDrawer;
