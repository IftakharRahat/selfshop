"use client";

import React, { useCallback, useRef, useState } from "react";
import { CloudUpload, X, AlertCircle } from "lucide-react";
import { toast } from "sonner";

/* ─────────────────── Types ─────────────────── */

interface R2MultiImageUploaderProps {
    /** Current files from form state */
    value?: File[];
    /** Called when files are added or removed */
    onChange: (files: File[]) => void;
    /** Accepted file types (default: "image/*") */
    accept?: string;
    /** Max file size per file in MB (default: 5) */
    maxSizeMB?: number;
    /** Label shown above the drop zone */
    label?: string;
    /** Additional class on wrapper */
    className?: string;
}

/* ─────────────────── Helpers ─────────────────── */

function formatMB(bytes: number): string {
    return (bytes / (1024 * 1024)).toFixed(1);
}

/* ═══════════════════════════════════════════════
   R2MultiImageUploader
   ═══════════════════════════════════════════════ */

export default function R2MultiImageUploader({
    value = [],
    onChange,
    accept = "image/*",
    maxSizeMB = 5,
    label,
    className = "",
}: R2MultiImageUploaderProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [isDragOver, setIsDragOver] = useState(false);
    const [previews, setPreviews] = useState<Map<File, string>>(new Map());

    /* ─── Generate previews ─── */
    React.useEffect(() => {
        const newPreviews = new Map<File, string>();
        const urls: string[] = [];

        for (const file of value) {
            if (previews.has(file)) {
                newPreviews.set(file, previews.get(file)!);
            } else if (file.type.startsWith("image/")) {
                const url = URL.createObjectURL(file);
                urls.push(url);
                newPreviews.set(file, url);
            }
        }

        setPreviews(newPreviews);

        // Revoke old URLs that are no longer in use
        return () => {
            urls.forEach((u) => URL.revokeObjectURL(u));
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [value]);

    /* ─── Validate & add files ─── */
    const handleFiles = useCallback(
        (fileList: FileList) => {
            const maxBytes = maxSizeMB * 1024 * 1024;
            const accepted: File[] = [];
            const acceptedTypes = accept.split(",").map((t) => t.trim());

            for (let i = 0; i < fileList.length; i++) {
                const file = fileList[i];

                if (file.size > maxBytes) {
                    toast.error(
                        `"${file.name}" is too large (${formatMB(file.size)}MB). Max ${maxSizeMB}MB per file.`,
                        { duration: 4000 },
                    );
                    continue;
                }

                if (accept !== "*/*") {
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
                        toast.error(`"${file.name}" — file type not supported.`, {
                            description: `Accepted: ${accept}`,
                            duration: 3000,
                        });
                        continue;
                    }
                }

                accepted.push(file);
            }

            if (accepted.length > 0) {
                onChange([...value, ...accepted]);
            }
        },
        [maxSizeMB, accept, onChange, value],
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
            if (e.dataTransfer.files?.length) {
                handleFiles(e.dataTransfer.files);
            }
        },
        [handleFiles],
    );

    /* ─── File input change ─── */
    const handleInputChange = useCallback(
        (e: React.ChangeEvent<HTMLInputElement>) => {
            if (e.target.files?.length) {
                handleFiles(e.target.files);
            }
            e.target.value = "";
        },
        [handleFiles],
    );

    /* ─── Remove single file ─── */
    const handleRemove = useCallback(
        (index: number, e: React.MouseEvent) => {
            e.stopPropagation();
            const newFiles = value.filter((_, i) => i !== index);
            onChange(newFiles);
        },
        [value, onChange],
    );

    /* ═════════════════ Render ═════════════════ */

    return (
        <div className={className}>
            {label && (
                <p className="text-sm font-medium text-gray-700 mb-1.5">{label}</p>
            )}

            {/* Drop zone */}
            <div
                onDragOver={handleDragOver}
                onDragLeave={handleDragLeave}
                onDrop={handleDrop}
                onClick={() => inputRef.current?.click()}
                className={`
					relative cursor-pointer rounded-xl border-2 border-dashed transition-all duration-200
					flex flex-col items-center justify-center py-5 px-4
					${isDragOver
                        ? "border-orange-400 bg-orange-50 scale-[1.01]"
                        : "border-gray-300 bg-gray-50 hover:border-orange-300 hover:bg-orange-50/50"
                    }
				`}
            >
                <div
                    className={`rounded-full mb-2 transition ${isDragOver ? "bg-orange-100" : "bg-gray-100"
                        } p-2`}
                >
                    <CloudUpload
                        className={`w-5 h-5 ${isDragOver ? "text-orange-500" : "text-gray-400"
                            }`}
                    />
                </div>
                <p className="font-medium text-gray-600 text-xs">
                    {isDragOver
                        ? "Drop files here"
                        : "Click or drag images to upload"}
                </p>
                <p className="flex items-center gap-1 text-xs text-gray-400 mt-1">
                    <AlertCircle className="w-3 h-3" />
                    Max {maxSizeMB}MB per file · Multiple files supported
                </p>

                <input
                    ref={inputRef}
                    type="file"
                    accept={accept}
                    multiple
                    className="hidden"
                    onChange={handleInputChange}
                />
            </div>

            {/* Preview grid */}
            {value.length > 0 && (
                <div className="mt-3 grid grid-cols-4 sm:grid-cols-5 gap-2">
                    {value.map((file, index) => {
                        const previewUrl = previews.get(file);
                        return (
                            <div
                                key={`${file.name}-${file.size}-${index}`}
                                className="relative group rounded-lg border border-gray-200 overflow-hidden bg-gray-50 aspect-square"
                            >
                                {previewUrl ? (
                                    <img
                                        src={previewUrl}
                                        alt={file.name}
                                        className="w-full h-full object-cover"
                                    />
                                ) : (
                                    <div className="w-full h-full flex items-center justify-center">
                                        <p className="text-[9px] text-gray-400 text-center px-1 truncate">
                                            {file.name}
                                        </p>
                                    </div>
                                )}

                                {/* Remove button */}
                                <button
                                    type="button"
                                    onClick={(e) => handleRemove(index, e)}
                                    className="absolute top-0.5 right-0.5 p-0.5 rounded-full bg-red-500 text-white shadow-md opacity-0 group-hover:opacity-100 transition z-10"
                                >
                                    <X className="w-3 h-3" />
                                </button>

                                {/* File size badge */}
                                <div className="absolute bottom-0 inset-x-0 bg-black/50 text-white text-[8px] text-center py-0.5">
                                    {formatMB(file.size)}MB
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
