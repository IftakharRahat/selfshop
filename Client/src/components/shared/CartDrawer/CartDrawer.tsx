/* eslint-disable @typescript-eslint/no-explicit-any */
import { formatBDT } from "@/lib/format-currency";

import { Drawer } from "antd";
import { Minus, Plus, ShoppingBag, X } from "lucide-react";
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
import { useIsActiveReseller } from "@/hooks/useIsActiveReseller";

interface CartDrawerProps {
	isOpen: boolean;
	onClose: () => void;
}

const CartDrawer: React.FC<CartDrawerProps> = ({ isOpen, onClose }) => {
	const token = useAppSelector((state) => state.auth.access_token);
	const { isActive: isResellerActive } = useIsActiveReseller();
	const { data: cartItems } = useGetAllCartItemsQuery(undefined, {
		skip: !token,
	});

	const [updateCartItem] = useUpdateCartItemMutation();
	const [deleteCartItem] = useDeleteCartItemMutation();

	const handleUpdateCartItem = async (cartId: number, newQty: number) => {
		await handleAsyncWithToast(async () => {
			return updateCartItem({ cartId, qty: newQty });
		});
	};

	const handleDeleteCartItem = async (cartId: number) => {
		await handleAsyncWithToast(
			async () => deleteCartItem(cartId),
			true,
			"Removing item...",
			"Item removed from cart",
		);
	};

	const totalPrice = cartItems?.data?.reduce(
		(total: number, item: any) => total + parseFloat(item.price) * item.qty,
		0,
	);

	const itemCount = cartItems?.data?.length || 0;

	return (
		<Drawer
			placement="right"
			closable={false}
			onClose={onClose}
			open={isOpen}
			width={380}
			styles={{
				header: { display: "none" },
				body: { padding: 0 },
			}}
		>
			<div className="flex flex-col h-full">
				{/* Header */}
				<div className="flex items-center justify-between px-5 py-4 border-b border-gray-100">
					<div className="flex items-center gap-2">
						<ShoppingBag className="w-5 h-5 text-pink-600" />
						<h2 className="text-lg font-bold text-gray-900">Your Cart</h2>
						{itemCount > 0 && (
							<span className="bg-pink-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">
								{itemCount}
							</span>
						)}
					</div>
					<button
						onClick={onClose}
						className="w-8 h-8 rounded-full hover:bg-gray-100 flex items-center justify-center transition-colors cursor-pointer"
					>
						<X className="w-5 h-5 text-gray-500" />
					</button>
				</div>

				{/* Cart Items */}
				<div className="flex-1 overflow-y-auto px-5 py-4">
					{itemCount > 0 ? (
						<div className="space-y-3">
							{cartItems.data.map((item: any) => (
								<div
									key={item.id}
									className="flex gap-3 p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition-colors group"
								>
									{/* Product Image */}
									<div className="w-16 h-16 rounded-lg overflow-hidden bg-white border border-gray-200 flex-shrink-0">
										<img
											src={getImageUrl(item.image)}
											alt={item?.name || "Cart item"}
											className="w-full h-full object-cover"
										/>
									</div>

									{/* Product Info */}
									<div className="flex-1 min-w-0">
										<p className="text-sm font-semibold text-gray-900 truncate leading-tight">
											{item.name}
										</p>
										<p className="text-xs text-gray-400 mt-0.5">{item.code}</p>
										<p className="text-sm font-bold text-pink-600 mt-1">
											{isResellerActive ? `৳${item.price}` : "***"}
										</p>

										{/* Quantity Controls */}
										<div className="flex items-center gap-1.5 mt-2">
											<button
												disabled={item.qty <= 1}
												onClick={() =>
													handleUpdateCartItem(item.id, item.qty - 1)
												}
												className="w-7 h-7 border border-gray-200 rounded-md flex items-center justify-center hover:bg-white transition-colors bg-white disabled:opacity-40 cursor-pointer"
											>
												<Minus className="w-3 h-3 text-gray-600" />
											</button>
											<span className="w-8 text-center text-sm font-semibold text-gray-900">
												{item.qty}
											</span>
											<button
												onClick={() =>
													handleUpdateCartItem(item.id, item.qty + 1)
												}
												className="w-7 h-7 border border-pink-200 rounded-md flex items-center justify-center hover:bg-pink-50 transition-colors bg-white cursor-pointer"
											>
												<Plus className="w-3 h-3 text-pink-600" />
											</button>

											{/* Delete */}
											<button
												className="ml-auto w-7 h-7 rounded-md flex items-center justify-center text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors opacity-0 group-hover:opacity-100 cursor-pointer"
												onClick={() =>
													handleDeleteCartItem(item.id)
												}
											>
												<MdDeleteOutline size={16} />
											</button>
										</div>
									</div>

									{/* Line Total */}
									<div className="text-right flex-shrink-0 self-start">
										<p className="text-sm font-bold text-gray-900">
											{isResellerActive ? `৳${formatBDT(parseFloat(item.price) * item.qty, 0)}` : "???"}
										</p>
									</div>
								</div>
							))}
						</div>
					) : (
						<div className="flex flex-col items-center justify-center h-full text-center py-12">
							<div className="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center mb-4">
								<ShoppingBag className="w-10 h-10 text-gray-300" />
							</div>
							<p className="text-gray-900 font-semibold text-lg mb-1">
								Your cart is empty
							</p>
							<p className="text-gray-400 text-sm">
								Looks like you haven&apos;t added anything yet.
							</p>
							<button
								onClick={onClose}
								className="mt-6 text-pink-600 font-medium text-sm hover:underline cursor-pointer"
							>
								Continue Shopping
							</button>
						</div>
					)}
				</div>

				{/* Footer */}
				{itemCount > 0 && (
					<div className="border-t border-gray-100 px-5 py-4 bg-white">
						{/* Subtotal */}
						<div className="flex items-center justify-between mb-4">
							<span className="text-gray-500 text-sm">
								Subtotal ({itemCount} {itemCount === 1 ? "item" : "items"})
							</span>
							<span className="text-xl font-bold text-gray-900">
								{isResellerActive ? `৳${formatBDT(totalPrice)}` : "***"}
							</span>
						</div>

						{/* Checkout Button */}
						{isResellerActive ? (
							<Link href="/order-confirmation">
								<button
									onClick={onClose}
									className="w-full bg-pink-600 hover:bg-pink-700 text-white font-semibold py-3 px-6 rounded-xl transition-colors cursor-pointer shadow-sm shadow-pink-200"
								>
									Proceed to Checkout
								</button>
							</Link>
						) : (
							<button
								onClick={() => (window.location.href = "/pricing")}
								className="w-full bg-pink-600 hover:bg-pink-700 text-white font-semibold py-3 px-6 rounded-xl transition-colors cursor-pointer shadow-sm shadow-pink-200"
							>
								Activate Account to Checkout
							</button>
						)}

						{/* Continue Shopping */}
						<button
							onClick={onClose}
							className="w-full mt-2 text-gray-500 hover:text-gray-700 font-medium text-sm py-2 transition-colors cursor-pointer"
						>
							Continue Shopping
						</button>
					</div>
				)}
			</div>
		</Drawer>
	);
};

export default CartDrawer;
