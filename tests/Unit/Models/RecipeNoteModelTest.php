<?php

namespace Tests\Unit\Models;

use App\Models\Recipe;
use App\Models\RecipeNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RecipeNoteModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_recipe_note_has_expected_relationships_and_route_key(): void
    {
        $note = RecipeNote::factory()->create();

        $this->assertInstanceOf(Recipe::class, $note->recipe);
        $this->assertInstanceOf(User::class, $note->author);
        $this->assertNotEmpty($note->uuid);
        $this->assertSame('uuid', $note->getRouteKeyName());
    }
}
