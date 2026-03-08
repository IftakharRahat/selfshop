<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class R2TestController extends Controller
{
    /**
     * Allowed folder prefixes for organized storage.
     */
    private const FOLDERS = [
        'products/images',
        'products/sliders',
        'products/vendor',
        'products/variants',
        'products/meta',
        'users/profiles',
        'users/nid',
        'vendors/kyc',
        'tickets',
        'categories',
        'brands',
        'banners/sliders',
        'banners/ads',
        'banners/menus',
        'campaigns',
        'payments',
        'courses',
        'misc',
    ];

    /**
     * Return the list of available folders.
     * GET /api/r2-test/folders
     */
    public function folders()
    {
        return response()->json([
            'status' => true,
            'data' => self::FOLDERS,
        ]);
    }

    /**
     * Upload one or more files to R2.
     * POST /api/r2-test/upload
     *
     * @bodyParam folder string required  One of the predefined folder names.
     * @bodyParam files  file[] required  One or more image/document files (max 10MB each).
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'folder' => ['required', 'string', 'in:' . implode(',', self::FOLDERS)],
            'files'  => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => ['file', 'max:10240'], // 10 MB each
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $folder = $request->input('folder');
        $uploaded = [];

        foreach ($request->file('files') as $file) {
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $safeName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME))
                . '_' . Str::random(8)
                . '.' . $extension;

            $path = $file->storeAs($folder, $safeName, 'r2');

            $publicUrl = rtrim(config('filesystems.disks.r2.url'), '/') . '/' . $path;

            $uploaded[] = [
                'name' => $originalName,
                'path' => $path,
                'url' => $publicUrl,
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ];
        }

        return response()->json([
            'status' => true,
            'message' => count($uploaded) . ' file(s) uploaded successfully',
            'data' => $uploaded,
        ]);
    }

    /**
     * List files in a given R2 folder.
     * GET /api/r2-test/files?folder=products/images
     */
    public function files(Request $request)
    {
        $folder = $request->query('folder', 'misc');

        if (!in_array($folder, self::FOLDERS)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid folder',
            ], 422);
        }

        $disk = Storage::disk('r2');
        $allFiles = $disk->files($folder);

        $baseUrl = rtrim(config('filesystems.disks.r2.url'), '/');

        $items = array_map(function ($filePath) use ($disk, $baseUrl) {
            return [
                'path' => $filePath,
                'name' => basename($filePath),
                'url' => $baseUrl . '/' . $filePath,
                'size' => $disk->size($filePath),
                'lastModified' => date('Y-m-d H:i:s', $disk->lastModified($filePath)),
            ];
        }, $allFiles);

        // Newest first
        usort($items, fn($a, $b) => strcmp($b['lastModified'], $a['lastModified']));

        return response()->json([
            'status' => true,
            'data' => [
                'folder' => $folder,
                'count' => count($items),
                'files' => array_values($items),
            ],
        ]);
    }

    /**
     * Delete a file from R2.
     * DELETE /api/r2-test/files
     *
     * @bodyParam path string required  The full R2 path to delete.
     */
    public function deleteFile(Request $request)
    {
        $path = $request->input('path');

        if (!$path) {
            return response()->json([
                'status' => false,
                'message' => 'Path is required',
            ], 422);
        }

        $disk = Storage::disk('r2');

        if (!$disk->exists($path)) {
            return response()->json([
                'status' => false,
                'message' => 'File not found',
            ], 404);
        }

        $disk->delete($path);

        return response()->json([
            'status' => true,
            'message' => 'File deleted successfully',
        ]);
    }
}
