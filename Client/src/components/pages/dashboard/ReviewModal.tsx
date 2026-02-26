"use client";

import { useState } from "react";
import { Star, X } from "lucide-react";
import { useSubmitReviewMutation } from "@/redux/features/dashboardApi";
import Swal from "sweetalert2";

interface ReviewModalProps {
    isOpen: boolean;
    onClose: () => void;
    productId: number;
    productName: string;
}

export default function ReviewModal({
    isOpen,
    onClose,
    productId,
    productName,
}: ReviewModalProps) {
    const [rating, setRating] = useState(0);
    const [hoveredRating, setHoveredRating] = useState(0);
    const [comment, setComment] = useState("");
    const [submitReview, { isLoading }] = useSubmitReviewMutation();

    if (!isOpen) return null;

    const handleSubmit = async () => {
        if (rating === 0) {
            Swal.fire({
                icon: "warning",
                title: "Rating Required",
                text: "Please select a star rating before submitting.",
                confirmButtonColor: "#E5005F",
            });
            return;
        }

        const formData = new FormData();
        formData.append("product_id", String(productId));
        formData.append("rating", String(rating));
        if (comment.trim()) {
            formData.append("messages", comment.trim());
        }

        try {
            const result = await submitReview(formData).unwrap();
            if (result.status) {
                Swal.fire({
                    icon: "success",
                    title: "Review Submitted!",
                    text: "Thank you for your feedback.",
                    confirmButtonColor: "#E5005F",
                });
                setRating(0);
                setComment("");
                onClose();
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: result.message || "Failed to submit review.",
                    confirmButtonColor: "#E5005F",
                });
            }
        } catch (err: unknown) {
            const error = err as { data?: { message?: string } };
            Swal.fire({
                icon: "error",
                title: "Error",
                text: error?.data?.message || "Something went wrong. Please try again.",
                confirmButtonColor: "#E5005F",
            });
        }
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center">
            {/* Backdrop */}
            <div
                className="absolute inset-0 bg-black/50 backdrop-blur-sm"
                onClick={onClose}
            />

            {/* Modal */}
            <div className="relative bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                {/* Header */}
                <div className="flex items-center justify-between px-6 pt-5 pb-3">
                    <div>
                        <h3 className="text-lg font-semibold text-gray-900">
                            Write a Review
                        </h3>
                        <p className="text-sm text-gray-500 mt-0.5 truncate max-w-[280px]">
                            {productName}
                        </p>
                    </div>
                    <button
                        onClick={onClose}
                        className="p-1.5 rounded-full hover:bg-gray-100 transition-colors cursor-pointer"
                    >
                        <X className="w-5 h-5 text-gray-400" />
                    </button>
                </div>

                {/* Content */}
                <div className="px-6 pb-6 space-y-5">
                    {/* Star Rating */}
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">
                            Your Rating
                        </label>
                        <div className="flex items-center gap-1.5">
                            {[1, 2, 3, 4, 5].map((value) => (
                                <button
                                    key={value}
                                    type="button"
                                    onClick={() => setRating(value)}
                                    onMouseEnter={() => setHoveredRating(value)}
                                    onMouseLeave={() => setHoveredRating(0)}
                                    className="p-0.5 transition-transform hover:scale-110 cursor-pointer"
                                >
                                    <Star
                                        className={`w-8 h-8 transition-colors ${value <= (hoveredRating || rating)
                                                ? "fill-amber-400 text-amber-400"
                                                : "fill-gray-200 text-gray-200"
                                            }`}
                                    />
                                </button>
                            ))}
                            {rating > 0 && (
                                <span className="ml-2 text-sm font-medium text-gray-600">
                                    {rating}/5
                                </span>
                            )}
                        </div>
                    </div>

                    {/* Comment */}
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-gray-700">
                            Your Comment{" "}
                            <span className="text-gray-400 font-normal">(optional)</span>
                        </label>
                        <textarea
                            value={comment}
                            onChange={(e) => setComment(e.target.value)}
                            placeholder="Share your experience with this product..."
                            rows={4}
                            maxLength={1000}
                            className="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-800 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-[#E5005F]/20 focus:border-[#E5005F]/40 resize-none transition-all"
                        />
                        <p className="text-xs text-gray-400 text-right">
                            {comment.length}/1000
                        </p>
                    </div>

                    {/* Actions */}
                    <div className="flex gap-3 pt-1">
                        <button
                            onClick={onClose}
                            disabled={isLoading}
                            className="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors cursor-pointer disabled:opacity-50"
                        >
                            Cancel
                        </button>
                        <button
                            onClick={handleSubmit}
                            disabled={isLoading || rating === 0}
                            className="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-[#E5005F] rounded-xl hover:bg-[#C80050] transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {isLoading ? "Submitting..." : "Submit Review"}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
