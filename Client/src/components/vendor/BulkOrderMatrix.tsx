"use client";

import { Minus, Plus } from "lucide-react";
import Image from "next/image";
import * as React from "react";
import { TbCurrencyTaka } from "react-icons/tb";
import { getImageUrl } from "@/lib/utils";

export type MatrixVariant = {
	id: string | number;
	label: string;
	size?: string;
	color?: string;
	price: number;
	stock?: number;
};

type Tier = { minQty: number; price: number; label: string };

const DEFAULT_TIERS: Tier[] = [
	{ minQty: 0, price: 1, label: "Tier 1" },
	{ minQty: 50, price: 0.95, label: "Tier 2" },
	{ minQty: 100, price: 0.9, label: "Tier 3" },
];

function getTierForQty(
	tiers: Tier[],
	basePrice: number,
	qty: number,
): { price: number; label: string } {
	const applicable = tiers.filter((t) => t.minQty <= qty);
	const tier = applicable[applicable.length - 1];
	if (!tier) return { price: basePrice, label: "Tier 1" };
	const price =
		tier.price >= 1 ? tier.price : Math.round(basePrice * tier.price);
	return { price, label: tier.label };
}

type Props = {
	productName: string;
	productImage?: string;
	basePrice: number;
	variants: MatrixVariant[];
	tiers?: Tier[];
	onConfirm: (payload: {
		variantQuantities: Record<string, number>;
		totalQty: number;
		unitPrice: number;
		total: number;
		variantLabels?: Record<string, string>;
	}) => void;
	onClose?: () => void;
};

export default function BulkOrderMatrix({
	productName,
	productImage,
	basePrice,
	variants,
	tiers = DEFAULT_TIERS,
	onConfirm,
	onClose,
}: Props) {
	const initialQty: Record<string, number> = {};
	variants.forEach((v) => (initialQty[String(v.id)] = 0));
	const [quantities, setQuantities] =
		React.useState<Record<string, number>>(initialQty);

	const totalQty = Object.values(quantities).reduce((a, b) => a + b, 0);
	const tierInfo = getTierForQty(tiers, basePrice, totalQty);
	const unitPrice = totalQty > 0 ? tierInfo.price : basePrice;
	const total = totalQty * unitPrice;

	const handleChange = (id: string | number, delta: number) => {
		setQuantities((prev) => {
			const key = String(id);
			const next = (prev[key] ?? 0) + delta;
			return { ...prev, [key]: Math.max(0, next) };
		});
	};

	const handleConfirm = () => {
		const variantLabels: Record<string, string> = {};
		variants.forEach((v) => (variantLabels[String(v.id)] = v.label));
		onConfirm({
			variantQuantities: quantities,
			totalQty,
			unitPrice,
			total,
			variantLabels,
		});
	};

	return (
		<div className="flex flex-col min-h-[calc(100vh-8rem)] pb-32">
			{/* Product header */}
			<div className="flex gap-4 mb-6 p-4 bg-white rounded-xl border border-gray-100">
				{productImage && (
					<div className="w-20 h-20 rounded-lg overflow-hidden flex-shrink-0 bg-gray-100">
						<Image
							src={getImageUrl(productImage)}
							alt={productName}
							width={80}
							height={80}
							className="w-full h-full object-cover"
						/>
					</div>
				)}
				<div>
					<h1 className="font-semibold text-gray-900">{productName}</h1>
					<p className="text-sm text-gray-500">Bulk order – mixed variants</p>
				</div>
			</div>

			{/* Variant list with steppers */}
			<div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
				<div className="px-4 py-3 border-b border-gray-100 font-medium text-gray-700 text-sm">
					Variant · Quantity
				</div>
				<ul className="divide-y divide-gray-100">
					{variants.map((v) => {
						const qty = quantities[String(v.id)] ?? 0;
						return (
							<li
								key={String(v.id)}
								className="flex items-center justify-between px-4 py-3"
							>
								<span className="text-gray-900">{v.label}</span>
								<div className="flex items-center gap-2">
									<button
										type="button"
										onClick={() => handleChange(v.id, -1)}
										className="w-9 h-9 rounded-lg border border-gray-300 flex items-center justify-center hover:bg-gray-50 text-gray-600 disabled:opacity-50"
										disabled={qty <= 0}
									>
										<Minus className="w-4 h-4" />
									</button>
									<span className="w-10 text-center font-medium">{qty}</span>
									<button
										type="button"
										onClick={() => handleChange(v.id, 1)}
										className="w-9 h-9 rounded-lg border border-gray-300 flex items-center justify-center hover:bg-gray-50 text-gray-600"
									>
										<Plus className="w-4 h-4" />
									</button>
								</div>
							</li>
						);
					})}
				</ul>
			</div>

			{/* Sticky footer – live calculation */}
			<footer className="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg p-4 safe-area-pb">
				<div className="container mx-auto space-y-2 text-sm">
					<div className="flex justify-between">
						<span className="text-gray-600">Total Qty</span>
						<span className="font-semibold">{totalQty}</span>
					</div>
					<div className="flex justify-between items-center">
						<span className="text-gray-600">Unit price</span>
						<span>
							{totalQty > 0 && basePrice !== unitPrice && (
								<span className="text-gray-400 line-through mr-1">
									<TbCurrencyTaka className="inline" />
									{basePrice}
								</span>
							)}
							<span className="font-medium">
								<TbCurrencyTaka className="inline" />
								{unitPrice.toFixed(0)}{" "}
								{totalQty > 0 && `(${tierInfo.label} applied)`}
							</span>
						</span>
					</div>
					<div className="flex justify-between text-base font-bold text-[#E5005F] pt-2 border-t border-gray-100">
						<span>Total</span>
						<span>
							<TbCurrencyTaka className="inline" />
							{total.toLocaleString("en-BD")} BDT
						</span>
					</div>
					<div className="grid grid-cols-2 gap-2 pt-2">
						{onClose && (
							<button
								type="button"
								onClick={onClose}
								className="py-2.5 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50"
							>
								Cancel
							</button>
						)}
						<button
							type="button"
							onClick={handleConfirm}
							disabled={totalQty === 0}
							className="py-2.5 rounded-lg bg-[#E5005F] text-white font-medium hover:bg-pink-700 disabled:opacity-50 disabled:cursor-not-allowed"
						>
							Add to cart · {totalQty} pcs
						</button>
					</div>
				</div>
			</footer>
		</div>
	);
}
