<?php

namespace Tests\Feature\Notes;

use App\Actions\Notes\CreateRecipeNoteAction;
use App\Actions\Notes\DeleteRecipeNoteAction;
use App\Actions\Notes\ListRecipeNotesAction;
use App\Actions\Notes\UpdateRecipeNoteAction;
use App\DTO\Notes\CreateRecipeNoteData;
use App\DTO\Notes\DeleteRecipeNoteData;
use App\DTO\Notes\UpdateRecipeNoteData;
use App\Models\Recipe;
use App\Models\RecipeNote;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

final class RecipeNoteActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_can_create_update_list_and_delete_own_note(): void
    {
        $recipe = Recipe::factory()->create();
        $author = User::factory()->create();
        $note = app(CreateRecipeNoteAction::class)->handle(new CreateRecipeNoteData(
            recipeUuid: $recipe->uuid,
            actorUserId: $author->getKey(),
            content: '  Test note  '
        ));

        $this->assertSame('Test note', $note->content);

        $updated = app(UpdateRecipeNoteAction::class)->handle(new UpdateRecipeNoteData(
            recipeUuid: $recipe->uuid,
            actorUserId: $author->getKey(),
            noteUuid: $note->uuid,
            content: '  Updated note  '
        ));

        $this->assertSame('Updated note', $updated->content);

        $notes = app(ListRecipeNotesAction::class)->handle(
            $recipe->uuid,
            $author->getKey(),
            10
        );

        $this->assertSame(1, $notes->total());
        $this->assertTrue($notes->items()[0]->is($note));

        app(DeleteRecipeNoteAction::class)->handle(new DeleteRecipeNoteData(
            recipeUuid: $recipe->uuid,
            actorUserId: $author->getKey(),
            noteUuid: $note->uuid
        ));

        $this->assertDatabaseMissing('recipe_notes', ['id' => $note->getKey()]);
    }

    public function test_empty_note_content_is_rejected(): void
    {
        $recipe = Recipe::factory()->create();
        $author = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        app(CreateRecipeNoteAction::class)->handle(new CreateRecipeNoteData(
            recipeUuid: $recipe->uuid,
            actorUserId: $author->getKey(),
            content: '   '
        ));
    }

    public function test_user_cannot_update_another_authors_note(): void
    {
        $recipe = Recipe::factory()->create();
        $note = RecipeNote::factory()->for($recipe)->create();
        $otherUser = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        app(UpdateRecipeNoteAction::class)->handle(new UpdateRecipeNoteData(
            recipeUuid: $recipe->uuid,
            actorUserId: $otherUser->getKey(),
            noteUuid: $note->uuid,
            content: 'Foreign update'
        ));
    }
}
