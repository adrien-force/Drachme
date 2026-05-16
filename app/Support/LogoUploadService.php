<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LogoUploadService
{
    private const DISK = 'public';

    public function url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return Storage::disk(self::DISK)->url($path);
    }

    public function store(UploadedFile $file, string $folder): string
    {
        $extension = $file->guessExtension() ?? 'png';
        $filename = Str::uuid()->toString().'.'.$extension;

        return $file->storeAs($folder, $filename, self::DISK);
    }

    public function delete(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        Storage::disk(self::DISK)->delete($path);
    }

    public function sync(
        ?string $currentPath,
        ?UploadedFile $file,
        bool $remove,
        string $folder,
    ): ?string {
        if ($remove) {
            $this->delete($currentPath);

            return null;
        }

        if ($file === null) {
            return $currentPath;
        }

        $this->delete($currentPath);

        return $this->store($file, $folder);
    }
}
