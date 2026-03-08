"use client";

import React, { useCallback, useRef, useState } from "react";
import { CloudUpload, X, ImageIcon, AlertCircle } from "lucide-react";
import { toast } from "sonner";

/* ─────────────────── Types ─────────────────── */

interface R2ImageUploaderProps {
    /** Current file from form state */
    value?: File | null;
    /** URL of existing image (edit mode — e.g. current profile photo) */
    existingImageUrl?: string;
    /** Called when a file is selected or cleared */
    onChange: (file: File | null) => void;
    /** Accepted file types (default: "image/*") */
    accept?: string;
    /** Max file size in MB (default: 5) */
    maxSizeMB?: number;
    /** Label shown above the drop zone */
    label?: string;
    /** Compact mode for modals */
    compact?: boolean;
    /** Additional class on wrapper */
    className?: string;
}

/* ─────────────────── Helpers ─────────────────── */

function formatMB(bytes: number): string {
    return (bytes / (1024 * 1024)).toFixed(1);
}

/* ═══════════════════════════════════════════════
   R2ImageUploader
   ═══════════════════════════════════════════════ */

export default function R2ImageUploader({
    value,
    existingImageUrl,
    onChange,
    accept = "image/*",
    maxSizeMB = 5,
    label,
    compact = false,
    className = "",
}: R2ImageUploaderProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [isDragOver, setIsDragOver] = useState(false);
    const [localPreview, setLocalPreview] = useState<string | null>(null);

    /* ─── Preview logic ─── */
    // Priority: localPreview (just-selected file) > existingImageUrl (from DB)
    const previewUrl = localPreview || (value ? undefined : existingImageUrl);

    // Generate preview when value is a File
    React.useEffect(() => {
        if (value && value instanceof File && value.type.startsWith("image/")) {
            const url = URL.createObjectURL(value);
            setLocalPreview(url);
            return () => URL.revokeObjectURL(url);
        }
        if (!value) {
            setLocalPreview(null);
        }
    }, [value]);

    /* ─── Validate & set file ─── */
    const handleFile = useCallback(
        (file: File) => {
            const maxBytes = maxSizeMB * 1024 * 1024;

            if (file.size > maxBytes) {
                toast.error(
                    `File too large! Maximum size is ${maxSizeMB}MB. Your file is ${formatMB(file.size)}MB.`,
                    {
                        description: "Please choose a smaller file and try again.",
                        duration: 5000,
                    },
                );
                return;
            }

            // Validate file type if accept is set
            if (accept !== "*/*") {
                const acceptedTypes = accept.split(",").map((t) => t.trim());
                const isAccepted = acceptedTypes.some((type) => {
                    if (type.endsWith("/*")) {
                        return file.type.startsWith(type.replace("/*", "/"));
                    }
                    if (type.startsWith(".")) {
                        return file.name.toLowerCase().endsWith(type.toLowerCase());
                    }
                    return file.type === type;
                });

                if (!isAccepted) {
                    toast.error("File type not supported.", {
                        description: `Accepted: ${accept}`,
                        duration: 4000,
                    });
                    return;
                }
            }

            onChange(file);
        },
        [maxSizeMB, accept, onChange],
    );

    /* ─── Drag handlers ─── */
    const handleDragOver = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        setIsDragOver(true);
    }, []);

    const handleDragLeave = useCallback(() => setIsDragOver(false), []);

    const handleDrop = useCallback(
        (e: React.DragEvent) => {
            e.preventDefault();
            setIsDragOver(false);
            const file = e.dataTransfer.files?.[0];
            if (file) handleFile(file);
        },
        [handleFile],
    );

    /* ─── File input change ─── */
    const handleInputChange = useCallback(
        (e: React.ChangeEvent<HTMLInputElement>) => {
            const file = e.target.files?.[0];
            if (file) handleFile(file);
            e.target.value = ""; // allow re-selecting same file
        },
        [handleFile],
    );

    /* ─── Clear file ─── */
    const handleClear = useCallback(
        (e: React.MouseEvent) => {
            e.stopPropagation();
            onChange(null);
            setLocalPreview(null);
        },
        [onChange],
    );

    /* ─── Has a preview to show? ─── */
    const showPreview = localPreview || previewUrl;
    const isImage =
        value?.type?.startsWith("image/") ||
        (previewUrl && /\.(jpe?g|png|gif|webp|svg|bmp)$/i.test(previewUrl));

    /* ═════════════════ Render ═════════════════ */

    return (
        <div className={className}>
            {label && (
                <p className="text-sm font-medium text-gray-700 mb-1.5">{label}</p>
            )}

            <div
                onDragOver={handleDragOver}
                onDragLeave={handleDragLeave}
                onDrop={handleDrop}
                onClick={() => inputRef.current?.click()}
                className={`
					relative cursor-pointer rounded-xl border-2 border-dashed transition-all duration-200
					flex flex-col items-center justify-center overflow-hidden
					${compact ? "py-5 px-4" : "py-8 px-6"}
					${isDragOver
                        ? "border-orange-400 bg-orange-50 scale-[1.01]"
                        : showPreview
                            ? "border-gray-200 bg-gray-50 hover:border-orange-300"
                            : "border-gray-300 bg-gray-50 hover:border-orange-300 hover:bg-orange-50/50"
                    }
				`}
            >
                {/* ─── Preview state ─── */}
                {showPreview && isImage ? (
                    <div className="relative">
                        <img
                            src={localPreview || previewUrl || ""}
                            alt="Preview"
                            className={`rounded-lg object-cover border border-gray-200 shadow-sm ${compact ? "max-h-24" : "max-h-36"
                                }`}
                        />
                        {/* Clear button */}
                        <button
                            type="button"
                            onClick={handleClear}
                            className="absolute -top-2 -right-2 p-1 rounded-full bg-red-500 text-white shadow-md hover:bg-red-600 transition z-10"
                        >
                            <X className="w-3.5 h-3.5" />
                        </button>
                        <p className="text-xs text-gray-400 mt-2 text-center">
                            {value?.name || "Current image"} ·{" "}
                            {value ? `${formatMB(value.size)}MB` : "Click to change"}
                        </p>
                    </div>
                ) : showPreview && !isImage ? (
                    /* Non-image file preview */
                    <div className="relative flex items-center gap-3 bg-white rounded-lg border border-gray-200 px-4 py-3 shadow-sm">
                        <ImageIcon className="w-8 h-8 text-gray-400 flex-shrink-0" />
                        <div className="min-w-0">
                            <p className="text-sm font-medium text-gray-700 truncate max-w-[200px]">
                                {value?.name || "File"}
                            </p>
                            <p className="text-xs text-gray-400">
                                {value ? `${formatMB(value.size)}MB` : "Click to change"}
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={handleClear}
                            className="p-1 rounded-full bg-red-500 text-white shadow-md hover:bg-red-600 transition"
                        >
                            <X className="w-3.5 h-3.5" />
                        </button>
                    </div>
                ) : (
                    /* ─── Empty state ─── */
                    <>
                        <div
                            className={`rounded-full mb-2 transition ${isDragOver ? "bg-orange-100" : "bg-gray-100"
                                } ${compact ? "p-2" : "p-3"}`}
                        >
                            <CloudUpload
                                className={`${compact ? "w-5 h-5" : "w-7 h-7"} ${isDragOver ? "text-orange-500" : "text-gray-400"
                                    }`}
                            />
                        </div>
                        <p
                            className={`font-medium text-gray-600 ${compact ? "text-xs" : "text-sm"}`}
                        >
                            {isDragOver
                                ? "Drop file here"
                                : "Click or drag file to upload"}
                        </p>
                        <p className="flex items-center gap-1 text-xs text-gray-400 mt-1">
                            <AlertCircle className="w-3 h-3" />
                            Max {maxSizeMB}MB
                        </p>
                    </>
                )}

                <input
                    ref={inputRef}
                    type="file"
                    accept={accept}
                    className="hidden"
                    onChange={handleInputChange}
                />
            </div>
        </div>
    );
}
