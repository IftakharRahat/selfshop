"use client";

import { useState } from "react";
import { Pencil, Star, User } from "lucide-react";
import {
    useGetProductReviewsQuery,
    useCheckUserReviewQuery,
    useSubmitReviewMutation,
    useUpdateReviewMutation,
} from "@/redux/features/dashboardApi";
import { useAppSelector } from "@/redux/hooks";
import Swal from "sweetalert2";

interface ProductReviewsSectionProps {
    productId: number;
}

function StarDisplay({ rating }: { rating: number }) {
    return (
        <div className="flex items-center gap-0.5">
            {[1, 2, 3, 4, 5].map((value) => (
                <Star
                    key={value}
                    className={`w-4 h-4 ${value <= rating
                            ? "fill-amber-400 text-amber-400"
                            : "fill-gray-200 text-gray-200"
                        }`}
                />
            ))}
        </div>
    );
}

function formatDate(dateStr: string) {
    try {
        return new Date(dateStr).toLocaleDateString("en-US", {
            year: "numeric",
            month: "short",
            day: "numeric",
        });
    } catch {
        return dateStr;
    }
}

/* ── Shared star picker + textarea form ── */
function ReviewForm({
    initialRating = 0,
    initialComment = "",
    isLoading,
    onSubmit,
    submitLabel,
    onCancel,
}: {
    initialRating?: number;
    initialComment?: string;
    isLoading: boolean;
    onSubmit: (rating: number, comment: string) => void;
    submitLabel: string;
    onCancel?: () => void;
}) {
    const [rating, setRating] = useState(initialRating);
    const [hoveredRating, setHoveredRating] = useState(0);
    const [comment, setComment] = useState(initialComment);

    return (
        <div>
            {/* Star Rating */}
            <div className="mb-4">
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
                                className={`w-7 h-7 transition-colors ${value <= (hoveredRating || rating)
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
            <textarea
                value={comment}
                onChange={(e) => setComment(e.target.value)}
                placeholder="Share your experience with this product... (optional)"
                rows={3}
                maxLength={1000}
                className="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-800 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-[#E5005F]/20 focus:border-[#E5005F]/40 resize-none transition-all mb-3"
            />

            <div className="flex items-center justify-between">
                <p className="text-xs text-gray-400">{comment.length}/1000</p>
                <div className="flex items-center gap-2">
                    {onCancel && (
                        <button
                            onClick={onCancel}
                            disabled={isLoading}
                            className="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors cursor-pointer disabled:opacity-50"
                        >
                            Cancel
                        </button>
                    )}
                    <button
                        onClick={() => onSubmit(rating, comment)}
                        disabled={isLoading || rating === 0}
                        className="px-5 py-2 text-sm font-medium text-white bg-[#E5005F] rounded-xl hover:bg-[#C80050] transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {isLoading ? "Saving..." : submitLabel}
                    </button>
                </div>
            </div>
        </div>
    );
}

/* ── New review form ── */
function WriteReviewForm({ productId }: { productId: number }) {
    const [submitReview, { isLoading }] = useSubmitReviewMutation();

    const handleSubmit = async (rating: number, comment: string) => {
        const formData = new FormData();
        formData.append("product_id", String(productId));
        formData.append("rating", String(rating));
        if (comment.trim()) formData.append("messages", comment.trim());

        try {
            const result = await submitReview(formData).unwrap();
            if (result.status) {
                Swal.fire({
                    icon: "success",
                    title: "Review Submitted!",
                    text: "Thank you for your feedback.",
                    confirmButtonColor: "#E5005F",
                });
            } else {
                Swal.fire({ icon: "error", title: "Error", text: result.message, confirmButtonColor: "#E5005F" });
            }
        } catch (err: unknown) {
            const error = err as { data?: { message?: string } };
            Swal.fire({ icon: "error", title: "Error", text: error?.data?.message || "Something went wrong.", confirmButtonColor: "#E5005F" });
        }
    };

    return (
        <div className="bg-gradient-to-br from-pink-50/80 to-amber-50/50 border border-pink-100 rounded-xl p-5 mb-6">
            <h3 className="text-sm font-semibold text-gray-900 mb-3">
                Write Your Review
            </h3>
            <ReviewForm isLoading={isLoading} onSubmit={handleSubmit} submitLabel="Submit Review" />
        </div>
    );
}

/* ── Edit existing review ── */
function EditReviewCard({
    review,
}: {
    review: { id: number; rating: number; messages: string | null; created_at: string };
}) {
    const [editing, setEditing] = useState(false);
    const [updateReview, { isLoading }] = useUpdateReviewMutation();

    const handleUpdate = async (rating: number, comment: string) => {
        try {
            const result = await updateReview({
                reviewId: review.id,
                rating,
                messages: comment.trim() || null,
            }).unwrap();
            if (result.status) {
                Swal.fire({
                    icon: "success",
                    title: "Review Updated!",
                    text: "Your review has been updated.",
                    confirmButtonColor: "#E5005F",
                });
                setEditing(false);
            } else {
                Swal.fire({ icon: "error", title: "Error", text: result.message, confirmButtonColor: "#E5005F" });
            }
        } catch (err: unknown) {
            const error = err as { data?: { message?: string } };
            Swal.fire({ icon: "error", title: "Error", text: error?.data?.message || "Something went wrong.", confirmButtonColor: "#E5005F" });
        }
    };

    if (editing) {
        return (
            <div className="bg-gradient-to-br from-blue-50/60 to-indigo-50/40 border border-blue-100 rounded-xl p-5 mb-6">
                <h3 className="text-sm font-semibold text-gray-900 mb-3">
                    Edit Your Review
                </h3>
                <ReviewForm
                    initialRating={review.rating}
                    initialComment={review.messages ?? ""}
                    isLoading={isLoading}
                    onSubmit={handleUpdate}
                    submitLabel="Update Review"
                    onCancel={() => setEditing(false)}
                />
            </div>
        );
    }

    return (
        <div className="flex items-center justify-between gap-3 mb-6 bg-green-50 border border-green-200 rounded-xl px-4 py-3">
            <div className="flex items-center gap-3 min-w-0">
                <Star className="w-4 h-4 fill-green-500 text-green-500 shrink-0" />
                <div className="min-w-0">
                    <div className="flex items-center gap-2">
                        <span className="text-sm text-green-700 font-medium">
                            Your review
                        </span>
                        <StarDisplay rating={review.rating} />
                    </div>
                    {review.messages && (
                        <p className="text-xs text-green-600/80 truncate mt-0.5">
                            {review.messages}
                        </p>
                    )}
                </div>
            </div>
            <button
                onClick={() => setEditing(true)}
                className="inline-flex items-center gap-1 text-xs font-medium text-green-700 hover:text-green-800 hover:bg-green-100 px-2.5 py-1.5 rounded-lg transition-colors cursor-pointer shrink-0"
            >
                <Pencil className="w-3 h-3" />
                Edit
            </button>
        </div>
    );
}

export default function ProductReviewsSection({
    productId,
}: ProductReviewsSectionProps) {
    const token = useAppSelector((state) => state.auth.access_token);
    const { data, isLoading } = useGetProductReviewsQuery(productId);
    const { data: reviewStatus } = useCheckUserReviewQuery(productId, {
        skip: !token,
    });

    const canReview = reviewStatus?.data?.can_review ?? false;
    const hasReviewed = reviewStatus?.data?.has_reviewed ?? false;
    const existingReview = reviewStatus?.data?.review ?? null;

    if (isLoading) {
        return (
            <div className="mt-12">
                <h2 className="text-xl font-semibold text-pink-600 border-b w-fit mb-6">
                    Customer Reviews
                </h2>
                <div className="flex justify-center py-8">
                    <div className="w-6 h-6 border-2 border-pink-500 border-t-transparent rounded-full animate-spin" />
                </div>
            </div>
        );
    }

    const reviews = data?.data?.reviews ?? [];
    const reviewCount = data?.data?.review_count ?? 0;
    const averageRating = data?.data?.average_rating ?? 0;

    return (
        <div className="mt-12">
            <h2 className="text-xl font-semibold text-pink-600 border-b w-fit mb-6">
                Customer Reviews
            </h2>

            {/* Write new review — only if eligible */}
            {canReview && <WriteReviewForm productId={productId} />}

            {/* Already reviewed — show with edit option */}
            {hasReviewed && existingReview && (
                <EditReviewCard review={existingReview} />
            )}

            {/* Summary */}
            {reviewCount > 0 && (
                <div className="flex items-center gap-4 mb-6 bg-gray-50 rounded-xl p-4">
                    <div className="flex flex-col items-center">
                        <span className="text-3xl font-bold text-gray-900">
                            {averageRating}
                        </span>
                        <StarDisplay rating={Math.round(averageRating)} />
                        <span className="text-xs text-gray-500 mt-1">
                            {reviewCount} {reviewCount === 1 ? "review" : "reviews"}
                        </span>
                    </div>

                    {/* Rating Distribution */}
                    <div className="flex-1 space-y-1">
                        {[5, 4, 3, 2, 1].map((star) => {
                            const count = reviews.filter(
                                (r) => Math.round(r.rating) === star,
                            ).length;
                            const percentage =
                                reviewCount > 0 ? (count / reviewCount) * 100 : 0;
                            return (
                                <div key={star} className="flex items-center gap-2">
                                    <span className="text-xs text-gray-500 w-3">{star}</span>
                                    <Star className="w-3 h-3 fill-amber-400 text-amber-400" />
                                    <div className="flex-1 bg-gray-200 rounded-full h-1.5">
                                        <div
                                            className="bg-amber-400 h-1.5 rounded-full transition-all"
                                            style={{ width: `${percentage}%` }}
                                        />
                                    </div>
                                    <span className="text-xs text-gray-400 w-6 text-right">
                                        {count}
                                    </span>
                                </div>
                            );
                        })}
                    </div>
                </div>
            )}

            {/* Review List */}
            {reviews.length > 0 ? (
                <div className="space-y-4">
                    {reviews.map((review) => (
                        <div
                            key={review.id}
                            className="border border-gray-100 rounded-xl p-4 hover:bg-gray-50/50 transition-colors"
                        >
                            <div className="flex items-start gap-3">
                                <div className="w-9 h-9 rounded-full bg-gradient-to-br from-pink-400 to-rose-500 flex items-center justify-center text-white text-sm font-semibold shrink-0">
                                    {review.user?.name?.[0]?.toUpperCase() ?? (
                                        <User className="w-4 h-4" />
                                    )}
                                </div>

                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center gap-2 flex-wrap">
                                        <span className="text-sm font-semibold text-gray-900">
                                            {review.user?.name ?? "Anonymous"}
                                        </span>
                                        <StarDisplay rating={review.rating} />
                                        <span className="text-xs text-gray-400">
                                            {formatDate(review.created_at)}
                                        </span>
                                    </div>

                                    {review.messages && (
                                        <p className="text-sm text-gray-600 mt-1.5 leading-relaxed">
                                            {review.messages}
                                        </p>
                                    )}
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            ) : (
                !canReview && (
                    <div className="text-center py-10 bg-gray-50/50 rounded-xl">
                        <Star className="w-10 h-10 text-gray-200 mx-auto mb-3" />
                        <p className="text-gray-400 text-sm">
                            No reviews yet. Be the first to review!
                        </p>
                    </div>
                )
            )}
        </div>
    );
}
