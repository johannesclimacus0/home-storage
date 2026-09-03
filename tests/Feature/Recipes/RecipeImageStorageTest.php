<?php

namespace Tests\Feature\Recipes;

use App\Services\Recipes\RecipeImageStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class RecipeImageStorageTest extends TestCase
{
    private RecipeImageStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->storage = app(RecipeImageStorage::class);
    }

    public function test_it_stores_recipe_image_on_public_disk(): void
    {
        $image = UploadedFile::fake()->image('recipe.jpg');
        $path = $this->storage->store($image);

        Storage::disk('public')->assertExists($path);
    }

    public function test_it_deletes_recipe_image_from_public_disk(): void
    {
        $image = UploadedFile::fake()->image('recipe.jpg');
        $path = $this->storage->store($image);

        Storage::disk('public')->assertExists($path);

        $this->storage->delete($path);

        Storage::disk('public')->assertMissing($path);
    }

    public function test_it_does_not_access_storage_when_image_path_is_null(): void
    {
        Storage::shouldReceive('disk')->never();

        $this->storage->delete(null);
    }
}
