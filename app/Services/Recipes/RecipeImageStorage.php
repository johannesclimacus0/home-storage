<?php

namespace App\Services\Recipes;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class RecipeImageStorage
{
    public function store(UploadedFile $image): string
    {
        $path = Storage::disk('public')->putFile('recipes', $image);

        if ($path === false) {
            throw new RuntimeException('Unable to store image.');
        }

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path === null) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
