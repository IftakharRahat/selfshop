import type { StaticImageData } from "next/image";

export interface TProduct {
	id: number;
	name: string;
	image: string | StaticImageData;
	currentPrice: number;
	originalPrice: number;
	rating: number;
	alt: string;
	bgColor?: string;
}

export interface TProductSectionProps {
	title: string;
	featuredProducts?: TProduct[];
	regularProducts?: TProduct[];
	className?: string;
}
