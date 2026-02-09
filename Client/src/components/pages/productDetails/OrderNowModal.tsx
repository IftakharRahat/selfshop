/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { Minus, Plus } from "lucide-react";
import Image from "next/image";
import React, { useState } from "react";
import { TbCurrencyTaka } from "react-icons/tb";

type ColorOption = {
	id: string | number;
	name?: string;
	color: string;
};

export default function OrderNowModal({
	open,
	onClose,
	variant,
}: {
	open: boolean;
	onClose: () => void;
	variant: any;
}) {
	// Manual colors (no type error)
	const derivedColors: ColorOption[] = [
		{ id: 1, name: "Red", color: "red" },
		{ id: 2, name: "Blue", color: "blue" },
		{ id: 3, name: "Green", color: "green" },
		{ id: 4, name: "Black", color: "black" },
	];

	const sizes: string[] = variant?.sizes ?? ["S", "M", "L", "XL", "2XL"];
	const defaultStock = variant?.stock ?? 49;
	const unitPrice = Number(variant?.price ?? variant?.unitPrice ?? 0);

	const [selectedColor, setSelectedColor] = useState<ColorOption>(
		derivedColors[0],
	);

	const [quantities, setQuantities] = useState<Record<string, number>>(() =>
		sizes.reduce(
			(acc, s) => {
				acc[s] = 0;
				return acc;
			},
			{} as Record<string, number>,
		),
	);

	const handleQtyChange = (size: string, type: "inc" | "dec") => {
		setQuantities((prev) => ({
			...prev,
			[size]: type === "inc" ? prev[size] + 1 : Math.max(0, prev[size] - 1),
		}));
	};

	const totalCount = Object.values(quantities).reduce((a, b) => a + b, 0);
	const totalPrice = totalCount * unitPrice;

	const handleConfirmOrder = () => {
		const orderPayload = {
			variantId: variant?.id,
			selectedColor: selectedColor?.name,
			quantities,
			totalCount,
			totalPrice,
		};

		console.log(orderPayload);
		onClose();
	};
	if (!open || !variant) return null;
	return (
		<div className="fixed inset-0 bg-black/30 flex items-center justify-center z-[999] p-2">
			<div className="bg-white rounded-lg w-full max-w-xl p-4 shadow-lg overflow-auto max-h-[90vh] text-sm">
				{/* Header */}
				<div className="flex justify-between items-start mb-3">
					<h2 className="text-base font-bold">Order Summary</h2>
					<button
						onClick={onClose}
						className="text-gray-600 hover:text-black text-lg"
					>
						✕
					</button>
				</div>

				{/* Color Select */}
				<div className="mb-3">
					<h3 className="font-medium mb-2 text-sm">Color</h3>
					<div className="flex gap-3 overflow-x-auto p-1">
						{derivedColors.map((c) => (
							<button
								key={c.id}
								onClick={() => setSelectedColor(c)}
								className={`flex flex-col items-center p-1 rounded-md transition ${
									selectedColor.id === c.id
										? "ring-2 ring-pink-500"
										: "hover:ring-1 hover:ring-gray-200"
								}`}
							>
								<div
									className="w-5 h-5 rounded-full border"
									style={{ backgroundColor: c.color }}
								></div>
								<span className="text-[10px] mt-1">{c.name}</span>
							</button>
						))}
					</div>
				</div>

				{/* Gallery – smaller */}
				<div className="mb-3 grid grid-cols-4 gap-2">
					{(variant.images ?? []).slice(0, 4).map((img: string, i: number) => (
						<div key={i} className="rounded overflow-hidden border h-16">
							<Image
								src={img}
								alt=""
								width={80}
								height={80}
								className="object-cover w-full h-full"
							/>
						</div>
					))}
				</div>

				{/* Table */}
				<div className="bg-[#fff0f4] rounded-lg p-3 text-xs">
					<div className="grid grid-cols-3 mb-2 font-semibold">
						<div>Size</div>
						<div>Stock</div>
						<div className="text-right">Qty</div>
					</div>

					{sizes.map((size) => (
						<div key={size} className="grid grid-cols-3 py-2 border-t">
							<div>
								<span className="px-2 py-0.5 border rounded text-xs">
									{size}
								</span>
							</div>

							<div>{defaultStock} pcs</div>

							<div className="flex items-center justify-end gap-1">
								<button
									onClick={() => handleQtyChange(size, "dec")}
									className="w-6 h-6 bg-white border rounded flex items-center justify-center"
								>
									<Minus size={12} />
								</button>

								<div className="w-7 h-6 flex items-center justify-center bg-white border text-xs">
									{quantities[size]}
								</div>

								<button
									onClick={() => handleQtyChange(size, "inc")}
									className="w-6 h-6 bg-white border rounded flex items-center justify-center"
								>
									<Plus size={12} />
								</button>
							</div>
						</div>
					))}
				</div>

				{/* Totals */}
				<div className="flex justify-between items-center mt-3 text-sm font-semibold">
					<p>Total: {totalCount} pcs</p>

					<p className="text-pink-600 flex items-center gap-1">
						<TbCurrencyTaka /> {totalPrice.toFixed(2)}
					</p>
				</div>

				{/* Buttons */}
				<div className="mt-3 grid grid-cols-3 gap-2">
					<button
						onClick={onClose}
						className="py-2 rounded-lg border bg-white hover:bg-gray-50 text-xs"
					>
						Cancel
					</button>

					<button
						onClick={handleConfirmOrder}
						className="col-span-2 py-2 rounded-lg bg-pink-600 text-white text-xs hover:bg-pink-700"
					>
						Confirm Order
						<span className="block text-[10px] mt-1">
							Selected: {selectedColor?.name}
						</span>
					</button>
				</div>
			</div>
		</div>
	);
}
