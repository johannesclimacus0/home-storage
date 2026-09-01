<?php

namespace Tests\Feature\Notes;

use App\Models\Recipe;
use App\Models\RecipeNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RecipeNoteControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_user_can_manage_own_recipe_notes(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->create();
        $baseUrl = "/api/recipes/{$recipe->uuid}/notes";

        $createResponse = $this->actingAs($user)->postJson($baseUrl, [
            'content' => '  Test note  ',
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.content', 'Test note')
            ->assertJsonStructure(['data' => ['uuid', 'content', 'created_at', 'updated_at']]);

        $noteUuid = $createResponse->json('data.uuid');

        $this->actingAs($user)
            ->getJson("{$baseUrl}/{$noteUuid}")
            ->assertOk()
            ->assertJsonPath('data.uuid', $noteUuid);

        $this->actingAs($user)
            ->patchJson("{$baseUrl}/{$noteUuid}", ['content' => 'Updated note'])
            ->assertOk()
            ->assertJsonPath('data.content', 'Updated note');

        $this->actingAs($user)
            ->deleteJson("{$baseUrl}/{$noteUuid}")
            ->assertNoContent();

        $this->assertDatabaseMissing('recipe_notes', ['uuid' => $noteUuid]);
    }

    public function test_list_contains_only_authenticated_users_notes(): void
    {
        $recipe = Recipe::factory()->create();
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $ownNote = RecipeNote::factory()->for($recipe)->for($user, 'author')->create();
        $foreignNote = RecipeNote::factory()->for($recipe)->for($otherUser, 'author')->create();

        $response = $this->actingAs($user)
            ->getJson("/api/recipes/{$recipe->uuid}/notes?per_page=1");

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('data.0.uuid', $ownNote->uuid);
        $response->assertJsonMissing(['uuid' => $foreignNote->uuid]);
    }

    public function test_user_cannot_read_update_or_delete_another_users_note(): void
    {
        $recipe = Recipe::factory()->create();
        $user = User::factory()->create();
        $foreignNote = RecipeNote::factory()->for($recipe)->create();
        $url = "/api/recipes/{$recipe->uuid}/notes/{$foreignNote->uuid}";

        $this->actingAs($user)->getJson($url)->assertNotFound();
        $this->actingAs($user)
            ->patchJson($url, ['content' => 'Foreign update'])
            ->assertNotFound();
        $this->actingAs($user)->deleteJson($url)->assertNotFound();

        $this->assertDatabaseHas('recipe_notes', [
            'id' => $foreignNote->getKey(),
            'content' => $foreignNote->content,
        ]);
    }

    public function test_note_is_scoped_to_parent_recipe(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->create();
        $otherRecipe = Recipe::factory()->create();
        $note = RecipeNote::factory()->for($otherRecipe)->for($user, 'author')->create();

        $this->actingAs($user)
            ->getJson("/api/recipes/{$recipe->uuid}/notes/{$note->uuid}")
            ->assertNotFound();
    }

    public function test_note_request_is_validated_and_guest_is_rejected(): void
    {
        $recipe = Recipe::factory()->create();
        $user = User::factory()->create();
        $url = "/api/recipes/{$recipe->uuid}/notes";

        $this->actingAs($user)
            ->postJson($url, ['content' => '   '])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('content');

        $this->app['auth']->forgetGuards();

        $this->postJson($url, ['content' => 'Guest note'])->assertUnauthorized();
    }
}
