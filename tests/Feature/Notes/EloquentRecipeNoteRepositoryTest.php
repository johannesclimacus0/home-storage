<?php

namespace Tests\Feature\Notes;

use App\Contracts\Notes\RecipeNoteRepository;
use App\Models\Recipe;
use App\Models\RecipeNote;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EloquentRecipeNoteRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_paginates_only_requested_authors_notes_for_recipe(): void
    {
        $recipe = Recipe::factory()->create();
        $author = User::factory()->create();
        $otherAuthor = User::factory()->create();
        $oldest = RecipeNote::factory()->for($recipe)->for($author, 'author')->create([
            'created_at' => '2026-09-01 10:00:00',
        ]);
        $newest = RecipeNote::factory()->for($recipe)->for($author, 'author')->create([
            'created_at' => '2026-09-01 11:00:00',
        ]);
        RecipeNote::factory()->for($recipe)->for($otherAuthor, 'author')->create();
        RecipeNote::factory()->for(Recipe::factory())->for($author, 'author')->create();

        $result = $this->repository()->paginateForRecipeAndAuthor($recipe, $author, 1);

        $this->assertSame(2, $result->total());
        $this->assertSame(1, $result->perPage());
        $this->assertCount(1, $result->items());
        $this->assertSame($newest->getKey(), $result->items()[0]->getKey());
        $this->assertNotSame($oldest->getKey(), $result->items()[0]->getKey());
    }

    public function test_it_finds_note_only_inside_recipe_and_author_scope(): void
    {
        $recipe = Recipe::factory()->create();
        $author = User::factory()->create();
        $note = RecipeNote::factory()->for($recipe)->for($author, 'author')->create();

        $found = $this->repository()->findForRecipeAndAuthor($recipe, $author, $note->uuid);

        $this->assertTrue($found->is($note));

        $this->expectException(ModelNotFoundException::class);

        $this->repository()->findForRecipeAndAuthor(
            $recipe,
            User::factory()->create(),
            $note->uuid
        );
    }

    public function test_it_creates_updates_and_deletes_note(): void
    {
        $recipe = Recipe::factory()->create();
        $author = User::factory()->create();
        $repository = $this->repository();

        $note = $repository->create($recipe, $author, 'Test note');

        $this->assertTrue($note->recipe->is($recipe));
        $this->assertTrue($note->author->is($author));
        $this->assertSame('Test note', $note->content);

        $repository->update($note, 'Updated note');
        $this->assertSame('Updated note', $note->refresh()->content);

        $repository->delete($note);
        $this->assertDatabaseMissing('recipe_notes', ['id' => $note->getKey()]);
    }

    private function repository(): RecipeNoteRepository
    {
        return app(RecipeNoteRepository::class);
    }
}
