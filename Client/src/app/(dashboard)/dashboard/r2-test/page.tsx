"use client";

import React, { useState, useCallback, useRef, useEffect } from "react";
import {
    Upload,
    X,
    Trash2,
    FolderOpen,
    ImageIcon,
    CheckCircle2,
    AlertCircle,
    Loader2,
    CloudUpload,
    RefreshCw,
    FileImage,
    ExternalLink,
} from "lucide-react";
import { toast } from "sonner";
import { useSelector } from "react-redux";
import type { RootState } from "@/redux/store";

/* ─────────────────── Types ─────────────────── */

interface R2File {
    path: string;
    name: string;
    url: string;
    size: number;
    lastModified: string;
}

interface UploadingFile {
    id: string;
    file: File;
    preview: string;
    status: "pending" | "uploading" | "success" | "error";
    progress: number;
    error?: string;
    result?: { url: string; path: string };
}

/* ─────────────────── Constants ─────────────────── */

const BASE_URL = process.env.NEXT_PUBLIC_BASE_URL ?? "";

const FOLDERS = [
    { value: "products/images", label: "🛍️ Product Images" },
    { value: "products/sliders", label: "🖼️ Product Sliders" },
    { value: "products/vendor", label: "🏪 Vendor Products" },
    { value: "products/variants", label: "🎨 Product Variants" },
    { value: "products/meta", label: "🔍 Meta / SEO Images" },
    { value: "users/profiles", label: "👤 User Profiles" },
    { value: "users/nid", label: "🪪 User NID Documents" },
    { value: "vendors/kyc", label: "📋 Vendor KYC" },
    { value: "tickets", label: "🎫 Support Tickets" },
    { value: "categories", label: "📂 Categories" },
    { value: "brands", label: "🏷️ Brands" },
    { value: "banners/sliders", label: "🎠 Banner Sliders" },
    { value: "banners/ads", label: "📢 Ad Banners" },
    { value: "banners/menus", label: "📋 Menu Banners" },
    { value: "campaigns", label: "🔥 Campaigns" },
    { value: "payments", label: "💳 Payment Icons" },
    { value: "courses", label: "📚 Courses" },
    { value: "misc", label: "📦 Miscellaneous" },
];

/* ─────────────────── Helpers ─────────────────── */

function formatFileSize(bytes: number): string {
    if (bytes < 1024) return bytes + " B";
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + " KB";
    return (bytes / (1024 * 1024)).toFixed(2) + " MB";
}

function generateId(): string {
    return Math.random().toString(36).slice(2, 10);
}

/* ═══════════════════════════════════════════════
   Component
   ═══════════════════════════════════════════════ */

export default function R2TestPage() {
    const token = useSelector((s: RootState) => s.auth.access_token);

    const [selectedFolder, setSelectedFolder] = useState(FOLDERS[0].value);
    const [uploadQueue, setUploadQueue] = useState<UploadingFile[]>([]);
    const [remoteFiles, setRemoteFiles] = useState<R2File[]>([]);
    const [loadingFiles, setLoadingFiles] = useState(false);
    const [isDragOver, setIsDragOver] = useState(false);
    const [deletingPaths, setDeletingPaths] = useState<Set<string>>(new Set());
    const [lightboxUrl, setLightboxUrl] = useState<string | null>(null);

    const inputRef = useRef<HTMLInputElement>(null);

    /* ─── Fetch remote files when folder changes ─── */
    const fetchFiles = useCallback(async () => {
        if (!token) return;
        setLoadingFiles(true);
        try {
            const res = await fetch(
                `${BASE_URL}/r2-test/files?folder=${encodeURIComponent(selectedFolder)}`,
                {
                    headers: {
                        Accept: "application/json",
                        Authorization: `Bearer ${token}`,
                    },
                },
            );
            const json = await res.json();
            if (json.status) {
                setRemoteFiles(json.data.files);
            }
        } catch (err) {
            console.error("Failed to fetch files:", err);
        } finally {
            setLoadingFiles(false);
        }
    }, [selectedFolder, token]);

    useEffect(() => {
        fetchFiles();
    }, [fetchFiles]);

    /* ─── Add files to queue ─── */
    const addFiles = useCallback((files: FileList | File[]) => {
        const newItems: UploadingFile[] = Array.from(files).map((file) => ({
            id: generateId(),
            file,
            preview: file.type.startsWith("image/")
                ? URL.createObjectURL(file)
                : "",
            status: "pending" as const,
            progress: 0,
        }));
        setUploadQueue((prev) => [...prev, ...newItems]);
    }, []);

    /* ─── Drag & Drop handlers ─── */
    const handleDragOver = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        setIsDragOver(true);
    }, []);
    const handleDragLeave = useCallback(() => setIsDragOver(false), []);
    const handleDrop = useCallback(
        (e: React.DragEvent) => {
            e.preventDefault();
            setIsDragOver(false);
            if (e.dataTransfer.files.length > 0) {
                addFiles(e.dataTransfer.files);
            }
        },
        [addFiles],
    );

    /* ─── Upload single file ─── */
    const uploadSingleFile = useCallback(
        async (item: UploadingFile) => {
            setUploadQueue((prev) =>
                prev.map((f) =>
                    f.id === item.id ? { ...f, status: "uploading", progress: 30 } : f,
                ),
            );

            try {
                const formData = new FormData();
                formData.append("folder", selectedFolder);
                formData.append("files[]", item.file);

                const res = await fetch(`${BASE_URL}/r2-test/upload`, {
                    method: "POST",
                    headers: {
                        Accept: "application/json",
                        Authorization: `Bearer ${token}`,
                    },
                    body: formData,
                });

                setUploadQueue((prev) =>
                    prev.map((f) =>
                        f.id === item.id ? { ...f, progress: 80 } : f,
                    ),
                );

                const json = await res.json();

                if (!res.ok || !json.status) {
                    throw new Error(json.message || "Upload failed");
                }

                const uploaded = json.data[0];

                setUploadQueue((prev) =>
                    prev.map((f) =>
                        f.id === item.id
                            ? {
                                ...f,
                                status: "success",
                                progress: 100,
                                result: { url: uploaded.url, path: uploaded.path },
                            }
                            : f,
                    ),
                );

                return true;
            } catch (err: unknown) {
                const msg = err instanceof Error ? err.message : "Upload failed";
                setUploadQueue((prev) =>
                    prev.map((f) =>
                        f.id === item.id
                            ? { ...f, status: "error", progress: 0, error: msg }
                            : f,
                    ),
                );
                return false;
            }
        },
        [selectedFolder, token],
    );

    /* ─── Upload all pending ─── */
    const handleUploadAll = useCallback(async () => {
        const pending = uploadQueue.filter((f) => f.status === "pending");
        if (pending.length === 0) {
            toast.info("No files to upload");
            return;
        }

        let successCount = 0;
        let failCount = 0;

        for (const item of pending) {
            const ok = await uploadSingleFile(item);
            if (ok) successCount++;
            else failCount++;
        }

        if (successCount > 0) {
            toast.success(`${successCount} file(s) uploaded successfully`);
            fetchFiles(); // Refresh gallery
        }
        if (failCount > 0) {
            toast.error(`${failCount} file(s) failed to upload`);
        }
    }, [uploadQueue, uploadSingleFile, fetchFiles]);

    /* ─── Remove from queue ─── */
    const removeFromQueue = useCallback((id: string) => {
        setUploadQueue((prev) => {
            const item = prev.find((f) => f.id === id);
            if (item?.preview) URL.revokeObjectURL(item.preview);
            return prev.filter((f) => f.id !== id);
        });
    }, []);

    /* ─── Clear completed ─── */
    const clearCompleted = useCallback(() => {
        setUploadQueue((prev) => {
            for (const item of prev) {
                if (
                    (item.status === "success" || item.status === "error") &&
                    item.preview
                ) {
                    URL.revokeObjectURL(item.preview);
                }
            }
            return prev.filter(
                (f) => f.status !== "success" && f.status !== "error",
            );
        });
    }, []);

    /* ─── Delete from R2 ─── */
    const handleDelete = useCallback(
        async (path: string) => {
            setDeletingPaths((prev) => new Set(prev).add(path));
            try {
                const res = await fetch(`${BASE_URL}/r2-test/files`, {
                    method: "DELETE",
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json",
                        Authorization: `Bearer ${token}`,
                    },
                    body: JSON.stringify({ path }),
                });
                const json = await res.json();
                if (json.status) {
                    setRemoteFiles((prev) => prev.filter((f) => f.path !== path));
                    toast.success("File deleted");
                } else {
                    toast.error(json.message || "Delete failed");
                }
            } catch {
                toast.error("Failed to delete file");
            } finally {
                setDeletingPaths((prev) => {
                    const next = new Set(prev);
                    next.delete(path);
                    return next;
                });
            }
        },
        [token],
    );

    /* ─── Counts ─── */
    const pendingCount = uploadQueue.filter((f) => f.status === "pending")
        .length;
    const uploadingCount = uploadQueue.filter((f) => f.status === "uploading")
        .length;
    const isUploading = uploadingCount > 0;

    /* ═══════════════════════════════════════════════
       Render
       ═══════════════════════════════════════════════ */

    return (
        <main className="flex-1 p-3 sm:p-5 lg:p-6 pb-24 max-w-6xl mx-auto">
            {/* Header */}
            <div className="mb-6">
                <h1 className="text-xl sm:text-2xl font-bold text-gray-900 flex items-center gap-2">
                    <CloudUpload className="w-6 h-6 text-orange-500" />
                    R2 Storage Test
                </h1>
                <p className="text-sm text-gray-500 mt-1">
                    Upload images to Cloudflare R2 and verify they load correctly.
                </p>
            </div>

            {/* Folder selector */}
            <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-4 sm:p-5 mb-5">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                    <FolderOpen className="w-4 h-4 inline mr-1.5 -mt-0.5" />
                    Target Folder
                </label>
                <select
                    value={selectedFolder}
                    onChange={(e) => setSelectedFolder(e.target.value)}
                    className="w-full sm:w-80 rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-orange-400 focus:ring-2 focus:ring-orange-100 outline-none transition"
                >
                    {FOLDERS.map((f) => (
                        <option key={f.value} value={f.value}>
                            {f.label}
                        </option>
                    ))}
                </select>
            </div>

            {/* ────── Upload Zone ────── */}
            <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-4 sm:p-5 mb-5">
                <h2 className="text-base font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    <Upload className="w-4 h-4" />
                    Upload Files
                </h2>

                {/* Drop zone */}
                <div
                    onDragOver={handleDragOver}
                    onDragLeave={handleDragLeave}
                    onDrop={handleDrop}
                    onClick={() => inputRef.current?.click()}
                    className={`
						relative cursor-pointer rounded-xl border-2 border-dashed transition-all duration-200
						flex flex-col items-center justify-center py-10
						${isDragOver
                            ? "border-orange-400 bg-orange-50 scale-[1.01]"
                            : "border-gray-300 bg-gray-50 hover:border-orange-300 hover:bg-orange-50/50"
                        }
					`}
                >
                    <div
                        className={`rounded-full p-3 mb-3 transition ${isDragOver ? "bg-orange-100" : "bg-gray-100"}`}
                    >
                        <CloudUpload
                            className={`w-8 h-8 ${isDragOver ? "text-orange-500" : "text-gray-400"}`}
                        />
                    </div>
                    <p className="text-sm font-medium text-gray-700">
                        {isDragOver ? "Drop files here" : "Click or drag files here to upload"}
                    </p>
                    <p className="text-xs text-gray-400 mt-1">
                        Max 10MB per file · Images, PDFs, and documents
                    </p>
                    <input
                        ref={inputRef}
                        type="file"
                        multiple
                        accept="image/*,.pdf,.doc,.docx"
                        className="hidden"
                        onChange={(e) => {
                            if (e.target.files) addFiles(e.target.files);
                            e.target.value = ""; // allow re-selecting same file
                        }}
                    />
                </div>

                {/* ─── Upload Queue ─── */}
                {uploadQueue.length > 0 && (
                    <div className="mt-4">
                        <div className="flex items-center justify-between mb-3">
                            <span className="text-sm text-gray-600">
                                {pendingCount > 0 && `${pendingCount} ready`}
                                {pendingCount > 0 && uploadingCount > 0 && " · "}
                                {uploadingCount > 0 && `${uploadingCount} uploading`}
                            </span>
                            <div className="flex gap-2">
                                {uploadQueue.some(
                                    (f) =>
                                        f.status === "success" || f.status === "error",
                                ) && (
                                        <button
                                            type="button"
                                            onClick={clearCompleted}
                                            className="text-xs text-gray-400 hover:text-gray-600 transition"
                                        >
                                            Clear completed
                                        </button>
                                    )}
                                <button
                                    type="button"
                                    onClick={handleUploadAll}
                                    disabled={pendingCount === 0 || isUploading}
                                    className={`
										px-4 py-2 rounded-lg text-sm font-medium transition-all
										${pendingCount > 0 && !isUploading
                                            ? "bg-orange-500 text-white hover:bg-orange-600 shadow-sm"
                                            : "bg-gray-100 text-gray-400 cursor-not-allowed"
                                        }
									`}
                                >
                                    {isUploading ? (
                                        <span className="flex items-center gap-1.5">
                                            <Loader2 className="w-4 h-4 animate-spin" />
                                            Uploading…
                                        </span>
                                    ) : (
                                        `Upload ${pendingCount > 0 ? `(${pendingCount})` : "All"}`
                                    )}
                                </button>
                            </div>
                        </div>

                        <div className="space-y-2 max-h-80 overflow-y-auto pr-1">
                            {uploadQueue.map((item) => (
                                <div
                                    key={item.id}
                                    className={`
										flex items-center gap-3 p-3 rounded-lg border transition-all
										${item.status === "success" ? "bg-green-50 border-green-200" : ""}
										${item.status === "error" ? "bg-red-50 border-red-200" : ""}
										${item.status === "uploading" ? "bg-orange-50 border-orange-200" : ""}
										${item.status === "pending" ? "bg-white border-gray-200" : ""}
									`}
                                >
                                    {/* Preview thumbnail */}
                                    <div className="w-12 h-12 rounded-lg bg-gray-100 flex-shrink-0 overflow-hidden flex items-center justify-center">
                                        {item.preview ? (
                                            <img
                                                src={item.preview}
                                                alt=""
                                                className="w-full h-full object-cover"
                                            />
                                        ) : (
                                            <FileImage className="w-5 h-5 text-gray-400" />
                                        )}
                                    </div>

                                    {/* Info */}
                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm font-medium text-gray-800 truncate">
                                            {item.file.name}
                                        </p>
                                        <div className="flex items-center gap-2 mt-0.5">
                                            <span className="text-xs text-gray-400">
                                                {formatFileSize(item.file.size)}
                                            </span>
                                            {item.status === "success" && (
                                                <span className="text-xs text-green-600 flex items-center gap-0.5">
                                                    <CheckCircle2 className="w-3 h-3" /> Uploaded
                                                </span>
                                            )}
                                            {item.status === "error" && (
                                                <span className="text-xs text-red-500 flex items-center gap-0.5">
                                                    <AlertCircle className="w-3 h-3" />{" "}
                                                    {item.error || "Failed"}
                                                </span>
                                            )}
                                            {item.status === "uploading" && (
                                                <span className="text-xs text-orange-500 flex items-center gap-0.5">
                                                    <Loader2 className="w-3 h-3 animate-spin" />{" "}
                                                    Uploading…
                                                </span>
                                            )}
                                        </div>
                                        {/* Progress bar */}
                                        {(item.status === "uploading" ||
                                            item.status === "success") && (
                                                <div className="w-full bg-gray-200 rounded-full h-1 mt-1.5">
                                                    <div
                                                        className={`h-1 rounded-full transition-all duration-500 ${item.status === "success"
                                                                ? "bg-green-500"
                                                                : "bg-orange-500"
                                                            }`}
                                                        style={{ width: `${item.progress}%` }}
                                                    />
                                                </div>
                                            )}
                                    </div>

                                    {/* Actions */}
                                    <button
                                        type="button"
                                        onClick={() => removeFromQueue(item.id)}
                                        className="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition flex-shrink-0"
                                    >
                                        <X className="w-4 h-4" />
                                    </button>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>

            {/* ────── Gallery ────── */}
            <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-4 sm:p-5">
                <div className="flex items-center justify-between mb-4">
                    <h2 className="text-base font-semibold text-gray-900 flex items-center gap-2">
                        <ImageIcon className="w-4 h-4" />
                        Files in{" "}
                        <span className="text-orange-500 font-mono text-sm">
                            /{selectedFolder}
                        </span>
                    </h2>
                    <button
                        type="button"
                        onClick={fetchFiles}
                        disabled={loadingFiles}
                        className="flex items-center gap-1.5 text-sm text-gray-500 hover:text-orange-500 transition"
                    >
                        <RefreshCw
                            className={`w-4 h-4 ${loadingFiles ? "animate-spin" : ""}`}
                        />
                        Refresh
                    </button>
                </div>

                {loadingFiles && (
                    <div className="flex items-center justify-center py-16 text-gray-400">
                        <Loader2 className="w-6 h-6 animate-spin mr-2" />
                        Loading files…
                    </div>
                )}

                {!loadingFiles && remoteFiles.length === 0 && (
                    <div className="flex flex-col items-center justify-center py-16 text-gray-400">
                        <FolderOpen className="w-10 h-10 mb-2 text-gray-300" />
                        <p className="text-sm">No files in this folder</p>
                        <p className="text-xs mt-1">Upload some files above to get started</p>
                    </div>
                )}

                {!loadingFiles && remoteFiles.length > 0 && (
                    <>
                        <p className="text-xs text-gray-400 mb-3">
                            {remoteFiles.length} file(s)
                        </p>
                        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                            {remoteFiles.map((file) => {
                                const isImage = /\.(jpe?g|png|gif|webp|svg|bmp|ico)$/i.test(
                                    file.name,
                                );
                                const isDeleting = deletingPaths.has(file.path);

                                return (
                                    <div
                                        key={file.path}
                                        className={`group relative rounded-xl border border-gray-200 overflow-hidden bg-gray-50 transition-all hover:shadow-md hover:border-orange-200 ${isDeleting ? "opacity-50 pointer-events-none" : ""}`}
                                    >
                                        {/* Image / file preview */}
                                        <div
                                            className="aspect-square flex items-center justify-center cursor-pointer bg-white"
                                            onClick={() => {
                                                if (isImage) setLightboxUrl(file.url);
                                                else window.open(file.url, "_blank");
                                            }}
                                        >
                                            {isImage ? (
                                                <img
                                                    src={file.url}
                                                    alt={file.name}
                                                    className="w-full h-full object-cover"
                                                    loading="lazy"
                                                />
                                            ) : (
                                                <FileImage className="w-10 h-10 text-gray-300" />
                                            )}
                                        </div>

                                        {/* Info bar */}
                                        <div className="p-2">
                                            <p
                                                className="text-xs font-medium text-gray-700 truncate"
                                                title={file.name}
                                            >
                                                {file.name}
                                            </p>
                                            <p className="text-[10px] text-gray-400">
                                                {formatFileSize(file.size)}
                                            </p>
                                        </div>

                                        {/* Hover actions */}
                                        <div className="absolute top-1.5 right-1.5 flex gap-1 opacity-0 group-hover:opacity-100 transition">
                                            <a
                                                href={file.url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="p-1.5 rounded-lg bg-white/90 backdrop-blur shadow-sm hover:bg-white text-gray-500 hover:text-blue-500 transition"
                                                title="Open in new tab"
                                            >
                                                <ExternalLink className="w-3.5 h-3.5" />
                                            </a>
                                            <button
                                                type="button"
                                                onClick={(e) => {
                                                    e.stopPropagation();
                                                    handleDelete(file.path);
                                                }}
                                                className="p-1.5 rounded-lg bg-white/90 backdrop-blur shadow-sm hover:bg-white text-gray-500 hover:text-red-500 transition"
                                                title="Delete"
                                            >
                                                {isDeleting ? (
                                                    <Loader2 className="w-3.5 h-3.5 animate-spin" />
                                                ) : (
                                                    <Trash2 className="w-3.5 h-3.5" />
                                                )}
                                            </button>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </>
                )}
            </div>

            {/* ────── Lightbox ────── */}
            {lightboxUrl && (
                <div
                    className="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4"
                    onClick={() => setLightboxUrl(null)}
                >
                    <div className="relative max-w-4xl max-h-[90vh]">
                        <img
                            src={lightboxUrl}
                            alt="Preview"
                            className="max-w-full max-h-[90vh] object-contain rounded-lg shadow-2xl"
                        />
                        <button
                            type="button"
                            onClick={() => setLightboxUrl(null)}
                            className="absolute -top-3 -right-3 p-2 rounded-full bg-white shadow-lg text-gray-600 hover:text-red-500 transition"
                        >
                            <X className="w-5 h-5" />
                        </button>
                    </div>
                </div>
            )}
        </main>
    );
}
