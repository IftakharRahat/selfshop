<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorageHelper
{
    /**
     * Determine whether R2 is configured.
     */
    public static function useR2(): bool
    {
        return !empty(config('filesystems.disks.r2.bucket'));
    }

    /**
     * Get the active disk name ('r2' or 'public').
     */
    public static function disk(): string
    {
        return static::useR2() ? 'r2' : 'public';
    }

    /**
     * Get the base URL for stored files.
     */
    public static function baseUrl(): string
    {
        if (static::useR2()) {
            return rtrim(config('filesystems.disks.r2.url'), '/');
        }

        return rtrim(config('app.url'), '/') . '/storage';
    }

    /**
     * Store a file and return its full public URL.
     *
     * @param  UploadedFile  $file
     * @param  string        $directory  e.g. 'admin/products'
     * @return string  Full public URL to the stored file.
     */
    public static function store(UploadedFile $file, string $directory): string
    {
        $safeName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs($directory, $safeName, static::disk());

        return static::baseUrl() . '/' . $path;
    }
}
